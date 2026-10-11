(() => {
    let deferredPrompt = null;

    const installButtons = () => Array.from(document.querySelectorAll('.pwa-install-trigger'));
    const desktopContainers = () => Array.from(document.querySelectorAll('.pwa-install-desktop'));
    const mobileButtons = () => Array.from(document.querySelectorAll('.pwa-install-mobile'));

    const isStandalone = () =>
        window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true;

    const isIos = () =>
        /iphone|ipad|ipod/i.test(window.navigator.userAgent) ||
        (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

    const isMobile = () =>
        window.matchMedia('(max-width: 1199.98px)').matches ||
        /android|iphone|ipad|ipod|mobile/i.test(window.navigator.userAgent);

    const setVisible = (visible, showDesktop = false) => {
        mobileButtons().forEach(button => button.classList.toggle('d-none', !visible));

        desktopContainers().forEach(container => {
            container.classList.toggle('d-none', !(visible && showDesktop));
        });
    };

    const openHelp = () => {
        const element = document.getElementById('pwaInstallHelp');
        if (!element || typeof bootstrap === 'undefined') {
            window.alert('Чтобы установить приложение, откройте меню браузера и выберите «Установить приложение» или «Добавить на главный экран». На iPhone: Safari → Поделиться → На экран Домой.');
            return;
        }

        bootstrap.Modal.getOrCreateInstance(element).show();
    };

    const handleInstall = async () => {
        if (isStandalone()) {
            setVisible(false);
            return;
        }

        if (deferredPrompt) {
            const prompt = deferredPrompt;
            deferredPrompt = null;
            await prompt.prompt();

            try {
                await prompt.userChoice;
            } catch (_) {
                // Browser may not expose userChoice.
            }

            if (!isStandalone()) {
                setVisible(isMobile(), false);
            }
            return;
        }

        openHelp();
    };

    installButtons().forEach(button => {
        button.addEventListener('click', handleInstall);
    });

    window.addEventListener('beforeinstallprompt', event => {
        event.preventDefault();
        deferredPrompt = event;
        setVisible(true, true);
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        setVisible(false);
    });

    if (isStandalone()) {
        setVisible(false);
        return;
    }

    // iOS does not expose beforeinstallprompt, so keep a manual install button visible.
    if (isIos()) {
        setVisible(true, false);
        return;
    }

    // On other mobile browsers keep a fallback button visible even when no native prompt is exposed.
    if (isMobile()) {
        setVisible(true, false);
    }
})();
