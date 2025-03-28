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

// Busca dados para os relatórios
$stmtAtendimentosStatus = $pdo->query('SELECT opcao_atendimento AS status, COUNT(*) AS total FROM atendimentos GROUP BY opcao_atendimento');
$atendimentosPorStatus = $stmtAtendimentosStatus->fetchAll(PDO::FETCH_ASSOC);

$stmtAtendimentosDia = $pdo->query('SELECT DATE(data_registro) AS dia, COUNT(*) AS total FROM atendimentos GROUP BY DATE(data_registro) ORDER BY dia DESC');
$atendimentosPorDia = $stmtAtendimentosDia->fetchAll(PDO::FETCH_ASSOC);

// Mapeamento de opcao_atendimento para nomes mais legíveis
$opcaoAtendimentoMap = [
    '1' => 'Curso Presencial e EAD',
    '2' => 'Atendimento a Empresas',
    '3' => 'Emissão de Boleto - SENAI',
    '4' => 'Documentação/Certificado',
    '5' => 'RH / Licitações / Outras áreas',
];
?>

<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - SENAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1.5rem;
        }
        .action-buttons button {
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            background-color: #E30613;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .action-buttons button:hover {
            background-color: #c20411;
        }
        .action-buttons button i {
            margin-right: 0.5rem;
        }
        .report-section {
            margin-bottom: 2rem;
        }
        .report-section h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 1rem;
        }
        .status-cards, .daily-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        .status-card, .daily-card {
            background-color: #ffffff;
            border-radius: 0.75rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .dark .status-card, .dark .daily-card {
            background-color: #f3f4f6;
        }
        .status-card:hover, .daily-card:hover {
            transform: translateY(-4px);
        }
        .status-card::before, .daily-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background-color: #E30613;
            transition: all 0.3s ease;
        }
        .status-card:hover::before, .daily-card:hover::before {
            width: 10px;
        }
        .status-card h3, .daily-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #E30613;
            margin-bottom: 0.5rem;
        }
        .status-card p, .daily-card p {
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        .status-card .total, .daily-card .total {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
        }
        .dark .status-card .total, .dark .daily-card .total {
            color: #111827;
        }
        .status-card .icon, .daily-card .icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 2rem;
            opacity: 0.1;
            color: #E30613;
        }
        .no-data {
            text-align: center;
            color: #64748b;
            padding: 2rem;
            background-color: #f8fafc;
            border-radius: 0.75rem;
        }
        .dark .no-data {
            background-color: #e5e7eb;
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
            .status-cards, .daily-cards {
                grid-template-columns: 1fr;
            }
            .action-buttons {
                justify-content: center;
            }
            .action-buttons button {
                width: 100%;
            }
        }
    </style>
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
                <h1 class="text-2xl sm:text-3xl font-bold text-senai-gray mb-6">Relatórios</h1>

                <!-- Botão para exportar PDF -->
                <div class="action-buttons">
                    <button onclick="exportarPDF()"><i class="fas fa-file-pdf"></i> Exportar PDF</button>
                </div>

                <!-- Atendimentos por Tipo -->
                <div class="report-section">
                    <h2>Atendimentos por Tipo</h2>
                    <?php if (count($atendimentosPorStatus) > 0): ?>
                        <div class="status-cards">
                            <?php foreach ($atendimentosPorStatus as $status): ?>
                                <div class="status-card shadow-custom shadow-custom-hover">
                                    <i class="fas fa-chart-pie icon"></i>
                                    <h3><?php echo htmlspecialchars($opcaoAtendimentoMap[$status['status']] ?? $status['status']); ?></h3>
                                    <p>Total de Atendimentos</p>
                                    <p class="total"><?php echo htmlspecialchars($status['total']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-data">Nenhum atendimento registrado para exibir.</p>
                    <?php endif; ?>
                </div>

                <!-- Atendimentos por Dia -->
                <div class="report-section">
                    <h2>Atendimentos por Dia</h2>
                    <?php if (count($atendimentosPorDia) > 0): ?>
                        <div class="daily-cards">
                            <?php foreach ($atendimentosPorDia as $dia): ?>
                                <div class="daily-card shadow-custom shadow-custom-hover">
                                    <i class="fas fa-calendar-alt icon"></i>
                                    <h3><?php echo htmlspecialchars(date('d/m/Y', strtotime($dia['dia']))); ?></h3>
                                    <p>Total de Atendimentos</p>
                                    <p class="total"><?php echo htmlspecialchars($dia['total']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-data">Nenhum atendimento registrado para exibir.</p>
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

        function exportarPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');

            // Cabeçalho com espaço para o logotipo
            doc.setFillColor(227, 6, 19); // senai-red
            doc.rect(0, 0, 210, 20, 'F'); // Barra vermelha no topo
            doc.setFontSize(18);
            doc.setTextColor(255, 255, 255); // Texto branco
            doc.text('Relatórios de Atendimentos - SENAI', 10, 15);
            // Espaço para o logotipo (você precisará adicionar a imagem do logotipo)
            doc.setFontSize(10);
            doc.setTextColor(255, 255, 255);
            doc.text('../img/logo-senai.png', 180, 15, { align: 'right' });

            // Informações do usuário e data
            let yPos = 30;
            doc.setFontSize(12);
            doc.setTextColor(100); // senai-gray
            doc.text('Gerado por: <?php echo htmlspecialchars($usuario['nome']); ?> (<?php echo htmlspecialchars($usuario['email']); ?>)', 10, yPos);
            doc.text('Data: <?php echo date('d/m/Y H:i'); ?>', 200, yPos, { align: 'right' });

            // Linha divisória
            yPos += 5;
            doc.setDrawColor(227, 6, 19); // senai-red
            doc.line(10, yPos, 200, yPos);

            // Atendimentos por Tipo
            yPos += 10;
            doc.setFontSize(16);
            doc.setTextColor(227, 6, 19); // senai-red
            doc.setFillColor(248, 250, 252); // senai-light
            doc.rect(10, yPos - 5, 190, 10, 'F'); // Fundo claro
            doc.setDrawColor(227, 6, 19);
            doc.rect(10, yPos - 5, 190, 10); // Borda vermelha
            doc.text('Atendimentos por Tipo', 15, yPos);
            yPos += 10;
            doc.setFontSize(12);
            doc.setTextColor(100); // senai-gray
            <?php if (count($atendimentosPorStatus) > 0): ?>
                <?php foreach ($atendimentosPorStatus as $status): ?>
                    doc.setFillColor(255, 255, 255);
                    doc.rect(10, yPos - 5, 190, 10, 'F'); // Fundo branco para cada item
                    doc.setDrawColor(227, 6, 19);
                    doc.rect(10, yPos - 5, 190, 10); // Borda vermelha
                    doc.text('<?php echo htmlspecialchars($opcaoAtendimentoMap[$status['status']] ?? $status['status']); ?>: <?php echo htmlspecialchars($status['total']); ?> atendimentos', 15, yPos);
                    yPos += 10;
                <?php endforeach; ?>
            <?php else: ?>
                doc.setFillColor(255, 255, 255);
                doc.rect(10, yPos - 5, 190, 10, 'F');
                doc.setDrawColor(227, 6, 19);
                doc.rect(10, yPos - 5, 190, 10);
                doc.text('Nenhum atendimento registrado para exibir.', 15, yPos);
                yPos += 10;
            <?php endif; ?>

            // Atendimentos por Dia
            yPos += 10;
            doc.setFontSize(16);
            doc.setTextColor(227, 6, 19); // senai-red
            doc.setFillColor(248, 250, 252); // senai-light
            doc.rect(10, yPos - 5, 190, 10, 'F'); // Fundo claro
            doc.setDrawColor(227, 6, 19);
            doc.rect(10, yPos - 5, 190, 10); // Borda vermelha
            doc.text('Atendimentos por Dia', 15, yPos);
            yPos += 10;
            doc.setFontSize(12);
            doc.setTextColor(100); // senai-gray
            <?php if (count($atendimentosPorDia) > 0): ?>
                <?php foreach ($atendimentosPorDia as $dia): ?>
                    doc.setFillColor(255, 255, 255);
                    doc.rect(10, yPos - 5, 190, 10, 'F'); // Fundo branco para cada item
                    doc.setDrawColor(227, 6, 19);
                    doc.rect(10, yPos - 5, 190, 10); // Borda vermelha
                    doc.text('<?php echo htmlspecialchars(date('d/m/Y', strtotime($dia['dia']))); ?>: <?php echo htmlspecialchars($dia['total']); ?> atendimentos', 15, yPos);
                    yPos += 10;
                <?php endforeach; ?>
            <?php else: ?>
                doc.setFillColor(255, 255, 255);
                doc.rect(10, yPos - 5, 190, 10, 'F');
                doc.setDrawColor(227, 6, 19);
                doc.rect(10, yPos - 5, 190, 10);
                doc.text('Nenhum atendimento registrado para exibir.', 15, yPos);
                yPos += 10;
            <?php endif; ?>

            // Rodapé
            doc.setFontSize(10);
            doc.setTextColor(100); // senai-gray
            doc.setFillColor(227, 6, 19); // senai-red
            doc.rect(0, 287, 210, 10, 'F'); // Barra vermelha no rodapé
            doc.setTextColor(255, 255, 255);
            doc.text('Relatório gerado pelo SENAI', 10, 292);
            doc.text('Página 1', 200, 292, { align: 'right' });

            // Salva o PDF
            doc.save('relatorios_atendimentos.pdf');
        }

        const themeToggle = document.getElementById('theme-toggle');
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark');
            localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
        });

        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
        }
    </script>
</body>
</html>