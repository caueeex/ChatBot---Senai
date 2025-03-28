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

// Configuração da paginação
$itensPorPagina = 10;
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// Parâmetros de filtro e pesquisa
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Conta o total de atendimentos com filtros
$totalQuery = 'SELECT COUNT(*) AS total FROM atendimentos WHERE 1=1';
$params = [];

if ($search) {
    $totalQuery .= ' AND (numero LIKE ? OR email LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($statusFilter && $statusFilter !== 'todos') {
    $totalQuery .= ' AND status_atendimento = ?';
    $params[] = $statusFilter;
}

$stmtTotal = $pdo->prepare($totalQuery);
$stmtTotal->execute($params);
$totalAtendimentos = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

// Calcula o total de páginas
$totalPaginas = ceil($totalAtendimentos / $itensPorPagina);

// Busca os atendimentos com filtros
$query = 'SELECT * FROM atendimentos WHERE 1=1';
$params = [];
$paramCount = 1;

if ($search) {
    $query .= ' AND (numero LIKE ? OR email LIKE ?)';
    $params[$paramCount++] = '%' . $search . '%';
    $paramCount++;
    $params[$paramCount++] = '%' . $search . '%';
}

if ($statusFilter && $statusFilter !== 'todos') {
    $query .= ' AND status_atendimento = ?';
    $params[$paramCount++] = $statusFilter;
}

$query .= ' ORDER BY data_registro DESC LIMIT ? OFFSET ?';

$stmtAtendimentos = $pdo->prepare($query);
foreach ($params as $index => $value) {
    $stmtAtendimentos->bindValue($index, $value);
}
$stmtAtendimentos->bindValue($paramCount++, $itensPorPagina, PDO::PARAM_INT);
$stmtAtendimentos->bindValue($paramCount++, $offset, PDO::PARAM_INT);
$stmtAtendimentos->execute();
$atendimentos = $stmtAtendimentos->fetchAll(PDO::FETCH_ASSOC);

// Mapeamento de opcao_atendimento
$opcaoAtendimentoMap = [
    '1' => 'Curso Presencial e EAD',
    '2' => 'Atendimento a Empresas',
    '3' => 'Emissão de Boleto - SENAI',
    '4' => 'Documentação/Certificado',
    '5' => 'RH / Licitações / Outras áreas',
];
?>

<!DOCTYPE html>
<html lang="pt-br" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard SENAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./css/dashboard.css">
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
                <!-- Welcome Section -->
                <section class="mb-6">
                    <h1 class="text-2xl sm:text-3xl font-bold text-senai-gray">Bem-vindo, <?php echo htmlspecialchars($usuario['nome']); ?>!</h1>
                    <p class="text-senai-gray mt-1 text-sm sm:text-base">Explore suas opções com facilidade:</p>
                    <div class="grid cards-grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mt-4">
                    <a href="./opcoes/cursos.php">  
                    <div class="bg-white dark:bg-gray-100 p-4 rounded-xl shadow-custom shadow-custom-hover transition-all cursor-pointer transform hover:-translate-y-1">
                        <h3 class="font-semibold text-senai-red">Cursos</h3>
                            <p class="text-sm text-senai-gray">Gerencie os cursos</p>
                        </div>
                        </a>  
                        <a href="./opcoes/empresas.php">
                        <div class="bg-white dark:bg-gray-100 p-4 rounded-xl shadow-custom shadow-custom-hover transition-all cursor-pointer transform hover:-translate-y-1">
                            <h3 class="font-semibold text-senai-red">Atendimento</h3>
                            <p class="text-sm text-senai-gray">Suporte a empresas</p>
                        </div>
                        </a>
                        <a href="./opcoes/boleto.php">
                        <div class="bg-white dark:bg-gray-100 p-4 rounded-xl shadow-custom shadow-custom-hover transition-all cursor-pointer transform hover:-translate-y-1">
                            <h3 class="font-semibold text-senai-red">Boletos</h3>
                            <p class="text-sm text-senai-gray">Emitir boletos</p>
                        </div>
                        </a>
                        <a href="./opcoes/documentacao.php">
                        <div class="bg-white dark:bg-gray-100 p-4 rounded-xl shadow-custom shadow-custom-hover transition-all cursor-pointer transform hover:-translate-y-1">
                            <h3 class="font-semibold text-senai-red">Documentação</h3>
                            <p class="text-sm text-senai-gray">Acesse documentos</p>
                        </div>
                        </a>
                        <a href="./opcoes/rh_outras.php">
                        <div class="bg-white dark:bg-gray-100 p-4 rounded-xl shadow-custom shadow-custom-hover transition-all cursor-pointer transform hover:-translate-y-1">
                            <h3 class="font-semibold text-senai-red">RH</h3>
                            <p class="text-sm text-senai-gray">Gestão de pessoas</p>
                        </div>
                        </a>
                    </div>
                </section>

                <!-- Search and Filters -->
                <section class="mb-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                    <form method="GET" action="dashboard.php" class="w-full sm:w-1/2">
                        <input type="text" name="search" placeholder="Pesquisar atendimentos..." value="<?php echo htmlspecialchars($search); ?>" class="w-full p-3 rounded-xl border border-senai-gray/30 dark:bg-gray-200 dark:text-gray-800 focus:outline-none focus:ring-2 focus:ring-senai-red transition-all shadow-custom">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                    </form>
                    <form method="GET" action="dashboard.php">
                        <select name="status" onchange="this.form.submit()" class="p-3 rounded-xl border border-senai-gray/30 dark:bg-gray-200 dark:text-gray-800 focus:outline-none focus:ring-2 focus:ring-senai-red transition-all shadow-custom">
                            <option value="todos" <?php echo $statusFilter === 'todos' ? 'selected' : ''; ?>>Todos os status</option>
                            <option value="Aberto" <?php echo $statusFilter === 'Aberto' ? 'selected' : ''; ?>>Aberto</option>
                            <option value="Finalizado" <?php echo $statusFilter === 'Finalizado' ? 'selected' : ''; ?>>Finalizado</option>
                        </select>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    </form>
                </section>

                <!-- Table -->
                <section class="bg-white dark:bg-gray-100 p-4 sm:p-6 rounded-xl shadow-custom table-container">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-senai-gray">
                                <th class="p-3 font-semibold">ID</th>
                                <th class="p-3 font-semibold">Número</th>
                                <th class="p-3 font-semibold">Email</th>
                                <th class="p-3 font-semibold">Opção</th>
                                <th class="p-3 font-semibold">Data</th>
                                <th class="p-3 font-semibold">Status</th>
                                <th class="p-3 font-semibold">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($atendimentos) > 0): ?>
                                <?php foreach ($atendimentos as $atendimento): ?>
                                    <tr class="border-t border-senai-gray/20 hover:bg-senai-light/50 transition-colors">
                                        <td class="p-3"><?php echo htmlspecialchars($atendimento['id']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars(str_replace(['@s.whatsapp.net', '@s.what'], '', $atendimento['numero'])); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($atendimento['email']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($opcaoAtendimentoMap[$atendimento['opcao_atendimento']] ?? $atendimento['opcao_atendimento']); ?></td>
                                        <td class="p-3"><?php echo htmlspecialchars($atendimento['data_registro']); ?></td>
                                        <td class="p-3">
                                            <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $atendimento['status_atendimento'] === 'Finalizado' ? 'bg-senai-green/20 text-senai-green' : 'bg-senai-yellow/20 text-senai-yellow'; ?>">
                                                <?php echo htmlspecialchars($atendimento['status_atendimento'] ?? 'Aberto'); ?>
                                            </span>
                                        </td>
                                        <td class="p-3 flex space-x-2">
                                            <button onclick="editAtendimento(<?php echo $atendimento['id']; ?>)" class="p-2 text-senai-gray hover:text-senai-red transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button onclick="deleteAtendimento(<?php echo $atendimento['id']; ?>)" class="p-2 text-senai-gray hover:text-senai-red transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4M9 7h6m-6 0V5h6v2"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="p-3 text-center text-senai-gray">Nenhum atendimento registrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <!-- Pagination and Actions -->
                    <div class="mt-6 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 pagination-buttons">
                        <div class="flex space-x-3">
                            <button onclick="saveQuery()" class="px-4 py-2 bg-senai-red text-white rounded-lg hover:bg-senai-red/80 transition-all shadow-custom">Salvar Consulta</button>
                            <button onclick="exportData()" class="px-4 py-2 bg-senai-gray text-white rounded-lg hover:bg-senai-gray/80 transition-all shadow-custom">Exportar</button>
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($paginaAtual > 1): ?>
                                <a href="?pagina=<?php echo $paginaAtual - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>" class="px-4 py-2 bg-senai-gray/20 rounded-lg hover:bg-senai-gray/40 transition-colors">Anterior</a>
                            <?php endif; ?>
                            <span class="px-4 py-2 bg-senai-gray/20 rounded-lg">Página <?php echo $paginaAtual; ?> de <?php echo $totalPaginas; ?></span>
                            <?php if ($paginaAtual < $totalPaginas): ?>
                                <a href="?pagina=<?php echo $paginaAtual + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>" class="px-4 py-2 bg-senai-gray/20 rounded-lg hover:bg-senai-gray/40 transition-colors">Próximo</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
    <script src="./script/dashboard.js"></script>
</body>
</html>