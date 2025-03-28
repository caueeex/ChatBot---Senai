<?php
session_start();

// Verifica se o usuário já está logado
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Processa o formulário de criação de conta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'conexao.php';

    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Verifica se o email já está cadastrado
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $erro = 'Este email já está cadastrado.';
    } else {
        // Hash da senha
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        // Insere o novo usuário no banco de dados
        $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)');
        $stmt->execute([$nome, $email, $senhaHash]);

        // Redireciona para a página de login após o cadastro
        header('Location: dashboard.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - SENAI</title>
    <link rel="stylesheet" href="./css/criar_conta.css">
    <!-- Favicon (opcional) -->
    <link rel="icon" type="image/png" sizes="32x32" href="./favicon_io/favicon-32x32.png">
</head>
<body>
    <div class="container">
        <h1>Criar Conta</h1>
        <p class="subtitle">Crie sua conta no Portal Educação Online</p>
        <?php if (isset($erro)): ?>
            <p class="erro"><?php echo $erro; ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required>

            <button type="submit">Criar Conta</button>
        </form>
        <p>Já tem uma conta? <a href="index.php">Faça login aqui</a>.</p>
    </div>
</body>
</html>