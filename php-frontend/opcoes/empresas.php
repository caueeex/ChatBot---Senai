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

// Configuração da paginação
$itensPorPagina = 10;
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// Parâmetros de filtro e pesquisa
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Monta a query para contar o total de atendimentos com filtros
$totalQuery = 'SELECT COUNT(*) AS total FROM atendimentos WHERE opcao_atendimento = :opcao';
$params = [':opcao' => '2'];

if ($search) {
    $totalQuery .= ' AND (numero LIKE :search OR email LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($statusFilter && $statusFilter !== 'todos') {
    $totalQuery .= ' AND status_atendimento = :status';
    $params[':status'] = $statusFilter;
}

$stmtTotal = $pdo->prepare($totalQuery);
$stmtTotal->execute($params);
$totalAtendimentos = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

// Calcula o total de páginas
$totalPaginas = ceil($totalAtendimentos / $itensPorPagina);

// Monta a query para buscar os atendimentos com filtros
$query = 'SELECT * FROM atendimentos WHERE opcao_atendimento = :opcao';
$params = [':opcao' => '2'];

if ($search) {
    $query .= ' AND (numero LIKE :search OR email LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($statusFilter && $statusFilter !== 'todos') {
    $query .= ' AND status_atendimento = :status';
    $params[':status'] = $statusFilter;
}

$query .= ' ORDER BY data_registro DESC LIMIT :limit OFFSET :offset';
$params[':limit'] = $itensPorPagina;
$params[':offset'] = $offset;

$stmtAtendimentos = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $paramType = ($key === ':limit' || $key === ':offset') ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmtAtendimentos->bindValue($key, $value, $paramType);
}
$stmtAtendimentos->execute();
$atendimentos = $stmtAtendimentos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atendimento a Empresas - SENAI</title>
    <link rel="stylesheet" href="../css/opcoes/empresas.css">
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
                        'senai-purple': '#8B5CF6'
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
                <h1 class="text-2xl sm:text-3xl font-bold text-senai-gray mb-6">Atendimento a Empresas</h1>

                <!-- Seção de Pesquisa -->
                <div class="search-section">
                    <form id="searchForm" method="GET" action="empresas.php">
                        <input type="text" id="searchInput" name="search" placeholder="Pesquisa rápida (número ou e-mail)" value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fas fa-search"></i> Pesquisar</button>
                        <button type="button" onclick="clearFilters()"><i class="fas fa-times"></i> Limpar</button>
                    </form>
                </div>

                <!-- Filtros -->
                <div class="filters mb-4">
                    <form id="filterForm" method="GET" action="empresas.php">
                        <select id="processFilter" name="status" onchange="this.form.submit()">
                            <option value="">Filtrar por status</option>
                            <option value="todos" <?php echo $statusFilter === 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="Aberto" <?php echo $statusFilter === 'Aberto' ? 'selected' : ''; ?>>Abertos</option>
                            <option value="Finalizado" <?php echo $statusFilter === 'Finalizado' ? 'selected' : ''; ?>>Finalizados</option>
                        </select>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    </form>
                </div>

                <!-- Botões de Ação -->
                <div class="action-buttons">
                    <button onclick="saveQuery()"><i class="fas fa-save"></i> Salvar Consulta</button>
                    <button onclick="exportData()"><i class="fas fa-download"></i> Exportar Dados</button>
                    <h2>Atendimentos Realizados</h2>
                </div>

                <!-- Lista de Atendimentos -->
                <div class="atendimentos-list" id="atendimentosList">
                    <?php if (count($atendimentos) > 0): ?>
                        <?php foreach ($atendimentos as $atendimento): ?>
                            <div class="card atendimento-card shadow-custom shadow-custom-hover" data-status="<?php echo htmlspecialchars($atendimento['status_atendimento'] ?? 'Aberto'); ?>" data-search="<?php echo strtolower(htmlspecialchars(str_replace(['@s.whatsapp.net', '@s.what'], '', $atendimento['numero']) . ' ' . $atendimento['email'])); ?>">
                                <h3>Número: <?php echo htmlspecialchars(str_replace(['@s.whatsapp.net', '@s.what'], '', $atendimento['numero'])); ?></h3>
                                <p><strong>Escolha:</strong> <?php echo htmlspecialchars($atendimento['escolha']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($atendimento['email']); ?></p>
                                <p><strong>Data:</strong> <?php echo htmlspecialchars($atendimento['data_registro']); ?></p>
                                <p><strong>Status:</strong> <span class="status-badge" status="<?php echo htmlspecialchars($atendimento['status_atendimento'] ?? 'Aberto'); ?>"><?php echo htmlspecialchars($atendimento['status_atendimento'] ?? 'Aberto'); ?></span></p>
                                <div class="card-actions">
                                    <button onclick="editAtendimento(<?php echo $atendimento['id']; ?>)"><i class="fas fa-edit"></i> Editar</button>
                                    <button onclick="responderAtendimento(<?php echo $atendimento['id']; ?>, '<?php echo htmlspecialchars(str_replace(['@s.whatsapp.net', '@s.what'], '', $atendimento['numero'])); ?>')"><i class="fas fa-reply"></i> Responder</button>
                                    <button onclick="deleteAtendimento(<?php echo $atendimento['id']; ?>)"><i class="fas fa-trash"></i> Excluir</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-data">Nenhum atendimento registrado para Atendimento a Empresas.</p>
                    <?php endif; ?>
                </div>

                <!-- Controles de Paginação -->
                <div class="paginacao">
                    <?php if ($paginaAtual > 1): ?>
                        <a href="?pagina=<?php echo $paginaAtual - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>" class="pagina-link"><i class="fas fa-chevron-left"></i> Anterior</a>
                    <?php endif; ?>
                    <span class="pagina-atual">Página <?php echo $paginaAtual; ?> de <?php echo $totalPaginas; ?></span>
                    <?php if ($paginaAtual < $totalPaginas): ?>
                        <a href="?pagina=<?php echo $paginaAtual + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>" class="pagina-link">Próximo <i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
    <script src="../script/opcoes/empresas.js"></script>
</body>
</html>