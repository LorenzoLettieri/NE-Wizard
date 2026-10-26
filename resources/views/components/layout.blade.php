<!DOCTYPE html>
<html  data-bs-theme="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>NEWizard</title>
        <script>
            // Applica il tema prima del primo paint, così non si vede il passaggio scuro -> chiaro
            (() => {
                let theme = null;
                try {
                    theme = localStorage.getItem('bs-theme');
                } catch (e) {}
                if (!theme) {
                    theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-bs-theme', theme);
            })();
        </script>
        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.12.1/font/bootstrap-icons.min.css">
    </head>
    <body class="">
        <x-navbar></x-navbar>
        {{$slot}}
        <div id="app-toasts" class="toast-container position-fixed bottom-0 end-0 p-3"></div>
        <script>
            (() => {
                const readHeaderWidths = (thead) => Array.from(thead.querySelectorAll('th'))
                    .map((th) => th.getBoundingClientRect().width);

                const syncStickyTable = (wrapper) => {
                    const state = wrapper.__stickyHeadState;
                    if (!state) {
                        return;
                    }

                    const table = wrapper.querySelector('table.laravel-livewire-table');
                    const thead = wrapper.querySelector('thead.table-sticky-head');

                    if (!table || !thead || !document.body.contains(wrapper)) {
                        state.overlay.style.display = 'none';
                        return;
                    }

                    const cloneHeads = Array.from(state.cloneTable.querySelectorAll('th'));

                    // Prima tutte le letture di layout...
                    const headerRect = thead.getBoundingClientRect();
                    const tableRect = table.getBoundingClientRect();
                    const wrapperRect = wrapper.getBoundingClientRect();
                    const shouldShow = headerRect.top < 0 && tableRect.bottom > headerRect.height;

                    if (!shouldShow) {
                        state.overlay.style.display = 'none';
                        return;
                    }

                    const widths = readHeaderWidths(thead);

                    if (widths.length !== cloneHeads.length) {
                        scheduleRebuild(wrapper);
                        return;
                    }

                    const clientWidth = wrapper.clientWidth;
                    const scrollLeft = wrapper.scrollLeft;

                    // ...poi tutte le scritture, per non forzare un reflow per ogni colonna
                    state.overlay.style.display = 'block';
                    state.overlay.style.left = `${wrapperRect.left}px`;
                    state.overlay.style.width = `${clientWidth}px`;
                    state.overlay.classList.toggle('table-sticky-actions', wrapper.classList.contains('table-sticky-actions'));

                    state.cloneTable.className = table.className;
                    state.cloneTable.style.width = `${tableRect.width}px`;

                    widths.forEach((width, index) => {
                        const clone = cloneHeads[index];
                        clone.style.width = `${width}px`;
                        clone.style.minWidth = `${width}px`;
                        clone.style.maxWidth = `${width}px`;
                    });

                    // scrollLeft (non transform) così la colonna azioni sticky resta ancorata anche nel clone
                    state.overlay.scrollLeft = scrollLeft;
                };

                const rebuildStickyTable = (wrapper) => {
                    const state = wrapper.__stickyHeadState;
                    if (!state) {
                        return;
                    }

                    const table = wrapper.querySelector('table.laravel-livewire-table');
                    const thead = wrapper.querySelector('thead.table-sticky-head');

                    if (!table || !thead) {
                        state.overlay.style.display = 'none';
                        return;
                    }

                    state.cloneTable.className = table.className;
                    state.cloneTable.innerHTML = '';
                    state.cloneTable.appendChild(thead.cloneNode(true));
                    syncStickyTable(wrapper);
                };

                // Scroll e mutazioni arrivano a raffica: si lavora al massimo una volta per frame
                const scheduleSync = (wrapper) => {
                    const state = wrapper.__stickyHeadState;
                    if (!state || state.syncFrame) {
                        return;
                    }

                    state.syncFrame = window.requestAnimationFrame(() => {
                        state.syncFrame = null;
                        syncStickyTable(wrapper);
                    });
                };

                const scheduleRebuild = (wrapper) => {
                    const state = wrapper.__stickyHeadState;
                    if (!state || state.rebuildFrame) {
                        return;
                    }

                    state.rebuildFrame = window.requestAnimationFrame(() => {
                        state.rebuildFrame = null;
                        rebuildStickyTable(wrapper);
                    });
                };

                const attachStickyTable = (wrapper) => {
                    if (wrapper.__stickyHeadState) {
                        scheduleRebuild(wrapper);
                        return;
                    }

                    const overlay = document.createElement('div');
                    overlay.className = 'table-sticky-head-overlay';

                    const cloneTable = document.createElement('table');
                    overlay.appendChild(cloneTable);
                    document.body.appendChild(overlay);

                    const state = {
                        overlay,
                        cloneTable,
                        syncFrame: null,
                        rebuildFrame: null,
                        sync: () => scheduleSync(wrapper),
                    };

                    overlay.addEventListener('click', (event) => {
                        const cloneTh = event.target.closest('th');
                        if (!cloneTh) {
                            return;
                        }

                        const table = wrapper.querySelector('table.laravel-livewire-table');
                        const thead = wrapper.querySelector('thead.table-sticky-head');

                        if (!table || !thead) {
                            return;
                        }

                        const cloneHeads = Array.from(state.cloneTable.querySelectorAll('th'));
                        const index = cloneHeads.indexOf(cloneTh);
                        if (index === -1) {
                            return;
                        }

                        const sourceTh = thead.querySelectorAll('th')[index];
                        const trigger = sourceTh?.querySelector('[wire\\:click]') ?? sourceTh;
                        trigger?.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
                    });

                    wrapper.addEventListener('scroll', state.sync, { passive: true });
                    window.addEventListener('scroll', state.sync, { passive: true });
                    window.addEventListener('resize', state.sync);

                    state.observer = new MutationObserver(() => {
                        scheduleRebuild(wrapper);
                    });
                    state.observer.observe(wrapper, {
                        childList: true,
                        subtree: true,
                        attributes: true,
                    });

                    wrapper.__stickyHeadState = state;
                    scheduleRebuild(wrapper);
                };

                const initStickyTables = () => {
                    document.querySelectorAll('.table-sticky-head-wrapper').forEach(attachStickyTable);
                };

                initStickyTables();
                document.addEventListener('DOMContentLoaded', initStickyTables);
                document.addEventListener('livewire:navigated', initStickyTables);
                document.addEventListener('livewire:init', initStickyTables);

                // Le celle sticky creano uno stacking context: un modal al loro interno finirebbe
                // sotto il backdrop, quindi lo spostiamo nel body finché resta aperto.
                document.addEventListener('show.bs.modal', (event) => {
                    const modal = event.target;
                    if (modal.parentElement === document.body || !modal.closest('.table-sticky-actions')) {
                        return;
                    }

                    const parent = modal.parentElement;
                    const next = modal.nextSibling;
                    document.body.appendChild(modal);

                    modal.addEventListener('hidden.bs.modal', () => {
                        // Se nel frattempo Livewire ha ri-renderizzato la riga, il modal originale non serve più
                        if (parent.isConnected && !parent.querySelector('.modal')) {
                            parent.insertBefore(modal, next?.parentNode === parent ? next : null);
                        } else {
                            modal.remove();
                        }
                    }, { once: true });
                });
            })();
        </script>
    </body>
</html>
