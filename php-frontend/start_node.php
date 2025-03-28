<?php
header('Content-Type: application/json');

// Caminho para o Node.js e o main.js
$nodePath = "node"; // Certifique-se de que o Node.js está no PATH do sistema
$mainJsPath = __DIR__ . "/main.js"; // main.js está no mesmo diretório que start_node.php

// Verifica se o arquivo main.js existe
if (!file_exists($mainJsPath)) {
    echo json_encode(["status" => "error", "message" => "Arquivo main.js não encontrado em: $mainJsPath"]);
    exit;
}

// Verifica se o Node.js está acessível
$nodeVersion = shell_exec("$nodePath --version 2>&1");
if (empty($nodeVersion)) {
    echo json_encode(["status" => "error", "message" => "Node.js não encontrado. Certifique-se de que o Node.js está instalado e no PATH do sistema."]);
    exit;
}

// Caminho para o arquivo de log
$logFile = __DIR__ . "/node.log";

// Verifica se o servidor já está rodando na porta 5000
$portCheck = shell_exec("netstat -an | findstr :5000"); // Para Windows
// $portCheck = shell_exec("netstat -an | grep :5000"); // Para Linux/macOS

if (!empty($portCheck)) {
    echo json_encode([
        "status" => "success",
        "message" => "Servidor Node.js já está rodando na porta 5000."
    ]);
    exit;
}

// Comando para iniciar o servidor Node.js em segundo plano
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Windows: Usa 'start /B' para iniciar em segundo plano e mantém o processo ativo
    $command = "start /B \"\" \"$nodePath\" \"$mainJsPath\" > \"$logFile\" 2>&1";
} else {
    // Linux/macOS
    $command = "nohup $nodePath $mainJsPath > $logFile 2>&1 & echo $!";
}

// Executa o comando
exec($command, $output, $returnVar);

// Log do comando executado para depuração
file_put_contents(__DIR__ . "/start_node.log", "Comando executado: $command\nRetorno: $returnVar\nSaída: " . print_r($output, true) . "\n", FILE_APPEND);

// Aguarda mais tempo para o servidor iniciar
sleep(15); // Aumenta o tempo de espera para 15 segundos

// Verifica se o servidor está rodando na porta 5000
$portCheck = shell_exec("netstat -an | findstr :5000"); // Para Windows
// $portCheck = shell_exec("netstat -an | grep :5000"); // Para Linux/macOS

if (!empty($portCheck)) {
    echo json_encode([
        "status" => "success",
        "message" => "Servidor Node.js iniciado com sucesso na porta 5000."
    ]);
    exit;
}

// Lê o log para obter mais detalhes
$logContent = file_exists($logFile) ? file_get_contents($logFile) : "Log não encontrado.";
echo json_encode([
    "status" => "error",
    "message" => "Servidor Node.js não iniciou na porta 5000 após 15 segundos. Verifique o log para mais detalhes.",
    "log" => $logContent
]);