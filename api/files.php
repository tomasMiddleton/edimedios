<?php
// ARCHIVO: api/files.php
// API REST para gestión de archivos

// Inicializar tiempo de solicitud
define('REQUEST_START_TIME', microtime(true));

// Headers de API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');

// Manejo de preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Cargar dependencias
require_once(__DIR__ . '/../lib/FileHandler.php');
require_once(__DIR__ . '/../lib/Logger.php');

try {
    // Cargar configuración
    $config = include(__DIR__ . '/../config/config.php');

    // Verificar si la API está habilitada
    if (!$config['api']['enable_api']) {
        throw new Exception('API deshabilitada');
    }

    $fileHandler = new FileHandler($config);
    $logger = new Logger($config['logging']);

    // Parsear la ruta
    $requestUri = $_SERVER['REQUEST_URI'];
    $path = parse_url($requestUri, PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));

    // Obtener método HTTP
    $method = $_SERVER['REQUEST_METHOD'];

    // Registrar acceso
    $logger->logAccess("api/files.php", $method);

    // Enrutamiento de la API
    switch ($method) {
        case 'GET':
            handleGetRequest($fileHandler, $pathParts);
            break;
        case 'POST':
            handlePostRequest($fileHandler, $logger);
            break;
        case 'DELETE':
            handleDeleteRequest($fileHandler, $pathParts, $logger);
            break;
        default:
            throw new Exception('Método no soportado');
    }
} catch (Exception $e) {
    $logger = $logger ?? new Logger(['log_directory' => 'logs/', 'log_level' => 'ERROR']);
    $logger->error("Error en API", [
        'error' => $e->getMessage(),
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => $_SERVER['REQUEST_URI']
    ]);

    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'success' => false
    ]);
}

/**
 * Manejo de solicitudes GET
 */
function handleGetRequest($fileHandler, $pathParts)
{
    // GET /api/files - Listar todos los archivos
    if (empty($pathParts) || $pathParts[count($pathParts) - 1] === 'files.php') {
        $files = $fileHandler->listFiles();

        // Agregar URLs de optimización a cada archivo
        foreach ($files as &$file) {
            $file['urls'] = generateOptimizationUrls($file['name']);
        }

        echo json_encode([
            'success' => true,
            'files' => $files,
            'total' => count($files),
            'total_size' => array_sum(array_column($files, 'size'))
        ]);
        return;
    }

    // GET /api/files/{filename} - Obtener información de un archivo específico
    $fileName = end($pathParts);
    if ($fileName) {
        $metadata = $fileHandler->getFileMetadata($fileName);
        if ($metadata) {
            $metadata['urls'] = generateOptimizationUrls($fileName);
            echo json_encode([
                'success' => true,
                'file' => $metadata
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Archivo no encontrado'
            ]);
        }
        return;
    }

    // GET /api/files/stats - Estadísticas
    if (in_array('stats', $pathParts)) {
        $files = $fileHandler->listFiles();
        $stats = [
            'total_files' => count($files),
            'total_size' => array_sum(array_column($files, 'size')),
            'formats' => [],
            'recent_uploads' => []
        ];

        // Contar formatos
        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $stats['formats'][$ext] = ($stats['formats'][$ext] ?? 0) + 1;
        }

        // Archivos recientes (últimos 10)
        usort($files, function ($a, $b) {
            return $b['modified'] - $a['modified'];
        });
        $stats['recent_uploads'] = array_slice($files, 0, 10);

        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        return;
    }
}

/**
 * Manejo de solicitudes POST (upload)
 */
function handlePostRequest($fileHandler, $logger)
{
    if (empty($_FILES)) {
        throw new Exception('No se recibieron archivos');
    }

    $uploadedFiles = [];
    $errors = [];

    foreach ($_FILES as $fieldName => $file) {
        try {
            if (is_array($file['name'])) {
                // Múltiples archivos
                for ($i = 0; $i < count($file['name']); $i++) {
                    $singleFile = [
                        'name' => $file['name'][$i],
                        'type' => $file['type'][$i],
                        'tmp_name' => $file['tmp_name'][$i],
                        'error' => $file['error'][$i],
                        'size' => $file['size'][$i]
                    ];

                    if ($singleFile['error'] === UPLOAD_ERR_OK) {
                        $result = $fileHandler->uploadFile($singleFile);
                        $result['urls'] = generateOptimizationUrls($result['stored_name']);
                        $uploadedFiles[] = $result;
                    }
                }
            } else {
                // Archivo único
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $result = $fileHandler->uploadFile($file);
                    $result['urls'] = generateOptimizationUrls($result['stored_name']);
                    $uploadedFiles[] = $result;
                }
            }
        } catch (Exception $e) {
            $errors[] = [
                'field' => $fieldName,
                'error' => $e->getMessage()
            ];
        }
    }

    echo json_encode([
        'success' => !empty($uploadedFiles),
        'files' => $uploadedFiles,
        'errors' => $errors,
        'total_uploaded' => count($uploadedFiles),
        'total_errors' => count($errors)
    ]);
}

/**
 * Manejo de solicitudes DELETE
 */
function handleDeleteRequest($fileHandler, $pathParts, $logger)
{
    $fileName = end($pathParts);
    if (!$fileName) {
        throw new Exception('Nombre de archivo requerido');
    }

    $filePath = 'uploads/' . $fileName;
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Archivo no encontrado'
        ]);
        return;
    }

    // Eliminar archivo
    if (unlink($filePath)) {
        // Eliminar metadata
        $metadataFile = 'storage/metadata/' . md5($fileName) . '.json';
        if (file_exists($metadataFile)) {
            unlink($metadataFile);
        }

        // Eliminar cache relacionado
        $cacheFiles = glob('cache/*');
        foreach ($cacheFiles as $cacheFile) {
            $cacheContent = file_get_contents($cacheFile);
            if (strpos($cacheContent, $fileName) !== false) {
                unlink($cacheFile);
            }
        }

        $logger->info("Archivo eliminado", [
            'file' => $fileName,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Archivo eliminado exitosamente'
        ]);
    } else {
        throw new Exception('Error al eliminar el archivo');
    }
}

/**
 * Genera URLs de optimización para un archivo
 */
function generateOptimizationUrls($fileName)
{
    $baseUrl = getBaseUrl() . '/uploads/' . $fileName;

    return [
        'original' => $baseUrl,
        'thumbnail' => $baseUrl . '?w=200&h=200&fit=cover',
        'medium' => $baseUrl . '?w=640&h=480',
        'large' => $baseUrl . '?w=1200&h=800',
        'webp_medium' => $baseUrl . '?w=640&h=480&f=webp&q=85',
        'avif_medium' => $baseUrl . '?w=640&h=480&f=avif&q=80',
        'optimized' => $baseUrl . '?w=800&f=webp&q=90'
    ];
}

/**
 * Obtiene la URL base del sitio
 */
function getBaseUrl()
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    $path = str_replace('/api', '', $path);
    return $protocol . '://' . $host . $path;
}
