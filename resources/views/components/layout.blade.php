<!DOCTYPE html>
<html  data-bs-theme="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>NEWizard</title>
        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.12.1/font/bootstrap-icons.min.css">
        <style>
            .table-sticky-head-wrapper {
                overflow-y: visible;
            }

            .table-sticky-head-wrapper .table-sticky-head th {
                position: sticky;
                top: 0;
                z-index: 2;
                background-color: var(--bs-tertiary-bg);
                box-shadow: inset 0 -1px 0 var(--bs-border-color);
            }

            .table-sticky-head-overlay {
                position: fixed;
                top: 0;
                z-index: 1030;
                display: none;
                overflow: hidden;
                pointer-events: auto;
                background-color: var(--bs-body-bg);
            }

            .table-sticky-head-overlay table {
                margin-bottom: 0;
            }

            .table-sticky-head-overlay th {
                background-color: var(--bs-tertiary-bg);
                box-shadow: inset 0 -1px 0 var(--bs-border-color);
            }
        </style>
    </head>
    <body class="">
        <x-navbar></x-navbar>
        {{$slot}}
        <script>
            (() => {
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

                    const sourceHeads = Array.from(thead.querySelectorAll('th'));
                    const cloneHeads = Array.from(state.cloneTable.querySelectorAll('th'));

                    if (sourceHeads.length !== cloneHeads.length) {
                        rebuildStickyTable(wrapper);
                        return;
                    }

                    const headerRect = thead.getBoundingClientRect();
                    const tableRect = table.getBoundingClientRect();
                    const wrapperRect = wrapper.getBoundingClientRect();
                    const shouldShow = headerRect.top < 0 && tableRect.bottom > headerRect.height;

                    if (!shouldShow) {
                        state.overlay.style.display = 'none';
                        return;
                    }

                    state.overlay.style.display = 'block';
                    state.overlay.style.left = `${wrapperRect.left}px`;
                    state.overlay.style.width = `${wrapper.clientWidth}px`;

                    state.cloneTable.className = table.className;
                    state.cloneTable.style.width = `${table.getBoundingClientRect().width}px`;
                    state.cloneTable.style.transform = `translateX(${-wrapper.scrollLeft}px)`;

                    sourceHeads.forEach((th, index) => {
                        const width = th.getBoundingClientRect().width;
                        const clone = cloneHeads[index];
                        clone.style.width = `${width}px`;
                        clone.style.minWidth = `${width}px`;
                        clone.style.maxWidth = `${width}px`;
                    });
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

                const attachStickyTable = (wrapper) => {
                    if (wrapper.__stickyHeadState) {
                        rebuildStickyTable(wrapper);
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
                        sync: () => syncStickyTable(wrapper),
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
                        rebuildStickyTable(wrapper);
                    });
                    state.observer.observe(wrapper, {
                        childList: true,
                        subtree: true,
                        attributes: true,
                    });

                    wrapper.__stickyHeadState = state;
                    rebuildStickyTable(wrapper);
                };

                const initStickyTables = () => {
                    document.querySelectorAll('.table-sticky-head-wrapper').forEach(attachStickyTable);
                };

                initStickyTables();
                document.addEventListener('DOMContentLoaded', initStickyTables);
                document.addEventListener('livewire:navigated', initStickyTables);
                document.addEventListener('livewire:init', initStickyTables);
            })();
        </script>
    </body>
</html>
