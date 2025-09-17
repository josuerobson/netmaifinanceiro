<?php
// Arquivo de instalação do Sistema Financeiro Netmai
// Execute este arquivo apenas uma vez para configurar o banco de dados

$erro = '';
$sucesso = '';
$instalado = false;

// Verificar se já foi instalado
if (file_exists('includes/config.php')) {
    try {
        require_once 'includes/config.php';
        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
        $instalado = true;
    } catch (Exception $e) {
        // Banco não existe ainda
    }
}

if ($_POST && !$instalado) {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    $admin_nome = $_POST['admin_nome'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';
    $admin_senha = $_POST['admin_senha'] ?? '';
    
    if (empty($admin_nome) || empty($admin_email) || empty($admin_senha)) {
        $erro = 'Por favor, preencha todos os campos do administrador.';
    } else {
        try {
            // Conectar ao MySQL
            $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Criar banco de dados
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `financeiro_ademar` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $pdo->exec("USE `financeiro_ademar`");
            
            // Criar tabelas
            $sql = "
            CREATE TABLE IF NOT EXISTS `usuarios` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `nome` varchar(255) NOT NULL,
              `email` varchar(255) NOT NULL,
              `senha` varchar(255) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `centro_custos` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `nome` varchar(255) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `categorias` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `nome` varchar(255) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `subcategorias` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `nome` varchar(255) NOT NULL,
              `categoria_id` int(11) NOT NULL,
              PRIMARY KEY (`id`),
              KEY `categoria_id` (`categoria_id`),
              CONSTRAINT `subcategorias_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `lancamentos` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `tipo` enum('entrada','saida') NOT NULL,
              `centro_custo_id` int(11) NOT NULL,
              `subcategoria_id` int(11) NOT NULL,
              `valor` decimal(10,2) NOT NULL,
              `data` date NOT NULL,
              `forma_pagamento` enum('a_vista','parcelado') NOT NULL,
              `parcelas` int(11) DEFAULT NULL,
              `usuario_id` int(11) NOT NULL,
              `pago` tinyint(1) NOT NULL DEFAULT 1,
              `data_pagamento` date NULL,
              PRIMARY KEY (`id`),
              KEY `centro_custo_id` (`centro_custo_id`),
              KEY `subcategoria_id` (`subcategoria_id`),
              KEY `usuario_id` (`usuario_id`),
              CONSTRAINT `lancamentos_ibfk_1` FOREIGN KEY (`centro_custo_id`) REFERENCES `centro_custos` (`id`),
              CONSTRAINT `lancamentos_ibfk_2` FOREIGN KEY (`subcategoria_id`) REFERENCES `subcategorias` (`id`),
              CONSTRAINT `lancamentos_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `parcelas` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `lancamento_id` int(11) NOT NULL,
              `numero_parcela` int(11) NOT NULL,
              `valor` decimal(10,2) NOT NULL,
              `data_vencimento` date NOT NULL,
              `pago` tinyint(1) NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`),
              KEY `lancamento_id` (`lancamento_id`),
              CONSTRAINT `parcelas_ibfk_1` FOREIGN KEY (`lancamento_id`) REFERENCES `lancamentos` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            
            $pdo->exec($sql);
            
            // Criar usuário administrador
            $senha_hash = password_hash($admin_senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
            $stmt->execute([$admin_nome, $admin_email, $senha_hash]);
            
            // Inserir dados iniciais
            $pdo->exec("
                INSERT INTO centro_custos (nome) VALUES 
                ('Administrativo'),
                ('Vendas'),
                ('Marketing'),
                ('Operacional');
                
                INSERT INTO categorias (nome) VALUES 
                ('Receitas'),
                ('Despesas Operacionais'),
                ('Despesas Administrativas'),
                ('Investimentos');
                
                INSERT INTO subcategorias (nome, categoria_id) VALUES 
                ('Vendas de Produtos', 1),
                ('Prestação de Serviços', 1),
                ('Receitas Financeiras', 1),
                ('Salários e Encargos', 2),
                ('Aluguel', 2),
                ('Energia Elétrica', 2),
                ('Telefone e Internet', 2),
                ('Material de Escritório', 3),
                ('Viagens e Hospedagem', 3),
                ('Treinamentos', 3),
                ('Equipamentos', 4),
                ('Software', 4);
            ");
            
            $sucesso = 'Sistema instalado com sucesso! Você pode fazer login agora.';
            $instalado = true;
            
        } catch (PDOException $e) {
            $erro = 'Erro na instalação: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Sistema Financeiro Netmai</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .install-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .logo p {
            color: #666;
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .success-message {
            text-align: center;
        }
        
        .success-message h2 {
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .features {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .features h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .features ul {
            list-style: none;
            padding: 0;
        }
        
        .features li {
            padding: 5px 0;
            color: #666;
        }
        
        .features li:before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="logo">
            <h1>Netmai</h1>
            <p>Sistema Financeiro</p>
        </div>
        
        <?php if ($instalado): ?>
            <div class="success-message">
                <h2>✓ Instalação Concluída!</h2>
                
                <?php if ($sucesso): ?>
                    <div class="alert alert-success"><?php echo $sucesso; ?></div>
                <?php endif; ?>
                
                <p style="margin-bottom: 20px;">O sistema foi instalado com sucesso e está pronto para uso.</p>
                
                <a href="login.php" class="btn">Acessar o Sistema</a>
                
                <div class="features">
                    <h3>Recursos Disponíveis:</h3>
                    <ul>
                        <li>Sistema de Login e Cadastro</li>
                        <li>Gestão de Centro de Custos</li>
                        <li>Categorias e Subcategorias</li>
                        <li>Lançamentos com Parcelamento</li>
                        <li>Fluxo de Caixa em Tempo Real</li>
                        <li>Relatórios Gráficos Avançados</li>
                        <li>Interface Totalmente Responsiva</li>
                    </ul>
                </div>
                
                <p style="margin-top: 20px; font-size: 14px; color: #666; text-align: center;">
                    <strong>Importante:</strong> Remova ou renomeie este arquivo (install.php) por segurança.
                </p>
            </div>
        <?php else: ?>
            <h2 style="text-align: center; margin-bottom: 30px; color: #333;">Instalação do Sistema</h2>
            
            <?php if ($erro): ?>
                <div class="alert alert-error"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <h3 style="margin-bottom: 20px; color: #333;">Configurações do Banco de Dados</h3>
                
                <div class="form-group">
                    <label for="db_host">Host do Banco:</label>
                    <input type="text" id="db_host" name="db_host" class="form-control" 
                           value="localhost" required>
                </div>
                
                <div class="form-group">
                    <label for="db_user">Usuário do Banco:</label>
                    <input type="text" id="db_user" name="db_user" class="form-control" 
                           value="root" required>
                </div>
                
                <div class="form-group">
                    <label for="db_pass">Senha do Banco:</label>
                    <input type="password" id="db_pass" name="db_pass" class="form-control" 
                           placeholder="Deixe em branco se não houver senha">
                </div>
                
                <h3 style="margin: 30px 0 20px 0; color: #333;">Dados do Administrador</h3>
                
                <div class="form-group">
                    <label for="admin_nome">Nome Completo:</label>
                    <input type="text" id="admin_nome" name="admin_nome" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_email">Email:</label>
                    <input type="email" id="admin_email" name="admin_email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_senha">Senha:</label>
                    <input type="password" id="admin_senha" name="admin_senha" class="form-control" 
                           required minlength="6">
                </div>
                
                <button type="submit" class="btn">Instalar Sistema</button>
            </form>
            
            <div class="features">
                <h3>O que será instalado:</h3>
                <ul>
                    <li>Banco de dados "financeiro_ademar"</li>
                    <li>Tabelas do sistema</li>
                    <li>Dados iniciais (categorias e centros de custo)</li>
                    <li>Usuário administrador</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
