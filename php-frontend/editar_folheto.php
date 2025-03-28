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

// Mapeamento de opcao_atendimento para nomes legíveis (subopções de cursos)
$opcaoAtendimentoMap = [
    '1' => 'Cursos de Curta Duração (Presencial)',
    '2' => 'Cursos de Curta Duração a Distância (EAD)',
    '3' => 'Cursos de Curta Duração (Bolsa de Estudos)',
    '4' => 'Curso Regular (Aprendizagem Industrial)',
    '5' => 'Curso Regular (Técnico)',
    '6' => 'Curso Regular (Faculdade)',
    '7' => 'Curso Regular (Pós Graduação)',
];

// Busca todos os folhetos existentes
$stmtFolhetos = $pdo->query('SELECT * FROM folhetos');
$folhetos = $stmtFolhetos->fetchAll(PDO::FETCH_ASSOC);

// Função para gerar o HTML do folheto com base nos campos
function gerarHtmlFolheto($titulo, $descricao, $data_inicio, $data_fim, $contato) {
    $html = "<h1>" . htmlspecialchars($titulo) . "</h1>";
    $html .= "<p>" . htmlspecialchars($descricao) . "</p>";
    if ($data_inicio) {
        $html .= "<p><strong>Data de Início:</strong> " . htmlspecialchars($data_inicio) . "</p>";
    }
    if ($data_fim) {
        $html .= "<p><strong>Data de Fim:</strong> " . htmlspecialchars($data_fim) . "</p>";
    }
    if ($contato) {
        $html .= "<p><em>Contato:</em> " . htmlspecialchars($contato) . "</p>";
    }
    return $html;
}

// CSS fixo para o folheto
$cssFolheto = "
    h1 { color: #E30613; font-size: 20px; }
    p { color: #64748B; font-size: 14px; }
    strong { color: #E30613; }
    em { color: #64748B; }
";

// Processa o formulário (Create ou Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $opcaoAtendimento = $_POST['opcao_atendimento'];
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $data_inicio = !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null;
    $data_fim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;
    $contato = !empty($_POST['contato']) ? $_POST['contato'] : null;

    // Verifica se já existe um folheto para essa opcao_atendimento
    $stmtCheck = $pdo->prepare('SELECT * FROM folhetos WHERE opcao_atendimento = ?');
    $stmtCheck->execute([$opcaoAtendimento]);
    $folhetoExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($folhetoExistente) {
        // Atualiza o folheto existente (Update)
        $stmtUpdate = $pdo->prepare('UPDATE folhetos SET titulo = ?, descricao = ?, data_inicio = ?, data_fim = ?, contato = ? WHERE opcao_atendimento = ?');
        $stmtUpdate->execute([$titulo, $descricao, $data_inicio, $data_fim, $contato, $opcaoAtendimento]);
    } else {
        // Insere um novo folheto (Create)
        $stmtInsert = $pdo->prepare('INSERT INTO folhetos (opcao_atendimento, titulo, descricao, data_inicio, data_fim, contato) VALUES (?, ?, ?, ?, ?, ?)');
        $stmtInsert->execute([$opcaoAtendimento, $titulo, $descricao, $data_inicio, $data_fim, $contato]);
    }

    // Redireciona para evitar reenvio do formulário
    header('Location: editar_folheto.php');
    exit;
}

// Processa a exclusão (Delete)
if (isset($_GET['delete'])) {
    $opcaoAtendimento = $_GET['delete'];
    $stmtDelete = $pdo->prepare('DELETE FROM folhetos WHERE opcao_atendimento = ?');
    $stmtDelete->execute([$opcaoAtendimento]);
    header('Location: editar_folheto.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Folheto - SENAI</title>
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
    <style>
        body {
            background-image: url('https://www.transparenttextures.com/patterns/aquarelle.png');
            background-size: cover;
            background-attachment: fixed;
            background-color: #F8FAFC;
        }
        .dark body {
            background-image: url('https://www.transparenttextures.com/patterns/aquarelle.png');
            background-color: #E5E7EB;
        }
        .shadow-custom {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        .shadow-custom-hover:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }
        .form-section {
            background-color: #ffffff;
            border-radius: 0.75rem;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .dark .form-section {
            background-color: #f3f4f6;
        }
        .folheto-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #ffffff;
            border-radius: 0.75rem;
            overflow: hidden;
        }
        .dark .folheto-table {
            background-color: #f3f4f6;
        }
        .folheto-table th, .folheto-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }
        .folheto-table th {
            background-color: #E30613;
            color: white;
        }
        .folheto-table td {
            color: #64748B;
        }
        .folheto-table .actions a {
            margin-right: 0.5rem;
        }
        .preview-section {
            margin-top: 1rem;
            border: 1px solid #E5E7EB;
            padding: 1rem;
            border-radius: 0.5rem;
            background-color: #F8FAFC;
        }
        @media (max-width: 1024px) {
            #sidebar {
                transform: translateX(-100%);
            }
            #sidebar.open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0 !important;
            }
            .header {
                left: 0 !important;
            }
        }
        @media (max-width: 640px) {
            .folheto-table th, .folheto-table td {
                font-size: 0.8rem;
                padding: 0.5rem;
            }
        }
    </style>
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
                <h1 class="text-2xl sm:text-3xl font-bold text-senai-gray mb-6">Editar Folheto</h1>

                <!-- Formulário para Criar/Editar Folheto -->
                <div class="form-section shadow-custom">
                    <h2 class="text-xl font-semibold text-senai-gray mb-4">Criar/Editar Folheto</h2>
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="opcao_atendimento" class="block text-senai-gray font-medium mb-2">Tipo de Curso</label>
                            <select name="opcao_atendimento" id="opcao_atendimento" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-senai-red" required>
                                <?php foreach ($opcaoAtendimentoMap as $key => $value): ?>
                                    <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($value); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="titulo" class="block text-senai-gray font-medium mb-2">Título do Folheto</label>
                            <input type="text" name="titulo" id="titulo" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-senai-red" required placeholder="Ex.: Folheto de Cursos Presenciais">
                        </div>
                        <div class="mb-4">
                            <label for="descricao" class="block text-senai-gray font-medium mb-2">Descrição</label>
                            <textarea name="descricao" id="descricao" rows="5" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-senai-red" required placeholder="Ex.: Conheça nossos cursos presenciais!"></textarea>
                        </div>
                        <div class="mb-4">
                            <label for="data_inicio" class="block text-senai-gray font-medium mb-2">Data de Início (Opcional)</label>
                            <input type="text" name="data_inicio" id="data_inicio" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-senai-red" placeholder="Ex.: 01/04/2025">
                        </div>
                        <div class="mb-4">
                            <label for="data_fim" class="block text-senai-gray font-medium mb-2">Data de Fim (Opcional)</label>
                            <input type="text" name="data_fim" id="data_fim" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-senai-red" placeholder="Ex.: 30/04/2025">
                        </div>
                        <div class="mb-4">
                            <label for="contato" class="block text-senai-gray font-medium mb-2">Contato (Opcional)</label>
                            <input type="text" name="contato" id="contato" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-senai-red" placeholder="Ex.: (11) 1234-5678 ou contato@senai.com">
                        </div>
                        <button type="submit" class="bg-senai-red text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">Salvar Folheto</button>
                    </form>
                </div>

                <!-- Lista de Folhetos Existentes -->
                <div class="form-section shadow-custom">
                    <h2 class="text-xl font-semibold text-senai-gray mb-4">Folhetos Existentes</h2>
                    <?php if (count($folhetos) > 0): ?>
                        <table class="folheto-table">
                            <thead>
                                <tr>
                                    <th>Tipo de Curso</th>
                                    <th>Pré-visualização</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($folhetos as $folheto): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($opcaoAtendimentoMap[$folheto['opcao_atendimento']] ?? $folheto['opcao_atendimento']); ?></td>
                                        <td>
                                            <div class="preview-section">
                                                <style><?php echo $cssFolheto; ?></style>
                                                <?php echo gerarHtmlFolheto($folheto['titulo'], $folheto['descricao'], $folheto['data_inicio'], $folheto['data_fim'], $folheto['contato']); ?>
                                            </div>
                                        </td>
                                        <td class="actions">
                                            <a href="editar_folheto.php?edit=<?php echo htmlspecialchars($folheto['opcao_atendimento']); ?>" class="text-senai-blue hover:underline">Editar</a>
                                            <a href="editar_folheto.php?delete=<?php echo htmlspecialchars($folheto['opcao_atendimento']); ?>" class="text-senai-red hover:underline" onclick="return confirm('Tem certeza que deseja excluir este folheto?')">Excluir</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-senai-gray">Nenhum folheto registrado.</p>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        const themeToggle = document.getElementById('theme-toggle');
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark');
            localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
        });

        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
        }

        // Preenche o formulário ao clicar em "Editar"
        <?php if (isset($_GET['edit'])): ?>
            <?php
            $opcaoAtendimentoEdit = $_GET['edit'];
            $stmtEdit = $pdo->prepare('SELECT * FROM folhetos WHERE opcao_atendimento = ?');
            $stmtEdit->execute([$opcaoAtendimentoEdit]);
            $folhetoEdit = $stmtEdit->fetch(PDO::FETCH_ASSOC);
            ?>
            document.getElementById('opcao_atendimento').value = '<?php echo htmlspecialchars($folhetoEdit['opcao_atendimento']); ?>';
            document.getElementById('titulo').value = '<?php echo htmlspecialchars($folhetoEdit['titulo']); ?>';
            document.getElementById('descricao').value = '<?php echo htmlspecialchars($folhetoEdit['descricao']); ?>';
            document.getElementById('data_inicio').value = '<?php echo htmlspecialchars($folhetoEdit['data_inicio']); ?>';
            document.getElementById('data_fim').value = '<?php echo htmlspecialchars($folhetoEdit['data_fim']); ?>';
            document.getElementById('contato').value = '<?php echo htmlspecialchars($folhetoEdit['contato']); ?>';
        <?php endif; ?>
    </script>
</body>
</html>