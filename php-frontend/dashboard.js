// Função para filtrar cards
function filterCards() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const processFilter = document.getElementById('processFilter').value;
    const cards = document.querySelectorAll('.atendimento-card');

    cards.forEach(card => {
        const searchText = card.getAttribute('data-search');
        const status = card.getAttribute('data-status');
        const matchesSearch = searchText.includes(searchInput);
        const matchesFilter = processFilter === '' || processFilter === 'todos' || processFilter === status;

        if (matchesSearch && matchesFilter) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Função para limpar filtros
function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('processFilter').value = '';
    filterCards();
}

// Função para salvar consulta (placeholder)
function saveQuery() {
    alert('Consulta salva! (Implemente a lógica de salvamento no backend)');
}

// Animação das barras de progresso
document.querySelectorAll('.progress').forEach(progress => {
    const width = progress.style.width;
    progress.style.width = '0';
    setTimeout(() => {
        progress.style.width = width;
    }, 100);
});