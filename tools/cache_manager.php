<?php
// ARCHIVO: tools/cache_manager.php
// Herramienta para gestión y limpieza de cache

// Inicializar tiempo de solicitud
define('REQUEST_START_TIME', microtime(true));

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

// Manejo de preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Cargar dependencias
require_once(__DIR__ . '/../lib/Logger.php');

try {
    // Cargar configuración
    $config = include(__DIR__ . '/../config/config.php');
    $logger = new Logger($config['logging']);

    // Registrar acceso
    $logger->logAccess('tools/cache_manager.php', $_SERVER['REQUEST_METHOD']);

    $cacheDir = $config['optimization']['cache_directory'];

    // Manejar solicitudes
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGetRequest($cacheDir);
            break;
        case 'POST':
            handlePostRequest($cacheDir, $logger);
            break;
        default:
            throw new Exception('Método no soportado');
    }
} catch (Exception $e) {
    $logger = $logger ?? new Logger(['log_directory' => 'logs/', 'log_level' => 'ERROR']);
    $logger->error("Error en cache manager", [
        'error' => $e->getMessage(),
        'method' => $_SERVER['REQUEST_METHOD']
    ]);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Manejo de solicitudes GET - Obtener información del cache
 */
function handleGetRequest($cacheDir)
{
    $stats = getCacheStats($cacheDir);

    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
}

/**
 * Manejo de solicitudes POST - Ejecutar acciones
 */
function handlePostRequest($cacheDir, $logger)
{
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'clear_cache':
            $result = clearCache($cacheDir, $logger);
            break;
        case 'clear_old_cache':
            $days = $input['days'] ?? 30;
            $result = clearOldCache($cacheDir, $days, $logger);
            break;
        case 'optimize_cache':
            $result = optimizeCache($cacheDir, $logger);
            break;
        case 'rebuild_cache':
            $result = rebuildCache($cacheDir, $logger);
            break;
        default:
            throw new Exception('Acción no válida');
    }

    echo json_encode($result);
}

/**
 * Obtiene estadísticas del cache
 */
function getCacheStats($cacheDir)
{
    $stats = [
        'total_files' => 0,
        'total_size' => 0,
        'by_format' => [],
        'by_age' => ['day' => 0, 'week' => 0, 'month' => 0, 'older' => 0],
        'oldest_file' => null,
        'newest_file' => null
    ];

    if (!is_dir($cacheDir)) {
        return $stats;
    }

    $now = time();
    $files = glob($cacheDir . '*');

    foreach ($files as $file) {
        if (!is_file($file)) continue;

        $stats['total_files']++;
        $stats['total_size'] += filesize($file);

        // Por formato
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $stats['by_format'][$ext] = ($stats['by_format'][$ext] ?? 0) + 1;

        // Por edad
        $age = $now - filemtime($file);
        if ($age < 86400) { // 1 día
            $stats['by_age']['day']++;
        } elseif ($age < 604800) { // 1 semana
            $stats['by_age']['week']++;
        } elseif ($age < 2592000) { // 1 mes
            $stats['by_age']['month']++;
        } else {
            $stats['by_age']['older']++;
        }

        // Archivos más antiguos/nuevos
        $mtime = filemtime($file);
        if (!$stats['oldest_file'] || $mtime < $stats['oldest_file']['time']) {
            $stats['oldest_file'] = ['file' => basename($file), 'time' => $mtime];
        }
        if (!$stats['newest_file'] || $mtime > $stats['newest_file']['time']) {
            $stats['newest_file'] = ['file' => basename($file), 'time' => $mtime];
        }
    }

    return $stats;
}

/**
 * Limpia todo el cache
 */
function clearCache($cacheDir, $logger)
{
    $deleted = 0;
    $errors = 0;
    $sizeSaved = 0;

    if (!is_dir($cacheDir)) {
        return [
            'success' => true,
            'message' => 'Directorio de cache no existe',
            'deleted' => 0,
            'size_saved' => 0
        ];
    }

    $files = glob($cacheDir . '*');

    foreach ($files as $file) {
        if (is_file($file)) {
            $fileSize = filesize($file);
            if (unlink($file)) {
                $deleted++;
                $sizeSaved += $fileSize;
            } else {
                $errors++;
            }
        }
    }

    $logger->info("Cache limpiado", [
        'files_deleted' => $deleted,
        'errors' => $errors,
        'size_saved' => $sizeSaved
    ]);

    return [
        'success' => true,
        'message' => "Cache limpiado: $deleted archivos eliminados",
        'deleted' => $deleted,
        'errors' => $errors,
        'size_saved' => formatBytes($sizeSaved)
    ];
}

/**
 * Limpia cache antiguo
 */
function clearOldCache($cacheDir, $days, $logger)
{
    $deleted = 0;
    $sizeSaved = 0;
    $cutoff = time() - ($days * 24 * 60 * 60);

    if (!is_dir($cacheDir)) {
        return [
            'success' => true,
            'message' => 'Directorio de cache no existe',
            'deleted' => 0
        ];
    }

    $files = glob($cacheDir . '*');

    foreach ($files as $file) {
        if (is_file($file) && filemtime($file) < $cutoff) {
            $fileSize = filesize($file);
            if (unlink($file)) {
                $deleted++;
                $sizeSaved += $fileSize;
            }
        }
    }

    $logger->info("Cache antiguo limpiado", [
        'files_deleted' => $deleted,
        'days' => $days,
        'size_saved' => $sizeSaved
    ]);

    return [
        'success' => true,
        'message' => "Archivos antiguos eliminados: $deleted archivos (>$days días)",
        'deleted' => $deleted,
        'size_saved' => formatBytes($sizeSaved)
    ];
}

/**
 * Optimiza el cache eliminando duplicados y reorganizando
 */
function optimizeCache($cacheDir, $logger)
{
    $duplicates = 0;
    $sizeSaved = 0;
    $checksums = [];

    if (!is_dir($cacheDir)) {
        return [
            'success' => true,
            'message' => 'Directorio de cache no existe',
            'optimized' => 0
        ];
    }

    $files = glob($cacheDir . '*');

    // Buscar duplicados por checksum
    foreach ($files as $file) {
        if (!is_file($file)) continue;

        $checksum = md5_file($file);
        if (isset($checksums[$checksum])) {
            // Archivo duplicado encontrado
            $fileSize = filesize($file);
            if (unlink($file)) {
                $duplicates++;
                $sizeSaved += $fileSize;
            }
        } else {
            $checksums[$checksum] = $file;
        }
    }

    $logger->info("Cache optimizado", [
        'duplicates_removed' => $duplicates,
        'size_saved' => $sizeSaved
    ]);

    return [
        'success' => true,
        'message' => "Cache optimizado: $duplicates duplicados eliminados",
        'optimized' => $duplicates,
        'size_saved' => formatBytes($sizeSaved)
    ];
}

/**
 * Reconstruye el cache eliminando archivos huérfanos
 */
function rebuildCache($cacheDir, $logger)
{
    $orphaned = 0;
    $sizeSaved = 0;
    $uploadsDir = 'uploads/';

    if (!is_dir($cacheDir) || !is_dir($uploadsDir)) {
        return [
            'success' => false,
            'message' => 'Directorios requeridos no existen'
        ];
    }

    // Obtener lista de archivos originales
    $originalFiles = [];
    foreach (glob($uploadsDir . '*') as $file) {
        if (is_file($file)) {
            $originalFiles[] = basename($file);
        }
    }

    // Verificar archivos de cache
    $cacheFiles = glob($cacheDir . '*');
    foreach ($cacheFiles as $cacheFile) {
        if (!is_file($cacheFile)) continue;

        $isOrphan = true;
        $cacheFileName = basename($cacheFile);

        // Verificar si el archivo de cache corresponde a algún original
        foreach ($originalFiles as $originalFile) {
            $hash = md5($originalFile . 'variations'); // Simplificado
            if (strpos($cacheFileName, $hash) !== false) {
                $isOrphan = false;
                break;
            }
        }

        if ($isOrphan) {
            $fileSize = filesize($cacheFile);
            if (unlink($cacheFile)) {
                $orphaned++;
                $sizeSaved += $fileSize;
            }
        }
    }

    $logger->info("Cache reconstruido", [
        'orphaned_removed' => $orphaned,
        'size_saved' => $sizeSaved
    ]);

    return [
        'success' => true,
        'message' => "Cache reconstruido: $orphaned archivos huérfanos eliminados",
        'orphaned' => $orphaned,
        'size_saved' => formatBytes($sizeSaved)
    ];
}

/**
 * Formatea bytes en formato legible
 */
function formatBytes($bytes)
{
    if ($bytes === 0) return '0 B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
