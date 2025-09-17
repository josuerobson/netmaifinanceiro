<?php
require_once '../includes/config.php';
verificarLogin();

header('Content-Type: application/json');

try {
    $usuario_id = $_SESSION['usuario_id'];
    $mes_atual = date('Y-m');
    
    // Totais do mês atual
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) as total_entradas,
            SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END) as total_saidas
        FROM lancamentos 
        WHERE usuario_id = ? AND DATE_FORMAT(data, '%Y-%m') = ?
    ");
    $stmt->execute([$usuario_id, $mes_atual]);
    $totais = $stmt->fetch();
    
    $total_entradas = floatval($totais['total_entradas'] ?? 0);
    $total_saidas = floatval($totais['total_saidas'] ?? 0);
    $saldo_mes = $total_entradas - $total_saidas;
    
    // Contar parcelas pendentes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as contas_pendentes
        FROM parcelas p
        JOIN lancamentos l ON p.lancamento_id = l.id
        WHERE l.usuario_id = ? AND p.pago = 0
    ");
    $stmt->execute([$usuario_id]);
    $pendentes = $stmt->fetch();
    $contas_pendentes = intval($pendentes['contas_pendentes'] ?? 0);
    
    // Dados para gráfico de entradas vs saídas (últimos 6 meses)
    $meses = [];
    $entradas_por_mes = [];
    $saidas_por_mes = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $mes = date('Y-m', strtotime("-$i months"));
        $mes_nome = date('M/Y', strtotime("-$i months"));
        
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) as entradas,
                SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END) as saidas
            FROM lancamentos 
            WHERE usuario_id = ? AND DATE_FORMAT(data, '%Y-%m') = ?
        ");
        $stmt->execute([$usuario_id, $mes]);
        $dados_mes = $stmt->fetch();
        
        $meses[] = $mes_nome;
        $entradas_por_mes[] = floatval($dados_mes['entradas'] ?? 0);
        $saidas_por_mes[] = floatval($dados_mes['saidas'] ?? 0);
    }
    
    // Dados para gráfico de centro de custos (mês atual)
    $stmt = $pdo->prepare("
        SELECT 
            cc.nome,
            SUM(l.valor) as total
        FROM lancamentos l
        JOIN centro_custos cc ON l.centro_custo_id = cc.id
        WHERE l.usuario_id = ? AND DATE_FORMAT(l.data, '%Y-%m') = ?
        GROUP BY cc.id, cc.nome
        ORDER BY total DESC
        LIMIT 8
    ");
    $stmt->execute([$usuario_id, $mes_atual]);
    $centro_custos = $stmt->fetchAll();
    
    // Formatar dados de centro de custos
    $centro_custos_formatado = [];
    foreach ($centro_custos as $cc) {
        $centro_custos_formatado[] = [
            'nome' => $cc['nome'],
            'total' => floatval($cc['total'])
        ];
    }
    
    echo json_encode([
        'total_entradas' => $total_entradas,
        'total_saidas' => $total_saidas,
        'saldo_mes' => $saldo_mes,
        'contas_pendentes' => $contas_pendentes,
        'meses' => $meses,
        'entradas_por_mes' => $entradas_por_mes,
        'saidas_por_mes' => $saidas_por_mes,
        'centro_custos' => $centro_custos_formatado
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'total_entradas' => 0,
        'total_saidas' => 0,
        'saldo_mes' => 0,
        'contas_pendentes' => 0,
        'meses' => [],
        'entradas_por_mes' => [],
        'saidas_por_mes' => [],
        'centro_custos' => []
    ]);
}
?>
