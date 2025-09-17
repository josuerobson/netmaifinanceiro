<?php
require_once '../includes/config.php';
verificarLogin();

header('Content-Type: application/json');

try {
    // Buscar contas a pagar (lançamentos não pagos + parcelas não pagas)
    $stmt = $pdo->prepare("
        SELECT 
            l.valor,
            l.data as data_vencimento,
            cc.nome as centro_custo,
            c.nome as categoria_nome,
            s.nome as subcategoria_nome,
            'À Vista' as parcela_info
        FROM lancamentos l
        JOIN centro_custos cc ON l.centro_custo_id = cc.id
        JOIN subcategorias s ON l.subcategoria_id = s.id
        JOIN categorias c ON s.categoria_id = c.id
        WHERE l.usuario_id = ? 
        AND l.tipo = 'saida'
        AND l.pago = 0
        AND l.forma_pagamento = 'a_vista'
        AND l.data <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        
        UNION ALL
        
        SELECT 
            p.valor,
            p.data_vencimento,
            cc.nome as centro_custo,
            c.nome as categoria_nome,
            s.nome as subcategoria_nome,
            CONCAT(p.numero_parcela, '/', l.parcelas) as parcela_info
        FROM parcelas p
        JOIN lancamentos l ON p.lancamento_id = l.id
        JOIN centro_custos cc ON l.centro_custo_id = cc.id
        JOIN subcategorias s ON l.subcategoria_id = s.id
        JOIN categorias c ON s.categoria_id = c.id
        WHERE l.usuario_id = ? 
        AND l.tipo = 'saida'
        AND p.pago = 0
        AND p.data_vencimento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        
        ORDER BY data_vencimento ASC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['usuario_id'], $_SESSION['usuario_id']]);
    $contas = $stmt->fetchAll();
    
    // Formatar dados para retorno
    $resultado = [];
    foreach ($contas as $conta) {
        $resultado[] = [
            'valor' => floatval($conta['valor']),
            'data_vencimento' => $conta['data_vencimento'],
            'centro_custo' => $conta['centro_custo'],
            'descricao' => $conta['categoria_nome'] . ' > ' . $conta['subcategoria_nome'],
            'parcela_info' => $conta['parcela_info']
        ];
    }
    
    echo json_encode($resultado);
    
} catch (PDOException $e) {
    echo json_encode([]);
}
?>
