<?php
// ARCHIVO: upload_organized_v2.php
// Sistema de carga con estructura organizada + tracking de estadísticas
// Mantiene retrocompatibilidad total con upload.php y upload_secure.php

// Inicializar tiempo de solicitud para logs
define('REQUEST_START_TIME', microtime(true));

// Cargar StatsManager
require_once(__DIR__ . '/lib/StatsManager.php');

// Headers de seguridad y CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Verificar que se subió un archivo
if (!isset($_FILES['filepond']) || $_FILES['filepond']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No se subió ningún archivo válido']);
    exit;
}

$file = $_FILES['filepond'];

try {
    // Inicializar sistema de estadísticas
    $stats = new StatsManager();

    // Información del archivo
    $originalName = $file['name'];
    $tmpPath = $file['tmp_name'];
    $fileSize = $file['size'];
    $mimeType = $file['type'];

    // Validar extensión
    $fileInfo = pathinfo($originalName);
    $extension = strtolower($fileInfo['extension'] ?? '');
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

    if (!in_array($extension, $allowedExtensions)) {
        throw new Exception('Tipo de archivo no permitido');
    }

    // Validar tamaño (100MB máximo)
    if ($fileSize > 100 * 1024 * 1024) {
        throw new Exception('Archivo demasiado grande (máximo 100MB)');
    }

    // Crear estructura de directorios por año/mes
    $now = new DateTime();
    $year = $now->format('Y');
    $month = $now->format('m');

    $uploadsDir = 'uploads';
    $yearDir = $uploadsDir . '/' . $year;
    $monthDir = $yearDir . '/' . $month;

    // Crear directorios si no existen
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    if (!is_dir($yearDir)) {
        mkdir($yearDir, 0755, true);
    }
    if (!is_dir($monthDir)) {
        mkdir($monthDir, 0755, true);
    }

    // Generar nombre único
    $timestamp = time();
    $randomString = bin2hex(random_bytes(8));
    $newFileName = $timestamp . '_' . $randomString . '.' . $extension;

    // Ruta completa
    $relativePath = $year . '/' . $month . '/' . $newFileName;
    $fullPath = $monthDir . '/' . $newFileName;

    // Verificar que no existe (muy improbable)
    while (file_exists($fullPath)) {
        $randomString = bin2hex(random_bytes(8));
        $newFileName = $timestamp . '_' . $randomString . '.' . $extension;
        $relativePath = $year . '/' . $month . '/' . $newFileName;
        $fullPath = $monthDir . '/' . $newFileName;
    }

    // Mover archivo
    if (!move_uploaded_file($tmpPath, $fullPath)) {
        throw new Exception('Error al guardar el archivo');
    }

    // Crear metadatos
    $metadata = [
        'original_name' => $originalName,
        'stored_name' => $newFileName,
        'relative_path' => $relativePath,
        'size' => $fileSize,
        'mime_type' => $mimeType,
        'extension' => $extension,
        'upload_date' => $now->format('Y-m-d H:i:s'),
        'year' => $year,
        'month' => $month,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];

    // Guardar metadatos en archivo JSON
    $metadataDir = 'storage/metadata/' . $year . '/' . $month;
    if (!is_dir($metadataDir)) {
        mkdir($metadataDir, 0755, true);
    }

    $metadataFile = $metadataDir . '/' . pathinfo($newFileName, PATHINFO_FILENAME) . '.json';
    file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));

    // 📊 REGISTRAR EN BASE DE DATOS DE ESTADÍSTICAS
    $uploadId = $stats->recordUpload([
        'filename' => $newFileName,
        'original_name' => $originalName,
        'relative_path' => $relativePath,
        'file_size' => $fileSize,
        'mime_type' => $mimeType,
        'extension' => $extension,
        'year' => (int)$year,
        'month' => (int)$month,
        'upload_date' => $metadata['upload_date'],
        'ip_address' => $metadata['ip_address'],
        'user_agent' => $metadata['user_agent']
    ]);

    // Respuesta de éxito
    $response = [
        'success' => true,
        'file' => [
            'id' => $uploadId, // ID de base de datos
            'name' => $newFileName,
            'original_name' => $originalName,
            'path' => $relativePath,
            'url' => $relativePath, // Para retrocompatibilidad
            'size' => $fileSize,
            'size_formatted' => $stats->formatFileSize($fileSize),
            'type' => $mimeType,
            'upload_date' => $metadata['upload_date']
        ],
        'optimization_urls' => [
            'thumbnail_100' => "simple_img_v3.php?src=" . urlencode($relativePath) . "&w=100&h=100",
            'thumbnail_200' => "simple_img_v3.php?src=" . urlencode($relativePath) . "&w=200&h=200",
            'medium_400' => "simple_img_v3.php?src=" . urlencode($relativePath) . "&w=400&h=300",
            'webp_thumb' => "simple_img_v3.php?src=" . urlencode($relativePath) . "&w=200&h=200&f=webp"
        ],
        'stats_url' => "stats_dashboard.php?image=" . urlencode($relativePath)
    ];

    // Log de éxito
    error_log("Upload success: " . $relativePath . " (" . number_format($fileSize) . " bytes) [ID: $uploadId]");

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    $error = ['error' => $e->getMessage()];

    // Log de error
    error_log("Upload error: " . $e->getMessage());

    echo json_encode($error);
}
