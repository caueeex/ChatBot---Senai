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
?>

<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - SENAI</title>
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
                        'senai-yellow': '#F59E0B',
                        'senai-blue': '#3B82F6',
                        'senai-purple': '#8B5CF6'
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
        .config-section {
            margin-bottom: 2rem;
        }
        .config-section h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 1rem;
            position: relative;
        }
        .config-section h2::before {
            content: '';
            position: absolute;
            bottom: -0.25rem;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: #E30613;
        }
        .config-item {
            background-color: #ffffff;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .dark .config-item {
            background-color: #f3f4f6;
        }
        .config-item:hover {
            transform: translateY(-2px);
        }
        .config-item label {
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 0.5rem;
            display: block;
        }
        .dark .config-item label {
            color: #111827;
        }
        .config-item input, .config-item select {
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            background-color: #f8fafc;
            color: #64748b;
            outline: none;
            transition: all 0.3s ease;
        }
        .dark .config-item input, .dark .config-item select {
            background-color: #e5e7eb;
            color: #1f2937;
        }
        .config-item input:focus, .config-item select:focus {
            border-color: #E30613;
            ring: 2px;
            ring-color: #E30613;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #E30613;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .color-option {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .color-option.selected {
            border-color: #E30613;
            transform: scale(1.1);
        }
        .profile-pic-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #E30613;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 50;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background-color: #ffffff;
            border-radius: 0.75rem;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            position: relative;
            animation: slideIn 0.3s ease;
        }
        .dark .modal-content {
            background-color: #f3f4f6;
        }
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-content h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #E30613;
            margin-bottom: 1rem;
        }
        .modal-content p {
            color: #64748b;
            margin-bottom: 1.5rem;
        }
        .modal-content .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        .modal-content .modal-actions button {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .modal-content .modal-actions button:first-child {
            background-color: #E30613;
            color: white;
        }
        .modal-content .modal-actions button:first-child:hover {
            background-color: #c20411;
        }
        .modal-content .modal-actions button:last-child {
            background-color: #64748b;
            color: white;
        }
        .modal-content .modal-actions button:last-child:hover {
            background-color: #4b5563;
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
            .config-item {
                padding: 1rem;
            }
            .config-item .flex {
                flex-direction: column;
                gap: 0.5rem;
            }
            .config-item button {
                width: 100%;
            }
            .color-options {
                flex-wrap: wrap;
                gap: 0.5rem;
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
                <h1 class="text-2xl sm:text-3xl font-bold text-senai-gray mb-6">Configurações</h1>

                <!-- Configurações Gerais -->
                <div class="config-section">
                    <h2>Configurações Gerais</h2>
                    <div class="config-item shadow-custom shadow-custom-hover">
                        <label for="nome">Nome</label>
                        <input type="text" id="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" placeholder="Seu nome">
                    </div>
                    <div class="config-item shadow-custom shadow-custom-hover">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" placeholder="Seu e-mail">
                    </div>
                    <div class="config-item shadow-custom shadow-custom-hover">
                        <label>Alterar Senha</label>
                        <div class="flex gap-4">
                            <input type="password" id="nova-senha" placeholder="Nova senha">
                            <input type="password" id="confirmar-senha" placeholder="Confirmar senha">
                            <button class="bg-senai-red text-white px-4 py-2 rounded-lg hover:bg-senai-red/80 transition-all" onclick="alterarSenha()">Alterar</button>
                        </div>
                    </div>
                    <div class="config-item shadow-custom shadow-custom-hover">
                        <label>Foto de Perfil</label>
                        <div class="flex items-center gap-4">
                            <img id="profile-pic-preview" src="https://via.placeholder.com/100" alt="Foto de Perfil" class="profile-pic-preview">
                            <input type="file" id="profile-pic" accept="image/*" onchange="previewProfilePic(event)">
                            <button class="bg-senai-red text-white px-4 py-2 rounded-lg hover:bg-senai-red/80 transition-all" onclick="document.getElementById('profile-pic').click()">Upload</button>
                        </div>
                    </div>
                </div>

                <!-- Aparência e Temas -->
                <div class="config-section">
                    <h2>Aparência e Temas</h2>
                    <div class="config-item shadow-custom shadow-custom-hover">
                        <label>Modo Claro/Escuro</label>
                        <div class="flex items-center gap-4">
                            <span class="text-senai-gray">Claro</span>
                            <label class="toggle-switch">
                                <input type="checkbox" id="theme-toggle-switch" onchange="toggleTheme()">
                                <span class="slider"></span>
                            </label>
                            <span class="text-senai-gray">Escuro</span>
                        </div>
                    </div>
                    <div class="config-item shadow-custom shadow-custom-hover">
                        <label>Tema de Cores</label>
                        <div class="color-options flex gap-4">
                            <div class="color-option bg-senai-red" data-color="senai-red" onclick="selectColor('senai-red')"></div>
                            <div class="color-option bg-senai-blue" data-color="senai-blue" onclick="selectColor('senai-blue')"></div>
                            <div class="color-option bg-senai-purple" data-color="senai-purple" onclick="selectColor('senai-purple')"></div>
                            <div class="color-option bg-senai-green" data-color="senai-green" onclick="selectColor('senai-green')"></div>
                        </div>
                    </div>
                    <div class="config-item shadow-custom shadow-custom-hover">
                        <label>Acessibilidade</label>
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <label for="font-size" class="text-sm">Tamanho da Fonte</label>
                                <select id="font-size" onchange="adjustFontSize()">
                                    <option value="small">Pequeno</option>
                                    <option value="medium" selected>Médio</option>
                                    <option value="large">Grande</option>
                                </select>
                            </div>
                            <div class="flex-1">
                                <label for="spacing" class="text-sm">Espaçamento</label>
                                <select id="spacing" onchange="adjustSpacing()">
                                    <option value="tight">Compacto</option>
                                    <option value="normal" selected>Normal</option>
                                    <option value="loose">Amplo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notificações e Permissões -->
                <div class="config-section">
                    <h2>Notificações e Permissões</h2>
                    <div class="config-item shadow-custom shadow-custom-hover">
                        <label>Notificações</label>
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center gap-4">
                                <span class="text-senai-gray">E-mail</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="notif-email">
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-senai-gray">SMS</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="notif-sms">
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-senai-gray">Push</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="notif-push">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="config-item shadow-custom shadow-custom-hover">
                        <label>Alertas</label>
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center gap-4">
                                <span class="text-senai-gray">Sons</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="alert-sounds">
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-senai-gray">Alertas Visuais</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="alert-visual">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="config-item shadow-custom shadow-custom-hover">
                        <label>Permissões</label>
                        <select id="permissions">
                            <option value="user">Usuário</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </div>

                <!-- Integrações e Conectividade -->
                <div class="config-section">
                    <h2>Integrações e Conectividade</h2>
                    <div class="config-item shadow-custom shadow-custom-hover">
                        <label>Conectar com WhatsApp</label>
                        <button class="bg-senai-green text-white px-4 py-2 rounded-lg hover:bg-senai-green/80 transition-all" onclick="connectWhatsApp()">Conectar</button>
                    </div>
                    <div class="config-item shadow-custom shadow-custom-hover">
                        <label>API Key</label>
                        <div class="flex gap-4">
                            <input type="text" id="api-key" placeholder="Sua API Key" readonly value="abc123xyz">
                            <button class="bg-senai-red text-white px-4 py-2 rounded-lg hover:bg-senai-red/80 transition-all" onclick="generateApiKey()">Gerar Nova Key</button>
                        </div>
                    </div>
                    <div class="config-item shadow-custom shadow-custom-hover">
                        <label>Autenticação em Duas Etapas (2FA)</label>
                        <div class="flex items-center gap-4">
                            <span class="text-senai-gray">Desativado</span>
                            <label class="toggle-switch">
                                <input type="checkbox" id="2fa">
                                <span class="slider"></span>
                            </label>
                            <span class="text-senai-gray">Ativado</span>
                        </div>
                    </div>
                </div>

                <!-- Gerenciamento de Dados -->
                <div class="config-section">
                    <h2>Gerenciamento de Dados</h2>
                    <div class="config-item shadow-custom shadow-custom-hover">
                        <label>Exportar Dados</label>
                        <div class="flex gap-4">
                            <button class="bg-senai-blue text-white px-4 py-2 rounded-lg hover:bg-senai-blue/80 transition-all" onclick="exportData('csv')">Exportar CSV</button>
                            <button class="bg-senai-blue text-white px-4 py-2 rounded-lg hover:bg-senai-blue/80 transition-all" onclick="exportData('pdf')">Exportar PDF</button>
                        </div>
                    </div>
                    <div class="config-item shadow-custom shadow-custom-hover">
                        <label>Limpar Cache e Redefinir Configurações</label>
                        <button class="bg-senai-yellow text-white px-4 py-2 rounded-lg hover:bg-senai-yellow/80 transition-all" onclick="clearCache()">Limpar</button>
                    </div>
                    <div class="config-item shadow-custom shadow-custom-hover">
                        <label>Excluir Conta</label>
                        <button class="bg-senai-red text-white px-4 py-2 rounded-lg hover:bg-senai-red/80 transition-all" onclick="openDeleteModal()">Excluir Conta</button>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div id="delete-modal" class="modal">
        <div class="modal-content shadow-custom">
            <h3>Confirmar Exclusão de Conta</h3>
            <p>Tem certeza que deseja excluir sua conta? Esta ação é irreversível e todos os seus dados serão permanentemente removidos.</p>
            <div class="modal-actions">
                <button onclick="deleteAccount()">Excluir</button>
                <button onclick="closeDeleteModal()">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        function toggleTheme() {
            document.body.classList.toggle('dark');
            localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
            document.getElementById('theme-toggle-switch').checked = document.body.classList.contains('dark');
        }

        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
            document.getElementById('theme-toggle-switch').checked = true;
        }

        function previewProfilePic(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('profile-pic-preview');
                output.src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        function selectColor(color) {
            document.querySelectorAll('.color-option').forEach(option => {
                option.classList.remove('selected');
            });
            document.querySelector(`.color-option[data-color="${color}"]`).classList.add('selected');
            alert(`Tema de cor ${color} selecionado! (Funcionalidade a ser implementada)`);
        }

        function adjustFontSize() {
            const size = document.getElementById('font-size').value;
            document.body.style.fontSize = size === 'small' ? '14px' : size === 'medium' ? '16px' : '18px';
        }

        function adjustSpacing() {
            const spacing = document.getElementById('spacing').value;
            document.body.style.lineHeight = spacing === 'tight' ? '1.25' : spacing === 'normal' ? '1.5' : '1.75';
        }

        function alterarSenha() {
            const novaSenha = document.getElementById('nova-senha').value;
            const confirmarSenha = document.getElementById('confirmar-senha').value;
            if (novaSenha !== confirmarSenha) {
                alert('As senhas não coincidem!');
                return;
            }
            alert('Senha alterada com sucesso! (Funcionalidade a ser implementada)');
        }

        function connectWhatsApp() {
            alert('Conectando com WhatsApp... (Funcionalidade a ser implementada)');
        }

        function generateApiKey() {
            const newKey = Math.random().toString(36).substring(2, 15);
            document.getElementById('api-key').value = newKey;
            alert('Nova API Key gerada com sucesso!');
        }

        function exportData(format) {
            alert(`Exportando dados em formato ${format.toUpperCase()}... (Funcionalidade a ser implementada)`);
        }

        function clearCache() {
            if (confirm('Tem certeza que deseja limpar o cache e redefinir as configurações?')) {
                alert('Cache limpo e configurações redefinidas! (Funcionalidade a ser implementada)');
            }
        }

        function openDeleteModal() {
            document.getElementById('delete-modal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('delete-modal').style.display = 'none';
        }

        function deleteAccount() {
            alert('Conta excluída com sucesso! (Funcionalidade a ser implementada)');
            closeDeleteModal();
        }
    </script>
</body>
</html>