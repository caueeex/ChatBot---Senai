const makeWASocket = require("@whiskeysockets/baileys").default;
const { useMultiFileAuthState } = require("@whiskeysockets/baileys");
const qrcode = require("qrcode");
const express = require("express");
const { createServer } = require("http");
const { Server } = require("socket.io");
const path = require("path");
const fs = require("fs");
const mysql = require("mysql2");
const portfinder = require("portfinder"); // Adicionado para verificar a porta

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
    res.redirect("http://localhost/php-frontend2/index.php");
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
function salvarAtendimento(numero, escolha, email, opcaoAtendimento, statusAtendimento = 'Aberto') {
    const query = `
        INSERT INTO atendimentos (numero, escolha, email, opcao_atendimento, status_atendimento)
        VALUES (?, ?, ?, ?, ?)
    `;
    connection.query(query, [numero, escolha, email, opcaoAtendimento, statusAtendimento], (err, results) => {
        if (err) {
            console.error("Erro ao salvar atendimento:", err);
        } else {
            console.log("Atendimento salvo com sucesso!");
            usuarios[numero].atendimentoId = results.insertId;
        }
    });
}

// Função para atualizar o status do atendimento
function atualizarStatusAtendimento(atendimentoId, statusAtendimento) {
    const query = `
        UPDATE atendimentos
        SET status_atendimento = ?
        WHERE id = ?
    `;
    connection.query(query, [statusAtendimento, atendimentoId], (err, results) => {
        if (err) {
            console.error("Erro ao atualizar status do atendimento:", err);
        } else {
            console.log("Status do atendimento atualizado com sucesso!");
        }
    });
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
                    await gerenciarFluxoConversa(sock, remetente, texto);
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
    if (!usuarios[remetente]) {
        usuarios[remetente] = { passo: 1 };
    }

    const usuario = usuarios[remetente];

    switch (usuario.passo) {
        case 1:
            await sock.sendMessage(remetente, {
                text: "Olá! Bem-vindo ao atendimento do SENAI. Como posso ajudar?",
            });
            await sock.sendMessage(remetente, {
                text: "Por favor nos informe para qual dos itens abaixo você deseja atendimento:\n\n1- Sesi\n2- Senai",
            });
            usuario.passo = 2;
            break;

        case 2:
            if (texto === "1" || texto === "2") {
                usuario.escolha = texto === "1" ? "Sesi" : "Senai";
                await sock.sendMessage(remetente, {
                    text: "Você concorda com os nossos Termos de Uso e Política de Privacidade? Acesse aqui: https://www.sp.senai.br/termos-de-uso-e-politica-de-privacidade' \n\n1- Sim\n2- Não  ",
                });
                usuario.passo = 3;
            } else {
                await sock.sendMessage(remetente, {
                    text: "Opção inválida. Por favor, digite 1 para Sesi ou 2 para Senai.",
                });
            }
            break;

        case 3:
            if (texto === "1" || texto === "2") {
                usuario.aceitouTermos = texto === "1";
                if (usuario.aceitouTermos) {
                    await sock.sendMessage(remetente, {
                        text: "Para iniciar seu atendimento, preciso do seu email. Por favor, digite seu email.",
                    });
                    usuario.passo = 4;
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

        case 4:
            if (texto.includes("@")) {
                usuario.email = texto;
                await sock.sendMessage(remetente, {
                    text: "Sobre qual assunto você deseja atendimento?\n\n1- Curso Presencial e EAD\n2- Atendimento a Empresas\n3- Emissão de Boleto - SENAI\n4- Documentação/Certificado\n5- RH / Licitações / Arrecadação / Outras áreas",
                });
                usuario.passo = 5;
            } else {
                await sock.sendMessage(remetente, {
                    text: "Email inválido. Por favor, digite um email válido.",
                });
            }
            break;

        case 5:
            if (["1", "2", "3", "4", "5"].includes(texto)) {
                usuario.opcaoAtendimento = texto;
                salvarAtendimento(
                    remetente,
                    usuario.escolha,
                    usuario.email,
                    usuario.opcaoAtendimento,
                    'Aberto'
                );
                switch (usuario.opcaoAtendimento) {
                    case "1":
                        await sock.sendMessage(remetente, {
                            text: "Você perguntou sobre Cursos. Como posso te ajudar?\n\n1- Cursos de Curta Duração (Presencial)\n2- Cursos de Curta Duração a Distância (EAD)\n3- Cursos de Curta Duração (Bolsa de Estudos)\n4- Curso Regular (Aprendizagem Industrial)\n5- Curso Regular (Técnico)\n6- Curso Regular (Faculdade)\n7- Curso Regular (Pós Graduação)",
                        });
                        usuario.passo = 6;
                        break;
                    case "2":
                        await sock.sendMessage(remetente, {
                            text: "Você perguntou sobre Atendimento às Empresas. Como posso te ajudar?\n\n1- Serviços Laboratoriais\n2- Assessoria\n3- Inovação\n4- Contratação de Alunos",
                        });
                        usuario.passo = 7;
                        break;
                    case "3":
                        await sock.sendMessage(remetente, {
                            text: "Para emissão de boleto, entre em contato conosco pelo site: https://www.senai.br/ou pelo telefone (XX) XXXX-XXXX.",
                        });
                        usuario.passo = 8;
                        break;
                    case "4":
                        await sock.sendMessage(remetente, {
                            text: "Para documentação/certificado, entre em contato conosco pelo site: https://www.senai.br/ou pelo telefone (XX) XXXX-XXXX.",
                        });
                        usuario.passo = 8;
                        break;
                    case "5":
                        await sock.sendMessage(remetente, {
                            text: "Para RH / Licitações / Arrecadação / Outras áreas, entre em contato conosco pelo site: https://www.senai.br/ou pelo telefone (XX) XXXX-XXXX.",
                        });
                        usuario.passo = 8;
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

        case 6:
            if (["1", "2", "3", "4", "5", "6", "7"].includes(texto)) {
                await sock.sendMessage(remetente, {
                    text: "Antes de concluirmos, suas dúvidas foram esclarecidas?\n\n1- Sim\n2- Não",
                });
                usuario.passo = 8;
            } else {
                await sock.sendMessage(remetente, {
                    text: "Opção inválida. Por favor, digite o número da opção desejada.",
                });
            }
            break;

        case 7:
            if (["1", "2", "3", "4"].includes(texto)) {
                await sock.sendMessage(remetente, {
                    text: "Antes de concluirmos, suas dúvidas foram esclarecidas?\n\n1- Sim\n2- Não",
                });
                usuario.passo = 8;
            } else {
                await sock.sendMessage(remetente, {
                    text: "Opção inválida. Por favor, digite o número da opção desejada.",
                });
            }
            break;

        case 8:
            if (texto === "1" || texto === "2") {
                if (texto === "1") {
                    await sock.sendMessage(remetente, {
                        text: "Que bom que suas dúvidas foram esclarecidas! Obrigado por entrar em contato.",
                    });
                    if (usuario.atendimentoId) {
                        atualizarStatusAtendimento(usuario.atendimentoId, 'Finalizado');
                    }
                    delete usuarios[remetente];
                } else {
                    await sock.sendMessage(remetente, {
                        text: "Podemos recomeçar o atendimento por aqui ou você pode enviar seu questionamento por escrito para ser respondido por um atendente. O que você prefere?\n\n1- Responder formulário\n2- Retornar ao menu\n3- Falar com Atendente",
                    });
                    usuario.passo = 9;
                }
            } else {
                await sock.sendMessage(remetente, {
                    text: "Opção inválida. Por favor, digite 1 para Sim ou 2 para Não.",
                });
            }
            break;

        case 9:
            if (["1", "2", "3"].includes(texto)) {
                switch (texto) {
                    case "1":
                        await sock.sendMessage(remetente, {
                            text: "Por favor, acesse o formulário em: https://www.senai.br/formulario",
                        });
                        break;
                    case "2":
                        await sock.sendMessage(remetente, {
                            text: "Retornando ao menu principal...",
                        });
                        usuario.passo = 1;
                        break;
                    case "3":
                        await sock.sendMessage(remetente, {
                            text: "Por favor, entre em contato conosco pelo telefone (XX) XXXX-XXXX.",
                        });
                        break;
                }
                if (texto !== "2" && usuario.atendimentoId) {
                    atualizarStatusAtendimento(usuario.atendimentoId, 'Finalizado');
                }
                if (texto !== "2") {
                    delete usuarios[remetente];
                }
            } else {
                await sock.sendMessage(remetente, {
                    text: "Opção inválida. Por favor, digite o número da opção desejada.",
                });
            }
            break;

        default:
            await sock.sendMessage(remetente, {
                text: "Ocorreu um erro no fluxo de atendimento. Por favor, tente novamente.",
            });
            delete usuarios[remetente];
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

    // Inicia o servidor se a porta estiver livre
    server.listen(PORT, () => {
        console.log(`Servidor rodando em http://localhost:${PORT}`);
    });
});