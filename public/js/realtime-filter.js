document.addEventListener('DOMContentLoaded', () => {
    // Buscar inputs de búsqueda por ID o por nombre
    const searchInputs = document.querySelectorAll('input#search, input[name="search"]');

    searchInputs.forEach(searchInput => {
        // Encontrar la tabla en la misma vista
        const table = document.querySelector('table.table');
        if (!table) return;

        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        // Capturar todas las filas de datos iniciales
        const rows = Array.from(tbody.querySelectorAll('tr'));

        // Desactivar el envío automático del formulario al presionar Enter
        const form = searchInput.closest('form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
            });
        }

        // Evento al escribir en el input de búsqueda
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase().trim();
            let visibleRowsCount = 0;

            rows.forEach(row => {
                // Ignorar filas de mensaje dinámico "no-results" o filas que no tengan datos reales
                if (row.classList.contains('no-results-row')) return;
                
                // Si la tabla inicialmente estaba vacía y muestra el mensaje de base de datos vacía, la ignoramos
                if (row.cells.length === 1 && row.cells[0].getAttribute('colspan')) {
                    // Si el usuario escribe algo, ocultamos el mensaje original de base de datos vacía
                    row.style.display = query === '' ? '' : 'none';
                    return;
                }

                // Filtrar por coincidencia case-insensitive en todo el contenido textual de la fila
                const textContent = row.textContent.toLowerCase();
                if (textContent.includes(query)) {
                    row.style.display = '';
                    visibleRowsCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Gestionar dinámicamente la fila de "No se encontraron resultados"
            let noResultsRow = tbody.querySelector('.no-results-row');
            if (visibleRowsCount === 0 && query !== '') {
                if (!noResultsRow) {
                    const columnsCount = table.querySelectorAll('thead th').length || 7;
                    noResultsRow = document.createElement('tr');
                    noResultsRow.className = 'no-results-row';
                    noResultsRow.innerHTML = `
                        <td colspan="${columnsCount}" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                            No se encontraron resultados para "${escapeHTML(searchInput.value)}"
                        </td>
                    `;
                    tbody.appendChild(noResultsRow);
                } else {
                    noResultsRow.style.display = '';
                    noResultsRow.querySelector('td').textContent = `No se encontraron resultados para "${searchInput.value}"`;
                }
            } else {
                if (noResultsRow) {
                    noResultsRow.style.display = 'none';
                }
            }
        });
    });

    // Función auxiliar para sanitizar el output dinámico
    function escapeHTML(str) {
        return str.replace(/[&<>'"]/g, 
            tag => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            }[tag] || tag)
        );
    }
});
