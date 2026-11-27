const OVERLAY_SELECTOR = '[data-loading-overlay]';
const ROOT_SELECTOR = '[data-loading-root]';
const NAVIGATION_KEY = 'app:loading-navigation';
const SKIP_HEADER = 'X-Skip-Loading';
const NAVIGATION_TTL_MS = 15_000;
const SHOW_DELAY_MS = 120;
const MIN_VISIBLE_MS = 250;

let overlay = null;
let roots = [];
let activeCount = 0;
let showTimer = null;
let hideTimer = null;
let shownAt = 0;
let previousFocus = null;
let installed = false;

function readNavigationFlag() {
    try {
        return sessionStorage.getItem(NAVIGATION_KEY);
    } catch (error) {
        return null;
    }
}

function writeNavigationFlag() {
    try {
        sessionStorage.setItem(NAVIGATION_KEY, String(Date.now()));
    } catch (error) {
        // Navegação continua funcionando mesmo sem sessionStorage.
    }
}

function clearNavigationFlag() {
    try {
        sessionStorage.removeItem(NAVIGATION_KEY);
    } catch (error) {
        // Nada a fazer.
    }
}

function setBusy(busy) {
    document.documentElement.classList.toggle('app-is-loading', busy);
    document.body.setAttribute('aria-busy', busy ? 'true' : 'false');

    roots.forEach((root) => {
        if (busy) {
            root.setAttribute('inert', '');
            root.setAttribute('aria-hidden', 'true');
        } else {
            root.removeAttribute('inert');
            root.removeAttribute('aria-hidden');
        }
    });
}

function display() {
    showTimer = null;
    shownAt = Date.now();

    if (document.activeElement instanceof HTMLElement && document.activeElement !== overlay) {
        previousFocus = document.activeElement;
    }

    overlay.hidden = false;
    setBusy(true);
    overlay.focus({ preventScroll: true });
}

function hide() {
    hideTimer = null;
    shownAt = 0;
    overlay.hidden = true;
    setBusy(false);

    if (previousFocus && previousFocus.isConnected) {
        previousFocus.focus({ preventScroll: true });
    }

    previousFocus = null;
}

function scheduleDisplay(immediate) {
    if (showTimer !== null || shownAt > 0) return;

    if (immediate) {
        display();
        return;
    }

    showTimer = window.setTimeout(display, SHOW_DELAY_MS);
}

function begin({ immediate = false } = {}) {
    if (hideTimer !== null) {
        window.clearTimeout(hideTimer);
        hideTimer = null;
    }

    activeCount += 1;
    scheduleDisplay(immediate);

    return () => {
        activeCount = Math.max(0, activeCount - 1);

        if (activeCount > 0) return;

        if (showTimer !== null) {
            window.clearTimeout(showTimer);
            showTimer = null;
        }

        if (shownAt === 0) return;

        const remaining = Math.max(0, MIN_VISIBLE_MS - (Date.now() - shownAt));
        hideTimer = window.setTimeout(hide, remaining);
    };
}

function headersFrom(input, init) {
    if (init && init.headers) return init.headers;
    if (typeof Request !== 'undefined' && input instanceof Request) return input.headers;

    return null;
}

function shouldSkipFetch(headers) {
    if (!headers) return false;

    if (typeof headers.get === 'function') {
        return headers.get(SKIP_HEADER) === '1';
    }

    const entries = Array.isArray(headers) ? headers : Object.entries(headers);

    return entries.some(([name, value]) => {
        return String(name).toLowerCase() === SKIP_HEADER.toLowerCase() && String(value) === '1';
    });
}

function patchFetch() {
    if (!window.fetch || window.fetch.__loadingOverlayPatched) return;

    const nativeFetch = window.fetch.bind(window);

    const patchedFetch = async (input, init) => {
        if (shouldSkipFetch(headersFrom(input, init))) {
            return nativeFetch(input, init);
        }

        const end = begin();

        try {
            return await nativeFetch(input, init);
        } finally {
            end();
        }
    };

    patchedFetch.__loadingOverlayPatched = true;
    window.fetch = patchedFetch;
}

function isBackgroundLivewireRequest(payload) {
    if (typeof payload !== 'string') return false;

    try {
        const components = JSON.parse(payload).components;

        if (!Array.isArray(components) || components.length === 0) return false;

        return components.every((component) => !Array.isArray(component.calls) || component.calls.length === 0);
    } catch (error) {
        return false;
    }
}

function registerLivewireHook() {
    window.Livewire.hook('request', ({ options, payload, respond, succeed, fail }) => {
        // O hook do Livewire controla este request; evita contagem dupla no patch do fetch.
        if (options && options.headers) {
            options.headers[SKIP_HEADER] = '1';
        }

        if (isBackgroundLivewireRequest(payload)) return;

        const end = begin({ immediate: true });
        let released = false;
        const release = () => {
            if (released) return;
            released = true;
            end();
        };

        respond(release);
        succeed(release);
        fail(release);
    });
}

function hookLivewire() {
    if (window.Livewire && typeof window.Livewire.hook === 'function') {
        registerLivewireHook();
        return;
    }

    document.addEventListener('livewire:init', registerLivewireHook, { once: true });
}

function isSkippableAnchor(anchor, event) {
    if (event.defaultPrevented || event.button !== 0) return true;
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return true;
    if (anchor.hasAttribute('download') || (anchor.target && anchor.target !== '_self')) return true;
    if (anchor.hasAttribute('data-no-loading') || anchor.hasAttribute('wire:navigate')) return true;

    const href = anchor.getAttribute('href') || '';

    if (!href || href.startsWith('#') || /^(mailto:|tel:|javascript:|data:)/i.test(href)) return true;

    const url = new URL(anchor.href, window.location.href);

    if (url.origin !== window.location.origin) return true;

    return url.pathname === window.location.pathname
        && url.search === window.location.search
        && url.hash !== '';
}

function markNavigation() {
    writeNavigationFlag();
    begin({ immediate: true });
}

function interceptNavigation() {
    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) return;

        const anchor = event.target.closest('a[href]');

        if (!anchor || isSkippableAnchor(anchor, event)) return;

        markNavigation();
    }, true);

    document.addEventListener('submit', (event) => {
        if (!(event.target instanceof HTMLFormElement)) return;
        if (event.defaultPrevented) return;

        const form = event.target;

        if (form.hasAttribute('wire:submit') || form.hasAttribute('wire:submit.prevent')) return;
        if (form.hasAttribute('wire:navigate') || form.hasAttribute('data-no-loading')) return;
        if (form.target && form.target !== '_self') return;

        markNavigation();
    }, true);
}

function restoreNavigation() {
    const flag = readNavigationFlag();
    clearNavigationFlag();

    if (!flag) return;

    const startedAt = Number(flag);

    if (!startedAt || Date.now() - startedAt > NAVIGATION_TTL_MS) return;

    activeCount += 1;
    shownAt = Date.now();
    overlay.hidden = false;
    setBusy(true);
    overlay.focus({ preventScroll: true });

    // Libera depois de duas frames para que os dados da nova página sejam pintados antes.
    window.requestAnimationFrame(() => {
        window.requestAnimationFrame(() => {
            activeCount = Math.max(0, activeCount - 1);

            if (activeCount === 0) {
                hideTimer = window.setTimeout(hide, MIN_VISIBLE_MS);
            }
        });
    });
}

function blockKeyboardWhileLoading(event) {
    if (shownAt === 0) return;
    if (event.key !== 'Tab' && event.key !== 'Enter' && event.key !== ' ') return;

    event.preventDefault();
}

function handlePageShow(event) {
    if (!event.persisted) return;

    clearNavigationFlag();

    if (showTimer !== null) {
        window.clearTimeout(showTimer);
        showTimer = null;
    }

    if (hideTimer !== null) {
        window.clearTimeout(hideTimer);
        hideTimer = null;
    }

    activeCount = 0;
    shownAt = 0;
    overlay.hidden = true;
    setBusy(false);
    previousFocus = null;
}

export function installLoadingOverlay() {
    if (installed) return;

    installed = true;
    overlay = document.querySelector(OVERLAY_SELECTOR);
    roots = Array.from(document.querySelectorAll(ROOT_SELECTOR));

    if (!overlay) return;

    patchFetch();
    hookLivewire();
    interceptNavigation();
    restoreNavigation();

    document.addEventListener('keydown', blockKeyboardWhileLoading, true);
    window.addEventListener('pageshow', handlePageShow);
}

// Os módulos Vite são deferred: o DOM já está completo e o listener de livewire:init
// é registrado antes do Livewire dispará-lo no DOMContentLoaded.
installLoadingOverlay();
