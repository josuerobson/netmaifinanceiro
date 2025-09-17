<?php 
include 'includes/header.php';

$erro = '';
$sucesso = '';

// Processar formulário de categoria
if (isset($_POST['acao']) && $_POST['acao'] === 'categoria') {
    $nome = trim($_POST['nome'] ?? '');
    
    if (empty($nome)) {
        $erro = 'Por favor, informe o nome da categoria.';
    } else {
        try {
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                // Editar categoria
                $stmt = $pdo->prepare("UPDATE categorias SET nome = ? WHERE id = ?");
                $stmt->execute([$nome, $_POST['id']]);
                $sucesso = 'Categoria atualizada com sucesso!';
            } else {
                // Cadastrar categoria
                $stmt = $pdo->prepare("INSERT INTO categorias (nome) VALUES (?)");
                $stmt->execute([$nome]);
                $sucesso = 'Categoria cadastrada com sucesso!';
            }
        } catch (PDOException $e) {
            $erro = 'Erro no sistema. Tente novamente.';
        }
    }
}

// Processar formulário de subcategoria
if (isset($_POST['acao']) && $_POST['acao'] === 'subcategoria') {
    $nome = trim($_POST['nome'] ?? '');
    $categoria_id = $_POST['categoria_id'] ?? '';
    
    if (empty($nome) || empty($categoria_id)) {
        $erro = 'Por favor, preencha todos os campos da subcategoria.';
    } else {
        try {
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                // Editar subcategoria
                $stmt = $pdo->prepare("UPDATE subcategorias SET nome = ?, categoria_id = ? WHERE id = ?");
                $stmt->execute([$nome, $categoria_id, $_POST['id']]);
                $sucesso = 'Subcategoria atualizada com sucesso!';
            } else {
                // Cadastrar subcategoria
                $stmt = $pdo->prepare("INSERT INTO subcategorias (nome, categoria_id) VALUES (?, ?)");
                $stmt->execute([$nome, $categoria_id]);
                $sucesso = 'Subcategoria cadastrada com sucesso!';
            }
        } catch (PDOException $e) {
            $erro = 'Erro no sistema. Tente novamente.';
        }
    }
}

// Excluir categoria
if (isset($_GET['excluir_categoria'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
        $stmt->execute([$_GET['excluir_categoria']]);
        $sucesso = 'Categoria excluída com sucesso!';
    } catch (PDOException $e) {
        $erro = 'Erro ao excluir. Verifique se não há subcategorias ou lançamentos vinculados a esta categoria.';
    }
}

// Excluir subcategoria
if (isset($_GET['excluir_subcategoria'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM subcategorias WHERE id = ?");
        $stmt->execute([$_GET['excluir_subcategoria']]);
        $sucesso = 'Subcategoria excluída com sucesso!';
    } catch (PDOException $e) {
        $erro = 'Erro ao excluir. Verifique se não há lançamentos vinculados a esta subcategoria.';
    }
}

// Buscar categoria para edição
$categoria_edicao = null;
if (isset($_GET['editar_categoria'])) {
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
    $stmt->execute([$_GET['editar_categoria']]);
    $categoria_edicao = $stmt->fetch();
}

// Buscar subcategoria para edição
$subcategoria_edicao = null;
if (isset($_GET['editar_subcategoria'])) {
    $stmt = $pdo->prepare("SELECT * FROM subcategorias WHERE id = ?");
    $stmt->execute([$_GET['editar_subcategoria']]);
    $subcategoria_edicao = $stmt->fetch();
}

// Listar categorias
$stmt = $pdo->query("SELECT * FROM categorias ORDER BY nome");
$categorias = $stmt->fetchAll();

// Listar subcategorias com nome da categoria
$stmt = $pdo->query("
    SELECT s.*, c.nome as categoria_nome 
    FROM subcategorias s 
    JOIN categorias c ON s.categoria_id = c.id 
    ORDER BY c.nome, s.nome
");
$subcategorias = $stmt->fetchAll();
?>

<h2>Categorias e Subcategorias</h2>

<?php if ($erro): ?>
    <div class="alert alert-error"><?php echo $erro; ?></div>
<?php endif; ?>

<?php if ($sucesso): ?>
    <div class="alert alert-success"><?php echo $sucesso; ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
    <!-- Formulário de Categoria -->
    <div class="card">
        <h3 class="card-title">
            <?php echo $categoria_edicao ? 'Editar Categoria' : 'Cadastrar Categoria'; ?>
        </h3>
        
        <form method="POST">
            <input type="hidden" name="acao" value="categoria">
            <?php if ($categoria_edicao): ?>
                <input type="hidden" name="id" value="<?php echo $categoria_edicao['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="nome_categoria">Nome da Categoria:</label>
                <input type="text" id="nome_categoria" name="nome" class="form-control" required 
                       value="<?php echo htmlspecialchars($categoria_edicao['nome'] ?? ''); ?>"
                       placeholder="Ex: Receitas, Despesas, Investimentos">
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn">
                    <?php echo $categoria_edicao ? 'Atualizar' : 'Cadastrar'; ?>
                </button>
                
                <?php if ($categoria_edicao): ?>
                    <a href="categorias.php" class="btn btn-secondary">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Formulário de Subcategoria -->
    <div class="card">
        <h3 class="card-title">
            <?php echo $subcategoria_edicao ? 'Editar Subcategoria' : 'Cadastrar Subcategoria'; ?>
        </h3>
        
        <form method="POST">
            <input type="hidden" name="acao" value="subcategoria">
            <?php if ($subcategoria_edicao): ?>
                <input type="hidden" name="id" value="<?php echo $subcategoria_edicao['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="categoria_id">Categoria:</label>
                <select id="categoria_id" name="categoria_id" class="form-control" required>
                    <option value="">Selecione uma categoria</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo $categoria['id']; ?>" 
                                <?php echo ($subcategoria_edicao && $subcategoria_edicao['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($categoria['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="nome_subcategoria">Nome da Subcategoria:</label>
                <input type="text" id="nome_subcategoria" name="nome" class="form-control" required 
                       value="<?php echo htmlspecialchars($subcategoria_edicao['nome'] ?? ''); ?>"
                       placeholder="Ex: Vendas Online, Marketing Digital">
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn">
                    <?php echo $subcategoria_edicao ? 'Atualizar' : 'Cadastrar'; ?>
                </button>
                
                <?php if ($subcategoria_edicao): ?>
                    <a href="categorias.php" class="btn btn-secondary">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <!-- Lista de Categorias -->
    <div class="card">
        <h3 class="card-title">Categorias Cadastradas</h3>
        
        <div style="margin-bottom: 20px;">
            <input type="text" id="filtro-categorias" class="form-control" placeholder="Buscar categoria..." style="max-width: 300px;">
        </div>
        
        <?php if (empty($categorias)): ?>
            <p style="text-align: center; color: #666; padding: 40px;">
                Nenhuma categoria cadastrada ainda.
            </p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="table" id="tabela-categorias">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $categoria): ?>
                            <tr>
                                <td><?php echo $categoria['id']; ?></td>
                                <td><?php echo htmlspecialchars($categoria['nome']); ?></td>
                                <td>
                                    <a href="?editar_categoria=<?php echo $categoria['id']; ?>" 
                                       style="color: #667eea; text-decoration: none; margin-right: 15px;">
                                        Editar
                                    </a>
                                    <a href="?excluir_categoria=<?php echo $categoria['id']; ?>" 
                                       style="color: #dc3545; text-decoration: none;"
                                       onclick="return confirmarExclusao('Tem certeza que deseja excluir esta categoria?')">
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
    
    <!-- Lista de Subcategorias -->
    <div class="card">
        <h3 class="card-title">Subcategorias Cadastradas</h3>
        
        <div style="margin-bottom: 20px;">
            <input type="text" id="filtro-subcategorias" class="form-control" placeholder="Buscar subcategoria..." style="max-width: 300px;">
        </div>
        
        <?php if (empty($subcategorias)): ?>
            <p style="text-align: center; color: #666; padding: 40px;">
                Nenhuma subcategoria cadastrada ainda.
            </p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="table" id="tabela-subcategorias">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Categoria > Subcategoria</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subcategorias as $subcategoria): ?>
                            <tr>
                                <td><?php echo $subcategoria['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($subcategoria['categoria_nome']); ?></strong> > 
                                    <?php echo htmlspecialchars($subcategoria['nome']); ?>
                                </td>
                                <td>
                                    <a href="?editar_subcategoria=<?php echo $subcategoria['id']; ?>" 
                                       style="color: #667eea; text-decoration: none; margin-right: 15px;">
                                        Editar
                                    </a>
                                    <a href="?excluir_subcategoria=<?php echo $subcategoria['id']; ?>" 
                                       style="color: #dc3545; text-decoration: none;"
                                       onclick="return confirmarExclusao('Tem certeza que deseja excluir esta subcategoria?')">
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
</div>

<script>
// Filtros de busca
document.addEventListener('DOMContentLoaded', function() {
    filtrarTabela('filtro-categorias', 'tabela-categorias');
    filtrarTabela('filtro-subcategorias', 'tabela-subcategorias');
});
</script>

<?php include 'includes/footer.php'; ?>
