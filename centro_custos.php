<?php 
include 'includes/header.php';

$erro = '';
$sucesso = '';

// Processar formulário
if ($_POST) {
    $nome = trim($_POST['nome'] ?? '');
    
    if (empty($nome)) {
        $erro = 'Por favor, informe o nome do centro de custo.';
    } else {
        try {
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                // Editar
                $stmt = $pdo->prepare("UPDATE centro_custos SET nome = ? WHERE id = ?");
                $stmt->execute([$nome, $_POST['id']]);
                $sucesso = 'Centro de custo atualizado com sucesso!';
            } else {
                // Cadastrar
                $stmt = $pdo->prepare("INSERT INTO centro_custos (nome) VALUES (?)");
                $stmt->execute([$nome]);
                $sucesso = 'Centro de custo cadastrado com sucesso!';
            }
        } catch (PDOException $e) {
            $erro = 'Erro no sistema. Tente novamente.';
        }
    }
}

// Excluir
if (isset($_GET['excluir'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM centro_custos WHERE id = ?");
        $stmt->execute([$_GET['excluir']]);
        $sucesso = 'Centro de custo excluído com sucesso!';
    } catch (PDOException $e) {
        $erro = 'Erro ao excluir. Verifique se não há lançamentos vinculados a este centro de custo.';
    }
}

// Buscar centro de custo para edição
$centro_custo_edicao = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM centro_custos WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $centro_custo_edicao = $stmt->fetch();
}

// Listar centros de custo
$stmt = $pdo->query("SELECT * FROM centro_custos ORDER BY nome");
$centros_custo = $stmt->fetchAll();
?>

<h2>Centro de Custos</h2>

<?php if ($erro): ?>
    <div class="alert alert-error"><?php echo $erro; ?></div>
<?php endif; ?>

<?php if ($sucesso): ?>
    <div class="alert alert-success"><?php echo $sucesso; ?></div>
<?php endif; ?>

<div class="card">
    <h3 class="card-title">
        <?php echo $centro_custo_edicao ? 'Editar Centro de Custo' : 'Cadastrar Centro de Custo'; ?>
    </h3>
    
    <form method="POST">
        <?php if ($centro_custo_edicao): ?>
            <input type="hidden" name="id" value="<?php echo $centro_custo_edicao['id']; ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="nome">Nome do Centro de Custo:</label>
            <input type="text" id="nome" name="nome" class="form-control" required 
                   value="<?php echo htmlspecialchars($centro_custo_edicao['nome'] ?? ''); ?>"
                   placeholder="Ex: Vendas, Marketing, Administrativo">
        </div>
        
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn">
                <?php echo $centro_custo_edicao ? 'Atualizar' : 'Cadastrar'; ?>
            </button>
            
            <?php if ($centro_custo_edicao): ?>
                <a href="centro_custos.php" class="btn btn-secondary">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h3 class="card-title">Centros de Custo Cadastrados</h3>
    
    <div style="margin-bottom: 20px;">
        <input type="text" id="filtro-busca" class="form-control" placeholder="Buscar centro de custo..." style="max-width: 300px;">
    </div>
    
    <?php if (empty($centros_custo)): ?>
        <p style="text-align: center; color: #666; padding: 40px;">
            Nenhum centro de custo cadastrado ainda.
        </p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table" id="tabela-centros-custo">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($centros_custo as $centro): ?>
                        <tr>
                            <td><?php echo $centro['id']; ?></td>
                            <td><?php echo htmlspecialchars($centro['nome']); ?></td>
                            <td>
                                <a href="?editar=<?php echo $centro['id']; ?>" 
                                   style="color: #667eea; text-decoration: none; margin-right: 15px;">
                                    Editar
                                </a>
                                <a href="?excluir=<?php echo $centro['id']; ?>" 
                                   style="color: #dc3545; text-decoration: none;"
                                   onclick="return confirmarExclusao('Tem certeza que deseja excluir este centro de custo?')">
                                    Excluir
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
// Filtro de busca
document.addEventListener('DOMContentLoaded', function() {
    filtrarTabela('filtro-busca', 'tabela-centros-custo');
});
</script>

<?php include 'includes/footer.php'; ?>
