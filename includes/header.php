<?php
require_once 'config.php';
verificarLogin();

// Obter informações do usuário
$stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="main-layout">
        <header class="header">
            <div class="header-left">
                <img src="images/logo_netmai.png" alt="Netmai" class="header-logo">
                <h1 class="header-title">Sistema Financeiro</h1>
            </div>
            <div class="header-right">
                <span class="user-info">Olá, <?php echo htmlspecialchars($usuario['nome']); ?>!</span>
                <a href="logout.php" class="logout-btn">Sair</a>
            </div>
        </header>
        
        <nav class="nav-menu">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <img src="images/icon_dashboard.png" alt="Dashboard" class="nav-icon">
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="lancamentos.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'lancamentos.php' ? 'active' : ''; ?>">
                        <img src="images/icon_lancamentos.png" alt="Lançamentos" class="nav-icon">
                        Lançamentos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="contas_pagar_receber.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contas_pagar_receber.php' ? 'active' : ''; ?>">
                        <img src="images/icon_fluxo_caixa.png" alt="Contas" class="nav-icon">
                        Contas a Pagar/Receber
                    </a>
                </li>
                <li class="nav-item">
                    <a href="relatorios.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'relatorios.php' ? 'active' : ''; ?>">
                        <img src="images/icon_relatorios.png" alt="Relatórios" class="nav-icon">
                        Relatórios
                    </a>
                </li>
                <li class="nav-item">
                    <a href="centro_custos.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'centro_custos.php' ? 'active' : ''; ?>">
                        <img src="images/icon_configuracoes.png" alt="Centro de Custos" class="nav-icon">
                        Centro de Custos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="categorias.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categorias.php' ? 'active' : ''; ?>">
                        <img src="images/icon_configuracoes.png" alt="Categorias" class="nav-icon">
                        Categorias
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="content-wrapper">
            <main class="main-content">
