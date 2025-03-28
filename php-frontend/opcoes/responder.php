<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

require '../conexao.php';

// Busca os dados do usuário logado
$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id_user = ?');
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Verifica se o atendimento_id e o número foram passados
if (!isset($_GET['atendimento_id']) || !isset($_GET['numero'])) {
    header('Location: cursos.php');
    exit;
}

$atendimentoId = $_GET['atendimento_id'];
$numero = $_GET['numero'];

// Busca os dados do atendimento
$stmtAtendimento = $pdo->prepare('SELECT * FROM atendimentos WHERE id = ?');
$stmtAtendimento->execute([$atendimentoId]);
$atendimento = $stmtAtendimento->fetch(PDO::FETCH_ASSOC);

if (!$atendimento) {
    header('Location: cursos.php');
    exit;
}

// Formata o número para o formato do WhatsApp
$numeroFormatado = $numero . '@s.whatsapp.net';
?>

<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat de Atendimento - SENAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/responder.css">
    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
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
                        'senai-purple': '#8B5CF6',
                        'whatsapp-green': '#25D366',
                        'whatsapp-bg': '#ECE5DD',
                        'whatsapp-light': '#DCF8C6',
                        'whatsapp-dark': '#075E54'
                    }
                }
            }
        }
    </script>
</head>
<body class="transition-colors duration-300">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 w-64 bg-senai-red text-white transform transition-transform duration-300 ease-in-out z-20" id="sidebar">
            <div class="p-4 flex justify-between items-center">
                <h2 class="text-2xl font-bold tracking-wide">SENAI</h2>
                <button class="lg:hidden" onclick="toggleSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <nav class="mt-5">
                <a href="../dashboard.php" class="flex items-center p-4 hover:bg-white/20 transition-colors rounded-r-lg">
                    <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                    Início
                </a>
                <a href="../whatsapp.php" class="flex items-center p-4 hover:bg-white/20 transition-colors rounded-r-lg">
                    <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.458 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7z"/></svg>
                    Conectar WhatsApp
                </a>
                <a href="../usuarios.php" class="flex items-center p-4 hover:bg-white/20 transition-colors rounded-r-lg">
                    <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
                    Usuários
                </a>
                <a href="../relatorios.php" class="flex items-center p-4 hover:bg-white/20 transition-colors rounded-r-lg">
                    <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M2 4a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V4zm2 0v12h12V4H4z"/></svg>
                    Relatórios
                </a>
                <a href="../editar_folheto.php" class="flex items-center p-4 hover:bg-white/20 transition-colors rounded-r-lg">
                    <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                    Editar Folheto
                </a>
                <a href="../configuracao.php" class="flex items-center p-4 hover:bg-white/20 transition-colors rounded-r-lg">
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
                <!-- Chat Section -->
                <div class="chat-section shadow-custom">
                    <div class="chat-header">
                        <div class="contact-info">
                            <img id="contactPhoto" src="https://via.placeholder.com/40" alt="Foto do Contato">
                            <h2 id="contactName"><?php echo htmlspecialchars(str_replace(['@s.whatsapp.net', '@s.what'], '', $atendimento['numero'])); ?></h2>
                        </div>
                        <span id="botStatus" class="bot-status">Carregando...</span>
                    </div>
                    <div class="chat-body" id="chatBody">
                        <!-- Mensagens serão adicionadas aqui dinamicamente -->
                    </div>
                    <div class="chat-actions">
                        <div class="action-buttons">
                            <div class="tooltip">
                                <button id="disableBotButton" class="disable-button">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v2h-2zm0 4h2v6h-2z"/></svg>
                                    Desativar Bot
                                </button>
                                <span class="tooltip-text">Desativa o bot e ativa o atendimento humanizado</span>
                            </div>
                            <div class="tooltip">
                                <button id="finalizeButton" class="finalize-button">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm4.29-9.29a1 1 0 00-1.41-1.41L12 12.17l-2.88-2.88a1 1 0 00-1.41 1.41L10.59 13l-2.88 2.88a1 1 0 001.41 1.41L12 14.41l2.88 2.88a1 1 0 001.41-1.41L13.41 13l2.88-2.88z"/></svg>
                                    Finalizar Atendimento
                                </button>
                                <span class="tooltip-text">Finaliza o atendimento humanizado e reativa o bot</span>
                            </div>
                        </div>
                        <div class="predefined-messages">
                            <button onclick="usePredefinedMessage('Olá, aqui é o <?php echo htmlspecialchars($usuario['nome']); ?>! Como posso ajudar você hoje?')">Saudação</button>
                            <button onclick="usePredefinedMessage('Aguarde um momento, por favor.')">Aguarde</button>
                            <button onclick="usePredefinedMessage('Obrigado por entrar em contato!')">Agradecimento</button>
                        </div>
                    </div>
                    <div class="chat-footer">
                        <textarea id="mensagem" placeholder="Digite sua mensagem..." required></textarea>
                        <button id="sendButton" class="send-button">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                            Enviar
                        </button>
                    </div>
                </div>

                <!-- Notificação -->
                <div id="notification" class="notification"></div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script>
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

        // Conectar ao Socket.IO
        const socket = io('http://localhost:5000');
        const chatBody = document.getElementById('chatBody');
        const mensagemInput = document.getElementById('mensagem');
        const sendButton = document.getElementById('sendButton');
        const disableBotButton = document.getElementById('disableBotButton');
        const finalizeButton = document.getElementById('finalizeButton');
        const botStatus = document.getElementById('botStatus');
        const contactPhoto = document.getElementById('contactPhoto');
        const contactName = document.getElementById('contactName');

        // Função para adicionar uma mensagem ao chat
        function addMessageToChat(text, fromMe, timestamp) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${fromMe ? 'sent' : 'received'}`;
            messageDiv.innerHTML = `
                <p>${text}</p>
                <div class="timestamp">${timestamp}</div>
            `;
            chatBody.appendChild(messageDiv);
            chatBody.scrollTop = chatBody.scrollHeight; // Rola para o final
        }

        // Função para atualizar o status do bot na interface
        function updateBotStatus(isActive) {
            botStatus.textContent = isActive ? 'Bot: Ativo' : 'Bot: Desativado';
            botStatus.className = `bot-status ${isActive ? 'active' : 'disabled'}`;
        }

        // Função para usar mensagens predefinidas
        function usePredefinedMessage(message) {
            mensagemInput.value = message;
            mensagemInput.focus();
        }

        // Verificar o estado da conexão com o WhatsApp e o estado do bot
        socket.on('connect', () => {
            console.log('Conectado ao servidor Socket.IO');
            socket.emit('verificarConexao');
            // Buscar o histórico de mensagens
            socket.emit('buscarHistorico', '<?php echo $numeroFormatado; ?>');
            // Verificar o estado do bot
            socket.emit('verificarEstadoBot', { numero: '<?php echo $numeroFormatado; ?>' });
            // Buscar informações do contato
            socket.emit('buscarInformacoesContato', '<?php echo $numeroFormatado; ?>');
        });

        // Receber informações do contato
        socket.on('informacoesContato', (data) => {
            if (data.success) {
                contactName.textContent = data.nome;
                if (data.foto) {
                    contactPhoto.src = data.foto;
                } else {
                    contactPhoto.src = 'https://via.placeholder.com/40?text=Sem+Foto';
                }
            } else {
                console.error('Erro ao buscar informações do contato:', data.error);
                contactName.textContent = '<?php echo htmlspecialchars(str_replace(['@s.whatsapp.net', '@s.what'], '', $atendimento['numero'])); ?>';
                contactPhoto.src = 'https://via.placeholder.com/40?text=Sem+Foto';
            }
        });

        socket.on('connected', (isConnected) => {
            if (!isConnected) {
                showNotification('Erro: WhatsApp não está conectado. Por favor, conecte-se em "Conectar WhatsApp".', 'error');
                sendButton.disabled = true;
                disableBotButton.disabled = true;
                finalizeButton.disabled = true;
            }
        });

        // Receber o estado do bot
        socket.on('estadoBot', (data) => {
            if (data.success) {
                updateBotStatus(!data.emAtendimentoHumano);
            } else {
                console.error('Erro ao verificar estado do bot:', data.error);
            }
        });

        // Receber o histórico de mensagens
        socket.on('historicoMensagens', (data) => {
            if (data.success) {
                data.mensagens.forEach(msg => {
                    addMessageToChat(msg.text, msg.fromMe, msg.timestamp);
                });
            } else {
                showNotification('Erro ao carregar histórico: ' + data.error, 'error');
            }
        });

        // Enviar mensagem
        sendButton.addEventListener('click', () => {
            const mensagem = mensagemInput.value.trim();
            if (!mensagem) return;

            // Adiciona a mensagem ao chat imediatamente
            const timestamp = new Date().toLocaleString('pt-BR');
            addMessageToChat(mensagem, true, timestamp);

            // Envia a mensagem via Socket.IO
            socket.emit('enviarMensagem', {
                numero: '<?php echo $numeroFormatado; ?>',
                mensagem: mensagem
            });

            // Limpa o campo de texto
            mensagemInput.value = '';
        });

        // Permitir envio com a tecla Enter
        mensagemInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendButton.click();
            }
        });

        // Receber confirmação de envio
        socket.on('mensagemEnviada', (data) => {
            if (data.success) {
                showNotification('Mensagem enviada com sucesso!', 'success');
                // Atualiza o estado do bot após enviar mensagem (deve desativar o bot)
                updateBotStatus(false);
            } else {
                showNotification('Erro ao enviar mensagem: ' + data.error, 'error');
            }
        });

        // Desativar o bot (ativar atendimento humanizado)
        disableBotButton.addEventListener('click', () => {
            socket.emit('desativarBot', {
                numero: '<?php echo $numeroFormatado; ?>'
            });
        });

        // Receber confirmação de desativação do bot
        socket.on('botDesativado', (data) => {
            if (data.success) {
                showNotification('Bot desativado com sucesso! O bot não responderá enquanto o atendimento humanizado estiver ativo.', 'success');
                updateBotStatus(false);
            } else {
                showNotification('Erro ao desativar o bot: ' + data.error, 'error');
            }
        });

        // Finalizar atendimento humanizado (reativar o bot)
        finalizeButton.addEventListener('click', () => {
            socket.emit('finalizarAtendimentoHumano', {
                numero: '<?php echo $numeroFormatado; ?>'
            });
        });

        // Receber confirmação de finalização do atendimento humanizado
        socket.on('atendimentoHumanoFinalizado', (data) => {
            if (data.success) {
                showNotification('Atendimento humanizado finalizado com sucesso! O bot voltará a responder.', 'success');
                updateBotStatus(true);
            } else {
                showNotification('Erro ao finalizar atendimento humanizado: ' + data.error, 'error');
            }
        });

        // Receber novas mensagens do cliente em tempo real
        socket.on('messages.upsert', (data) => {
            data.messages.forEach(msg => {
                if (!msg.key.fromMe && msg.key.remoteJid === '<?php echo $numeroFormatado; ?>') {
                    const text = msg.message?.conversation || msg.message?.extendedTextMessage?.text || '';
                    const timestamp = msg.messageTimestamp ? new Date(msg.messageTimestamp * 1000).toLocaleString('pt-BR') : '';
                    addMessageToChat(text, false, timestamp);
                }
            });
        });

        // Função para exibir notificações
        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.style.display = 'block';
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>