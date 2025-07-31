<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Establecer codificación UTF-8
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

// Parámetros básicos - Compatible con PHP 5.5
$src = isset($_GET['src']) ? $_GET['src'] : '';
$w = isset($_GET['w']) ? (int)$_GET['w'] : 0;
$h = isset($_GET['h']) ? (int)$_GET['h'] : 0;

// Si no hay parámetros, mostrar ayuda
if (empty($src)) {
    echo "<!DOCTYPE html>";
    echo "<html lang='es'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>Optimización de Imágenes</title>";
    echo "</head>";
    echo "<body>";
    echo "<h1>Optimización de Imágenes</h1>";
    echo "<p>Uso: simple_img.php?src=imagen.jpg&w=100&h=100</p>";
    echo "<p><a href='simple_img.php?src=dbdc084939e778491a168dfbd94f14ba.jpg&w=100&h=100'>PRUEBA: Thumbnail 100x100</a></p>";
    echo "<p><a href='simple_img.php?src=dbdc084939e778491a168dfbd94f14ba.jpg&w=200&h=200'>PRUEBA: Thumbnail 200x200</a></p>";
    echo "<p><a href='simple_img.php?src=dbdc084939e778491a168dfbd94f14ba.jpg&w=50&h=50'>PRUEBA: Thumbnail 50x50</a></p>";

    // Info del sistema
    echo "<h2>Estado del sistema:</h2>";
    if (extension_loaded('gd')) {
        echo "<p>✅ GD disponible</p>";
    } else {
        echo "<p>❌ GD NO disponible</p>";
    }

    if (is_dir('uploads')) {
        echo "<p>✅ Directorio uploads existe</p>";
    } else {
        echo "<p>❌ Directorio uploads NO existe</p>";
    }

    if (is_dir('cache')) {
        echo "<p>✅ Directorio cache existe</p>";
    } else {
        echo "<p>❌ Directorio cache NO existe - Se creará automáticamente</p>";
    }

    $testFile = 'uploads/dbdc084939e778491a168dfbd94f14ba.jpg';
    if (file_exists($testFile)) {
        echo "<p>✅ Imagen de prueba existe (" . number_format(filesize($testFile)) . " bytes)</p>";
    } else {
        echo "<p>❌ Imagen de prueba NO existe</p>";
    }

    echo "</body>";
    echo "</html>";
    exit;
}

// Validar archivo
$filePath = 'uploads/' . basename($src);
if (!file_exists($filePath)) {
    header('Content-Type: text/plain');
    http_response_code(404);
    echo "Error: Archivo no encontrado";
    exit;
}

// Si no hay redimensionado, servir original
if (!$w && !$h) {
    header('Content-Type: image/jpeg');
    readfile($filePath);
    exit;
}

// Verificar GD
if (!extension_loaded('gd')) {
    header('Content-Type: text/plain');
    http_response_code(500);
    echo "Error: Extension GD no disponible";
    exit;
}

// Crear directorio cache si no existe
if (!is_dir('cache')) {
    mkdir('cache', 0755);
}

try {
    // Obtener información de la imagen
    $imageInfo = getimagesize($filePath);
    if (!$imageInfo) {
        throw new Exception("No se pudo leer la imagen");
    }

    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    $mimeType = $imageInfo['mime'];

    // Solo soportar JPEG por simplicidad
    if ($mimeType !== 'image/jpeg') {
        throw new Exception("Solo se soporta JPEG");
    }

    $sourceImage = imagecreatefromjpeg($filePath);
    if (!$sourceImage) {
        throw new Exception("No se pudo crear imagen desde archivo");
    }

    // Calcular nuevas dimensiones
    if ($w && $h) {
        $newWidth = $w;
        $newHeight = $h;
    } else if ($w) {
        $newWidth = $w;
        $newHeight = ($originalHeight * $w) / $originalWidth;
    } else if ($h) {
        $newHeight = $h;
        $newWidth = ($originalWidth * $h) / $originalHeight;
    }

    // Crear imagen redimensionada
    $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
    if (!$resizedImage) {
        throw new Exception("No se pudo crear imagen redimensionada");
    }

    // Redimensionar
    $result = imagecopyresampled(
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

    if (!$result) {
        throw new Exception("Error al redimensionar imagen");
    }

    // Servir imagen
    header('Content-Type: image/jpeg');
    header('Cache-Control: public, max-age=3600');

    imagejpeg($resizedImage, null, 85);

    // Limpiar memoria
    imagedestroy($sourceImage);
    imagedestroy($resizedImage);
} catch (Exception $e) {
    header('Content-Type: text/plain');
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
