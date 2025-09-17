<?php 
include 'includes/header.php';

$erro = '';
$sucesso = '';

// Processar formulário
if ($_POST) {
    $tipo = $_POST['tipo'] ?? '';
    $centro_custo_id = $_POST['centro_custo_id'] ?? '';
    $subcategoria_id = $_POST['subcategoria_id'] ?? '';
    $valor = converterMoeda($_POST['valor'] ?? '');
    $data = $_POST['data'] ?? '';
    $forma_pagamento = $_POST['forma_pagamento'] ?? '';
    $parcelas = $_POST['parcelas'] ?? null;
    
    if (empty($tipo) || empty($centro_custo_id) || empty($subcategoria_id) || empty($valor) || empty($data) || empty($forma_pagamento)) {
        $erro = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif ($forma_pagamento === 'parcelado' && (empty($parcelas) || $parcelas < 2)) {
        $erro = 'Para pagamento parcelado, informe o número de parcelas (mínimo 2).';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Determinar se o lançamento está pago baseado na data
            $data_lancamento = new DateTime($data);
            $hoje = new DateTime();
            $pago = $data_lancamento <= $hoje ? 1 : 0;
            $data_pagamento = $pago ? $data : null;
            
            // Inserir lançamento principal
            $stmt = $pdo->prepare("
                INSERT INTO lancamentos (tipo, centro_custo_id, subcategoria_id, valor, data, forma_pagamento, parcelas, usuario_id, pago, data_pagamento) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$tipo, $centro_custo_id, $subcategoria_id, $valor, $data, $forma_pagamento, $parcelas, $_SESSION['usuario_id'], $pago, $data_pagamento]);
            
            $lancamento_id = $pdo->lastInsertId();
            
            // Se for parcelado, inserir as parcelas
            if ($forma_pagamento === 'parcelado' && $parcelas > 1) {
                $valor_parcela = $valor / $parcelas;
                $data_base = new DateTime($data);
                
                // Verificar se foram enviadas datas personalizadas
                $datas_parcelas = $_POST['data_parcela'] ?? [];
                
                for ($i = 1; $i <= $parcelas; $i++) {
                    if (isset($datas_parcelas[$i-1]) && !empty($datas_parcelas[$i-1])) {
                        $data_vencimento = $datas_parcelas[$i-1];
                    } else {
                        $data_parcela = clone $data_base;
                        $data_parcela->modify('+' . ($i-1) . ' month');
                        $data_vencimento = $data_parcela->format('Y-m-d');
                    }
                    
                    // Determinar se a parcela está paga baseado na data de vencimento
                    $data_venc = new DateTime($data_vencimento);
                    $pago_parcela = $data_venc <= $hoje ? 1 : 0;
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO parcelas (lancamento_id, numero_parcela, valor, data_vencimento, pago) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$lancamento_id, $i, $valor_parcela, $data_vencimento, $pago_parcela]);
                }
            }
            
            $pdo->commit();
            $sucesso = 'Lançamento cadastrado com sucesso!';
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erro = 'Erro no sistema. Tente novamente.';
        }
    }
}

// Dar baixa no lançamento
if (isset($_GET['dar_baixa'])) {
    try {
        $stmt = $pdo->prepare("
            UPDATE lancamentos 
            SET pago = 1, data_pagamento = CURDATE() 
            WHERE id = ? AND usuario_id = ? AND pago = 0
        ");
        $stmt->execute([$_GET['dar_baixa'], $_SESSION['usuario_id']]);
        
        if ($stmt->rowCount() > 0) {
            $sucesso = 'Baixa realizada com sucesso! O lançamento agora impacta o fluxo de caixa.';
        } else {
            $erro = 'Lançamento não encontrado ou já foi pago.';
        }
        
    } catch (PDOException $e) {
        $erro = 'Erro ao dar baixa no lançamento.';
    }
}

// Excluir lançamento
if (isset($_GET['excluir'])) {
    try {
        $pdo->beginTransaction();
        
        // Excluir parcelas primeiro
        $stmt = $pdo->prepare("DELETE FROM parcelas WHERE lancamento_id = ?");
        $stmt->execute([$_GET['excluir']]);
        
        // Excluir lançamento
        $stmt = $pdo->prepare("DELETE FROM lancamentos WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$_GET['excluir'], $_SESSION['usuario_id']]);
        
        $pdo->commit();
        $sucesso = 'Lançamento excluído com sucesso!';
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $erro = 'Erro ao excluir lançamento.';
    }
}

// Buscar dados para os selects
$stmt = $pdo->query("SELECT * FROM centro_custos ORDER BY nome");
$centros_custo = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM categorias ORDER BY nome");
$categorias = $stmt->fetchAll();

// Listar lançamentos
$filtro_tipo = $_GET['filtro_tipo'] ?? '';
$filtro_data_inicio = $_GET['filtro_data_inicio'] ?? '';
$filtro_data_fim = $_GET['filtro_data_fim'] ?? '';

$where_conditions = ["l.usuario_id = ?"];
$params = [$_SESSION['usuario_id']];

if (!empty($filtro_tipo)) {
    $where_conditions[] = "l.tipo = ?";
    $params[] = $filtro_tipo;
}

if (!empty($filtro_data_inicio)) {
    $where_conditions[] = "l.data >= ?";
    $params[] = $filtro_data_inicio;
}

if (!empty($filtro_data_fim)) {
    $where_conditions[] = "l.data <= ?";
    $params[] = $filtro_data_fim;
}

$where_clause = implode(' AND ', $where_conditions);

$stmt = $pdo->prepare("
    SELECT l.*, cc.nome as centro_custo_nome, c.nome as categoria_nome, s.nome as subcategoria_nome
    FROM lancamentos l
    JOIN centro_custos cc ON l.centro_custo_id = cc.id
    JOIN subcategorias s ON l.subcategoria_id = s.id
    JOIN categorias c ON s.categoria_id = c.id
    WHERE $where_clause
    ORDER BY l.data DESC, l.id DESC
    LIMIT 50
");
$stmt->execute($params);
$lancamentos = $stmt->fetchAll();
?>

<h2>Lançamentos Financeiros</h2>

<?php if ($erro): ?>
    <div class="alert alert-error"><?php echo $erro; ?></div>
<?php endif; ?>

<?php if ($sucesso): ?>
    <div class="alert alert-success"><?php echo $sucesso; ?></div>
<?php endif; ?>

<div class="card">
    <h3 class="card-title">Novo Lançamento</h3>
    
    <form method="POST" id="form-lancamento">
        <div class="form-row">
            <div class="form-col">
                <label for="tipo">Tipo de Lançamento:</label>
                <select id="tipo" name="tipo" class="form-control" required>
                    <option value="">Selecione o tipo</option>
                    <option value="entrada">Entrada</option>
                    <option value="saida">Saída</option>
                </select>
            </div>
            
            <div class="form-col">
                <label for="centro_custo_id">Centro de Custo:</label>
                <select id="centro_custo_id" name="centro_custo_id" class="form-control" required>
                    <option value="">Selecione o centro de custo</option>
                    <?php foreach ($centros_custo as $centro): ?>
                        <option value="<?php echo $centro['id']; ?>">
                            <?php echo htmlspecialchars($centro['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-col">
                <label for="categoria_id">Categoria:</label>
                <select id="categoria_id" class="form-control" required>
                    <option value="">Selecione a categoria</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo $categoria['id']; ?>">
                            <?php echo htmlspecialchars($categoria['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-col">
                <label for="subcategoria_id">Subcategoria:</label>
                <select id="subcategoria_id" name="subcategoria_id" class="form-control" required>
                    <option value="">Selecione uma categoria primeiro</option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-col">
                <label for="valor">Valor:</label>
                <input type="text" id="valor" name="valor" class="form-control currency-input" required 
                       placeholder="R$ 0,00">
            </div>
            
            <div class="form-col">
                <label for="data">Data:</label>
                <input type="date" id="data" name="data" class="form-control" required 
                       value="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>Forma de Pagamento:</label>
            <div class="radio-group">
                <div class="radio-item">
                    <input type="radio" id="a_vista" name="forma_pagamento" value="a_vista" checked>
                    <label for="a_vista">À Vista</label>
                </div>
                <div class="radio-item">
                    <input type="radio" id="parcelado" name="forma_pagamento" value="parcelado">
                    <label for="parcelado">Parcelado</label>
                </div>
            </div>
        </div>
        
        <div id="parcelas-container" style="display: none;">
            <div class="form-group">
                <label for="parcelas-select">Número de Parcelas:</label>
                <select id="parcelas-select" name="parcelas" class="form-control" style="max-width: 200px;">
                    <option value="">Selecione</option>
                    <?php for ($i = 2; $i <= 48; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?>x</option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div id="parcelas-preview" class="parcelas-preview"></div>
        </div>
        
        <button type="submit" class="btn">Cadastrar Lançamento</button>
    </form>
</div>

<div class="card">
    <h3 class="card-title">Filtros e Busca</h3>
    
    <form method="GET" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
        <div class="form-group" style="margin-bottom: 0;">
            <label for="filtro_tipo">Tipo:</label>
            <select id="filtro_tipo" name="filtro_tipo" class="form-control" style="min-width: 120px;">
                <option value="">Todos</option>
                <option value="entrada" <?php echo $filtro_tipo === 'entrada' ? 'selected' : ''; ?>>Entrada</option>
                <option value="saida" <?php echo $filtro_tipo === 'saida' ? 'selected' : ''; ?>>Saída</option>
            </select>
        </div>
        
        <div class="form-group" style="margin-bottom: 0;">
            <label for="filtro_data_inicio">Data Início:</label>
            <input type="date" id="filtro_data_inicio" name="filtro_data_inicio" class="form-control" 
                   value="<?php echo htmlspecialchars($filtro_data_inicio); ?>">
        </div>
        
        <div class="form-group" style="margin-bottom: 0;">
            <label for="filtro_data_fim">Data Fim:</label>
            <input type="date" id="filtro_data_fim" name="filtro_data_fim" class="form-control" 
                   value="<?php echo htmlspecialchars($filtro_data_fim); ?>">
        </div>
        
        <button type="submit" class="btn" style="height: fit-content;">Filtrar</button>
        <a href="lancamentos.php" class="btn btn-secondary" style="height: fit-content;">Limpar</a>
    </form>
</div>

<div class="card">
    <h3 class="card-title">Lançamentos Cadastrados</h3>
    
    <?php if (empty($lancamentos)): ?>
        <p style="text-align: center; color: #666; padding: 40px;">
            Nenhum lançamento encontrado.
        </p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Centro de Custo</th>
                        <th>Categoria > Subcategoria</th>
                        <th>Valor</th>
                        <th>Forma Pagamento</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lancamentos as $lancamento): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($lancamento['data'])); ?></td>
                            <td>
                                <span style="color: <?php echo $lancamento['tipo'] === 'entrada' ? '#28a745' : '#dc3545'; ?>; font-weight: bold;">
                                    <?php echo ucfirst($lancamento['tipo']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($lancamento['centro_custo_nome']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($lancamento['categoria_nome']); ?></strong> > 
                                <?php echo htmlspecialchars($lancamento['subcategoria_nome']); ?>
                            </td>
                            <td style="font-weight: bold;">
                                <?php echo formatarMoeda($lancamento['valor']); ?>
                            </td>
                            <td>
                                <?php if ($lancamento['forma_pagamento'] === 'a_vista'): ?>
                                    À Vista
                                <?php else: ?>
                                    <?php echo $lancamento['parcelas']; ?>x
                                    <a href="parcelas.php?lancamento_id=<?php echo $lancamento['id']; ?>" 
                                       style="color: #667eea; text-decoration: none; font-size: 12px;">
                                        (ver parcelas)
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($lancamento['pago']): ?>
                                    <span style="color: #28a745; font-weight: bold;">✓ Pago</span>
                                    <?php if ($lancamento['data_pagamento']): ?>
                                        <br><small style="color: #666;">em <?php echo date('d/m/Y', strtotime($lancamento['data_pagamento'])); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #dc3545; font-weight: bold;">⏳ Pendente</span>
                                    <br>
                                    <a href="?dar_baixa=<?php echo $lancamento['id']; ?>" 
                                       style="color: #28a745; text-decoration: none; font-size: 12px;"
                                       onclick="return confirm('Confirma a baixa deste lançamento?')">
                                        Dar Baixa
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?excluir=<?php echo $lancamento['id']; ?>" 
                                   style="color: #dc3545; text-decoration: none;"
                                   onclick="return confirmarExclusao('Tem certeza que deseja excluir este lançamento?')">
                                    Excluir
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (count($lancamentos) >= 50): ?>
            <p style="text-align: center; color: #666; margin-top: 20px;">
                Mostrando os últimos 50 lançamentos. Use os filtros para refinar a busca.
            </p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// Event listeners específicos para lançamentos
document.addEventListener('DOMContentLoaded', function() {
    // Carregar subcategorias quando categoria mudar
    const categoriaSelect = document.getElementById('categoria_id');
    if (categoriaSelect) {
        categoriaSelect.addEventListener('change', function() {
            carregarSubcategorias(this.value);
        });
    }
    
    // Controlar parcelamento
    const radiosPagamento = document.querySelectorAll('input[name="forma_pagamento"]');
    radiosPagamento.forEach(function(radio) {
        radio.addEventListener('change', toggleParcelamento);
    });
    
    // Gerar preview das parcelas
    const parcelasSelect = document.getElementById('parcelas-select');
    if (parcelasSelect) {
        parcelasSelect.addEventListener('change', gerarPreviewParcelas);
    }
    
    const valorInput = document.getElementById('valor');
    if (valorInput) {
        valorInput.addEventListener('input', function() {
            if (document.getElementById('parcelas-select').value) {
                gerarPreviewParcelas();
            }
        });
    }
    
    const dataInput = document.getElementById('data');
    if (dataInput) {
        dataInput.addEventListener('change', function() {
            if (document.getElementById('parcelas-select').value) {
                gerarPreviewParcelas();
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
