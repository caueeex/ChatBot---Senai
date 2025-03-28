<?php
session_start();

// Verifica se o usuário já está logado
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Processa o formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'conexao.php';

    $email = $_POST['email'] ?? ''; // Garante que $email não seja undefined
    $senha = $_POST['senha'] ?? ''; // Garante que $senha não seja undefined

    // Verifica se o usuário existe no banco de dados
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        error_log("Usuário encontrado: " . print_r($usuario, true));
        error_log("Senha de entrada: $senha, Senha esperada: " . ($usuario['senha'] ?? 'não encontrada'));

        // Verifica se a chave 'senha' existe antes de usar password_verify
        if (isset($usuario['senha']) && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id_user'];
            header('Location: dashboard.php');
            exit;
        } else {
            $erro = 'Senha incorreta.';
        }
    } else {
        $erro = 'Email não encontrado.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Educação Online</title>
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
    <style>
        body {
            background: linear-gradient(135deg, #F8FAFC 0%, rgba(227, 6, 19, 0.05) 50%, #F8FAFC 100%);
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }
        .dark body {
            background: linear-gradient(135deg, #E5E7EB 0%, rgba(227, 6, 19, 0.1) 50%, #E5E7EB 100%);
        }
        .background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
        }
        .particles span {
            position: absolute;
            background: rgba(227, 6, 19, 0.3);
            border-radius: 50%;
            animation: float 15s infinite linear;
            pointer-events: none;
        }
        .particles span:nth-child(odd) {
            background: rgba(227, 6, 19, 0.5);
            animation-duration: 20s;
        }
        .particles span:nth-child(1) { width: 10px; height: 10px; left: 10%; top: 20%; }
        .particles span:nth-child(2) { width: 15px; height: 15px; left: 20%; top: 70%; }
        .particles span:nth-child(3) { width: 8px; height: 8px; left: 30%; top: 40%; }
        .particles span:nth-child(4) { width: 12px; height: 12px; left: 40%; top: 90%; }
        .particles span:nth-child(5) { width: 14px; height: 14px; left: 50%; top: 10%; }
        .particles span:nth-child(6) { width: 9px; height: 9px; left: 60%; top: 60%; }
        .particles span:nth-child(7) { width: 11px; height: 11px; left: 70%; top: 30%; }
        .particles span:nth-child(8) { width: 13px; height: 13px; left: 80%; top: 80%; }
        .particles span:nth-child(9) { width: 7px; height: 7px; left: 90%; top: 50%; }
        .particles span:nth-child(10) { width: 16px; height: 16px; left: 15%; top: 85%; }
        @keyframes float {
            0% { transform: translateY(0) translateX(0); opacity: 0.8; }
            50% { opacity: 0.4; }
            100% { transform: translateY(-100vh) translateX(20px); opacity: 0.8; }
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            background-color: #ffffff;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            z-index: 1;
        }
        .dark .login-container {
            background-color: #f3f4f6;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header .logo {
            width: 120px;
            height: auto;
        }
        .header .manual a {
            color: #3B82F6;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .header .manual a:hover {
            color: #2563EB;
        }
        .login-box {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        .login-box h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1F2937;
        }
        .login-box p {
            font-size: 0.875rem;
            color: #64748B;
        }
        .login-box .erro {
            color: #E30613;
            font-size: 0.875rem;
            background-color: #FEE2E2;
            padding: 0.5rem;
            border-radius: 0.5rem;
        }
        .login-box form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .login-box input {
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .login-box input:focus {
            border-color: #E30613;
            box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.1);
        }
        .login-box button {
            background-color: #E30613;
            color: white;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .login-box button:hover {
            background-color: #c20511;
        }
        .login-box a {
            color: #3B82F6;
            font-size: 0.875rem;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .login-box a:hover {
            color: #2563EB;
        }
        .footer {
            text-align: center;
            font-size: 0.75rem;
            color: #64748B;
        }
        .footer .support {
            margin-top: 0.5rem;
        }
        .footer .support span {
            color: #1F2937;
            font-weight: 500;
        }
        @media (max-width: 640px) {
            .login-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            .header .logo {
                width: 100px;
            }
            .login-box h2 {
                font-size: 1.25rem;
            }
        }
    </style>
    <link rel="icon" type="image/png" sizes="16x16" href="./favicon_io/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="./favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="./favicon_io/android-chrome-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="./favicon_io/android-chrome-512x512.png">
    <link rel="apple-touch-icon" sizes="180x180" href="./favicon_io/apple-touch-icon.png">
    <link rel="manifest" href="./favicon_io/site.webmanifest">
</head>
<body class="transition-colors duration-300">
    <div class="background">
        <div class="particles">
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>

    <div class="login-container">
        <div class="header">
            <img src="./img/logo-senai.png" alt="SENAI Educação Online" class="logo">
            <span class="manual"><a href="#">Manual de Acesso</a></span>
        </div>
        <div class="login-box">
            <h2>Login</h2>
            <p>Bem-vindo(a) ao Portal Educação Online</p>
            <?php if (isset($erro)): ?>
                <p class="erro"><?php echo htmlspecialchars($erro); ?></p>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="email" placeholder="Insira seu e-mail ou CPF" required>
                <input type="password" name="senha" placeholder="Senha" required>
                <button type="submit">Avançar</button>
            </form>
            <p>Não tem uma conta? <a href="criar_conta.php">Criar Conta</a></p>
        </div>
        <div class="footer">
            <p>© SENAI-SP - 2025</p>
            <div class="support">
                <span>Atendimento Educação Online</span>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>