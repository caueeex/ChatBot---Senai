<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

require 'conexao.php';

// Busca os dados do usuário logado
$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id_user = ?');
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Busca todos os usuários no banco de dados
$stmtUsuarios = $pdo->query('SELECT * FROM usuarios ORDER BY nome ASC');
$usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - SENAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'senai-red': '#E30613',
                        'senai-light': '#F8FAFC',
                        'senai-gray': '#64748B',
                        'senai-green': '#10B981',
                        'senai-yellow': '#F59E0B'
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-image: url('https://www.transparenttextures.com/patterns/aquarelle.png');
            background-size: cover;
            background-attachment: fixed;
            background-color: #F8FAFC;
        }
        .dark body {
            background-image: url('https://www.transparenttextures.com/patterns/aquarelle.png');
            background-color: #E5E7EB;
        }
        .shadow-custom {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        .shadow-custom-hover:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }
        .search-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .search-section input {
            padding: 0.75rem;
            border-radius: 0.75rem;
            border: 1px solid #e5e7eb;
            background-color: #f8fafc;
            color: #64748b;
            outline: none;
            transition: all 0.3s ease;
        }
        .dark .search-section input {
            background-color: #e5e7eb;
            color: #1f2937;
        }
        .search-section input:focus {
            border-color: #E30613;
            ring: 2px;
            ring-color: #E30613;
        }
        .search-section button {
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            background-color: #E30613;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .search-section button:hover {
            background-color: #c20411;
        }
        .usuarios-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .usuario-card {
            background-color: #ffffff;
            border-radius: 0.75rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        .dark .usuario-card {
            background-color: #f3f4f6;
        }
        .usuario-card:hover {
            transform: translateY(-4px);
        }
        .usuario-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #E30613;
            margin-bottom: 0.5rem;
        }
        .usuario-card p {
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        .usuario-card p strong {
            color: #1f2937;
        }
        .dark .usuario-card p strong {
            color: #111827;
        }
        .card-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
        }
        .card-actions button {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .card-actions button:first-child {
            background-color: #10b981;
            color: white;
        }
        .card-actions button:first-child:hover {
            background-color: #059669;
        }
        .card-actions button:last-child {
            background-color: #E30613;
            color: white;
        }
        .card-actions button:last-child:hover {
            background-color: #c20411;
        }
        .card-actions button i {
            margin-right: 0.5rem;
        }
        .no-data {
            text-align: center;
            color: #64748b;
            padding: 2rem;
            background-color: #f8fafc;
            border-radius: 0.75rem;
        }
        .dark .no-data {
            background-color: #e5e7eb;
        }
        @media (max-width: 1024px) {
            #sidebar {
                transform: translateX(-100%);
            }
            #sidebar.open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0 !important;
            }
            .header {
                left: 0 !important;
            }
        }
        @media (max-width: 640px) {
            .search-section {
                flex-direction: column;
            }
            .search-section input,
            .search-section button {
                width: 100%;
            }
            .usuarios-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="transition-colors duration-300">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <!-- Sidebar -->
<aside class="fixed inset-y-0 left-0 w-64 bg-senai-red text-white transform transition-transform duration-300 ease-in-out z-20" id="sidebar">
    <div class="p-4 flex justify-between items-center">
        <h2 class="text-2xl font-bold tracking-wide">SENAI</h2>
        <button class="lg:hidden" onclick="toggleSidebar()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <nav class="mt-5">
        <a href="dashboard.php" class="flex items-center p-4 hover:bg-white/20 transition-colors rounded-r-lg">
            <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
            Início
        </a>
        <a href="whatsapp.php" class="flex items-center p-4 hover:bg-white/20 transition-colors rounded-r-lg">
            <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.458 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7z"/></svg>
            Conectar WhatsApp
        </a>
        <a href="usuarios.php" class="flex items-center p-4 hover:bg-white/20 transition-colors rounded-r-lg">
            <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
            Usuários
        </a>
        <a href="relatorios.php" class="flex items-center p-4 hover:bg-white/20 transition-colors rounded-r-lg">
            <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M2 4a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V4zm2 0v12h12V4H4z"/></svg>
            Relatórios
        </a>
        <a href="editar_folheto.php" class="flex items-center p-4 hover:bg-white/20 transition-colors rounded-r-lg">
            <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
            Editar Folheto
        </a>
        <a href="configuracao.php" class="flex items-center p-4 hover:bg-white/20 transition-colors rounded-r-lg">
            <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M13 7H7v6h6V7z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm0-2a6 6 0 100-12 6 6 0 000 12z" clip-rule="evenodd"/></svg>
            Configurações
        </a>
    </nav>
</aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden main-content lg:ml-64">
            <!-- Header -->
            <header class="fixed top-0 left-0 right-0 bg-white dark:bg-gray-100 shadow-custom p-4 flex justify-between items-center z-10 header">
                <button class="lg:hidden" onclick="toggleSidebar()">
                    <svg class="w-6 h-6 text-senai-gray" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="flex items-center space-x-4">
                    <span class="font-semibold text-senai-gray hidden sm:block">Olá, <?php echo htmlspecialchars($usuario['nome']); ?>!</span>
                    <button class="p-2 rounded-full hover:bg-senai-red/20 transition-colors">
                        <svg class="w-6 h-6 text-senai-gray" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                    </button>
                    <button id="theme-toggle" class="p-2 rounded-full hover:bg-senai-red/20 transition-colors">
                        <svg class="w-6 h-6 text-senai-gray" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </button>
                </div>
            </header>

            <!-- Main Section -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 mt-16">
                <h1 class="text-2xl sm:text-3xl font-bold text-senai-gray mb-6">Usuários</h1>
                <div class="search-section">
                    <input type="text" id="searchInput" placeholder="Pesquisar usuário..." onkeyup="filterUsers()" class="shadow-custom">
                    <button onclick="clearFilters()"><i class="fas fa-times"></i> Limpar</button>
                </div>
                <div class="usuarios-list" id="usuariosList">
                    <?php if (count($usuarios) > 0): ?>
                        <?php foreach ($usuarios as $user): ?>
                            <div class="card usuario-card shadow-custom shadow-custom-hover" data-search="<?php echo strtolower(htmlspecialchars($user['nome'] . ' ' . $user['email'])); ?>">
                                <h3><?php echo htmlspecialchars($user['nome']); ?></h3>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                <p><strong>Função:</strong> <?php echo htmlspecialchars($user['funcao']); ?></p>
                                <div class="card-actions">
                                    <button onclick="editUsuario(<?php echo $user['id_user']; ?>)"><i class="fas fa-edit"></i> Editar</button>
                                    <button onclick="deleteUsuario(<?php echo $user['id_user']; ?>)"><i class="fas fa-trash"></i> Excluir</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-data">Nenhum usuário cadastrado.</p>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        function filterUsers() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const usuarios = document.querySelectorAll('.usuario-card');

            usuarios.forEach(usuario => {
                const searchData = usuario.getAttribute('data-search');
                if (searchData.includes(searchInput)) {
                    usuario.style.display = 'block';
                } else {
                    usuario.style.display = 'none';
                }
            });
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            filterUsers();
        }

        function editUsuario(id) {
            alert('Função de edição para o usuário ID: ' + id);
            // Implemente a lógica de edição aqui
        }

        function deleteUsuario(id) {
            if (confirm('Tem certeza que deseja excluir este usuário?')) {
                alert('Usuário ID: ' + id + ' excluído com sucesso!');
                // Implemente a lógica de exclusão aqui
            }
        }

        const themeToggle = document.getElementById('theme-toggle');
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark');
            localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
        });

        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
        }
    </script>
</body>
</html>