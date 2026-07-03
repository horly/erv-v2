(() => {
    const normalize = (value = '') => String(value)
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    const pageList = (currentPage, totalPages) => {
        if (totalPages <= 7) {
            return Array.from({ length: totalPages }, (_, index) => index + 1);
        }

        const pages = new Set([1, totalPages, currentPage, currentPage - 1, currentPage + 1]);

        if (currentPage <= 3) {
            pages.add(2);
            pages.add(3);
            pages.add(4);
        }

        if (currentPage >= totalPages - 2) {
            pages.add(totalPages - 3);
            pages.add(totalPages - 2);
            pages.add(totalPages - 1);
        }

        const orderedPages = Array.from(pages)
            .filter((pageNumber) => pageNumber >= 1 && pageNumber <= totalPages)
            .sort((left, right) => left - right);

        return orderedPages.reduce((items, pageNumber, index) => {
            const previous = orderedPages[index - 1];

            if (previous && pageNumber - previous > 1) {
                items.push('ellipsis');
            }

            items.push(pageNumber);
            return items;
        }, []);
    };

    const initPaymentsTable = (wrapper) => {
        if (!wrapper || wrapper.dataset.paymentsTableReady === 'true') {
            return;
        }

        wrapper.dataset.paymentsTableReady = 'true';

        const search = wrapper.querySelector('[data-sales-payments-search]');
        const body = wrapper.querySelector('[data-sales-payments-body]');
        const rows = Array.from(body?.querySelectorAll('[data-payment-row]') || []);
        const empty = wrapper.querySelector('[data-sales-payments-empty]');
        const visibleCount = wrapper.querySelector('[data-sales-payments-visible-count]');
        const pagination = wrapper.querySelector('[data-sales-payments-pagination]');
        const paginationCount = wrapper.querySelector('[data-sales-payments-pagination-count]');
        const paginationNav = wrapper.querySelector('[data-sales-payments-pagination-nav]');
        const totalCount = wrapper.querySelector('[data-sales-payments-total-count]');
        const perPage = 5;
        let page = 1;
        let sortIndex = null;
        let sortType = 'text';
        let sortDirection = 'asc';

        if (totalCount) {
            totalCount.textContent = String(rows.length);
        }

        const cellValue = (row, index) => {
            const cell = row.children[index];
            return cell?.dataset.sortValue ?? cell?.textContent?.trim() ?? '';
        };

        const compareRows = (a, b) => {
            if (sortIndex === null) {
                return 0;
            }

            const aValue = cellValue(a, sortIndex);
            const bValue = cellValue(b, sortIndex);

            if (sortType === 'number') {
                return (Number(String(aValue).replace(',', '.')) || 0) - (Number(String(bValue).replace(',', '.')) || 0);
            }

            return normalize(aValue).localeCompare(normalize(bValue));
        };

        const render = () => {
            const query = normalize(search?.value || '');
            let filteredRows = rows.filter((row) => !query || normalize(row.textContent).includes(query));

            if (sortIndex !== null) {
                filteredRows = filteredRows.sort((a, b) => (sortDirection === 'asc' ? 1 : -1) * compareRows(a, b));
            }

            const totalPages = Math.max(1, Math.ceil(filteredRows.length / perPage));
            page = Math.min(page, totalPages);
            const startIndex = (page - 1) * perPage;
            const pageRows = new Set(filteredRows.slice(startIndex, startIndex + perPage));

            rows.forEach((row) => {
                row.hidden = !pageRows.has(row);
            });

            if (empty) {
                empty.hidden = filteredRows.length > 0;
            }

            if (visibleCount) {
                visibleCount.textContent = String(filteredRows.length);
            }

            if (!pagination || !paginationNav) {
                return;
            }

            if (filteredRows.length > perPage) {
                const previousLabel = pagination.dataset.previousLabel || 'Previous';
                const nextLabel = pagination.dataset.nextLabel || 'Next';
                const showingLabel = pagination.dataset.showingLabel || 'Showing';
                const toLabel = pagination.dataset.toLabel || 'to';
                const onLabel = pagination.dataset.onLabel || 'of';
                const start = startIndex + 1;
                const end = Math.min(startIndex + perPage, filteredRows.length);

                pagination.hidden = false;
                if (paginationCount) {
                    paginationCount.textContent = `${showingLabel} ${start} ${toLabel} ${end} ${onLabel} ${filteredRows.length}`;
                }

                paginationNav.innerHTML = `
                    <button type="button" class="modal-page-button modal-page-edge" ${page === 1 ? 'disabled' : ''} data-sales-payments-page="${page - 1}">${previousLabel}</button>
                    ${pageList(page, totalPages).map((item) => item === 'ellipsis'
                        ? '<span class="pagination-ellipsis" aria-hidden="true">...</span>'
                        : `<button type="button" class="modal-page-button ${item === page ? 'active' : ''}" data-sales-payments-page="${item}">${item}</button>`
                    ).join('')}
                    <button type="button" class="modal-page-button modal-page-edge" ${page === totalPages ? 'disabled' : ''} data-sales-payments-page="${page + 1}">${nextLabel}</button>
                `;
            } else {
                pagination.hidden = true;
            }
        };

        search?.addEventListener('input', () => {
            page = 1;
            render();
        });

        wrapper.querySelectorAll('[data-sales-payments-sort]').forEach((button) => {
            button.addEventListener('click', () => {
                const nextIndex = Number(button.dataset.salesPaymentsSort);
                sortDirection = sortIndex === nextIndex && sortDirection === 'asc' ? 'desc' : 'asc';
                sortIndex = nextIndex;
                sortType = button.dataset.sortType || 'text';
                page = 1;
                render();
            });
        });

        pagination?.addEventListener('click', (event) => {
            const button = event.target.closest('[data-sales-payments-page]');

            if (!button || button.disabled) {
                return;
            }

            page = Number(button.dataset.salesPaymentsPage || '1');
            render();
        });

        render();
    };

    const initAll = () => {
        document.querySelectorAll('[data-sales-payments-table]').forEach(initPaymentsTable);
    };

    initAll();
    document.addEventListener('shown.bs.modal', (event) => {
        initPaymentsTable(event.target.querySelector('[data-sales-payments-table]'));
    });
})();
