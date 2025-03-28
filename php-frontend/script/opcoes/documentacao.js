
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

function clearFilters() {
    window.location.href = 'documentacao.php';
}

function editAtendimento(id) {
    alert('Função de edição para o atendimento ID: ' + id);
}

function responderAtendimento(id, numero) {
    alert('Função de resposta para o atendimento ID: ' + id + '\nNúmero: ' + numero);
    // Aqui você pode implementar a lógica real para responder ao atendimento
    // Por exemplo, abrir um modal ou redirecionar para uma página de resposta
}

function deleteAtendimento(id) {
    if (confirm('Tem certeza que deseja excluir este atendimento?')) {
        alert('Atendimento ID: ' + id + ' excluído com sucesso!');
    }
}

function saveQuery() {
    alert('Consulta salva com sucesso!');
}

function exportData() {
    alert('Dados exportados com sucesso!');
}

const themeToggle = document.getElementById('theme-toggle');
themeToggle.addEventListener('click', () => {
    document.body.classList.toggle('dark');
    localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
});

if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
}
