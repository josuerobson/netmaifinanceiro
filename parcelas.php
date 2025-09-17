<?php 
include 'includes/header.php';

$erro = '';
$sucesso = '';

// Verificar se foi passado o ID do lançamento
if (!isset($_GET['lancamento_id']) || empty($_GET['lancamento_id'])) {
    header('Location: lancamentos.php');
    exit();
}

$lancamento_id = $_GET['lancamento_id'];

// Buscar informações do lançamento
$stmt = $pdo->prepare("
    SELECT l.*, cc.nome as centro_custo_nome, c.nome as categoria_nome, s.nome as subcategoria_nome
    FROM lancamentos l
    JOIN centro_custos cc ON l.centro_custo_id = cc.id
    JOIN subcategorias s ON l.subcategoria_id = s.id
    JOIN categorias c ON s.categoria_id = c.id
    WHERE l.id = ? AND l.usuario_id = ?
");
$stmt->execute([$lancamento_id, $_SESSION['usuario_id']]);
$lancamento = $stmt->fetch();

if (!$lancamento) {
    header('Location: lancamentos.php');
    exit();
}

// Processar marcação de parcela como paga/não paga
if (isset($_POST['acao']) && $_POST['acao'] === 'toggle_pago') {
    $parcela_id = $_POST['parcela_id'] ?? '';
    $pago = $_POST['pago'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE parcelas SET pago = ? WHERE id = ? AND lancamento_id = ?");
        $stmt->execute([$pago, $parcela_id, $lancamento_id]);
        $sucesso = 'Status da parcela atualizado com sucesso!';
    } catch (PDOException $e) {
        $erro = 'Erro ao atualizar status da parcela.';
    }
}

// Processar edição de parcela
if (isset($_POST['acao']) && $_POST['acao'] === 'editar_parcela') {
    $parcela_id = $_POST['parcela_id'] ?? '';
    $valor = converterMoeda($_POST['valor'] ?? '');
    $data_vencimento = $_POST['data_vencimento'] ?? '';
    
    if (empty($valor) || empty($data_vencimento)) {
        $erro = 'Por favor, preencha todos os campos da parcela.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE parcelas SET valor = ?, data_vencimento = ? WHERE id = ? AND lancamento_id = ?");
            $stmt->execute([$valor, $data_vencimento, $parcela_id, $lancamento_id]);
            $sucesso = 'Parcela atualizada com sucesso!';
        } catch (PDOException $e) {
            $erro = 'Erro ao atualizar parcela.';
        }
    }
}

// Buscar parcelas do lançamento
$stmt = $pdo->prepare("SELECT * FROM parcelas WHERE lancamento_id = ? ORDER BY numero_parcela");
$stmt->execute([$lancamento_id]);
$parcelas = $stmt->fetchAll();

// Calcular estatísticas das parcelas
$total_parcelas = count($parcelas);
$parcelas_pagas = array_filter($parcelas, function($p) { return $p['pago'] == 1; });
$total_pago = array_sum(array_column($parcelas_pagas, 'valor'));
$total_pendente = array_sum(array_column(array_filter($parcelas, function($p) { return $p['pago'] == 0; }), 'valor'));
?>

<h2>Gerenciar Parcelas</h2>

<div style="margin-bottom: 20px;">
    <a href="lancamentos.php" style="color: #667eea; text-decoration: none;">
        ← Voltar aos Lançamentos
    </a>
</div>

<?php if ($erro): ?>
    <div class="alert alert-error"><?php echo $erro; ?></div>
<?php endif; ?>

<?php if ($sucesso): ?>
    <div class="alert alert-success"><?php echo $sucesso; ?></div>
<?php endif; ?>

<div class="card">
    <h3 class="card-title">Informações do Lançamento</h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
        <div>
            <strong>Tipo:</strong><br>
            <span style="color: <?php echo $lancamento['tipo'] === 'entrada' ? '#28a745' : '#dc3545'; ?>; font-weight: bold;">
                <?php echo ucfirst($lancamento['tipo']); ?>
            </span>
        </div>
        <div>
            <strong>Centro de Custo:</strong><br>
            <?php echo htmlspecialchars($lancamento['centro_custo_nome']); ?>
        </div>
        <div>
            <strong>Categoria:</strong><br>
            <?php echo htmlspecialchars($lancamento['categoria_nome']); ?> > <?php echo htmlspecialchars($lancamento['subcategoria_nome']); ?>
        </div>
        <div>
            <strong>Valor Total:</strong><br>
            <span style="font-size: 18px; font-weight: bold;">
                <?php echo formatarMoeda($lancamento['valor']); ?>
            </span>
        </div>
        <div>
            <strong>Data:</strong><br>
            <?php echo date('d/m/Y', strtotime($lancamento['data'])); ?>
        </div>
        <div>
            <strong>Parcelas:</strong><br>
            <?php echo $lancamento['parcelas']; ?>x
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
        <h4 style="margin-bottom: 10px; font-size: 16px;">Parcelas Pagas</h4>
        <div style="font-size: 24px; font-weight: bold;"><?php echo count($parcelas_pagas); ?>/<?php echo $total_parcelas; ?></div>
        <div style="font-size: 14px; opacity: 0.9;"><?php echo formatarMoeda($total_pago); ?></div>
    </div>
    
    <div style="background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
        <h4 style="margin-bottom: 10px; font-size: 16px;">Valor Pendente</h4>
        <div style="font-size: 24px; font-weight: bold;"><?php echo formatarMoeda($total_pendente); ?></div>
        <div style="font-size: 14px; opacity: 0.9;"><?php echo ($total_parcelas - count($parcelas_pagas)); ?> parcelas</div>
    </div>
    
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
        <h4 style="margin-bottom: 10px; font-size: 16px;">Progresso</h4>
        <div style="font-size: 24px; font-weight: bold;">
            <?php echo $total_parcelas > 0 ? round((count($parcelas_pagas) / $total_parcelas) * 100) : 0; ?>%
        </div>
        <div style="font-size: 14px; opacity: 0.9;">Concluído</div>
    </div>
</div>

<div class="card">
    <h3 class="card-title">Lista de Parcelas</h3>
    
    <?php if (empty($parcelas)): ?>
        <p style="text-align: center; color: #666; padding: 40px;">
            Este lançamento não possui parcelas.
        </p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Parcela</th>
                        <th>Valor</th>
                        <th>Data Vencimento</th>
                        <th>Status</th>
                        <th>Situação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parcelas as $parcela): ?>
                        <?php
                        $vencimento = new DateTime($parcela['data_vencimento']);
                        $hoje = new DateTime();
                        $dias_vencimento = $vencimento->diff($hoje)->days;
                        $vencido = $vencimento < $hoje;
                        
                        $situacao = '';
                        $cor_situacao = '#666';
                        
                        if ($parcela['pago']) {
                            $situacao = 'Pago';
                            $cor_situacao = '#28a745';
                        } elseif ($vencido) {
                            $situacao = 'Vencido (' . $dias_vencimento . ' dias)';
                            $cor_situacao = '#dc3545';
                        } elseif ($dias_vencimento <= 7) {
                            $situacao = 'Vence em ' . $dias_vencimento . ' dias';
                            $cor_situacao = '#ffc107';
                        } else {
                            $situacao = 'Em dia';
                            $cor_situacao = '#28a745';
                        }
                        ?>
                        <tr id="parcela-<?php echo $parcela['id']; ?>">
                            <td>
                                <strong><?php echo $parcela['numero_parcela']; ?>ª Parcela</strong>
                            </td>
                            <td>
                                <span class="valor-parcela" style="font-weight: bold;">
                                    <?php echo formatarMoeda($parcela['valor']); ?>
                                </span>
                                <input type="text" class="form-control currency-input edit-valor" 
                                       value="<?php echo formatarMoeda($parcela['valor']); ?>" 
                                       style="display: none; width: 120px;">
                            </td>
                            <td>
                                <span class="data-parcela">
                                    <?php echo date('d/m/Y', strtotime($parcela['data_vencimento'])); ?>
                                </span>
                                <input type="date" class="form-control edit-data" 
                                       value="<?php echo $parcela['data_vencimento']; ?>" 
                                       style="display: none; width: 150px;">
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="acao" value="toggle_pago">
                                    <input type="hidden" name="parcela_id" value="<?php echo $parcela['id']; ?>">
                                    <input type="hidden" name="pago" value="<?php echo $parcela['pago'] ? 0 : 1; ?>">
                                    <button type="submit" style="background: none; border: none; cursor: pointer; font-size: 16px;">
                                        <?php if ($parcela['pago']): ?>
                                            <span style="color: #28a745;">✓ Pago</span>
                                        <?php else: ?>
                                            <span style="color: #dc3545;">✗ Pendente</span>
                                        <?php endif; ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <span style="color: <?php echo $cor_situacao; ?>; font-weight: bold;">
                                    <?php echo $situacao; ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn-editar" onclick="editarParcela(<?php echo $parcela['id']; ?>)" 
                                        style="background: none; border: none; color: #667eea; cursor: pointer; margin-right: 10px;">
                                    Editar
                                </button>
                                <button type="button" class="btn-salvar" onclick="salvarParcela(<?php echo $parcela['id']; ?>)" 
                                        style="background: none; border: none; color: #28a745; cursor: pointer; margin-right: 10px; display: none;">
                                    Salvar
                                </button>
                                <button type="button" class="btn-cancelar" onclick="cancelarEdicao(<?php echo $parcela['id']; ?>)" 
                                        style="background: none; border: none; color: #6c757d; cursor: pointer; display: none;">
                                    Cancelar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function editarParcela(parcelaId) {
    const row = document.getElementById('parcela-' + parcelaId);
    
    // Mostrar campos de edição
    row.querySelector('.valor-parcela').style.display = 'none';
    row.querySelector('.edit-valor').style.display = 'inline-block';
    row.querySelector('.data-parcela').style.display = 'none';
    row.querySelector('.edit-data').style.display = 'inline-block';
    
    // Mostrar botões de salvar/cancelar
    row.querySelector('.btn-editar').style.display = 'none';
    row.querySelector('.btn-salvar').style.display = 'inline-block';
    row.querySelector('.btn-cancelar').style.display = 'inline-block';
}

function cancelarEdicao(parcelaId) {
    const row = document.getElementById('parcela-' + parcelaId);
    
    // Ocultar campos de edição
    row.querySelector('.valor-parcela').style.display = 'inline';
    row.querySelector('.edit-valor').style.display = 'none';
    row.querySelector('.data-parcela').style.display = 'inline';
    row.querySelector('.edit-data').style.display = 'none';
    
    // Mostrar botão de editar
    row.querySelector('.btn-editar').style.display = 'inline-block';
    row.querySelector('.btn-salvar').style.display = 'none';
    row.querySelector('.btn-cancelar').style.display = 'none';
}

function salvarParcela(parcelaId) {
    const row = document.getElementById('parcela-' + parcelaId);
    const valor = row.querySelector('.edit-valor').value;
    const dataVencimento = row.querySelector('.edit-data').value;
    
    if (!valor || !dataVencimento) {
        alert('Por favor, preencha todos os campos.');
        return;
    }
    
    // Criar formulário para envio
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="acao" value="editar_parcela">
        <input type="hidden" name="parcela_id" value="${parcelaId}">
        <input type="hidden" name="valor" value="${valor}">
        <input type="hidden" name="data_vencimento" value="${dataVencimento}">
    `;
    
    document.body.appendChild(form);
    form.submit();
}

// Aplicar máscara de moeda nos campos de edição
document.addEventListener('DOMContentLoaded', function() {
    const camposEdicao = document.querySelectorAll('.edit-valor');
    camposEdicao.forEach(function(campo) {
        campo.addEventListener('input', function() {
            aplicarMascaraMoeda(this);
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
