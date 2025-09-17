<?php include 'includes/header.php'; ?>

<?php
$erro = '';
$sucesso = '';

// Dar baixa em lançamento
if (isset($_GET['dar_baixa_lancamento'])) {
    try {
        $stmt = $pdo->prepare("
            UPDATE lancamentos 
            SET pago = 1, data_pagamento = CURDATE() 
            WHERE id = ? AND usuario_id = ? AND pago = 0
        ");
        $stmt->execute([$_GET['dar_baixa_lancamento'], $_SESSION['usuario_id']]);
        
        if ($stmt->rowCount() > 0) {
            $sucesso = 'Baixa realizada com sucesso!';
        } else {
            $erro = 'Lançamento não encontrado ou já foi pago.';
        }
        
    } catch (PDOException $e) {
        $erro = 'Erro ao dar baixa no lançamento.';
    }
}

// Dar baixa em parcela
if (isset($_GET['dar_baixa_parcela'])) {
    try {
        $stmt = $pdo->prepare("
            UPDATE parcelas p
            JOIN lancamentos l ON p.lancamento_id = l.id
            SET p.pago = 1 
            WHERE p.id = ? AND l.usuario_id = ? AND p.pago = 0
        ");
        $stmt->execute([$_GET['dar_baixa_parcela'], $_SESSION['usuario_id']]);
        
        if ($stmt->rowCount() > 0) {
            $sucesso = 'Baixa da parcela realizada com sucesso!';
        } else {
            $erro = 'Parcela não encontrada ou já foi paga.';
        }
        
    } catch (PDOException $e) {
        $erro = 'Erro ao dar baixa na parcela.';
    }
}

// Buscar contas a pagar (saídas não pagas)
$stmt = $pdo->prepare("
    SELECT 
        l.id,
        l.tipo,
        l.valor,
        l.data,
        l.forma_pagamento,
        l.parcelas,
        cc.nome as centro_custo,
        c.nome as categoria,
        s.nome as subcategoria,
        'lancamento' as origem
    FROM lancamentos l
    JOIN centro_custos cc ON l.centro_custo_id = cc.id
    JOIN subcategorias s ON l.subcategoria_id = s.id
    JOIN categorias c ON s.categoria_id = c.id
    WHERE l.usuario_id = ? 
    AND l.tipo = 'saida'
    AND l.pago = 0
    AND l.forma_pagamento = 'a_vista'
    
    UNION ALL
    
    SELECT 
        p.id,
        l.tipo,
        p.valor,
        p.data_vencimento as data,
        l.forma_pagamento,
        CONCAT(p.numero_parcela, '/', l.parcelas) as parcelas,
        cc.nome as centro_custo,
        c.nome as categoria,
        s.nome as subcategoria,
        'parcela' as origem
    FROM parcelas p
    JOIN lancamentos l ON p.lancamento_id = l.id
    JOIN centro_custos cc ON l.centro_custo_id = cc.id
    JOIN subcategorias s ON l.subcategoria_id = s.id
    JOIN categorias c ON s.categoria_id = c.id
    WHERE l.usuario_id = ? 
    AND l.tipo = 'saida'
    AND p.pago = 0
    
    ORDER BY data ASC
");
$stmt->execute([$_SESSION['usuario_id'], $_SESSION['usuario_id']]);
$contas_pagar = $stmt->fetchAll();

// Buscar contas a receber (entradas não pagas)
$stmt = $pdo->prepare("
    SELECT 
        l.id,
        l.tipo,
        l.valor,
        l.data,
        l.forma_pagamento,
        l.parcelas,
        cc.nome as centro_custo,
        c.nome as categoria,
        s.nome as subcategoria,
        'lancamento' as origem
    FROM lancamentos l
    JOIN centro_custos cc ON l.centro_custo_id = cc.id
    JOIN subcategorias s ON l.subcategoria_id = s.id
    JOIN categorias c ON s.categoria_id = c.id
    WHERE l.usuario_id = ? 
    AND l.tipo = 'entrada'
    AND l.pago = 0
    AND l.forma_pagamento = 'a_vista'
    
    UNION ALL
    
    SELECT 
        p.id,
        l.tipo,
        p.valor,
        p.data_vencimento as data,
        l.forma_pagamento,
        CONCAT(p.numero_parcela, '/', l.parcelas) as parcelas,
        cc.nome as centro_custo,
        c.nome as categoria,
        s.nome as subcategoria,
        'parcela' as origem
    FROM parcelas p
    JOIN lancamentos l ON p.lancamento_id = l.id
    JOIN centro_custos cc ON l.centro_custo_id = cc.id
    JOIN subcategorias s ON l.subcategoria_id = s.id
    JOIN categorias c ON s.categoria_id = c.id
    WHERE l.usuario_id = ? 
    AND l.tipo = 'entrada'
    AND p.pago = 0
    
    ORDER BY data ASC
");
$stmt->execute([$_SESSION['usuario_id'], $_SESSION['usuario_id']]);
$contas_receber = $stmt->fetchAll();
?>

<h2>Contas a Pagar e Receber</h2>

<?php if ($erro): ?>
    <div class="alert alert-error"><?php echo $erro; ?></div>
<?php endif; ?>

<?php if ($sucesso): ?>
    <div class="alert alert-success"><?php echo $sucesso; ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
    <!-- Resumo -->
    <div class="card">
        <h3 class="card-title">Resumo</h3>
        
        <?php
        $total_pagar = array_sum(array_column($contas_pagar, 'valor'));
        $total_receber = array_sum(array_column($contas_receber, 'valor'));
        $saldo_pendente = $total_receber - $total_pagar;
        ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div style="text-align: center; padding: 15px; background: #f8d7da; border-radius: 8px;">
                <div style="font-size: 14px; color: #721c24; margin-bottom: 5px;">Total a Pagar</div>
                <div style="font-size: 20px; font-weight: bold; color: #dc3545;">
                    <?php echo formatarMoeda($total_pagar); ?>
                </div>
                <div style="font-size: 12px; color: #721c24;">
                    <?php echo count($contas_pagar); ?> conta(s)
                </div>
            </div>
            
            <div style="text-align: center; padding: 15px; background: #d4edda; border-radius: 8px;">
                <div style="font-size: 14px; color: #155724; margin-bottom: 5px;">Total a Receber</div>
                <div style="font-size: 20px; font-weight: bold; color: #28a745;">
                    <?php echo formatarMoeda($total_receber); ?>
                </div>
                <div style="font-size: 12px; color: #155724;">
                    <?php echo count($contas_receber); ?> conta(s)
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 15px; padding: 15px; background: <?php echo $saldo_pendente >= 0 ? '#d4edda' : '#f8d7da'; ?>; border-radius: 8px;">
            <div style="font-size: 14px; color: <?php echo $saldo_pendente >= 0 ? '#155724' : '#721c24'; ?>; margin-bottom: 5px;">
                Saldo Pendente
            </div>
            <div style="font-size: 24px; font-weight: bold; color: <?php echo $saldo_pendente >= 0 ? '#28a745' : '#dc3545'; ?>;">
                <?php echo formatarMoeda($saldo_pendente); ?>
            </div>
        </div>
    </div>
    
    <!-- Vencimentos Próximos -->
    <div class="card">
        <h3 class="card-title">Vencimentos nos Próximos 7 Dias</h3>
        
        <?php
        $hoje = new DateTime();
        $limite = new DateTime('+7 days');
        $vencimentos_proximos = array_filter(array_merge($contas_pagar, $contas_receber), function($conta) use ($hoje, $limite) {
            $vencimento = new DateTime($conta['data']);
            return $vencimento >= $hoje && $vencimento <= $limite;
        });
        
        usort($vencimentos_proximos, function($a, $b) {
            return strtotime($a['data']) - strtotime($b['data']);
        });
        ?>
        
        <?php if (empty($vencimentos_proximos)): ?>
            <p style="text-align: center; color: #666; padding: 20px;">
                Nenhum vencimento nos próximos 7 dias.
            </p>
        <?php else: ?>
            <div style="max-height: 300px; overflow-y: auto;">
                <?php foreach ($vencimentos_proximos as $conta): ?>
                    <div style="border-left: 3px solid <?php echo $conta['tipo'] === 'entrada' ? '#28a745' : '#dc3545'; ?>; padding: 10px; margin-bottom: 10px; background: #f8f9fa; border-radius: 0 5px 5px 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: bold; color: <?php echo $conta['tipo'] === 'entrada' ? '#28a745' : '#dc3545'; ?>;">
                                    <?php echo formatarMoeda($conta['valor']); ?>
                                </div>
                                <div style="font-size: 12px; color: #666;">
                                    <?php echo date('d/m/Y', strtotime($conta['data'])); ?> - 
                                    <?php echo $conta['categoria']; ?> > <?php echo $conta['subcategoria']; ?>
                                </div>
                            </div>
                            <div style="font-size: 12px; color: #666;">
                                <?php echo ucfirst($conta['tipo']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Contas a Pagar -->
<div class="card">
    <h3 class="card-title" style="color: #dc3545;">💳 Contas a Pagar</h3>
    
    <?php if (empty($contas_pagar)): ?>
        <p style="text-align: center; color: #666; padding: 20px;">
            Nenhuma conta a pagar pendente.
        </p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Vencimento</th>
                        <th>Descrição</th>
                        <th>Centro de Custo</th>
                        <th>Valor</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contas_pagar as $conta): ?>
                        <?php
                        $vencimento = new DateTime($conta['data']);
                        $hoje = new DateTime();
                        $dias_vencimento = $vencimento->diff($hoje)->days;
                        $vencido = $vencimento < $hoje;
                        ?>
                        <tr style="<?php echo $vencido ? 'background-color: #f8d7da;' : ''; ?>">
                            <td>
                                <?php echo date('d/m/Y', strtotime($conta['data'])); ?>
                                <?php if ($vencido): ?>
                                    <br><small style="color: #dc3545; font-weight: bold;">Vencido há <?php echo $dias_vencimento; ?> dia(s)</small>
                                <?php elseif ($dias_vencimento <= 7): ?>
                                    <br><small style="color: #ffc107; font-weight: bold;">Vence em <?php echo $dias_vencimento; ?> dia(s)</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($conta['categoria']); ?></strong><br>
                                <small><?php echo htmlspecialchars($conta['subcategoria']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($conta['centro_custo']); ?></td>
                            <td style="font-weight: bold; color: #dc3545;">
                                <?php echo formatarMoeda($conta['valor']); ?>
                            </td>
                            <td>
                                <?php if ($conta['origem'] === 'parcela'): ?>
                                    Parcela <?php echo $conta['parcelas']; ?>
                                <?php else: ?>
                                    À Vista
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="color: #dc3545; font-weight: bold;">⏳ Pendente</span>
                            </td>
                            <td>
                                <?php if ($conta['origem'] === 'parcela'): ?>
                                    <a href="?dar_baixa_parcela=<?php echo $conta['id']; ?>" 
                                       style="color: #28a745; text-decoration: none; font-size: 12px;"
                                       onclick="return confirm('Confirma a baixa desta parcela?')">
                                        Dar Baixa
                                    </a>
                                <?php else: ?>
                                    <a href="?dar_baixa_lancamento=<?php echo $conta['id']; ?>" 
                                       style="color: #28a745; text-decoration: none; font-size: 12px;"
                                       onclick="return confirm('Confirma a baixa deste lançamento?')">
                                        Dar Baixa
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Contas a Receber -->
<div class="card">
    <h3 class="card-title" style="color: #28a745;">💰 Contas a Receber</h3>
    
    <?php if (empty($contas_receber)): ?>
        <p style="text-align: center; color: #666; padding: 20px;">
            Nenhuma conta a receber pendente.
        </p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Vencimento</th>
                        <th>Descrição</th>
                        <th>Centro de Custo</th>
                        <th>Valor</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contas_receber as $conta): ?>
                        <?php
                        $vencimento = new DateTime($conta['data']);
                        $hoje = new DateTime();
                        $dias_vencimento = $vencimento->diff($hoje)->days;
                        $vencido = $vencimento < $hoje;
                        ?>
                        <tr style="<?php echo $vencido ? 'background-color: #f8d7da;' : ''; ?>">
                            <td>
                                <?php echo date('d/m/Y', strtotime($conta['data'])); ?>
                                <?php if ($vencido): ?>
                                    <br><small style="color: #dc3545; font-weight: bold;">Vencido há <?php echo $dias_vencimento; ?> dia(s)</small>
                                <?php elseif ($dias_vencimento <= 7): ?>
                                    <br><small style="color: #ffc107; font-weight: bold;">Vence em <?php echo $dias_vencimento; ?> dia(s)</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($conta['categoria']); ?></strong><br>
                                <small><?php echo htmlspecialchars($conta['subcategoria']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($conta['centro_custo']); ?></td>
                            <td style="font-weight: bold; color: #28a745;">
                                <?php echo formatarMoeda($conta['valor']); ?>
                            </td>
                            <td>
                                <?php if ($conta['origem'] === 'parcela'): ?>
                                    Parcela <?php echo $conta['parcelas']; ?>
                                <?php else: ?>
                                    À Vista
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="color: #dc3545; font-weight: bold;">⏳ Pendente</span>
                            </td>
                            <td>
                                <?php if ($conta['origem'] === 'parcela'): ?>
                                    <a href="?dar_baixa_parcela=<?php echo $conta['id']; ?>" 
                                       style="color: #28a745; text-decoration: none; font-size: 12px;"
                                       onclick="return confirm('Confirma o recebimento desta parcela?')">
                                        Dar Baixa
                                    </a>
                                <?php else: ?>
                                    <a href="?dar_baixa_lancamento=<?php echo $conta['id']; ?>" 
                                       style="color: #28a745; text-decoration: none; font-size: 12px;"
                                       onclick="return confirm('Confirma o recebimento deste lançamento?')">
                                        Dar Baixa
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
