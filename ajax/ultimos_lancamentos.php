<?php
require_once '../includes/config.php';
verificarLogin();

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT 
            l.*,
            cc.nome as centro_custo_nome,
            c.nome as categoria_nome,
            s.nome as subcategoria_nome
        FROM lancamentos l
        JOIN centro_custos cc ON l.centro_custo_id = cc.id
        JOIN subcategorias s ON l.subcategoria_id = s.id
        JOIN categorias c ON s.categoria_id = c.id
        WHERE l.usuario_id = ?
        ORDER BY l.data DESC, l.id DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $lancamentos = $stmt->fetchAll();
    
    // Formatar dados para retorno
    $resultado = [];
    foreach ($lancamentos as $lancamento) {
        $resultado[] = [
            'id' => $lancamento['id'],
            'tipo' => $lancamento['tipo'],
            'valor' => floatval($lancamento['valor']),
            'data' => $lancamento['data'],
            'forma_pagamento' => $lancamento['forma_pagamento'],
            'parcelas' => $lancamento['parcelas'],
            'centro_custo_nome' => $lancamento['centro_custo_nome'],
            'categoria_nome' => $lancamento['categoria_nome'],
            'subcategoria_nome' => $lancamento['subcategoria_nome']
        ];
    }
    
    echo json_encode($resultado);
    
} catch (PDOException $e) {
    echo json_encode([]);
}
?>
