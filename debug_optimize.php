<?php
// ARCHIVO: debug_optimize.php  
// Script de debug para probar la optimización

// Verificar si GD está disponible antes de usar funciones de imagen
if (!extension_loaded('gd')) {
    die('<h1>Error: Extensión GD no disponible</h1><p>La extensión PHP GD es requerida para el procesamiento de imágenes.</p>');
}

echo "<h1>Debug Sistema de Optimización</h1>";

// Simular parámetros de una URL real
$_GET['img'] = 'dbdc084939e778491a168dfbd94f14ba.jpg';
$_GET['w'] = '100';
$_GET['h'] = '100';
$_GET['q'] = '85';
$_GET['f'] = 'webp';
$_GET['fit'] = 'cover';

echo "<h2>Parámetros de prueba:</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

// Verificar si el archivo existe
$imagePath = $_GET['img'] ?? '';
$fullPath = 'uploads/' . basename($imagePath);

echo "<h2>Verificaciones:</h2>";
echo "<p><strong>Imagen solicitada:</strong> " . $imagePath . "</p>";
echo "<p><strong>Ruta completa:</strong> " . $fullPath . "</p>";
echo "<p><strong>Archivo existe:</strong> " . (file_exists($fullPath) ? '✅ SÍ' : '❌ NO') . "</p>";

if (file_exists($fullPath)) {
    echo "<p><strong>Tamaño del archivo:</strong> " . number_format(filesize($fullPath)) . " bytes</p>";

    $imageInfo = getimagesize($fullPath);
    if ($imageInfo) {
        echo "<p><strong>Dimensiones originales:</strong> " . $imageInfo[0] . "x" . $imageInfo[1] . " px</p>";
        echo "<p><strong>Tipo MIME:</strong> " . $imageInfo['mime'] . "</p>";
    }
}

// Verificar extensiones PHP
echo "<h2>Extensiones PHP:</h2>";
echo "<p><strong>GD:</strong> " . (extension_loaded('gd') ? '✅ Disponible' : '❌ No disponible') . "</p>";
echo "<p><strong>WebP:</strong> " . (function_exists('imagewebp') ? '✅ Disponible' : '❌ No disponible') . "</p>";
echo "<p><strong>AVIF:</strong> " . (function_exists('imageavif') ? '✅ Disponible' : '❌ No disponible') . "</p>";

// Generar nombre de cache
$width = (int)($_GET['w'] ?? 0);
$height = (int)($_GET['h'] ?? 0);
$quality = (int)($_GET['q'] ?? 85);
$format = $_GET['f'] ?? 'auto';
$fit = $_GET['fit'] ?? 'cover';

$cacheKey = md5($imagePath . $width . $height . $quality . $format . $fit);
$cacheDir = 'cache/';
$cacheExt = ($format === 'webp') ? 'webp' : (($format === 'avif') ? 'avif' : 'jpg');
$cachePath = $cacheDir . $cacheKey . '.' . $cacheExt;

echo "<h2>Sistema de Cache:</h2>";
echo "<p><strong>Cache Key:</strong> " . $cacheKey . "</p>";
echo "<p><strong>Cache Path:</strong> " . $cachePath . "</p>";
echo "<p><strong>Cache existe:</strong> " . (file_exists($cachePath) ? '✅ SÍ' : '❌ NO') . "</p>";
echo "<p><strong>Directorio cache escribible:</strong> " . (is_writable($cacheDir) ? '✅ SÍ' : '❌ NO') . "</p>";

// Test de rewrite
echo "<h2>Test de Rewrite:</h2>";
echo "<p>Si las reglas funcionan, esta URL debería redirigir a optimize.php:</p>";
echo "<p><a href='uploads/" . $imagePath . "?w=100&h=100&f=webp' target='_blank'>uploads/" . $imagePath . "?w=100&h=100&f=webp</a></p>";

echo "<h2>URLs de prueba:</h2>";
echo "<p><strong>Original:</strong> <a href='uploads/" . $imagePath . "' target='_blank'>uploads/" . $imagePath . "</a></p>";
echo "<p><strong>Thumbnail:</strong> <a href='uploads/" . $imagePath . "?w=100&h=100' target='_blank'>uploads/" . $imagePath . "?w=100&h=100</a></p>";
echo "<p><strong>WebP:</strong> <a href='uploads/" . $imagePath . "?w=100&h=100&f=webp' target='_blank'>uploads/" . $imagePath . "?w=100&h=100&f=webp</a></p>";

// Intentar procesar imagen si todo está bien
if (file_exists($fullPath) && extension_loaded('gd')) {
    echo "<h2>Test de Procesamiento:</h2>";

    try {
        $imageInfo = getimagesize($fullPath);
        if ($imageInfo) {
            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];

            // Calcular nuevas dimensiones
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

            echo "<p><strong>Dimensiones calculadas:</strong> " . round($newWidth) . "x" . round($newHeight) . " px</p>";
            echo "<p>✅ El procesamiento debería funcionar correctamente</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error en procesamiento: " . $e->getMessage() . "</p>";
    }
}
