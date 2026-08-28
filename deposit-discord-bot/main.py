#!/usr/bin/env python3
"""
Monitora depósitos pagos em tempo real via errorlog.log e envia embed ao Discord.

Uso:
  pip install -r requirements.txt
  python main.py

Variáveis de ambiente (opcionais):
  DISCORD_WEBHOOK_URL  - URL do webhook Discord
  ERRORLOG_URL         - default https://91b99.com/errorlog.log
  DRAKON_URL           - default https://91b99.com/callback/drakon.php
  CASA_URL             - default https://91b99.com/
  POLL_INTERVAL        - segundos entre polls (default 5)
  STATE_FILE           - arquivo de estado (IDs já enviados nesta sessão)
"""

from __future__ import annotations

import json
import os
import re
import sys
import time
from datetime import datetime, timezone
from pathlib import Path

import requests
from dotenv import load_dotenv

PROJECT_DIR = Path(__file__).resolve().parent
load_dotenv(PROJECT_DIR / ".env")

DISCORD_WEBHOOK_URL = os.getenv("DISCORD_WEBHOOK_URL", "")
ERRORLOG_URL = os.getenv("ERRORLOG_URL", "https://91b99.com/errorlog.log")
DRAKON_URL = os.getenv("DRAKON_URL", "https://91b99.com/callback/drakon.php")
CASA_URL = os.getenv("CASA_URL", "https://91b99.com/")
POLL_INTERVAL = float(os.getenv("POLL_INTERVAL", "5"))
STATE_FILE = Path(os.getenv("STATE_FILE", str(PROJECT_DIR / "output" / "state.json")))

INSERT_PAYMENT_RE = re.compile(
    r"insert_payment: Iniciando inser.*?\. Dados: (\{.*\})",
    re.IGNORECASE,
)
WEBHOOK_COMPLETED_RE = re.compile(
    r"\[EXPFYPAY WEBHOOK\] Recebido: ID=([^,\s]+), Status=completed, Valor=([0-9]+(?:\.[0-9]+)?)",
    re.IGNORECASE,
)

SESSION = requests.Session()
SESSION.headers.update({"User-Agent": "deposit-discord-bot/1.0"})


def load_state() -> dict:
    if STATE_FILE.exists():
        try:
            return json.loads(STATE_FILE.read_text(encoding="utf-8"))
        except (json.JSONDecodeError, OSError):
            pass
    return {"sent_ids": [], "tx_user_map": {}}


def save_state(state: dict) -> None:
    STATE_FILE.parent.mkdir(parents=True, exist_ok=True)
    state["sent_ids"] = state["sent_ids"][-5000:]
    if len(state["tx_user_map"]) > 10000:
        items = list(state["tx_user_map"].items())[-5000:]
        state["tx_user_map"] = dict(items)
    STATE_FILE.write_text(json.dumps(state, ensure_ascii=False, indent=2), encoding="utf-8")


def fetch_log_tail(offset: int) -> tuple[str, int]:
    headers = {"Range": f"bytes={offset}-"}
    resp = SESSION.get(ERRORLOG_URL, headers=headers, timeout=30)

    if resp.status_code == 416:
        return _reset_offset_after_shrink()

    if resp.status_code == 206:
        new_offset = offset + len(resp.content)
        return resp.content.decode("utf-8", errors="replace"), new_offset

    if resp.status_code == 200:
        file_size = len(resp.content)
        if offset == 0:
            return resp.text, file_size
        if file_size <= offset:
            print(
                f"[AVISO] Log encolheu ou foi rotacionado "
                f"({file_size} bytes < offset {offset}); monitorando só linhas novas."
            )
            return "", file_size
        chunk = resp.content[offset:].decode("utf-8", errors="replace")
        return chunk, file_size

    resp.raise_for_status()
    return "", offset


def _reset_offset_after_shrink() -> tuple[str, int]:
    resp = SESSION.get(ERRORLOG_URL, timeout=30)
    resp.raise_for_status()
    file_size = len(resp.content)
    print(
        f"[AVISO] Offset inválido (log rotacionado?); "
        f"monitorando só linhas novas a partir de {file_size} bytes."
    )
    return "", file_size


def parse_insert_payments(chunk: str, tx_user_map: dict) -> None:
    for match in INSERT_PAYMENT_RE.finditer(chunk):
        raw = match.group(1)
        try:
            data = json.loads(raw)
        except json.JSONDecodeError:
            continue
        tx_id = data.get("transacao_id") or data.get("transaction_id")
        usuario = data.get("usuario")
        if tx_id and usuario:
            tx_user_map[str(tx_id)] = {
                "usuario": int(usuario),
                "valor": data.get("valor"),
            }


def fetch_phone(user_id: int) -> str:
    try:
        resp = SESSION.post(
            DRAKON_URL,
            json={"method": "account_details", "user_id": user_id},
            timeout=15,
        )
        text = resp.content.decode("utf-8-sig", errors="replace").strip()
        data = json.loads(text)
        if data.get("status") == 1:
            return str(data.get("email") or data.get("name_jogador") or user_id)
    except (requests.RequestException, json.JSONDecodeError, TypeError):
        pass
    return f"ID {user_id} (telefone não encontrado)"


def send_discord_embed(telefone: str, valor: str, transacao_id: str) -> None:
    valor_fmt = valor if str(valor).startswith("R$") else f"R$ {valor}"

    embed = {
        "title": "💰 Novo Depósito",
        "color": 0x2ECC71,
        "fields": [
            {"name": "Usuario", "value": telefone, "inline": False},
            {"name": "Valor Deposito", "value": valor_fmt, "inline": True},
            {"name": "Status", "value": "Pago", "inline": True},
            {"name": "Casa", "value": CASA_URL, "inline": False},
        ],
        "footer": {"text": f"TX: {transacao_id}"},
        "timestamp": datetime.now(timezone.utc).isoformat(),
    }

    resp = SESSION.post(DISCORD_WEBHOOK_URL, json={"embeds": [embed]}, timeout=15)
    resp.raise_for_status()


def process_chunk(chunk: str, state: dict) -> int:
    if not chunk:
        return 0

    tx_user_map = state.setdefault("tx_user_map", {})
    sent_ids: set[str] = set(state.get("sent_ids", []))

    parse_insert_payments(chunk, tx_user_map)

    sent_count = 0
    for match in WEBHOOK_COMPLETED_RE.finditer(chunk):
        tx_id = match.group(1).strip()
        valor = match.group(2).strip()

        if tx_id in sent_ids:
            continue

        info = tx_user_map.get(tx_id, {})
        user_id = info.get("usuario")
        if not user_id:
            print(f"[AVISO] Depósito pago sem usuário mapeado: {tx_id} valor={valor}")
            continue

        telefone = fetch_phone(int(user_id))
        try:
            send_discord_embed(telefone, valor, tx_id)
            sent_ids.add(tx_id)
            state["sent_ids"] = list(sent_ids)
            save_state(state)
            sent_count += 1
            print(f"[OK] Discord: {telefone} | R$ {valor} | TX {tx_id}")
        except requests.RequestException as exc:
            print(f"[ERRO] Falha ao enviar Discord para TX {tx_id}: {exc}", file=sys.stderr)

    state["sent_ids"] = list(sent_ids)
    return sent_count


def current_log_size() -> int:
    try:
        resp = SESSION.get(ERRORLOG_URL, timeout=30)
        resp.raise_for_status()
        return len(resp.content)
    except requests.RequestException as exc:
        print(f"[ERRO] Não foi possível obter tamanho do log: {exc}", file=sys.stderr)
        return 0


def main() -> None:
    if not DISCORD_WEBHOOK_URL:
        print("[ERRO] DISCORD_WEBHOOK_URL não definido. Configure o arquivo .env", file=sys.stderr)
        sys.exit(1)

    state = load_state()
    state["tx_user_map"] = {}

    offset = current_log_size()
    if offset == 0:
        print("[ERRO] Log vazio ou inacessível.", file=sys.stderr)
        sys.exit(1)

    print(f"[INIT] Monitorando só depósitos novos a partir de agora (byte {offset})")

    while True:
        try:
            print(f"[RUN] Poll a cada {POLL_INTERVAL}s | log={ERRORLOG_URL}")
            chunk, new_offset = fetch_log_tail(offset)
            offset = new_offset
            if chunk:
                sent = process_chunk(chunk, state)
                if sent:
                    print(f"[RUN] {sent} depósito(s) enviado(s) ao Discord.")
            else:
                print("[RUN] Nenhuma linha nova no log.")
        except KeyboardInterrupt:
            print("\n[STOP] Encerrado pelo usuário.")
            save_state(state)
            break
        except requests.RequestException as exc:
            print(f"[ERRO] Poll falhou: {exc}", file=sys.stderr)
        except Exception as exc:  # noqa: BLE001
            print(f"[ERRO] Inesperado: {exc}", file=sys.stderr)

        time.sleep(POLL_INTERVAL)


if __name__ == "__main__":
    main()
