# README.md

## Projeto de Atendimento Automatizado - SENAI

Este projeto é um sistema de atendimento automatizado integrado ao WhatsApp, desenvolvido para o SENAI. Ele utiliza um backend em Node.js com a biblioteca @whiskeysockets/baileys para comunicação com o WhatsApp e um frontend em PHP para interface administrativa. O sistema permite que usuários interajam com um bot no WhatsApp e que secretários realizem atendimentos humanizados quando necessário.

## Funcionalidades

- **Bot no WhatsApp:** Atendimento automatizado com fluxos de conversa (ex.: cursos, atendimento a empresas, emissão de boleto, etc.).
- **Atendimento Humanizado:** Secretários podem desativar o bot e responder diretamente aos usuários.
- **Interface Administrativa:** Painel em PHP para gerenciar atendimentos, usuários, relatórios e folhetos.
- **Integração com Banco de Dados:** Armazena atendimentos, usuários e folhetos no banco MySQL (`db_senai`).
- **Mensagens Predefinidas:** Secretários podem usar mensagens prontas para agilizar respostas.
- **Foto e Nome do Contato:** Exibe a foto de perfil e o nome do contato do WhatsApp no chat.

## Capturas de Tela

Para melhor compreensão do funcionamento do sistema, abaixo estão algumas imagens ilustrativas:

![Dashboard](/dashboard.png)
*Painel administrativo para gerenciamento de atendimentos.*

![Fluxo de Conversa BOT](Fluxo-ChatBot.png)
*Exemplo da tela de login do sistema.*

![Chat WhatsApp](ChatBot-Whatsapp.png)
*Exemplo de um atendimento automatizado via WhatsApp.*

## Pré-requisitos

Antes de começar, certifique-se de ter os seguintes softwares instalados:

- **Node.js** (versão 18 ou superior): [Download Node.js](https://nodejs.org/)
- **PHP** (versão 7.4 ou superior): [Download PHP](https://www.php.net/downloads)
- **XAMPP** (ou outro servidor com Apache e MySQL): [Download XAMPP](https://www.apachefriends.org/pt_br/index.html)
- **MySQL:** Incluído no XAMPP.
- **Git** (opcional, para clonar o repositório): [Download Git](https://git-scm.com/)
- **Navegador Web:** Google Chrome, Firefox, etc.

## Passo a Passo para Configuração e Execução

### 1. Organizar os Diretórios

#### 1.1. Backend (Node.js)

Crie uma pasta chamada `node-backend` na sua Área de Trabalho:

- **No Windows:** `C:\Users\SeuUsuario\Desktop\node-backend`
- **No macOS/Linux:** `/home/SeuUsuario/Desktop/node-backend`

Coloque os arquivos do backend (ex.: `main.js`, `package.json`) dentro dessa pasta.

#### 1.2. Frontend (PHP)

Localize o diretório `htdocs` do XAMPP:

- **No Windows:** `C:\xampp\htdocs`
- **No macOS/Linux:** `/opt/lampp/htdocs` (pode variar dependendo da instalação)

Crie uma pasta chamada `php-frontend` dentro de `htdocs`:

```plaintext
C:\xampp\htdocs\php-frontend
```

Coloque os arquivos do frontend (ex.: `index.php`, `responder.php`, `dashboard.php`, etc.) dentro dessa pasta.

### 2. Configurar o Banco de Dados (`db_senai`)

#### 2.1. Iniciar o XAMPP

1. Abra o XAMPP Control Panel.
2. Inicie os módulos Apache e MySQL.

#### 2.2. Acessar o phpMyAdmin

1. Abra o navegador e acesse: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Faça login (se necessário):
   - **Usuário padrão:** `root`
   - **Senha padrão:** (vazio, ou seja, sem senha)

#### 2.3. Criar o Banco de Dados

1. No phpMyAdmin, clique em "Novo" (ou "New") no menu à esquerda.
2. Digite o nome do banco: `db_senai`.
3. Escolha a codificação: `utf8mb4_general_ci`.
4. Clique em "Criar".

#### 2.4. Criar as Tabelas

Selecione o banco `db_senai` e execute os seguintes comandos SQL para criar as tabelas:

```sql
CREATE TABLE usuarios (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel_acesso ENUM('admin', 'secretario') DEFAULT 'secretario'
);

CREATE TABLE atendimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(255) NOT NULL,
    escolha VARCHAR(255),
    email VARCHAR(255),
    opcao_atendimento VARCHAR(255),
    status_atendimento ENUM('Aberto', 'Finalizado') DEFAULT 'Aberto',
    em_atendimento_humano BOOLEAN DEFAULT FALSE,
    ultima_interacao_secretario DATETIME DEFAULT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE folhetos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opcao_atendimento VARCHAR(255) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    data_inicio DATE,
    data_fim DATE,
    contato VARCHAR(255)
);
```

---

**Nota:** A documentação completa com mais detalhes sobre configuração do backend, frontend e solução de problemas está disponível no repositório.

Para dúvidas ou suporte, entre em contato com a equipe de desenvolvimento:

- **Email:** suporte@senai.com
- **Telefone:** (11) 1234-5678

---

Este documento serve como referência para instalação e execução do projeto. Boa sorte! 🚀

