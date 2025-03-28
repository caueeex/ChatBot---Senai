const makeWASocket = require("@whiskeysockets/baileys").default;
const { useMultiFileAuthState } = require("@whiskeysockets/baileys");
const qrcode = require("qrcode");
const express = require("express");
const { createServer } = require("http");
const { Server } = require("socket.io");
const path = require("path");
const fs = require("fs");
const mysql = require("mysql2");
const portfinder = require("portfinder");

const app = express();
const server = createServer(app);
const io = new Server(server, {
    cors: {
        origin: "*", // Permite todas as origens (ajuste para produção)
    },
});

// Configuração do servidor de arquivos estáticos
app.use(express.static(path.join(__dirname, "public")));

// Rota principal
app.get("/", (req, res) => {
    res.redirect("http://localhost/php-frontend/index.php");
});

// Objeto para armazenar o estado da conversa de cada usuário
const usuarios = {};

// Estado da conexão
let sock = null;
let qrCodeData = null;

// Configuração da conexão com o banco de dados
const connection = mysql.createConnection({
    host: "localhost",
    user: "root",
    password: "",
    database: "db_senai",
});

// Função para salvar os dados no banco de dados
function salvarAtendimento(numero, escolha, email, opcaoAtendimento, statusAtendimento = 'Aberto', emAtendimentoHumano = false) {
    return new Promise((resolve, reject) => {
        const query = `
            INSERT INTO atendimentos (numero, escolha, email, opcao_atendimento, status_atendimento, em_atendimento_humano)
            VALUES (?, ?, ?, ?, ?, ?)
        `;
        connection.query(query, [numero, escolha, email, opcaoAtendimento, statusAtendimento, emAtendimentoHumano], (err, results) => {
            if (err) {
                console.error("Erro ao salvar atendimento:", err);
                reject(err);
            } else {
                console.log("Atendimento salvo com sucesso! ID:", results.insertId, "em_atendimento_humano:", emAtendimentoHumano);
                if (usuarios[numero]) {
                    usuarios[numero].atendimentoId = results.insertId;
                }
                resolve(results.insertId);
            }
        });
    });
}

// Função para atualizar o status do atendimento
function atualizarStatusAtendimento(atendimentoId, statusAtendimento, emAtendimentoHumano = null, ultimaInteracaoSecretario = null) {
    return new Promise((resolve, reject) => {
        let query = `
            UPDATE atendimentos
            SET status_atendimento = ?
        `;
        const params = [statusAtendimento];

        if (emAtendimentoHumano !== null) {
            query += `, em_atendimento_humano = ?`;
            params.push(emAtendimentoHumano);
        }

        if (ultimaInteracaoSecretario !== null) {
            query += `, ultima_interacao_secretario = ?`;
            params.push(ultimaInteracaoSecretario);
        } else {
            query += `, ultima_interacao_secretario = NULL`;
        }

        query += ` WHERE id = ?`;
        params.push(atendimentoId);

        connection.query(query, params, (err, results) => {
            if (err) {
                console.error("Erro ao atualizar status do atendimento:", err);
                reject(err);
            } else {
                console.log(`Status do atendimento atualizado com sucesso! ID: ${atendimentoId}, status_atendimento: ${statusAtendimento}, em_atendimento_humano: ${emAtendimentoHumano}, ultima_interacao_secretario: ${ultimaInteracaoSecretario}`);
                resolve(results);
            }
        });
    });
}

// Função para verificar se o usuário está em atendimento humano ou teve interação recente com o secretário
async function verificarAtendimentoHumano(numero) {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT id, em_atendimento_humano, ultima_interacao_secretario
            FROM atendimentos
            WHERE numero = ?
            ORDER BY id DESC
            LIMIT 1
        `;
        connection.query(query, [numero], (err, results) => {
            if (err) {
                console.error("Erro ao verificar atendimento humano:", err);
                reject(err);
            } else if (results.length > 0) {
                const atendimento = results[0];
                const emAtendimentoHumano = atendimento.em_atendimento_humano;
                const ultimaInteracaoSecretario = atendimento.ultima_interacao_secretario;

                console.log(`Verificado para ${numero}: id: ${atendimento.id}, em_atendimento_humano: ${emAtendimentoHumano}, ultima_interacao_secretario: ${ultimaInteracaoSecretario}`);

                if (emAtendimentoHumano) {
                    resolve({ emAtendimentoHumano: true, atendimentoId: atendimento.id });
                } else if (ultimaInteracaoSecretario) {
                    const now = new Date();
                    const ultimaInteracao = new Date(ultimaInteracaoSecretario);
                    const diffHours = (now - ultimaInteracao) / (1000 * 60 * 60);
                    if (diffHours < 24) {
                        console.log(`Interação recente com secretário detectada para ${numero} (${diffHours} horas atrás). Considerando como atendimento humanizado.`);
                        resolve({ emAtendimentoHumano: true, atendimentoId: atendimento.id });
                    } else {
                        resolve({ emAtendimentoHumano: false, atendimentoId: atendimento.id });
                    }
                } else {
                    resolve({ emAtendimentoHumano: false, atendimentoId: atendimento.id });
                }
            } else {
                console.log(`Nenhum atendimento encontrado para ${numero}. Assumindo em_atendimento_humano como false.`);
                resolve({ emAtendimentoHumano: false, atendimentoId: null });
            }
        });
    });
}

// Função para buscar o folheto no banco de dados
async function buscarFolheto(opcaoAtendimento) {
    return new Promise((resolve, reject) => {
        const query = `SELECT * FROM folhetos WHERE opcao_atendimento = ?`;
        connection.query(query, [opcaoAtendimento], (err, results) => {
            if (err) {
                console.error("Erro ao buscar folheto:", err);
                reject(err);
            } else if (results.length > 0) {
                resolve(results[0]);
            } else {
                resolve(null);
            }
        });
    });
}

// Função para converter os dados do folheto em texto formatado para WhatsApp
function converterFolhetoParaTexto(folheto) {
    let texto = `*${folheto.titulo}*\n\n`;
    texto += `${folheto.descricao}\n`;
    if (folheto.data_inicio) {
        texto += `*Data de Início:* ${folheto.data_inicio}\n`;
    }
    if (folheto.data_fim) {
        texto += `*Data de Fim:* ${folheto.data_fim}\n`;
    }
    if (folheto.contato) {
        texto += `_Contato:_ ${folheto.contato}\n`;
    }
    return texto.trim();
}

// Função para apagar a pasta auth_info
function apagarAuthInfo() {
    const authInfoPath = path.join(__dirname, "auth_info");
    if (fs.existsSync(authInfoPath)) {
        fs.rmSync(authInfoPath, { recursive: true, force: true });
        console.log("Pasta auth_info apagada com sucesso.");
    }
}

// Função para desconectar o bot
function desconectarBot() {
    if (sock) {
        sock.end();
        sock = null;
    }
    apagarAuthInfo();
    io.emit("disconnected");
}

// Função para iniciar a conexão com o WhatsApp
async function iniciarBot() {
    try {
        const authDir = path.join(__dirname, "auth_info");

        if (!fs.existsSync(authDir)) {
            fs.mkdirSync(authDir, { recursive: true });
            console.log("Diretório auth_info criado.");
        }

        if (process.platform !== "win32") {
            fs.chmodSync(authDir, 0o777);
            console.log("Permissões do diretório auth_info definidas.");
        }

        const { state, saveCreds } = await useMultiFileAuthState(authDir);
        console.log("Estado de autenticação carregado:", state);

        sock = makeWASocket({
            auth: state,
            printQRInTerminal: false,
            generateHighQualityLinkPreview: false,
        });

        sock.ev.on("connection.update", async (update) => {
            const { qr, connection, lastDisconnect } = update;

            if (qr) {
                console.log("QR Code gerado.");
                qrCodeData = await qrcode.toDataURL(qr);
                io.emit("qrCode", qrCodeData);
            }

            if (connection === "open") {
                console.log("Conexão estabelecida com sucesso.");
                io.emit("connected", true);
            }

            if (connection === "close") {
                console.log("Conexão fechada. Motivo:", lastDisconnect?.error?.output?.statusCode);
                const shouldReconnect = lastDisconnect?.error?.output?.statusCode !== 401;
                if (shouldReconnect) {
                    console.log("Reconectando em 10 segundos...");
                    setTimeout(iniciarBot, 10000);
                } else {
                    console.log("Conexão fechada. Por favor, escaneie o QR Code novamente.");
                    io.emit("qrCode", null);
                }
            }
        });

        sock.ev.on("creds.update", saveCreds);

        sock.ev.on("messages.upsert", async ({ messages }) => {
            for (const mensagem of messages) {
                if (!mensagem.key.fromMe && mensagem.message?.conversation) {
                    const texto = mensagem.message.conversation.toLowerCase();
                    const remetente = mensagem.key.remoteJid;

                    console.log(`📩 Mensagem recebida de ${remetente}: ${texto}`);

                    const { emAtendimentoHumano, atendimentoId } = await verificarAtendimentoHumano(remetente);
                    if (emAtendimentoHumano) {
                        console.log(`Usuário ${remetente} está em atendimento humano ou teve interação recente com o secretário. Bot não responderá.`);
                        io.emit('messages.upsert', { messages: [mensagem] });
                        continue;
                    }

                    if (!usuarios[remetente]) {
                        usuarios[remetente] = { passo: 1 };
                        if (!atendimentoId) {
                            const novoAtendimentoId = await salvarAtendimento(remetente, "Senai", "email@desconhecido.com", "1");
                            usuarios[remetente].atendimentoId = novoAtendimentoId;
                        } else {
                            usuarios[remetente].atendimentoId = atendimentoId;
                        }
                    }

                    await gerenciarFluxoConversa(sock, remetente, texto);
                    io.emit('messages.upsert', { messages: [mensagem] });
                }
            }
        });
    } catch (error) {
        console.error("Erro ao iniciar o bot:", error);
        io.emit("error", "Erro ao conectar. Tente novamente.");
        apagarAuthInfo();
        setTimeout(iniciarBot, 10000);
    }
}

// Função para gerenciar o fluxo de conversa
async function gerenciarFluxoConversa(sock, remetente, texto) {
    const usuario = usuarios[remetente];
    if (!usuario || !usuario.passo) {
        console.log(`Inicializando estado para ${remetente} no passo 1.`);
        usuarios[remetente] = { passo: 1 };
        if (!usuario.atendimentoId) {
            const novoAtendimentoId = await salvarAtendimento(remetente, "Senai", "email@desconhecido.com", "1");
            usuarios[remetente].atendimentoId = novoAtendimentoId;
        }
    }

    console.log(`Processando fluxo para ${remetente}, passo: ${usuario.passo}, atendimentoId: ${usuario.atendimentoId}`);

    switch (usuario.passo) {
        case 1:
            await sock.sendMessage(remetente, {
                text: "Olá! Bem-vindo ao atendimento do SENAI. Como posso ajudar?",
            });
            await sock.sendMessage(remetente, {
                text: "Você concorda com os nossos Termos de Uso e Política de Privacidade? Acesse aqui: https://www.sp.senai.br/termos-de-uso-e-politica-de-privacidade \n\n1- Sim\n2- Não",
            });
            usuario.passo = 2;
            break;

        case 2:
            if (texto === "1" || texto === "2") {
                usuario.aceitouTermos = texto === "1";
                if (usuario.aceitouTermos) {
                    await sock.sendMessage(remetente, {
                        text: "Para iniciar seu atendimento, preciso do seu email. Por favor, digite seu email.",
                    });
                    usuario.passo = 3;
                } else {
                    await sock.sendMessage(remetente, {
                        text: "Infelizmente, não podemos continuar sem sua aceitação dos Termos de Uso e Política de Privacidade.",
                    });
                    delete usuarios[remetente];
                }
            } else {
                await sock.sendMessage(remetente, {
                    text: "Opção inválida. Por favor, digite 1 para Sim ou 2 para Não.",
                });
            }
            break;

        case 3:
            if (texto.includes("@")) {
                usuario.email = texto;
                await sock.sendMessage(remetente, {
                    text: "Sobre qual assunto você deseja atendimento?\n\n1- Curso Presencial e EAD\n2- Atendimento a Empresas\n3- Emissão de Boleto - SENAI\n4- Documentação/Certificado\n5- Atendimento Humanizado",
                });
                usuario.passo = 4;
            } else {
                await sock.sendMessage(remetente, {
                    text: "Email inválido. Por favor, digite um email válido.",
                });
            }
            break;

        case 4:
            if (["1", "2", "3", "4", "5"].includes(texto)) {
                usuario.opcaoAtendimento = texto;
                usuario.escolha = "Senai";
                const emAtendimentoHumano = texto === "5";
                const atendimentoId = await salvarAtendimento(
                    remetente,
                    usuario.escolha,
                    usuario.email,
                    usuario.opcaoAtendimento,
                    'Aberto',
                    emAtendimentoHumano
                );
                usuario.atendimentoId = atendimentoId;
                switch (usuario.opcaoAtendimento) {
                    case "1":
                        await sock.sendMessage(remetente, {
                            text: "Você perguntou sobre Cursos. Como posso te ajudar?\n\n1- Cursos de Curta Duração (Presencial)\n2- Cursos de Curta Duração a Distância (EAD)\n3- Cursos de Curta Duração (Bolsa de Estudos)\n4- Curso Regular (Aprendizagem Industrial)\n5- Curso Regular (Técnico)\n6- Curso Regular (Faculdade)\n7- Curso Regular (Pós Graduação)",
                        });
                        usuario.passo = 5;
                        break;
                    case "2":
                        await sock.sendMessage(remetente, {
                            text: "Você perguntou sobre Atendimento às Empresas. Como posso te ajudar?\n\n1- Serviços Laboratoriais\n2- Assessoria\n3- Inovação\n4- Contratação de Alunos",
                        });
                        usuario.passo = 6;
                        break;
                    case "3":
                        await sock.sendMessage(remetente, {
                            text: "Para emissão de boleto, entre em contato conosco pelo site: https://www.senai.br ou pelo telefone (11) 1234-5678.",
                        });
                        await sock.sendMessage(remetente, {
                            text: "Suas dúvidas foram esclarecidas?\n\n1- Sim\n2- Não",
                        });
                        usuario.passo = 7;
                        break;
                    case "4":
                        await sock.sendMessage(remetente, {
                            text: "Para documentação/certificado, entre em contato conosco pelo site: https://www.senai.br ou pelo telefone (11) 1234-5678.",
                        });
                        await sock.sendMessage(remetente, {
                            text: "Suas dúvidas foram esclarecidas?\n\n1- Sim\n2- Não",
                        });
                        usuario.passo = 7;
                        break;
                    case "5":
                        await sock.sendMessage(remetente, {
                            text: "Você selecionou Atendimento Humanizado. Um atendente entrará em contato com você o mais rápido possível.\n\nVocê também pode entrar em contato diretamente pelo telefone (11) 1234-5678.",
                        });
                        if (usuario.atendimentoId) {
                            await atualizarStatusAtendimento(usuario.atendimentoId, 'Aberto', true);
                        }
                        delete usuario.passo;
                        break;
                    default:
                        await sock.sendMessage(remetente, {
                            text: "Opção inválida. Por favor, digite o número da opção desejada.",
                        });
                        break;
                }
            } else {
                await sock.sendMessage(remetente, {
                    text: "Opção inválida. Por favor, digite o número da opção desejada.",
                });
            }
            break;

        case 5:
            if (["1", "2", "3", "4", "5", "6", "7"].includes(texto)) {
                usuario.subOpcaoAtendimento = texto;
                const folheto = await buscarFolheto(usuario.subOpcaoAtendimento);
                if (folheto) {
                    const folhetoTexto = converterFolhetoParaTexto(folheto);
                    await sock.sendMessage(remetente, {
                        text: "📄 *Folheto Informativo*\n\n" + folhetoTexto + "\n",
                    });
                } else {
                    await sock.sendMessage(remetente, {
                        text: "📄 *Folheto Informativo*\n\nNenhum folheto disponível para esta opção no momento.\n",
                    });
                }

                await sock.sendMessage(remetente, {
                    text: "Antes de concluirmos, suas dúvidas foram esclarecidas?\n\n1- Sim\n2- Não",
                });
                usuario.passo = 7;
            } else {
                await sock.sendMessage(remetente, {
                    text: "Opção inválida. Por favor, digite o número da opção desejada.",
                });
            }
            break;

        case 6:
            if (["1", "2", "3", "4"].includes(texto)) {
                await sock.sendMessage(remetente, {
                    text: "Você será direcionado para um atendimento humanizado. Um atendente entrará em contato com você o mais rápido possível.\n\nVocê também pode entrar em contato diretamente pelo telefone (11) 1234-5678.",
                });
                if (usuario.atendimentoId) {
                    await atualizarStatusAtendimento(usuario.atendimentoId, 'Aberto', true);
                }
                delete usuario.passo;
            } else {
                await sock.sendMessage(remetente, {
                    text: "Opção inválida. Por favor, digite o número da opção desejada.",
                });
            }
            break;

        case 7:
            if (texto === "1" || texto === "2") {
                if (texto === "1") {
                    await sock.sendMessage(remetente, {
                        text: "Que bom que suas dúvidas foram esclarecidas! Obrigado por entrar em contato.",
                    });
                    if (usuario.atendimentoId) {
                        await atualizarStatusAtendimento(usuario.atendimentoId, 'Finalizado', false);
                    }
                    delete usuarios[remetente];
                } else {
                    await sock.sendMessage(remetente, {
                        text: "Podemos recomeçar o atendimento por aqui ou você pode falar com um atendente. O que você prefere?\n\n1- Retornar ao menu\n2- Falar com Atendente",
                    });
                    usuario.passo = 8;
                }
            } else {
                await sock.sendMessage(remetente, {
                    text: "Opção inválida. Por favor, digite 1 para Sim ou 2 para Não.",
                });
            }
            break;

        case 8:
            if (["1", "2"].includes(texto)) {
                switch (texto) {
                    case "1":
                        await sock.sendMessage(remetente, {
                            text: "Retornando ao menu principal...",
                        });
                        usuario.passo = 1;
                        if (usuario.atendimentoId) {
                            await atualizarStatusAtendimento(usuario.atendimentoId, 'Aberto', false);
                        }
                        break;
                    case "2":
                        await sock.sendMessage(remetente, {
                            text: "Você será direcionado para um atendimento humanizado. Um atendente entrará em contato com você o mais rápido possível.\n\nVocê também pode entrar em contato diretamente pelo telefone (11) 1234-5678.",
                        });
                        if (usuario.atendimentoId) {
                            await atualizarStatusAtendimento(usuario.atendimentoId, 'Aberto', true);
                        }
                        delete usuario.passo;
                        break;
                }
            } else {
                await sock.sendMessage(remetente, {
                    text: "Opção inválida. Por favor, digite o número da opção desejada.",
                });
            }
            break;

        default:
            console.log(`Passo inválido para ${remetente}. Reiniciando o fluxo.`);
            await sock.sendMessage(remetente, {
                text: "Ocorreu um erro no fluxo de atendimento. Vamos recomeçar.",
            });
            usuario.passo = 1;
            await gerenciarFluxoConversa(sock, remetente, texto);
            break;
    }
}

// Inicia a conexão quando o cliente clica no botão
io.on("connection", (socket) => {
    console.log("Cliente conectado ao Socket.IO");

    socket.on("iniciarConexao", () => {
        iniciarBot();
    });

    socket.on("desconectarBot", () => {
        desconectarBot();
    });

    socket.on("verificarConexao", () => {
        console.log("Verificando estado da conexão...");
        if (sock && sock.user) {
            console.log("Conexão ativa:", sock.user);
            socket.emit("connected", true);
        } else {
            console.log("Conexão não está ativa.");
            socket.emit("disconnected");
        }
    });

    // Evento para buscar informações do contato (nome e foto de perfil)
    socket.on('buscarInformacoesContato', async (numero) => {
        try {
            if (!sock) {
                socket.emit('informacoesContato', { success: false, error: 'WhatsApp não está conectado.' });
                return;
            }

            // Busca o nome do contato
            let nomeContato = numero.replace('@s.whatsapp.net', '');
            try {
                const contact = await sock.getContactById(numero);
                nomeContato = contact?.notify || contact?.vname || contact?.name || numero.replace('@s.whatsapp.net', '');
            } catch (error) {
                console.error(`Erro ao buscar nome do contato ${numero}:`, error);
            }

            // Busca a foto de perfil
            let fotoPerfil = null;
            try {
                fotoPerfil = await sock.profilePictureUrl(numero, 'image');
            } catch (error) {
                console.log(`Foto de perfil não disponível para ${numero}:`, error.message);
            }

            console.log(`Informações do contato ${numero}: Nome: ${nomeContato}, Foto: ${fotoPerfil || 'Não disponível'}`);
            socket.emit('informacoesContato', {
                success: true,
                nome: nomeContato,
                foto: fotoPerfil
            });
        } catch (error) {
            console.error('Erro ao buscar informações do contato:', error);
            socket.emit('informacoesContato', { success: false, error: error.message });
        }
    });

    // Evento para enviar mensagem
    socket.on('enviarMensagem', async (data) => {
        const { numero, mensagem } = data;
        try {
            if (!sock) {
                socket.emit('mensagemEnviada', { success: false, error: 'WhatsApp não está conectado.' });
                return;
            }

            await sock.sendMessage(numero, { text: mensagem });
            console.log(`Mensagem enviada para ${numero}: ${mensagem}`);
            socket.emit('mensagemEnviada', { success: true });

            const { emAtendimentoHumano, atendimentoId } = await verificarAtendimentoHumano(numero);
            const now = new Date().toISOString().slice(0, 19).replace('T', ' ');
            if (atendimentoId) {
                await atualizarStatusAtendimento(atendimentoId, 'Aberto', true, now);
                console.log(`Atendimento humanizado ativado e ultima_interacao_secretario atualizada para ${numero}`);
            } else {
                console.log(`Nenhum atendimento encontrado para ${numero}. Criando novo atendimento.`);
                const novoAtendimentoId = await salvarAtendimento(numero, "Senai", "email@desconhecido.com", "1", 'Aberto', true);
                await atualizarStatusAtendimento(novoAtendimentoId, 'Aberto', true, now);
                if (usuarios[numero]) {
                    usuarios[numero].atendimentoId = novoAtendimentoId;
                }
            }
        } catch (error) {
            console.error('Erro ao enviar mensagem:', error);
            socket.emit('mensagemEnviada', { success: false, error: error.message });
        }
    });

    // Evento para desativar o bot (ativar atendimento humanizado)
    socket.on('desativarBot', async (data) => {
        const { numero } = data;
        try {
            const { atendimentoId } = await verificarAtendimentoHumano(numero);
            const now = new Date().toISOString().slice(0, 19).replace('T', ' ');
            if (atendimentoId) {
                await atualizarStatusAtendimento(atendimentoId, 'Aberto', true, now);
                console.log(`Bot desativado para ${numero}. Atendimento humanizado ativado.`);
                socket.emit('botDesativado', { success: true });
            } else {
                console.log(`Nenhum atendimento encontrado para ${numero}. Criando novo atendimento.`);
                const novoAtendimentoId = await salvarAtendimento(numero, "Senai", "email@desconhecido.com", "1", 'Aberto', true);
                await atualizarStatusAtendimento(novoAtendimentoId, 'Aberto', true, now);
                if (usuarios[numero]) {
                    usuarios[numero].atendimentoId = novoAtendimentoId;
                }
                console.log(`Bot desativado para ${numero}. Atendimento humanizado ativado.`);
                socket.emit('botDesativado', { success: true });
            }
        } catch (error) {
            console.error('Erro ao desativar o bot:', error);
            socket.emit('botDesativado', { success: false, error: error.message });
        }
    });

    // Evento para finalizar o atendimento humanizado (reativar o bot)
    socket.on('finalizarAtendimentoHumano', async (data) => {
        const { numero } = data;
        try {
            const { atendimentoId } = await verificarAtendimentoHumano(numero);
            if (atendimentoId) {
                await atualizarStatusAtendimento(atendimentoId, 'Aberto', false, null);
                console.log(`Atendimento humanizado finalizado para ${numero}. Bot reativado.`);

                if (usuarios[numero]) {
                    usuarios[numero].passo = 1;
                    console.log(`Estado do usuário ${numero} reiniciado para passo 1.`);
                }

                socket.emit('atendimentoHumanoFinalizado', { success: true });
            } else {
                console.log(`Nenhum atendimento encontrado para ${numero}. Não é necessário finalizar atendimento humanizado.`);
                if (usuarios[numero]) {
                    usuarios[numero].passo = 1;
                    console.log(`Estado do usuário ${numero} reiniciado para passo 1.`);
                }
                socket.emit('atendimentoHumanoFinalizado', { success: true });
            }
        } catch (error) {
            console.error('Erro ao finalizar atendimento humanizado:', error);
            socket.emit('atendimentoHumanoFinalizado', { success: false, error: error.message });
        }
    });

    // Evento para verificar o estado do bot (ativo ou desativado)
    socket.on('verificarEstadoBot', async (data) => {
        const { numero } = data;
        try {
            const { emAtendimentoHumano } = await verificarAtendimentoHumano(numero);
            socket.emit('estadoBot', { success: true, emAtendimentoHumano });
        } catch (error) {
            console.error('Erro ao verificar estado do bot:', error);
            socket.emit('estadoBot', { success: false, error: error.message });
        }
    });
});

// Verifica se a porta está em uso antes de iniciar o servidor
const PORT = 5000;
portfinder.getPort({ port: PORT }, (err, port) => {
    if (err) {
        console.error("Erro ao verificar a porta:", err);
        return;
    }

    if (port !== PORT) {
        console.log(`Porta ${PORT} já está em uso. O servidor não será iniciado.`);
        return;
    }

    server.listen(PORT, () => {
        console.log(`Servidor rodando em http://localhost:${PORT}`);
    });
});