<?php
// ARCHIVO: upload_secure.php
// Versión segura y mejorada del sistema de carga de archivos
// MANTIENE RETROCOMPATIBILIDAD TOTAL con upload.php

// Inicializar tiempo de solicitud para logs
define('REQUEST_START_TIME', microtime(true));

// Cargar dependencias
require_once(__DIR__ . '/lib/FileHandler.php');
require_once(__DIR__ . '/lib/Logger.php');

// Headers de seguridad y CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Content-Type: application/json; charset=utf-8');

// Manejo de preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

try {
    // Cargar configuración
    $config = include(__DIR__ . '/config/config.php');
    $fileHandler = new FileHandler($config);
    $logger = new Logger($config['logging']);

    // Registrar acceso
    $logger->logAccess('upload_secure.php', 'POST');

    // Verificar si se enviaron archivos
    if (empty($_FILES)) {
        throw new Exception('No se recibieron archivos');
    }

    $uploadedFiles = [];
    $errors = [];

    // Procesar archivos
    foreach ($_FILES as $fieldName => $file) {
        try {
            // Manejar múltiples archivos o archivo único
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
                        $uploadedFiles[] = $result;
                    }
                }
            } else {
                // Archivo único
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $result = $fileHandler->uploadFile($file);
                    $uploadedFiles[] = $result;
                }
            }
        } catch (Exception $e) {
            $errors[] = [
                'field' => $fieldName,
                'error' => $e->getMessage()
            ];
            $logger->error("Error en carga de archivo", [
                'field' => $fieldName,
                'error' => $e->getMessage(),
                'file' => $file['name'] ?? 'unknown'
            ]);
        }
    }

    // Preparar respuesta
    $response = [
        'success' => !empty($uploadedFiles),
        'files' => $uploadedFiles,
        'errors' => $errors,
        'total_uploaded' => count($uploadedFiles),
        'total_errors' => count($errors)
    ];

    // RETROCOMPATIBILIDAD: Si solo hay un archivo y se usa FilePond
    // devolver solo el nombre del archivo como lo hace upload.php original
    if (count($uploadedFiles) === 1 && isset($_FILES['filepond']) && empty($errors)) {
        // Respuesta compatible con upload.php original
        echo $uploadedFiles[0]['stored_name'];
    } else {
        // Respuesta JSON moderna
        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    // Log de éxito
    $logger->info("Carga completada", [
        'files_uploaded' => count($uploadedFiles),
        'errors' => count($errors),
        'total_size' => array_sum(array_column($uploadedFiles, 'size'))
    ]);
} catch (Exception $e) {
    // Log del error
    $logger = $logger ?? new Logger(['log_directory' => 'logs/', 'log_level' => 'ERROR']);
    $logger->error("Error crítico en upload", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    // Respuesta de error
    http_response_code(500);

    // RETROCOMPATIBILIDAD: respuesta simple como upload.php
    if (isset($_FILES['filepond'])) {
        echo 'Error: ' . $e->getMessage();
    } else {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'files' => [],
            'total_uploaded' => 0
        ]);
    }
}
