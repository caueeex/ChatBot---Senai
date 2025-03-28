// Função para tentar conectar ao Socket.IO com tentativas
function connectSocketWithRetry(maxRetries = 10, retryInterval = 3000) {
    let retries = 0;

    const tryConnect = () => {
        console.log(`Tentativa ${retries + 1}/${maxRetries} de conexão ao Socket.IO...`);
        const socket = io('http://localhost:5000', { 
            reconnection: false // Desativa reconexão automática para controle manual
        });

        socket.on('connect', () => {
            console.log('Conectado ao Socket.IO com sucesso.');
            setupSocketListeners(socket);
        });

        socket.on('connect_error', (error) => {
            retries++;
            console.log(`Erro ao conectar ao Socket.IO: ${error.message}`);
            if (retries < maxRetries) {
                console.log(`Tentativa ${retries}/${maxRetries}: Não foi possível conectar ao Socket.IO. Tentando novamente em ${retryInterval/1000} segundos...`);
                setTimeout(tryConnect, retryInterval);
            } else {
                console.log('Não foi possível conectar ao Socket.IO após várias tentativas.');
                errorContainer.classList.remove('hidden');
                errorText.textContent = 'Não foi possível conectar ao servidor. Verifique se o servidor Node.js está rodando.';
                conectarBtn.disabled = false;
                conectarBtn.innerHTML = '<i class="fas fa-qrcode"></i> Conectar WhatsApp';
            }
        });

        return socket;
    };

    return tryConnect();
}

let socket = null;

const conectarBtn = document.getElementById("conectarBtn");
const desconectarBtn = document.getElementById("desconectarBtn");
const qrCodeContainer = document.getElementById("qrCodeContainer");
const qrCodeImage = document.getElementById("qrCodeImage");
const statusContainer = document.getElementById("statusContainer");
const statusText = document.getElementById("statusText");
const errorContainer = document.getElementById("errorContainer");
const errorText = document.getElementById("errorText");
const connectionStatus = document.getElementById("connectionStatus");

let conexaoIniciada = false;

function setupSocketListeners(socket) {
    socket.on("qrCode", (qrCodeData) => {
        console.log("QR Code recebido:", qrCodeData ? "Dados recebidos" : "Nenhum dado");
        if (conexaoIniciada && qrCodeData) {
            qrCodeContainer.classList.remove("hidden");
            qrCodeImage.src = qrCodeData;
            statusContainer.classList.remove("hidden");
            statusText.textContent = "Escaneie o QR Code para conectar ao WhatsApp.";
        } else {
            qrCodeContainer.classList.add("hidden");
        }
    });

    socket.on("connected", () => {
        console.log("Evento 'connected' recebido.");
        if (conexaoIniciada) {
            qrCodeContainer.classList.add("hidden");
            statusContainer.classList.remove("hidden");
            statusText.innerHTML = '<i class="fas fa-check-circle"></i> Conectado com sucesso!';
            conectarBtn.innerHTML = '<i class="fas fa-check"></i> Conectado';
            conectarBtn.disabled = true;
            desconectarBtn.classList.remove("hidden");
            connectionStatus.classList.remove('disconnected');
            connectionStatus.classList.add('connected');
            connectionStatus.querySelector('span').textContent = 'Conectado';
            connectionStatus.querySelector('i').classList.replace('fa-circle', 'fa-check-circle');
            addHistoryEntry('Conectado ao WhatsApp');
        }
    });

    socket.on("disconnected", () => {
        console.log("Evento 'disconnected' recebido.");
        conexaoIniciada = false;
        statusContainer.classList.remove("hidden");
        statusText.textContent = 'Desconectado com sucesso!';
        connectionStatus.classList.remove('connected');
        connectionStatus.classList.add('disconnected');
        connectionStatus.querySelector('span').textContent = 'Desconectado';
        connectionStatus.querySelector('i').classList.replace('fa-check-circle', 'fa-circle');
        addHistoryEntry('Desconectado do WhatsApp');
        qrCodeContainer.classList.add("hidden");
        statusContainer.classList.add("hidden");
        conectarBtn.disabled = false;
        conectarBtn.innerHTML = '<i class="fas fa-qrcode"></i> Conectar WhatsApp';
        desconectarBtn.classList.add("hidden");
    });

    socket.on("error", (message) => {
        console.log("Erro recebido do servidor:", message);
        errorContainer.classList.remove("hidden");
        errorText.textContent = message;
        conectarBtn.disabled = false;
        conectarBtn.innerHTML = '<i class="fas fa-qrcode"></i> Conectar WhatsApp';
        conexaoIniciada = false;
    });
}

conectarBtn.addEventListener("click", () => {
    if (!conexaoIniciada) {
        conexaoIniciada = true;
        conectarBtn.disabled = true;
        conectarBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Conectando...';
        desconectarBtn.classList.remove("hidden");
        errorContainer.classList.add("hidden");

        console.log("Iniciando requisição para start_node.php...");
        fetch('start_node.php', {
            method: 'GET',
        })
        .then(response => {
            console.log("Resposta recebida do start_node.php:", response);
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("Dados do start_node.php:", data);
            if (data.status === 'success') {
                console.log(data.message);
                socket = connectSocketWithRetry();
                socket.emit("iniciarConexao");
            } else {
                console.error("Erro ao iniciar o servidor:", data.message);
                if (data.log) {
                    console.error("Conteúdo do log:", data.log);
                }
                errorContainer.classList.remove("hidden");
                errorText.textContent = data.message;
                conectarBtn.disabled = false;
                conectarBtn.innerHTML = '<i class="fas fa-qrcode"></i> Conectar WhatsApp';
                conexaoIniciada = false;
            }
        })
        .catch(error => {
            console.error('Erro ao chamar start_node.php:', error);
            errorContainer.classList.remove("hidden");
            errorText.textContent = 'Erro ao iniciar o servidor Node.js: ' + error.message;
            conectarBtn.disabled = false;
            conectarBtn.innerHTML = '<i class="fas fa-qrcode"></i> Conectar WhatsApp';
            conexaoIniciada = false;
        });
    }
});

desconectarBtn.addEventListener("click", () => {
    console.log("Botão Desconectar clicado.");
    if (socket) {
        socket.emit("desconectarBot");
        socket.disconnect();
    }
    conexaoIniciada = false;
    conectarBtn.disabled = false;
    conectarBtn.innerHTML = '<i class="fas fa-qrcode"></i> Conectar WhatsApp';
    desconectarBtn.classList.add("hidden");
    qrCodeContainer.classList.add("hidden");
    statusContainer.classList.add("hidden");
});