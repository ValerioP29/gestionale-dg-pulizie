const STYLE_ID = 'fi-top-scrollbar-style';
const SCROLLABLE_SELECTORS = ['.fi-ta-ctn', '.filament-table-container', '[data-table-container]'];

const injectStyles = () => {
    if (document.getElementById(STYLE_ID)) {
        return;
    }

    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = `
        .fi-top-scrollbar {
            position: relative;
            overflow-x: auto;
            overflow-y: hidden;
            height: 12px;
            margin-bottom: 0.35rem;
        }

        .fi-top-scrollbar::-webkit-scrollbar {
            height: 8px;
        }

        .fi-top-scrollbar__spacer {
            height: 1px;
        }
    `;

    document.head.appendChild(style);
};

const syncScrollbars = (container, topScrollbar) => {
    const topSpacer = topScrollbar.firstElementChild;

    const updateSpacer = () => {
        if (!topSpacer) {
            return;
        }

        topSpacer.style.width = `${container.scrollWidth}px`;
    };

    const toggleVisibility = () => {
        const hasOverflow = container.scrollWidth > container.clientWidth + 1;
        topScrollbar.style.display = hasOverflow ? 'block' : 'none';

        if (hasOverflow) {
            updateSpacer();
        }
    };

    const syncFromContainer = () => {
        if (topScrollbar.scrollLeft !== container.scrollLeft) {
            topScrollbar.scrollLeft = container.scrollLeft;
        }
    };

    const syncFromTop = () => {
        if (container.scrollLeft !== topScrollbar.scrollLeft) {
            container.scrollLeft = topScrollbar.scrollLeft;
        }
    };

    const resizeObserver = new ResizeObserver(() => {
        updateSpacer();
        toggleVisibility();
    });

    resizeObserver.observe(container);

    const mutationObserver = new MutationObserver(() => {
        updateSpacer();
        toggleVisibility();
    });

    mutationObserver.observe(container, { childList: true, subtree: true });

    window.addEventListener('resize', toggleVisibility);

    container.addEventListener('scroll', syncFromContainer);
    topScrollbar.addEventListener('scroll', syncFromTop);

    updateSpacer();
    toggleVisibility();
};

const attachTopScrollbar = (container) => {
    if (container.dataset.topScrollbarAttached === 'true') {
        return;
    }

    const topScrollbar = document.createElement('div');
    topScrollbar.className = 'fi-top-scrollbar';

    const spacer = document.createElement('div');
    spacer.className = 'fi-top-scrollbar__spacer';
    topScrollbar.appendChild(spacer);

    container.before(topScrollbar);

    syncScrollbars(container, topScrollbar);

    container.dataset.topScrollbarAttached = 'true';
};

const initTopScrollbars = () => {
    injectStyles();

    document
        .querySelectorAll(SCROLLABLE_SELECTORS.join(','))
        .forEach((container) => attachTopScrollbar(container));
};

const registerWithLivewire = () => {
    if (!window.Livewire || window.__fiTopScrollbarLivewireHooked) {
        return;
    }

    window.__fiTopScrollbarLivewireHooked = true;

    window.Livewire.hook('message.processed', () => {
        initTopScrollbars();
    });
};

const start = () => {
    initTopScrollbars();
    registerWithLivewire();
};

document.addEventListener('DOMContentLoaded', start);
document.addEventListener('livewire:load', start);

