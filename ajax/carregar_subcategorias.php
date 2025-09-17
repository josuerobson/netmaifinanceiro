<?php
require_once '../includes/config.php';
verificarLogin();

header('Content-Type: application/json');

if (!isset($_POST['categoria_id']) || empty($_POST['categoria_id'])) {
    echo json_encode([]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT s.id, s.nome, c.nome as categoria_nome 
        FROM subcategorias s 
        JOIN categorias c ON s.categoria_id = c.id 
        WHERE s.categoria_id = ? 
        ORDER BY s.nome
    ");
    $stmt->execute([$_POST['categoria_id']]);
    $subcategorias = $stmt->fetchAll();
    
    echo json_encode($subcategorias);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>
