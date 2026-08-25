(function () {
    'use strict';

    var deferredPrompt = null;
    var installPending = false;
    var isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || ('standalone' in navigator && navigator.standalone);
    var isIos = /iPhone|iPad|iPod/i.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

    function isWakeupInstallUrl(url) {
        if (typeof url !== 'string') return false;
        return url.indexOf('wakeup=true') !== -1
            || (url.indexOf('sd=2') !== -1 && url.indexOf('domainType=') !== -1);
    }

    function clearPrompt() {
        deferredPrompt = null;
        window.__deferredPWAInstall = null;
    }

    function markInstalled() {
        try { localStorage.setItem('webAppInstalled', 'true'); } catch (e) {}
        clearPrompt();
        installPending = false;
    }

    function openNativeInstallPrompt() {
        var prompt = deferredPrompt || window.__deferredPWAInstall;
        if (!prompt) return false;
        prompt.prompt();
        prompt.userChoice.then(function (choice) {
            if (choice.outcome === 'accepted') {
                markInstalled();
            }
        }).catch(function () {
            installPending = false;
        });
        return true;
    }

    function requestInstall() {
        if (isStandalone) return;
        if (openNativeInstallPrompt()) return;

        installPending = true;
        var attempts = 0;
        var timer = setInterval(function () {
            attempts += 1;
            if (openNativeInstallPrompt()) {
                clearInterval(timer);
                return;
            }
            if (attempts >= 24) {
                installPending = false;
                clearInterval(timer);
            }
        }, 250);
    }

    window.__tryInstallPWA = requestInstall;

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
        window.__deferredPWAInstall = e;
        if (installPending) {
            installPending = false;
            openNativeInstallPrompt();
        }
    });

    window.addEventListener('appinstalled', markInstalled);

    if (!isStandalone && !isIos) {
        var nativeOpen = window.open;
        window.open = function (url) {
            if (isWakeupInstallUrl(url)) {
                requestInstall();
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
        return text.length > 0 && text.length < 80
            && /(instalar|baixar|download|install app|add to home)/i.test(text);
    }

    if (!isStandalone && !isIos) {
        document.addEventListener('click', function (e) {
            var matched = false;
            for (var cur = e.target; cur && cur !== document.documentElement; cur = cur.parentElement) {
                if (isInstallClickTarget(cur)) {
                    matched = true;
                    break;
                }
            }
            if (!matched) return;

            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            requestInstall();
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
