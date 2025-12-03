<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$logFile = __DIR__ . '/debug.log';
$appEnv = getenv('APP_ENV') ?: (getenv('ENV') ?: 'production');

function logBuscarEquipos($message, $context = [])
{
    global $logFile;
    $contextText = $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    file_put_contents($logFile, sprintf(
        '[%s] [buscar_equipos] %s%s%s',
        date('Y-m-d H:i:s'),
        $message,
        $contextText,
        PHP_EOL
    ), FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$centroId = isset($_GET['centro_id']) ? intval($_GET['centro_id']) : 0;

logBuscarEquipos('Solicitud recibida', ['centro_id' => $centroId, 'q' => $q]);

if ($centroId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Debe seleccionar un centro médico válido'
    ]);
    exit;
}

if ($q === '' || mb_strlen($q) < 1) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Ingrese al menos 1 carácter para buscar el equipo'
    ]);
    exit;
}

$q = mb_substr($q, 0, 50);

try {
    $db = Database::getInstance()->getConnection();
    logBuscarEquipos('Conexión obtenida correctamente');

    $stmt = $db->prepare('SELECT 
            e.id,
            e.id_maquina,
            e.codigo,
            e.marca,
            e.modelo
        FROM equipos e
        WHERE e.activo = 1
            AND e.centro_medico_id = :centro
            AND (
                e.id_maquina LIKE :term1
                OR e.codigo LIKE :term2
                OR e.marca LIKE :term3
                OR e.modelo LIKE :term4
            )
        ORDER BY e.id_maquina ASC
        LIMIT 5');
    $term = '%' . $q . '%';
    $stmt->bindValue(':centro', $centroId, PDO::PARAM_INT);
    $stmt->bindValue(':term1', $term, PDO::PARAM_STR);
    $stmt->bindValue(':term2', $term, PDO::PARAM_STR);
    $stmt->bindValue(':term3', $term, PDO::PARAM_STR);
    $stmt->bindValue(':term4', $term, PDO::PARAM_STR);
    $stmt->execute();

    $equipos = array_map(function ($row) {
        return [
            'id' => (int)($row['id'] ?? 0),
            'id_maquina' => $row['id_maquina'] ?? '',
            'codigo' => $row['codigo'] ?? '',
            'marca' => $row['marca'] ?? '',
            'modelo' => $row['modelo'] ?? ''
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);

    logBuscarEquipos('Consulta completada', ['resultados' => count($equipos)]);
    echo json_encode([
        'success' => true,
        'results' => $equipos
    ]);
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    logBuscarEquipos('Error en buscar_equipos.php', ['error' => $errorMessage]);
    error_log('Error en buscar_equipos.php: ' . $errorMessage);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $appEnv === 'production'
            ? 'Error al buscar equipos'
            : 'Error al buscar equipos: ' . $errorMessage
    ]);
}
