<?php
require_once '../includes/config.php';
verificarLogin();

header('Content-Type: application/json');

try {
    $usuario_id = $_SESSION['usuario_id'];
    $data_inicio = $_GET['data_inicio'] ?? date('Y-m-01', strtotime('-5 months'));
    $data_fim = $_GET['data_fim'] ?? date('Y-m-t');
    
    // Resumo do período
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) as total_entradas,
            SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END) as total_saidas
        FROM lancamentos 
        WHERE usuario_id = ? AND data BETWEEN ? AND ?
    ");
    $stmt->execute([$usuario_id, $data_inicio, $data_fim]);
    $resumo = $stmt->fetch();
    
    $total_entradas = floatval($resumo['total_entradas'] ?? 0);
    $total_saidas = floatval($resumo['total_saidas'] ?? 0);
    $saldo_periodo = $total_entradas - $total_saidas;
    
    // Calcular média mensal
    $inicio = new DateTime($data_inicio);
    $fim = new DateTime($data_fim);
    $meses = $inicio->diff($fim)->m + ($inicio->diff($fim)->y * 12) + 1;
    $media_mensal = $meses > 0 ? $saldo_periodo / $meses : 0;
    
    // Evolução mensal
    $evolucao_mensal = [
        'meses' => [],
        'entradas' => [],
        'saidas' => [],
        'saldo' => []
    ];
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(data, '%Y-%m') as mes,
            SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) as entradas,
            SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END) as saidas
        FROM lancamentos 
        WHERE usuario_id = ? AND data BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(data, '%Y-%m')
        ORDER BY mes
    ");
    $stmt->execute([$usuario_id, $data_inicio, $data_fim]);
    $dados_mensais = $stmt->fetchAll();
    
    foreach ($dados_mensais as $mes) {
        $evolucao_mensal['meses'][] = date('M/Y', strtotime($mes['mes'] . '-01'));
        $evolucao_mensal['entradas'][] = floatval($mes['entradas']);
        $evolucao_mensal['saidas'][] = floatval($mes['saidas']);
        $evolucao_mensal['saldo'][] = floatval($mes['entradas']) - floatval($mes['saidas']);
    }
    
    // Top 10 categorias
    $stmt = $pdo->prepare("
        SELECT 
            CONCAT(c.nome, ' > ', s.nome) as nome,
            SUM(l.valor) as total
        FROM lancamentos l
        JOIN subcategorias s ON l.subcategoria_id = s.id
        JOIN categorias c ON s.categoria_id = c.id
        WHERE l.usuario_id = ? AND l.data BETWEEN ? AND ?
        GROUP BY s.id
        ORDER BY total DESC
        LIMIT 10
    ");
    $stmt->execute([$usuario_id, $data_inicio, $data_fim]);
    $top_categorias = $stmt->fetchAll();
    
    $top_categorias_formatado = [];
    foreach ($top_categorias as $cat) {
        $top_categorias_formatado[] = [
            'nome' => $cat['nome'],
            'total' => floatval($cat['total'])
        ];
    }
    
    // Centro de custos
    $stmt = $pdo->prepare("
        SELECT 
            cc.nome,
            SUM(l.valor) as total
        FROM lancamentos l
        JOIN centro_custos cc ON l.centro_custo_id = cc.id
        WHERE l.usuario_id = ? AND l.data BETWEEN ? AND ?
        GROUP BY cc.id
        ORDER BY total DESC
    ");
    $stmt->execute([$usuario_id, $data_inicio, $data_fim]);
    $centro_custos = $stmt->fetchAll();
    
    $centro_custos_formatado = [];
    foreach ($centro_custos as $cc) {
        $centro_custos_formatado[] = [
            'nome' => $cc['nome'],
            'total' => floatval($cc['total'])
        ];
    }
    
    // Tendências (saldo acumulado)
    $stmt = $pdo->prepare("
        SELECT 
            data,
            SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END) as movimento_dia
        FROM lancamentos 
        WHERE usuario_id = ? AND data BETWEEN ? AND ?
        GROUP BY data
        ORDER BY data
    ");
    $stmt->execute([$usuario_id, $data_inicio, $data_fim]);
    $movimentos_diarios = $stmt->fetchAll();
    
    $tendencias = [
        'datas' => [],
        'saldo_acumulado' => []
    ];
    
    $saldo_acumulado = 0;
    foreach ($movimentos_diarios as $movimento) {
        $saldo_acumulado += floatval($movimento['movimento_dia']);
        $tendencias['datas'][] = date('d/m', strtotime($movimento['data']));
        $tendencias['saldo_acumulado'][] = $saldo_acumulado;
    }
    
    // Sazonalidade
    $sazonalidade = [
        'por_mes' => array_fill(0, 12, 0),
        'por_dia_semana' => array_fill(0, 7, 0)
    ];
    
    // Por mês
    $stmt = $pdo->prepare("
        SELECT 
            MONTH(data) as mes,
            AVG(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END) as media
        FROM lancamentos 
        WHERE usuario_id = ? AND data BETWEEN ? AND ?
        GROUP BY MONTH(data)
    ");
    $stmt->execute([$usuario_id, $data_inicio, $data_fim]);
    $dados_mes = $stmt->fetchAll();
    
    foreach ($dados_mes as $mes) {
        $sazonalidade['por_mes'][$mes['mes'] - 1] = floatval($mes['media']);
    }
    
    // Por dia da semana
    $stmt = $pdo->prepare("
        SELECT 
            DAYOFWEEK(data) as dia_semana,
            AVG(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END) as media
        FROM lancamentos 
        WHERE usuario_id = ? AND data BETWEEN ? AND ?
        GROUP BY DAYOFWEEK(data)
    ");
    $stmt->execute([$usuario_id, $data_inicio, $data_fim]);
    $dados_dia = $stmt->fetchAll();
    
    foreach ($dados_dia as $dia) {
        $sazonalidade['por_dia_semana'][$dia['dia_semana'] - 1] = floatval($dia['media']);
    }
    
    // Projeção (próximos 3 meses baseado na média)
    $projecao = [
        'meses' => [],
        'historico' => [],
        'projetado' => []
    ];
    
    // Últimos 6 meses históricos
    for ($i = 5; $i >= 0; $i--) {
        $mes = date('M/Y', strtotime("-$i months"));
        $projecao['meses'][] = $mes;
        
        $mes_inicio = date('Y-m-01', strtotime("-$i months"));
        $mes_fim = date('Y-m-t', strtotime("-$i months"));
        
        $stmt = $pdo->prepare("
            SELECT SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END) as saldo_mes
            FROM lancamentos 
            WHERE usuario_id = ? AND data BETWEEN ? AND ?
        ");
        $stmt->execute([$usuario_id, $mes_inicio, $mes_fim]);
        $saldo_mes = $stmt->fetch();
        
        $projecao['historico'][] = floatval($saldo_mes['saldo_mes'] ?? 0);
        $projecao['projetado'][] = null;
    }
    
    // Próximos 3 meses projetados
    for ($i = 1; $i <= 3; $i++) {
        $mes = date('M/Y', strtotime("+$i months"));
        $projecao['meses'][] = $mes;
        $projecao['historico'][] = null;
        $projecao['projetado'][] = $media_mensal;
    }
    
    // Comparativo com período anterior
    $dias_periodo = (new DateTime($data_inicio))->diff(new DateTime($data_fim))->days + 1;
    $data_anterior_inicio = date('Y-m-d', strtotime($data_inicio . " -$dias_periodo days"));
    $data_anterior_fim = date('Y-m-d', strtotime($data_fim . " -$dias_periodo days"));
    
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) as entradas_anterior,
            SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END) as saidas_anterior
        FROM lancamentos 
        WHERE usuario_id = ? AND data BETWEEN ? AND ?
    ");
    $stmt->execute([$usuario_id, $data_anterior_inicio, $data_anterior_fim]);
    $anterior = $stmt->fetch();
    
    $comparativo = [
        'anterior' => [
            'entradas' => floatval($anterior['entradas_anterior'] ?? 0),
            'saidas' => floatval($anterior['saidas_anterior'] ?? 0)
        ],
        'atual' => [
            'entradas' => $total_entradas,
            'saidas' => $total_saidas
        ]
    ];
    
    // Relatório detalhado por categoria
    $stmt = $pdo->prepare("
        SELECT 
            c.nome as categoria_nome,
            s.nome as subcategoria_nome,
            SUM(CASE WHEN l.tipo = 'entrada' THEN l.valor ELSE 0 END) as entradas,
            SUM(CASE WHEN l.tipo = 'saida' THEN l.valor ELSE 0 END) as saidas
        FROM lancamentos l
        JOIN subcategorias s ON l.subcategoria_id = s.id
        JOIN categorias c ON s.categoria_id = c.id
        WHERE l.usuario_id = ? AND l.data BETWEEN ? AND ?
        GROUP BY s.id
        ORDER BY c.nome, s.nome
    ");
    $stmt->execute([$usuario_id, $data_inicio, $data_fim]);
    $detalhado_categorias = $stmt->fetchAll();
    
    $detalhado_formatado = [];
    foreach ($detalhado_categorias as $item) {
        $detalhado_formatado[] = [
            'categoria_nome' => $item['categoria_nome'],
            'subcategoria_nome' => $item['subcategoria_nome'],
            'entradas' => floatval($item['entradas']),
            'saidas' => floatval($item['saidas'])
        ];
    }
    
    echo json_encode([
        'resumo' => [
            'total_entradas' => $total_entradas,
            'total_saidas' => $total_saidas,
            'saldo_periodo' => $saldo_periodo,
            'media_mensal' => $media_mensal
        ],
        'evolucao_mensal' => $evolucao_mensal,
        'top_categorias' => $top_categorias_formatado,
        'centro_custos' => $centro_custos_formatado,
        'tendencias' => $tendencias,
        'sazonalidade' => $sazonalidade,
        'projecao' => $projecao,
        'comparativo' => $comparativo,
        'detalhado_categorias' => $detalhado_formatado
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'resumo' => [
            'total_entradas' => 0,
            'total_saidas' => 0,
            'saldo_periodo' => 0,
            'media_mensal' => 0
        ],
        'evolucao_mensal' => ['meses' => [], 'entradas' => [], 'saidas' => [], 'saldo' => []],
        'top_categorias' => [],
        'centro_custos' => [],
        'tendencias' => ['datas' => [], 'saldo_acumulado' => []],
        'sazonalidade' => ['por_mes' => array_fill(0, 12, 0), 'por_dia_semana' => array_fill(0, 7, 0)],
        'projecao' => ['meses' => [], 'historico' => [], 'projetado' => []],
        'comparativo' => ['anterior' => ['entradas' => 0, 'saidas' => 0], 'atual' => ['entradas' => 0, 'saidas' => 0]],
        'detalhado_categorias' => []
    ]);
}
?>
