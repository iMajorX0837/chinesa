(function () {
    'use strict';

    var deferredPrompt = null;
    var isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || ('standalone' in navigator && navigator.standalone);
    var isIos = /iPhone|iPad|iPod/i.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

    function isWakeupInstallUrl(url) {
        if (typeof url !== 'string') return false;
        return url.indexOf('wakeup=true') !== -1
            || (url.indexOf('sd=2') !== -1 && url.indexOf('domainType=') !== -1);
    }

    function isDesktop() {
        return !/Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);
    }

    function showInstallHint() {
        if (document.getElementById('pwa-install-hint')) return;
        var msg = isDesktop()
            ? 'Clique no ícone <strong>Instalar</strong> (⊕) na barra de endereço do Chrome/Edge, ou abra o menu e escolha <strong>Instalar app</strong>.'
            : 'Toque no menu <strong>⋮</strong> do Chrome e escolha <strong>Instalar app</strong> ou <strong>Adicionar à tela inicial</strong>.';

        var el = document.createElement('div');
        el.id = 'pwa-install-hint';
        el.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.72);z-index:999999;display:flex;align-items:center;justify-content:center;padding:20px;box-sizing:border-box;';
        el.innerHTML = '<div style="background:#fff;border-radius:12px;padding:24px;max-width:340px;text-align:center;font-family:system-ui,sans-serif;">'
            + '<p style="margin:0 0 12px;font-size:17px;font-weight:600;color:#111;">Adicionar atalho</p>'
            + '<p style="margin:0 0 18px;font-size:14px;line-height:1.5;color:#444;">' + msg + '</p>'
            + '<button type="button" style="padding:10px 24px;border:none;border-radius:8px;background:#16a34a;color:#fff;font-size:14px;font-weight:600;cursor:pointer;">Entendi</button>'
            + '</div>';
        el.querySelector('button').onclick = function () { el.remove(); };
        el.onclick = function (ev) { if (ev.target === el) el.remove(); };
        document.body.appendChild(el);
    }

    function runInstallPrompt() {
        var prompt = deferredPrompt || window.__deferredPWAInstall;
        if (prompt) {
            prompt.prompt();
            return prompt.userChoice.then(function (choice) {
                if (choice.outcome === 'accepted') {
                    try { localStorage.setItem('webAppInstalled', 'true'); } catch (e) {}
                    deferredPrompt = null;
                    window.__deferredPWAInstall = null;
                }
                return choice;
            });
        }
        return new Promise(function (resolve) {
            setTimeout(function () {
                var latePrompt = deferredPrompt || window.__deferredPWAInstall;
                if (latePrompt) {
                    latePrompt.prompt();
                    latePrompt.userChoice.then(resolve);
                    return;
                }
                showInstallHint();
                resolve({ outcome: 'dismissed' });
            }, 600);
        });
    }

    window.__tryInstallPWA = runInstallPrompt;

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
        window.__deferredPWAInstall = e;
    });

    window.addEventListener('appinstalled', function () {
        try { localStorage.setItem('webAppInstalled', 'true'); } catch (e) {}
        deferredPrompt = null;
        window.__deferredPWAInstall = null;
    });

    if (!isStandalone && !isIos) {
        var nativeOpen = window.open;
        window.open = function (url) {
            if (isWakeupInstallUrl(url)) {
                runInstallPrompt();
                return null;
            }
            return nativeOpen.apply(window, arguments);
        };
    }

    function isInstallClickTarget(el) {
        if (!el || el.nodeType !== 1) return false;
        if (el.closest && (
            el.closest('.install-btn') ||
            el.closest('#pwa-bar') ||
            el.closest('[type="download-pwa"]') ||
            el.closest('.pwa-HeaderBar-Default2')
        )) {
            return true;
        }
        var node = el.closest ? (el.closest('button') || el.closest('a') || el) : el;
        var text = (node.innerText || node.textContent || '').replace(/\s+/g, ' ').trim();
        if (text.length > 0 && text.length < 80 && /(instalar|baixar|download|atalho|add to home|install app)/i.test(text)) {
            return true;
        }
        return false;
    }

    if (!isStandalone && !isIos) {
        document.addEventListener('click', function (e) {
            var el = e.target;
            var matched = false;
            for (var cur = el; cur && cur !== document.documentElement; cur = cur.parentElement) {
                if (isInstallClickTarget(cur)) {
                    matched = true;
                    break;
                }
            }
            if (!matched) return;

            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            runInstallPrompt();
        }, true);
    }

    if ('serviceWorker' in navigator && !isStandalone) {
        navigator.serviceWorker.register('/sw.produce.min.2.1.6.js', { scope: '/' }).catch(function () {});
    }

    if (!isStandalone && isWakeupInstallUrl(location.href)) {
        try {
            var cleanUrl = new URL(location.href);
            cleanUrl.searchParams.delete('wakeup');
            if (cleanUrl.searchParams.get('sd') === '2' && cleanUrl.searchParams.get('domainType') === 'main') {
                cleanUrl.searchParams.delete('sd');
                cleanUrl.searchParams.delete('domainType');
            }
            history.replaceState(null, '', cleanUrl.pathname + cleanUrl.search + cleanUrl.hash);
        } catch (err) {}
    }
})();
