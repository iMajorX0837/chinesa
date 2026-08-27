#!/usr/bin/env python3
"""
Consulta o total depositado em um dia (somente Status=completed no errorlog).

Uso:
  python daily_deposits.py
  python daily_deposits.py 2026-08-26
  python daily_deposits.py 2026-08-26 --detalhes
  python daily_deposits.py --arquivo errorlog.log 2026-08-26
"""

from __future__ import annotations

import argparse
import re
import sys
from decimal import Decimal
from pathlib import Path

import requests
from dotenv import load_dotenv

import os

PROJECT_DIR = Path(__file__).resolve().parent
load_dotenv(PROJECT_DIR / ".env")

ERRORLOG_URL = os.getenv("ERRORLOG_URL", "https://91b99.com/errorlog.log")

MONTH_MAP = {
    "Jan": "01", "Feb": "02", "Mar": "03", "Apr": "04",
    "May": "05", "Jun": "06", "Jul": "07", "Aug": "08",
    "Sep": "09", "Oct": "10", "Nov": "11", "Dec": "12",
}

COMPLETED_LINE_RE = re.compile(
    r"^\[(?P<ts>[^\]]+)\]\s*\[EXPFYPAY WEBHOOK\]\s*Recebido:\s*"
    r"ID=(?P<id>[^,\s]+),\s*Status=completed,\s*Valor=(?P<valor>[0-9]+(?:\.[0-9]+)?)",
    re.IGNORECASE,
)


def normalize_date(timestamp: str) -> str | None:
    timestamp = timestamp.strip()
    if re.match(r"\d{4}-\d{2}-\d{2}", timestamp):
        return timestamp[:10]

    match = re.match(r"(\d{2})-([A-Za-z]{3})-(\d{4})", timestamp)
    if match:
        day, month, year = match.groups()
        month_num = MONTH_MAP.get(month)
        if month_num:
            return f"{year}-{month_num}-{day}"
    return None


def load_log(source: str | None, log_file: Path | None) -> str:
    if log_file:
        if not log_file.exists():
            print(f"[ERRO] Arquivo não encontrado: {log_file}", file=sys.stderr)
            sys.exit(1)
        return log_file.read_text(encoding="utf-8", errors="replace")

    url = source or ERRORLOG_URL
    print(f"[INFO] Baixando log: {url}")
    resp = requests.get(url, timeout=120)
    resp.raise_for_status()
    return resp.text


def calc_daily_total(log_text: str, target_date: str) -> tuple[Decimal, list[dict]]:
    deposits: list[dict] = []
    seen_ids: set[str] = set()

    for line in log_text.splitlines():
        match = COMPLETED_LINE_RE.match(line.strip())
        if not match:
            continue

        line_date = normalize_date(match.group("ts"))
        if line_date != target_date:
            continue

        tx_id = match.group("id").strip()
        if tx_id in seen_ids:
            continue
        seen_ids.add(tx_id)

        valor = Decimal(match.group("valor"))
        deposits.append({
            "id": tx_id,
            "valor": valor,
            "timestamp": match.group("ts").strip(),
        })

    total = sum((d["valor"] for d in deposits), Decimal("0"))
    return total, deposits


def format_brl(value: Decimal) -> str:
    return f"R$ {value:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")


def main() -> None:
    parser = argparse.ArgumentParser(description="Total de depósitos completed por dia")
    parser.add_argument(
        "data",
        nargs="?",
        default="2026-08-26",
        help="Data no formato YYYY-MM-DD (default: 2026-08-26)",
    )
    parser.add_argument("--url", help="URL do errorlog (default: ERRORLOG_URL do .env)")
    parser.add_argument("--arquivo", type=Path, help="Ler log de arquivo local")
    parser.add_argument("--detalhes", action="store_true", help="Lista cada depósito")
    args = parser.parse_args()

    if not re.fullmatch(r"\d{4}-\d{2}-\d{2}", args.data):
        print("[ERRO] Data inválida. Use YYYY-MM-DD", file=sys.stderr)
        sys.exit(1)

    log_text = load_log(args.url, args.arquivo)
    total, deposits = calc_daily_total(log_text, args.data)

    print()
    print(f"Data:              {args.data}")
    print(f"Status:            completed")
    print(f"Depósitos:         {len(deposits)}")
    print(f"Total depositado:  {format_brl(total)}")
    print()

    if args.detalhes and deposits:
        print("ID transação                          | Valor      | Horário")
        print("-" * 72)
        for dep in sorted(deposits, key=lambda d: d["timestamp"]):
            print(f"{dep['id']:<37} | {format_brl(dep['valor']):>10} | {dep['timestamp']}")


if __name__ == "__main__":
    main()
