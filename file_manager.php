<?php
// ARCHIVO: file_manager.php
// Endpoint para gestión de archivos (listado, eliminación)

// Cargar sistema de seguridad
require_once(__DIR__ . '/lib/SecurityManager.php');

try {
    $security = new SecurityManager();
    $security->applySecurityChecks();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Security system error: ' . $e->getMessage()]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once(__DIR__ . '/lib/StatsManager.php');

try {
    $stats = new StatsManager();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
        case 'list':
            handleListFiles($stats);
            break;

        case 'delete_file':
            handleDeleteFile($stats);
            break;

        case 'delete_logs':
            handleDeleteLogs($stats);
            break;

        case 'delete_all_logs':
            handleDeleteAllLogs($stats);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'error' => 'Acción no válida',
                'valid_actions' => ['list', 'delete_file', 'delete_logs', 'delete_all_logs']
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'message' => 'Error interno del servidor'
    ]);
}

/**
 * Listar archivos para gestión
 */
function handleListFiles($stats)
{
    $type = $_GET['type'] ?? 'all'; // all, uploads, orphaned
    $limit = min((int)($_GET['limit'] ?? 50), 100); // Máximo 100

    try {
        $files = $stats->getFilesForDeletion($type, $limit);

        // Estadísticas adicionales
        $fileStats = [
            'total_files' => count($files),
            'database_files' => count(array_filter($files, function ($f) {
                return $f['type'] === 'database';
            })),
            'orphaned_files' => count(array_filter($files, function ($f) {
                return $f['type'] === 'orphaned';
            })),
            'total_size' => array_sum(array_column($files, 'size')),
            'total_views' => array_sum(array_column($files, 'view_count'))
        ];

        echo json_encode([
            'success' => true,
            'files' => $files,
            'stats' => $fileStats,
            'formatted_size' => $stats->formatFileSize($fileStats['total_size'])
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => $e->getMessage(),
            'message' => 'Error obteniendo lista de archivos'
        ]);
    }
}

/**
 * Eliminar archivo específico
 */
function handleDeleteFile($stats)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido. Use POST.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $filePath = $input['file_path'] ?? '';
    $confirm = $input['confirm'] ?? false;

    if (empty($filePath)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Ruta de archivo requerida',
            'message' => 'Debe especificar file_path'
        ]);
        return;
    }

    if (!$confirm) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Confirmación requerida',
            'message' => 'Debe enviar confirm: true para confirmar eliminación'
        ]);
        return;
    }

    try {
        $result = $stats->deleteFile($filePath, true);

        if (!empty($result['errors'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'result' => $result,
                'message' => 'Eliminación completada con errores: ' . implode(', ', $result['errors'])
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'result' => $result,
                'message' => '✅ Archivo eliminado completamente'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => $e->getMessage(),
            'message' => 'Error eliminando archivo'
        ]);
    }
}

/**
 * Eliminar logs por criterios
 */
function handleDeleteLogs($stats)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido. Use POST.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $activityType = $input['activity_type'] ?? '';
    $status = $input['status'] ?? null;
    $olderThanDays = $input['older_than_days'] ?? null;

    if (empty($activityType)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Tipo de actividad requerido',
            'message' => 'Debe especificar activity_type (upload, image_view, system, etc.)'
        ]);
        return;
    }

    try {
        $deletedCount = $stats->deleteLogsByType($activityType, $status, $olderThanDays);

        echo json_encode([
            'success' => true,
            'deleted_count' => $deletedCount,
            'message' => "✅ Se eliminaron $deletedCount registros de logs"
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => $e->getMessage(),
            'message' => 'Error eliminando logs'
        ]);
    }
}

/**
 * Eliminar TODOS los logs (PELIGROSO)
 */
function handleDeleteAllLogs($stats)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido. Use POST.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $confirmationCode = $input['confirmation_code'] ?? '';

    $expectedCode = 'DELETE_ALL_LOGS_' . date('Ymd');

    if (empty($confirmationCode)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Código de confirmación requerido',
            'message' => "Para eliminar TODOS los logs, envíe: confirmation_code: '$expectedCode'",
            'warning' => '⚠️ Esta acción es IRREVERSIBLE y eliminará TODOS los logs'
        ]);
        return;
    }

    try {
        $deletedCount = $stats->deleteAllLogs($confirmationCode);

        echo json_encode([
            'success' => true,
            'deleted_count' => $deletedCount,
            'message' => "⚠️ Se eliminaron TODOS los logs ($deletedCount registros)",
            'warning' => 'Esta acción fue registrada en el nuevo log'
        ]);
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Código de confirmación incorrecto') !== false) {
            http_response_code(400);
            echo json_encode([
                'error' => $e->getMessage(),
                'message' => 'Código de confirmación inválido'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'error' => $e->getMessage(),
                'message' => 'Error eliminando logs'
            ]);
        }
    }
}
