
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

const themeToggle = document.getElementById('theme-toggle');
themeToggle.addEventListener('click', () => {
    document.body.classList.toggle('dark');
    localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
});

if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
}

function redirectWithSound(url) {
    const clickSound = new Audio('https://www.soundjay.com/buttons/button-3.mp3');
    clickSound.play().then(() => {
        window.location.href = url;
    }).catch(() => {
        window.location.href = url;
    });
}

function editAtendimento(id) {
    alert('Função de edição para o atendimento ID: ' + id);
    // Implemente a lógica de edição aqui
}

function deleteAtendimento(id) {
    if (confirm('Tem certeza que deseja excluir este atendimento?')) {
        alert('Atendimento ID: ' + id + ' excluído com sucesso!');
        // Implemente a lógica de exclusão aqui
    }
}

function saveQuery() {
    alert('Consulta salva com sucesso!');
}

function exportData() {
    alert('Dados exportados com sucesso!');
}
