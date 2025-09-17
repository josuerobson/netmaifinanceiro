<?php
require_once '../includes/config.php';
verificarLogin();

header('Content-Type: application/json');

try {
    $data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
    $data_fim = $_GET['data_fim'] ?? date('Y-m-t');
    
    // Calcular saldo atual (apenas transações pagas até hoje)
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) as total_entradas,
            SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END) as total_saidas
        FROM lancamentos 
        WHERE usuario_id = ? AND data <= CURDATE() AND pago = 1
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $saldo_atual_data = $stmt->fetch();
    
    $saldo_atual = ($saldo_atual_data['total_entradas'] ?? 0) - ($saldo_atual_data['total_saidas'] ?? 0);
    
    // Calcular entradas e saídas do período filtrado (apenas lançamentos pagos)
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) as entradas_periodo,
            SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END) as saidas_periodo
        FROM lancamentos 
        WHERE usuario_id = ? AND data BETWEEN ? AND ? AND pago = 1
    ");
    $stmt->execute([$_SESSION['usuario_id'], $data_inicio, $data_fim]);
    $periodo_data = $stmt->fetch();
    
    $entradas_periodo = $periodo_data['entradas_periodo'] ?? 0;
    $saidas_periodo = $periodo_data['saidas_periodo'] ?? 0;
    
    // Calcular saldo projetado (incluindo lançamentos não pagos e parcelas futuras do período)
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN l.tipo = 'entrada' THEN l.valor ELSE 0 END) as entradas_nao_pagas,
            SUM(CASE WHEN l.tipo = 'saida' THEN l.valor ELSE 0 END) as saidas_nao_pagas
        FROM lancamentos l
        WHERE l.usuario_id = ? 
        AND l.data BETWEEN ? AND ?
        AND l.pago = 0
        AND l.forma_pagamento = 'a_vista'
    ");
    $stmt->execute([$_SESSION['usuario_id'], $data_inicio, $data_fim]);
    $nao_pagas_data = $stmt->fetch();
    
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN l.tipo = 'entrada' THEN p.valor ELSE 0 END) as entradas_futuras,
            SUM(CASE WHEN l.tipo = 'saida' THEN p.valor ELSE 0 END) as saidas_futuras
        FROM parcelas p
        JOIN lancamentos l ON p.lancamento_id = l.id
        WHERE l.usuario_id = ? 
        AND p.data_vencimento BETWEEN ? AND ?
        AND p.pago = 0
    ");
    $stmt->execute([$_SESSION['usuario_id'], $data_inicio, $data_fim]);
    $futuras_data = $stmt->fetch();
    
    $entradas_nao_pagas = $nao_pagas_data['entradas_nao_pagas'] ?? 0;
    $saidas_nao_pagas = $nao_pagas_data['saidas_nao_pagas'] ?? 0;
    $entradas_futuras = $futuras_data['entradas_futuras'] ?? 0;
    $saidas_futuras = $futuras_data['saidas_futuras'] ?? 0;
    
    // Saldo projetado = saldo do período + lançamentos não pagos + parcelas futuras
    $saldo_periodo = $entradas_periodo - $saidas_periodo;
    $saldo_projetado = $saldo_periodo + $entradas_nao_pagas - $saidas_nao_pagas + $entradas_futuras - $saidas_futuras;
    
    echo json_encode([
        'saldo_atual' => floatval($saldo_atual),
        'entradas_periodo' => floatval($entradas_periodo),
        'saidas_periodo' => floatval($saidas_periodo),
        'saldo_periodo' => floatval($saldo_periodo),
        'saldo_projetado' => floatval($saldo_projetado),
        'entradas_futuras' => floatval($entradas_futuras),
        'saidas_futuras' => floatval($saidas_futuras)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'saldo_atual' => 0,
        'entradas_periodo' => 0,
        'saidas_periodo' => 0,
        'saldo_periodo' => 0,
        'saldo_projetado' => 0,
        'entradas_futuras' => 0,
        'saidas_futuras' => 0
    ]);
}
?>
