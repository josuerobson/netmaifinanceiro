<?php
require_once '../includes/config.php';
verificarLogin();

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-t');
$usuario_id = $_SESSION['usuario_id'];

// Buscar dados do usuário
$stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

// Buscar dados do relatório
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) as total_entradas,
        SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END) as total_saidas,
        COUNT(*) as total_lancamentos
    FROM lancamentos 
    WHERE usuario_id = ? AND data BETWEEN ? AND ?
");
$stmt->execute([$usuario_id, $data_inicio, $data_fim]);
$resumo = $stmt->fetch();

$total_entradas = floatval($resumo['total_entradas'] ?? 0);
$total_saidas = floatval($resumo['total_saidas'] ?? 0);
$saldo_periodo = $total_entradas - $total_saidas;

// Buscar lançamentos detalhados
$stmt = $pdo->prepare("
    SELECT 
        l.data,
        l.tipo,
        l.valor,
        cc.nome as centro_custo,
        c.nome as categoria,
        s.nome as subcategoria,
        l.forma_pagamento,
        l.parcelas
    FROM lancamentos l
    JOIN centro_custos cc ON l.centro_custo_id = cc.id
    JOIN subcategorias s ON l.subcategoria_id = s.id
    JOIN categorias c ON s.categoria_id = c.id
    WHERE l.usuario_id = ? AND l.data BETWEEN ? AND ?
    ORDER BY l.data DESC, l.id DESC
");
$stmt->execute([$usuario_id, $data_inicio, $data_fim]);
$lancamentos = $stmt->fetchAll();

// Gerar HTML do relatório
$html = '
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório Financeiro</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { width: 100px; }
        .resumo { background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .resumo-item { display: inline-block; width: 30%; margin-right: 3%; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .entrada { color: #28a745; font-weight: bold; }
        .saida { color: #dc3545; font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Netmai - Sistema Financeiro</h1>
        <h2>Relatório Financeiro</h2>
        <p>Período: ' . date('d/m/Y', strtotime($data_inicio)) . ' a ' . date('d/m/Y', strtotime($data_fim)) . '</p>
        <p>Usuário: ' . htmlspecialchars($usuario['nome']) . '</p>
        <p>Gerado em: ' . date('d/m/Y H:i:s') . '</p>
    </div>
    
    <div class="resumo">
        <h3>Resumo do Período</h3>
        <div class="resumo-item">
            <strong>Total de Entradas:</strong><br>
            <span class="entrada">' . formatarMoeda($total_entradas) . '</span>
        </div>
        <div class="resumo-item">
            <strong>Total de Saídas:</strong><br>
            <span class="saida">' . formatarMoeda($total_saidas) . '</span>
        </div>
        <div class="resumo-item">
            <strong>Saldo do Período:</strong><br>
            <span style="color: ' . ($saldo_periodo >= 0 ? '#28a745' : '#dc3545') . '; font-weight: bold;">' . formatarMoeda($saldo_periodo) . '</span>
        </div>
        <div style="clear: both; margin-top: 15px;">
            <strong>Total de Lançamentos:</strong> ' . $resumo['total_lancamentos'] . '
        </div>
    </div>
    
    <h3>Lançamentos Detalhados</h3>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Tipo</th>
                <th>Centro de Custo</th>
                <th>Categoria > Subcategoria</th>
                <th>Valor</th>
                <th>Forma Pagamento</th>
            </tr>
        </thead>
        <tbody>';

foreach ($lancamentos as $lancamento) {
    $classe_tipo = $lancamento['tipo'] === 'entrada' ? 'entrada' : 'saida';
    $forma_pagamento = $lancamento['forma_pagamento'] === 'a_vista' ? 'À Vista' : $lancamento['parcelas'] . 'x';
    
    $html .= '
            <tr>
                <td>' . date('d/m/Y', strtotime($lancamento['data'])) . '</td>
                <td class="' . $classe_tipo . '">' . ucfirst($lancamento['tipo']) . '</td>
                <td>' . htmlspecialchars($lancamento['centro_custo']) . '</td>
                <td>' . htmlspecialchars($lancamento['categoria']) . ' > ' . htmlspecialchars($lancamento['subcategoria']) . '</td>
                <td class="' . $classe_tipo . '">' . formatarMoeda($lancamento['valor']) . '</td>
                <td>' . $forma_pagamento . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>
    
    <div class="footer">
        <p>Relatório gerado pelo Sistema Financeiro Netmai</p>
        <p>Este documento foi gerado automaticamente em ' . date('d/m/Y H:i:s') . '</p>
    </div>
</body>
</html>';

// Configurar headers para download do PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="relatorio_financeiro_' . date('Y-m-d') . '.pdf"');

// Usar biblioteca para gerar PDF (simulação - em produção usar TCPDF, FPDF ou similar)
// Por simplicidade, vamos retornar o HTML que pode ser convertido para PDF pelo navegador
header('Content-Type: text/html; charset=utf-8');
echo $html;
?>
