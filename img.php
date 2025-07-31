<?php
// ARCHIVO: img.php
// Sistema de routing directo que no depende de .htaccess
// USO: img.php?src=imagen.jpg&w=100&h=100&f=webp

// Headers básicos
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Obtener parámetros
$imagePath = $_GET['src'] ?? '';
$width = (int)($_GET['w'] ?? 0);
$height = (int)($_GET['h'] ?? 0);
$quality = (int)($_GET['q'] ?? 85);
$format = $_GET['f'] ?? 'auto';
$fit = $_GET['fit'] ?? 'cover';

// Validar que la imagen existe
if (empty($imagePath)) {
    http_response_code(400);
    exit('Parámetro src requerido');
}

$fullPath = 'uploads/' . basename($imagePath);
if (!file_exists($fullPath)) {
    http_response_code(404);
    exit('Image not found');
}

// Generar nombre de cache
$cacheKey = md5($imagePath . $width . $height . $quality . $format . $fit);
$cacheDir = 'cache/';
$cacheExt = ($format === 'webp') ? 'webp' : (($format === 'avif') ? 'avif' : 'jpg');
$cachePath = $cacheDir . $cacheKey . '.' . $cacheExt;

// Crear directorio cache si no existe
if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Si existe en cache, servir directamente
if (file_exists($cachePath)) {
    $contentType = ($format === 'webp') ? 'image/webp' : (($format === 'avif') ? 'image/avif' : 'image/jpeg');

    header('Content-Type: ' . $contentType);
    header('Cache-Control: public, max-age=31536000'); // 1 año
    header('ETag: "' . $cacheKey . '"');

    readfile($cachePath);
    exit;
}

// Si no hay parámetros de optimización, servir original
if (!$width && !$height && $format === 'auto') {
    header('Content-Type: ' . mime_content_type($fullPath));
    header('Cache-Control: public, max-age=31536000');
    readfile($fullPath);
    exit;
}

// Procesar imagen
try {
    // Detectar formato original
    $imageInfo = getimagesize($fullPath);
    if (!$imageInfo) {
        throw new Exception('No se pudo leer la información de la imagen');
    }

    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    $mimeType = $imageInfo['mime'];

    // Crear imagen desde archivo
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($fullPath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($fullPath);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($fullPath);
            break;
        default:
            throw new Exception('Formato no soportado');
    }

    // Calcular dimensiones
    if ($width && $height) {
        $newWidth = $width;
        $newHeight = $height;
    } elseif ($width) {
        $newWidth = $width;
        $newHeight = ($originalHeight * $width) / $originalWidth;
    } elseif ($height) {
        $newHeight = $height;
        $newWidth = ($originalWidth * $height) / $originalHeight;
    } else {
        $newWidth = $originalWidth;
        $newHeight = $originalHeight;
    }

    // Crear imagen redimensionada
    $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

    // Preservar transparencia para PNG
    if ($mimeType === 'image/png') {
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
        imagefill($resizedImage, 0, 0, $transparent);
    }

    // Redimensionar
    imagecopyresampled(
        $resizedImage,
        $sourceImage,
        0,
        0,
        0,
        0,
        $newWidth,
        $newHeight,
        $originalWidth,
        $originalHeight
    );

    // Guardar según formato solicitado
    switch ($format) {
        case 'webp':
            if (function_exists('imagewebp')) {
                imagewebp($resizedImage, $cachePath, $quality);
                $contentType = 'image/webp';
            } else {
                imagejpeg($resizedImage, $cachePath, $quality);
                $contentType = 'image/jpeg';
            }
            break;
        case 'avif':
            if (function_exists('imageavif')) {
                imageavif($resizedImage, $cachePath, $quality);
                $contentType = 'image/avif';
            } else {
                imagejpeg($resizedImage, $cachePath, $quality);
                $contentType = 'image/jpeg';
            }
            break;
        default:
            imagejpeg($resizedImage, $cachePath, $quality);
            $contentType = 'image/jpeg';
    }

    // Headers de respuesta
    header('Content-Type: ' . $contentType);
    header('Cache-Control: public, max-age=31536000');
    header('ETag: "' . $cacheKey . '"');

    // Servir imagen
    readfile($cachePath);

    // Limpiar memoria
    imagedestroy($sourceImage);
    imagedestroy($resizedImage);
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}
