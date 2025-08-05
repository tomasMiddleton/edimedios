<?php
// ARCHIVO: security_manager.php
// Endpoint para gestionar configuración de seguridad

header('Content-Type: application/json; charset=utf-8');

// Cargar sistema de seguridad
require_once(__DIR__ . '/lib/SecurityManager.php');

try {
    $security = new SecurityManager();
    $security->applyCORS(); // Solo CORS para este endpoint

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'get_config':
            handleGetConfig($security);
            break;

        case 'update_config':
            handleUpdateConfig($security);
            break;

        case 'test_cors':
            handleTestCORS($security);
            break;

        case 'get_security_logs':
            handleGetSecurityLogs();
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'error' => 'Acción no válida',
                'valid_actions' => ['get_config', 'update_config', 'test_cors', 'get_security_logs']
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'message' => 'Error en sistema de seguridad'
    ]);
}

/**
 * Obtener configuración actual
 */
function handleGetConfig($security)
{
    try {
        $config = $security->getConfig();

        echo json_encode([
            'success' => true,
            'config' => $config,
            'version' => $config['version'] ?? '1.0.0',
            'last_updated' => $config['last_updated'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => $e->getMessage(),
            'message' => 'Error obteniendo configuración'
        ]);
    }
}

/**
 * Actualizar configuración
 */
function handleUpdateConfig($security)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido. Use POST.']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $newConfig = $input['config'] ?? [];

    if (empty($newConfig)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Configuración requerida',
            'message' => 'Debe enviar el objeto config'
        ]);
        return;
    }

    try {
        // Validar configuración antes de guardar
        $validationErrors = validateSecurityConfig($newConfig);

        if (!empty($validationErrors)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Configuración inválida',
                'validation_errors' => $validationErrors
            ]);
            return;
        }

        // Actualizar cada sección
        foreach ($newConfig as $section => $data) {
            $security->updateConfig($section, $data, 'dashboard_user');
        }

        echo json_encode([
            'success' => true,
            'message' => 'Configuración actualizada correctamente',
            'timestamp' => date('c')
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => $e->getMessage(),
            'message' => 'Error guardando configuración'
        ]);
    }
}

/**
 * Validar configuración de seguridad
 */
function validateSecurityConfig($config)
{
    $errors = [];

    // Validar CORS
    if (isset($config['cors'])) {
        $cors = $config['cors'];

        if (isset($cors['allowed_origins']) && !is_array($cors['allowed_origins'])) {
            $errors[] = 'cors.allowed_origins debe ser un array';
        }

        if (isset($cors['max_age']) && (!is_numeric($cors['max_age']) || $cors['max_age'] < 0)) {
            $errors[] = 'cors.max_age debe ser un número positivo';
        }
    }

    // Validar Rate Limiting
    if (isset($config['rate_limiting'])) {
        $rl = $config['rate_limiting'];

        if (isset($rl['requests_per_minute']) && (!is_numeric($rl['requests_per_minute']) || $rl['requests_per_minute'] < 1)) {
            $errors[] = 'rate_limiting.requests_per_minute debe ser un número mayor a 0';
        }

        if (isset($rl['requests_per_hour']) && (!is_numeric($rl['requests_per_hour']) || $rl['requests_per_hour'] < 1)) {
            $errors[] = 'rate_limiting.requests_per_hour debe ser un número mayor a 0';
        }
    }

    // Validar File Upload
    if (isset($config['file_upload'])) {
        $fu = $config['file_upload'];

        if (isset($fu['max_file_size_mb']) && (!is_numeric($fu['max_file_size_mb']) || $fu['max_file_size_mb'] < 1)) {
            $errors[] = 'file_upload.max_file_size_mb debe ser un número mayor a 0';
        }

        if (isset($fu['allowed_extensions']) && !is_array($fu['allowed_extensions'])) {
            $errors[] = 'file_upload.allowed_extensions debe ser un array';
        }
    }

    return $errors;
}

/**
 * Probar configuración CORS
 */
function handleTestCORS($security)
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? $_GET['origin'] ?? '';

    if (empty($origin)) {
        echo json_encode([
            'success' => false,
            'message' => 'No se proporcionó origen para probar',
            'help' => 'Agregue ?origin=https://ejemplo.com para probar'
        ]);
        return;
    }

    try {
        $config = $security->getConfig('cors');
        $allowedOrigins = $config['allowed_origins'] ?? [];

        // Simular verificación CORS
        $isAllowed = false;
        foreach ($allowedOrigins as $allowed) {
            if ($allowed === '*' || $allowed === $origin) {
                $isAllowed = true;
                break;
            }

            // Soporte para wildcards
            if (strpos($allowed, '*') !== false) {
                $pattern = str_replace('*', '.*', preg_quote($allowed, '/'));
                if (preg_match("/^$pattern$/i", $origin)) {
                    $isAllowed = true;
                    break;
                }
            }
        }

        echo json_encode([
            'success' => true,
            'origin' => $origin,
            'allowed' => $isAllowed,
            'message' => $isAllowed ? 'Origen permitido' : 'Origen bloqueado',
            'allowed_origins' => $allowedOrigins
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => $e->getMessage(),
            'message' => 'Error probando CORS'
        ]);
    }
}

/**
 * Obtener logs de seguridad
 */
function handleGetSecurityLogs()
{
    $limit = min((int)($_GET['limit'] ?? 50), 200);
    $logFile = __DIR__ . '/logs/security.log';

    if (!file_exists($logFile)) {
        echo json_encode([
            'success' => true,
            'logs' => [],
            'message' => 'No hay logs de seguridad aún'
        ]);
        return;
    }

    try {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = [];

        // Obtener últimas líneas
        $recentLines = array_slice($lines, -$limit);

        foreach ($recentLines as $line) {
            $logEntry = json_decode($line, true);
            if ($logEntry) {
                $logs[] = $logEntry;
            }
        }

        // Ordenar por timestamp descendente
        usort($logs, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        echo json_encode([
            'success' => true,
            'logs' => $logs,
            'total_lines' => count($lines),
            'returned_logs' => count($logs)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => $e->getMessage(),
            'message' => 'Error leyendo logs de seguridad'
        ]);
    }
}
