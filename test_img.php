<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Parámetros básicos
$src = $_GET['src'] ?? '';
$w = (int)($_GET['w'] ?? 0);
$h = (int)($_GET['h'] ?? 0);

// Si no hay parámetros, mostrar ayuda
if (empty($src)) {
    echo "<h1>Test Optimización de Imágenes</h1>";
    echo "<p>Uso: test_img.php?src=imagen.jpg&w=100&h=100</p>";
    echo "<p>Ejemplo: <a href='test_img.php?src=dbdc084939e778491a168dfbd94f14ba.jpg&w=100&h=100'>test_img.php?src=dbdc084939e778491a168dfbd94f14ba.jpg&w=100&h=100</a></p>";

    // Verificar archivo de ejemplo
    $testFile = 'uploads/dbdc084939e778491a168dfbd94f14ba.jpg';
    if (file_exists($testFile)) {
        echo "<p>✅ Archivo de prueba existe: " . $testFile . "</p>";
        echo "<p>Tamaño: " . number_format(filesize($testFile)) . " bytes</p>";
    } else {
        echo "<p>❌ Archivo de prueba NO existe: " . $testFile . "</p>";
    }

    // Verificar extensión GD
    if (extension_loaded('gd')) {
        echo "<p>✅ Extensión GD disponible</p>";
    } else {
        echo "<p>❌ Extensión GD NO disponible</p>";
    }

    exit;
}

// Validar archivo
$filePath = 'uploads/' . basename($src);
if (!file_exists($filePath)) {
    header('Content-Type: text/plain');
    http_response_code(404);
    echo "Error: Archivo no encontrado: " . $filePath;
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
    echo "Error: Extensión GD no disponible";
    exit;
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

    // Crear imagen desde archivo
    if ($mimeType === 'image/jpeg') {
        $sourceImage = imagecreatefromjpeg($filePath);
    } else {
        throw new Exception("Solo se soporta JPEG por ahora");
    }

    // Calcular nuevas dimensiones
    if ($w && $h) {
        $newWidth = $w;
        $newHeight = $h;
    } elseif ($w) {
        $newWidth = $w;
        $newHeight = ($originalHeight * $w) / $originalWidth;
    } elseif ($h) {
        $newHeight = $h;
        $newWidth = ($originalWidth * $h) / $originalHeight;
    }

    // Crear imagen redimensionada
    $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

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

    // Servir imagen
    header('Content-Type: image/jpeg');
    imagejpeg($resizedImage, null, 85);

    // Limpiar memoria
    imagedestroy($sourceImage);
    imagedestroy($resizedImage);
} catch (Exception $e) {
    header('Content-Type: text/plain');
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
