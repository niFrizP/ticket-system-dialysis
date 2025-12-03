<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q === '' || mb_strlen($q) < 3) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Ingrese al menos 3 caracteres para buscar'
    ]);
    exit;
}

$q = mb_substr($q, 0, 50);

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare('SELECT 
        cm.id AS centro_id,
        cm.nombre AS centro_nombre,
        cm.cliente_id,
        COALESCE(c.nombre, "") AS cliente_nombre
    FROM centros_medicos cm
    LEFT JOIN clientes c ON cm.cliente_id = c.id AND (c.activo = 1 OR c.activo IS NULL)
    WHERE cm.activo = 1 AND cm.nombre LIKE :term
    ORDER BY cm.nombre
    LIMIT 5');
    $term = '%' . $q . '%';
    $stmt->bindParam(':term', $term, PDO::PARAM_STR);
    $stmt->execute();

    $centros = array_map(function ($row) {
        return [
            'id' => (int)$row['centro_id'],
            'nombre' => $row['centro_nombre'] ?? '',
            'cliente_id' => isset($row['cliente_id']) ? (int)$row['cliente_id'] : null,
            'cliente_nombre' => $row['cliente_nombre'] ?? ''
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);

    echo json_encode([
        'success' => true,
        'results' => $centros
    ]);
} catch (Exception $e) {
    error_log('Error en buscar_clientes.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al buscar centros'
    ]);
}
