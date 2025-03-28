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
    <title>Conectar WhatsApp - SENAI</title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" sizes="16x16" href="./favicon_io/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="./favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="./favicon_io/android-chrome-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="./favicon_io/android-chrome-512x512.png">
    <link rel="apple-touch-icon" sizes="180x180" href="./favicon_io/apple-touch-icon.png">
    <link rel="manifest" href="./favicon_io/site.webmanifest">
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
        .connection-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            color: #64748B;
        }
        .connection-status i {
            font-size: 12px;
        }
        .connection-status.disconnected i {
            color: #E30613; /* senai-red */
        }
        .connection-status.connected i {
            color: #10B981; /* senai-green */
        }
        .hidden {
            display: none;
        }
        .whatsapp-section button {
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .whatsapp-section button:disabled {
            background-color: #E5E7EB;
            cursor: not-allowed;
        }
        #ligarServidorBtn, #conectarBtn {
            background-color: #10B981; /* senai-green */
            color: white;
        }
        #ligarServidorBtn:hover:not(:disabled), #conectarBtn:hover:not(:disabled) {
            background-color: #059669; /* Tom mais escuro de senai-green */
        }
        #desconectarBtn, #reconectarBtn, .clear-history-btn {
            background-color: #E30613; /* senai-red */
            color: white;
        }
        #desconectarBtn:hover, #reconectarBtn:hover, .clear-history-btn:hover {
            background-color: #C20411; /* Tom mais escuro de senai-red */
        }
        #qrCodeContainer {
            text-align: center;
            margin: 1.5rem 0;
            background-color: #FFFFFF;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        .dark #qrCodeContainer {
            background-color: #F3F4F6;
        }
        #qrCodeImage {
            width: 200px;
            height: 200px;
            border-radius: 0.5rem;
        }
        #qrCodeContainer p {
            color: #64748B;
            margin-top: 0.5rem;
        }
        #qrTimer {
            color: #F59E0B; /* senai-yellow */
            font-weight: bold;
        }
        #statusContainer, #errorContainer {
            margin: 1.5rem 0;
            padding: 1rem;
            border-radius: 0.75rem;
        }
        #statusContainer {
            background-color: #F8FAFC; /* senai-light */
            color: #64748B;
        }
        .dark #statusContainer {
            background-color: #E5E7EB;
        }
        #errorContainer {
            background-color: #FDEDED; /* Fundo vermelho claro */
            color: #E30613; /* senai-red */
        }
        .connection-history {
            margin-top: 2rem;
        }
        .connection-history h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #E30613; /* senai-red */
            margin-bottom: 0.5rem;
        }
        .history-list {
            background-color: #FFFFFF;
            border-radius: 0.75rem;
            padding: 1rem;
            max-height: 200px;
            overflow-y: auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        .dark .history-list {
            background-color: #F3F4F6;
        }
        .history-item {
            padding: 0.5rem 0;
            color: #64748B;
            border-bottom: 1px solid #E5E7EB;
        }
        .history-item:last-child {
            border-bottom: none;
        }
        .clear-history-btn {
            margin-top: 0.75rem;
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
            .whatsapp-section button {
                width: 100%;
                justify-content: center;
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
                <h1 class="text-2xl sm:text-3xl font-bold text-senai-gray mb-6">Conectar WhatsApp</h1>
                <div class="whatsapp-section">
                    <!-- Status de Conexão -->
                    <div class="connection-status" id="connectionStatus">
                        <i class="fas fa-circle"></i>
                        <span>Verificando conexão...</span>
                    </div>

                    <!-- Botões -->
                    <button id="ligarServidorBtn"><i class="fas fa-server"></i> Ligar Servidor</button>
                    <button id="conectarBtn" disabled><i class="fas fa-qrcode"></i> Conectar WhatsApp</button>
                    <button id="desconectarBtn" class="hidden"><i class="fas fa-times"></i> Desconectar</button>
                    <button id="reconectarBtn" class="hidden"><i class="fas fa-sync-alt"></i> Tentar Reconectar</button>

                    <!-- QR Code -->
                    <div id="qrCodeContainer" class="hidden">
                        <img id="qrCodeImage" src="" alt="QR Code">
                        <p>Escaneie o QR Code para conectar ao WhatsApp. <span id="qrTimer"></span></p>
                    </div>

                    <!-- Mensagens de Status e Erro -->
                    <div id="statusContainer" class="hidden">
                        <p id="statusText"></p>
                    </div>
                    <div id="errorContainer" class="hidden">
                        <p id="errorText"></p>
                    </div>

                    <!-- Histórico de Conexões -->
                    <div class="connection-history">
                        <h3>Histórico de Conexões</h3>
                        <div class="history-list" id="historyList"></div>
                        <button class="clear-history-btn" onclick="clearHistory()">Limpar Histórico</button>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
    <script>
        let socket = io('http://localhost:5000', { 
            reconnection: true,
            reconnectionAttempts: 30,
            reconnectionDelay: 1000,
            timeout: 20000
        });

        const connectionStatus = document.getElementById('connectionStatus');
        const ligarServidorBtn = document.getElementById('ligarServidorBtn');
        const conectarBtn = document.getElementById('conectarBtn');
        const desconectarBtn = document.getElementById('desconectarBtn');
        const reconectarBtn = document.getElementById('reconectarBtn');
        const qrCodeContainer = document.getElementById('qrCodeContainer');
        const qrCodeImage = document.getElementById('qrCodeImage');
        const qrTimer = document.getElementById('qrTimer');
        const statusContainer = document.getElementById('statusContainer');
        const statusText = document.getElementById('statusText');
        const errorContainer = document.getElementById('errorContainer');
        const errorText = document.getElementById('errorText');
        const historyList = document.getElementById('historyList');

        let qrTimeout = null;
        let serverRunning = false;
        let connectionInitiated = false; // Flag para controlar se a conexão foi iniciada pelo usuário

        function startQrTimer() {
            let timeLeft = 60;
            qrTimer.textContent = `(${timeLeft} segundos restantes)`;
            qrTimeout = setInterval(() => {
                timeLeft--;
                qrTimer.textContent = `(${timeLeft} segundos restantes)`;
                if (timeLeft <= 0) {
                    clearInterval(qrTimeout);
                    qrTimer.textContent = '';
                }
            }, 1000);
        }

        function stopQrTimer() {
            if (qrTimeout) {
                clearInterval(qrTimeout);
                qrTimer.textContent = '';
            }
        }

        socket.on('connect', () => {
            console.log('Conectado ao Socket.IO com sucesso');
            connectionStatus.innerHTML = '<i class="fas fa-circle"></i> <span>Verificando conexão...</span>';
            serverRunning = true;
            ligarServidorBtn.classList.add('hidden');
            conectarBtn.disabled = false;
            socket.emit('verificarConexao');
            reconectarBtn.classList.add('hidden');
            statusContainer.classList.remove('hidden');
            statusText.textContent = 'Clique em "Conectar WhatsApp" para iniciar a conexão.';
        });

        socket.on('connect_error', (error) => {
            console.error('Erro ao conectar ao Socket.IO:', error.message);
            connectionStatus.className = 'connection-status disconnected';
            connectionStatus.innerHTML = '<i class="fas fa-circle"></i> <span>Servidor não está rodando</span>';
            ligarServidorBtn.classList.remove('hidden');
            conectarBtn.disabled = true;
            desconectarBtn.classList.add('hidden');
            reconectarBtn.classList.remove('hidden');
            qrCodeContainer.classList.add('hidden');
            errorContainer.classList.remove('hidden');
            errorText.textContent = 'Servidor Node.js não está rodando. Clique em "Ligar Servidor" para iniciar.';
            stopQrTimer();
        });

        socket.on('qrCode', (qrCodeData) => {
            console.log('QR Code recebido:', qrCodeData ? 'Dados recebidos' : 'Nenhum dado');
            if (connectionInitiated && qrCodeData) { // Só exibe o QR Code se o usuário iniciou a conexão
                qrCodeImage.src = qrCodeData;
                qrCodeContainer.classList.remove('hidden');
                statusContainer.classList.remove('hidden');
                statusText.textContent = 'Escaneie o QR Code para conectar ao WhatsApp.';
                connectionStatus.className = 'connection-status';
                connectionStatus.innerHTML = '<i class="fas fa-circle"></i> <span>Aguardando conexão...</span>';
                conectarBtn.classList.add('hidden');
                desconectarBtn.classList.remove('hidden');
                reconectarBtn.classList.add('hidden');
                startQrTimer();
            } else {
                qrCodeContainer.classList.add('hidden');
                statusContainer.classList.add('hidden');
                connectionStatus.className = 'connection-status disconnected';
                connectionStatus.innerHTML = '<i class="fas fa-circle"></i> <span>Desconectado</span>';
                conectarBtn.classList.remove('hidden');
                desconectarBtn.classList.add('hidden');
                reconectarBtn.classList.remove('hidden');
                stopQrTimer();
            }
        });

        socket.on('connected', (isConnected) => {
            console.log('Evento connected recebido:', isConnected);
            if (isConnected) {
                qrCodeContainer.classList.add('hidden');
                statusContainer.classList.add('hidden');
                connectionStatus.className = 'connection-status connected';
                connectionStatus.innerHTML = '<i class="fas fa-circle"></i> <span>Conectado ao WhatsApp</span>';
                conectarBtn.classList.add('hidden');
                desconectarBtn.classList.remove('hidden');
                reconectarBtn.classList.add('hidden');
                errorContainer.classList.add('hidden');
                addHistoryEntry('Conectado ao WhatsApp');
                stopQrTimer();
                connectionInitiated = false; // Reseta a flag após conexão bem-sucedida
            }
        });

        socket.on('disconnected', () => {
            console.log('Evento disconnected recebido');
            qrCodeContainer.classList.add('hidden');
            statusContainer.classList.remove('hidden');
            statusText.textContent = 'Clique em "Conectar WhatsApp" para iniciar a conexão.';
            connectionStatus.className = 'connection-status disconnected';
            connectionStatus.innerHTML = '<i class="fas fa-circle"></i> <span>Desconectado</span>';
            conectarBtn.classList.remove('hidden');
            desconectarBtn.classList.add('hidden');
            reconectarBtn.classList.remove('hidden');
            errorContainer.classList.add('hidden');
            addHistoryEntry('Desconectado do WhatsApp');
            stopQrTimer();
            connectionInitiated = false; // Reseta a flag ao desconectar
        });

        socket.on('error', (message) => {
            console.error('Erro:', message);
            connectionStatus.className = 'connection-status disconnected';
            connectionStatus.innerHTML = '<i class="fas fa-circle"></i> <span>Erro</span>';
            conectarBtn.classList.remove('hidden');
            desconectarBtn.classList.add('hidden');
            reconectarBtn.classList.remove('hidden');
            qrCodeContainer.classList.add('hidden');
            errorContainer.classList.remove('hidden');
            errorText.textContent = message;
            stopQrTimer();
            connectionInitiated = false; // Reseta a flag em caso de erro
        });

        socket.on('erroMensagem', (message) => {
            console.error('Erro ao enviar mensagem:', message);
            errorContainer.classList.remove('hidden');
            errorText.textContent = message;
        });

        ligarServidorBtn.addEventListener('click', () => {
            console.log('Iniciando servidor...');
            ligarServidorBtn.disabled = true;
            connectionStatus.innerHTML = '<i class="fas fa-circle"></i> <span>Iniciando servidor...</span>';

            fetch('start_node.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        console.log('Servidor Node.js iniciado com sucesso:', data.message);
                        connectionStatus.innerHTML = '<i class="fas fa-circle"></i> <span>Verificando conexão...</span>';
                        ligarServidorBtn.classList.add('hidden');
                        conectarBtn.disabled = false;
                        serverRunning = true;
                    } else {
                        console.error('Erro ao iniciar o servidor Node.js:', data.message);
                        connectionStatus.className = 'connection-status disconnected';
                        connectionStatus.innerHTML = '<i class="fas fa-circle"></i> <span>Erro ao iniciar o servidor</span>';
                        errorContainer.classList.remove('hidden');
                        errorText.textContent = data.message + (data.log ? '\nLog: ' + data.log : '');
                        ligarServidorBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Erro ao chamar start_node.php:', error);
                    connectionStatus.className = 'connection-status disconnected';
                    connectionStatus.innerHTML = '<i class="fas fa-circle"></i> <span>Erro ao iniciar o servidor</span>';
                    errorContainer.classList.remove('hidden');
                    errorText.textContent = 'Erro ao chamar o script de inicialização do servidor. Verifique o console para mais detalhes.';
                    ligarServidorBtn.disabled = false;
                });
        });

        conectarBtn.addEventListener('click', () => {
            if (!serverRunning) {
                errorContainer.classList.remove('hidden');
                errorText.textContent = 'O servidor Node.js não está rodando. Clique em "Ligar Servidor" primeiro.';
                return;
            }
            console.log('Iniciando conexão com o WhatsApp...');
            connectionInitiated = true; // Marca que o usuário iniciou a conexão
            conectarBtn.classList.add('hidden');
            socket.emit('iniciarConexao');
        });

        desconectarBtn.addEventListener('click', () => {
            console.log('Desconectando bot...');
            socket.emit('desconectarBot');
            desconectarBtn.classList.add('hidden');
        });

        reconectarBtn.addEventListener('click', () => {
            console.log('Tentando reconectar...');
            socket.connect();
            reconectarBtn.classList.add('hidden');
            connectionStatus.innerHTML = '<i class="fas fa-circle"></i> <span>Verificando conexão...</span>';
        });

        // Função para toggle da sidebar
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        // Funções para o histórico de conexões
        function addHistoryEntry(action) {
            const timestamp = new Date().toLocaleString('pt-BR');
            const entry = document.createElement('div');
            entry.className = 'history-item';
            entry.textContent = `${action} - ${timestamp}`;
            historyList.prepend(entry);

            let history = JSON.parse(localStorage.getItem('whatsappHistory')) || [];
            history.unshift(`${action} - ${timestamp}`);
            localStorage.setItem('whatsappHistory', JSON.stringify(history));
        }

        function loadHistory() {
            const history = JSON.parse(localStorage.getItem('whatsappHistory')) || [];
            history.forEach(item => {
                const entry = document.createElement('div');
                entry.className = 'history-item';
                entry.textContent = item;
                historyList.appendChild(entry);
            });
        }

        function clearHistory() {
            if (confirm('Tem certeza que deseja limpar o histórico?')) {
                localStorage.removeItem('whatsappHistory');
                historyList.innerHTML = '';
            }
        }

        // Carregar histórico ao iniciar
        window.onload = loadHistory;

        // Toggle de tema
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