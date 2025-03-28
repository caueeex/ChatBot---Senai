-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 28/03/2025 às 08:18
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `db_senai`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `atendimentos`
--

CREATE TABLE `atendimentos` (
  `id` int(11) NOT NULL,
  `numero` varchar(100) NOT NULL,
  `escolha` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `opcao_atendimento` varchar(50) NOT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_atendimento` enum('Aberto','Finalizado') NOT NULL,
  `secretario_id` int(11) DEFAULT NULL,
  `em_atendimento_humano` tinyint(1) DEFAULT 0,
  `ultima_interacao_secretario` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `atendimentos`
--

INSERT INTO `atendimentos` (`id`, `numero`, `escolha`, `email`, `opcao_atendimento`, `data_registro`, `status_atendimento`, `secretario_id`, `em_atendimento_humano`, `ultima_interacao_secretario`) VALUES
(38, '5512997116023@s.whatsapp.net', 'Senai', 'opa@gmail.com', '1', '2025-03-27 06:09:33', 'Aberto', NULL, 0, NULL),
(39, '5512997116023@s.whatsapp.net', 'Senai', 'testee@gmail.com', '1', '2025-03-27 06:18:37', 'Aberto', NULL, 0, NULL),
(40, '5512997116023@s.whatsapp.net', 'Senai', 'opaaa@gmail.com', '1', '2025-03-27 06:43:28', 'Finalizado', 6, 1, '2025-03-27 06:44:58');

-- --------------------------------------------------------

--
-- Estrutura para tabela `folhetos`
--

CREATE TABLE `folhetos` (
  `id` int(11) NOT NULL,
  `opcao_atendimento` varchar(50) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text NOT NULL,
  `data_inicio` varchar(50) DEFAULT NULL,
  `data_fim` varchar(50) DEFAULT NULL,
  `contato` varchar(255) DEFAULT NULL,
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `folhetos`
--

INSERT INTO `folhetos` (`id`, `opcao_atendimento`, `titulo`, `descricao`, `data_inicio`, `data_fim`, `contato`, `data_atualizacao`) VALUES
(2, '5', 'Folheto de Cursos', 'CURSO ADS', '31/03/2025', '31/04/2025', '12991992477', '2025-03-27 06:45:29'),
(3, '1', 'Folheto de Cursos', 'TESTEEEE', '31/03/2025', '31/04/2025', '12991992477', '2025-03-27 06:10:56'),
(4, '7', 'Folheto de Cursosssss', 'TESTEEE', '31/03/2025', '31/04/2025', '12991992477', '2025-03-27 06:18:13');

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagens`
--

CREATE TABLE `mensagens` (
  `id` int(11) NOT NULL,
  `atendimento_id` int(11) NOT NULL,
  `remetente` varchar(50) NOT NULL,
  `mensagem` text NOT NULL,
  `data_envio` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id_user` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `senha` varchar(300) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `funcao` enum('Secretário(a)','Administrador') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id_user`, `email`, `senha`, `nome`, `funcao`) VALUES
(5, 'adm@gmail.com', '$2y$10$5WGOle9IvfNVpFlkOMu8SOU6A9O4J.zxEGP9uhDfJrfEL9KE9VG.O', 'adm', 'Administrador'),
(6, 'caue@gmail.com', '$2y$10$OlqS/uk7Hh4YHxEKruDdTuA9Q72s5h0vWquruyNWnBatQKfxV8/c2', 'cauee', 'Secretário(a)');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `atendimentos`
--
ALTER TABLE `atendimentos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `folhetos`
--
ALTER TABLE `folhetos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `opcao_atendimento` (`opcao_atendimento`);

--
-- Índices de tabela `mensagens`
--
ALTER TABLE `mensagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `atendimento_id` (`atendimento_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `atendimentos`
--
ALTER TABLE `atendimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de tabela `folhetos`
--
ALTER TABLE `folhetos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `mensagens`
--
ALTER TABLE `mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `mensagens`
--
ALTER TABLE `mensagens`
  ADD CONSTRAINT `mensagens_ibfk_1` FOREIGN KEY (`atendimento_id`) REFERENCES `atendimentos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
