<?php
require_once 'includes/config.php';

$erro = '';
$sucesso = '';

// Processar login
if ($_POST) {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, nome, senha FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                header('Location: index.php');
                exit();
            } else {
                $erro = 'Email ou senha incorretos.';
            }
        } catch (PDOException $e) {
            $erro = 'Erro no sistema. Tente novamente.';
        }
    }
}

// Processar cadastro
if (isset($_POST['acao']) && $_POST['acao'] === 'cadastrar') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } elseif ($senha !== $confirmar_senha) {
        $erro = 'As senhas não coincidem.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } else {
        try {
            // Verificar se o email já existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $erro = 'Este email já está cadastrado.';
            } else {
                // Cadastrar novo usuário
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
                $stmt->execute([$nome, $email, $senha_hash]);
                
                $sucesso = 'Usuário cadastrado com sucesso! Faça login para continuar.';
            }
        } catch (PDOException $e) {
            $erro = 'Erro no sistema. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <img src="images/logo_netmai.png" alt="Netmai" class="logo">
            <h2 class="login-title">Sistema Financeiro</h2>
            
            <?php if ($erro): ?>
                <div class="alert alert-error"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <?php if ($sucesso): ?>
                <div class="alert alert-success"><?php echo $sucesso; ?></div>
            <?php endif; ?>
            
            <div id="login-form" <?php echo (isset($_POST['acao']) && $_POST['acao'] === 'cadastrar') ? 'style="display:none"' : ''; ?>>
                <form method="POST">
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="senha">Senha:</label>
                        <input type="password" id="senha" name="senha" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn">Entrar</button>
                    <button type="button" class="btn btn-secondary" onclick="mostrarCadastro()">Cadastrar-se</button>
                </form>
            </div>
            
            <div id="cadastro-form" style="display:none">
                <form method="POST">
                    <input type="hidden" name="acao" value="cadastrar">
                    
                    <div class="form-group">
                        <label for="nome_cadastro">Nome:</label>
                        <input type="text" id="nome_cadastro" name="nome" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email_cadastro">Email:</label>
                        <input type="email" id="email_cadastro" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="senha_cadastro">Senha:</label>
                        <input type="password" id="senha_cadastro" name="senha" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmar_senha">Confirmar Senha:</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn">Cadastrar</button>
                    <button type="button" class="btn btn-secondary" onclick="mostrarLogin()">Voltar ao Login</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function mostrarCadastro() {
            document.getElementById('login-form').style.display = 'none';
            document.getElementById('cadastro-form').style.display = 'block';
        }
        
        function mostrarLogin() {
            document.getElementById('cadastro-form').style.display = 'none';
            document.getElementById('login-form').style.display = 'block';
        }
        
        <?php if (isset($_POST['acao']) && $_POST['acao'] === 'cadastrar' && !$sucesso): ?>
            mostrarCadastro();
        <?php endif; ?>
    </script>
</body>
</html>
