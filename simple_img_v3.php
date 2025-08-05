<?php
// ARCHIVO: simple_img_v3.php
// Sistema de optimización con tracking de estadísticas
// Compatible con estructura organizada y archivos legacy

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inicializar tiempo de procesamiento
$startTime = microtime(true);

// Cargar sistemas de gestión
require_once(__DIR__ . '/lib/StatsManager.php');
require_once(__DIR__ . '/lib/SecurityManager.php');

// Establecer codificación UTF-8
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

// Parámetros básicos - Compatible con PHP 5.5+
$src = isset($_GET['src']) ? $_GET['src'] : '';
$w = isset($_GET['w']) ? (int)$_GET['w'] : 0;
$h = isset($_GET['h']) ? (int)$_GET['h'] : 0;
$f = isset($_GET['f']) ? $_GET['f'] : 'auto'; // Formato: webp, avif, auto
$q = isset($_GET['q']) ? (int)$_GET['q'] : 85; // Calidad

// Si no hay parámetros, mostrar ayuda
if (empty($src)) {
    echo "<!DOCTYPE html>";
    echo "<html lang='es'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>Optimización de Imágenes v3 📊</title>";
    echo "<style>body{font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px;} .example{background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 5px;} .old{color: #666;} .new{color: #007cba; font-weight: bold;} .stats{background: #e8f4fd; padding: 15px; border-radius: 5px; margin: 15px 0;}</style>";
    echo "</head>";
    echo "<body>";
    echo "<h1>🖼️ Optimización de Imágenes v3 📊</h1>";
    echo "<p>Sistema mejorado con <strong>tracking de estadísticas</strong> y soporte para estructura organizada</p>";

    // Mostrar estadísticas generales
    try {
        $stats = new StatsManager();
        $generalStats = $stats->getGeneralStats();

        echo "<div class='stats'>";
        echo "<h2>📊 Estadísticas del sistema:</h2>";
        echo "<ul>";
        echo "<li><strong>Total de archivos subidos:</strong> " . number_format($generalStats['total_uploads']) . "</li>";
        echo "<li><strong>Total de visualizaciones:</strong> " . number_format($generalStats['total_views']) . "</li>";
        echo "<li><strong>Imágenes únicas vistas:</strong> " . number_format($generalStats['unique_images']) . "</li>";
        echo "<li><strong>Tamaño total:</strong> " . $stats->formatFileSize($generalStats['total_size']) . "</li>";
        echo "<li><strong>Promedio de vistas por imagen:</strong> " . $generalStats['avg_views_per_image'] . "</li>";
        echo "<li><strong>Uploads hoy:</strong> " . $generalStats['today_uploads'] . "</li>";
        echo "<li><strong>Visualizaciones hoy:</strong> " . $generalStats['today_views'] . "</li>";
        echo "</ul>";
        echo "<p><a href='stats_dashboard.php' style='background: #007cba; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px;'>📊 Ver Dashboard Completo</a></p>";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div class='stats'>";
        echo "<p>⚠️ Sistema de estadísticas inicializándose...</p>";
        echo "</div>";
    }

    echo "<h2>📋 Uso:</h2>";
    echo "<div class='example'>simple_img_v3.php?src=RUTA&w=ANCHO&h=ALTO&f=FORMATO&q=CALIDAD</div>";

    echo "<h2>🗂️ Estructura soportada:</h2>";
    echo "<ul>";
    echo "<li><strong class='new'>Nueva:</strong> <code>2025/01/archivo.jpg</code> (organizada por año/mes)</li>";
    echo "<li><strong class='old'>Legacy:</strong> <code>archivo.jpg</code> (archivos antiguos)</li>";
    echo "</ul>";

    echo "<h2>🧪 Ejemplos de prueba:</h2>";

    // Buscar archivos de ejemplo
    $examples = [];

    // Buscar en estructura nueva (últimos 3 meses)
    $currentYear = date('Y');
    $currentMonth = (int)date('m');

    for ($i = 0; $i < 3; $i++) {
        $checkMonth = $currentMonth - $i;
        $checkYear = $currentYear;

        if ($checkMonth <= 0) {
            $checkMonth += 12;
            $checkYear--;
        }

        $monthStr = sprintf('%02d', $checkMonth);
        $checkDir = "uploads/$checkYear/$monthStr";

        if (is_dir($checkDir)) {
            $files = glob($checkDir . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
            foreach (array_slice($files, 0, 2) as $file) {
                $relativePath = str_replace('uploads/', '', $file);
                $examples[] = ['path' => $relativePath, 'type' => 'nueva'];
            }
        }
    }

    // Buscar en estructura legacy
    if (is_dir('uploads/legacy')) {
        $legacyFiles = glob('uploads/legacy/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        foreach (array_slice($legacyFiles, 0, 2) as $file) {
            $relativePath = str_replace('uploads/', '', $file);
            $examples[] = ['path' => $relativePath, 'type' => 'legacy'];
        }
    }

    if (!empty($examples)) {
        echo "<h3>📁 Archivos encontrados:</h3>";
        foreach ($examples as $example) {
            $path = $example['path'];
            $type = $example['type'];
            $typeLabel = $type === 'nueva' ? '<span class="new">Nueva estructura</span>' : '<span class="old">Legacy</span>';

            echo "<div class='example'>";
            echo "<strong>$typeLabel:</strong> $path<br>";
            echo "<a href='simple_img_v3.php?src=" . urlencode($path) . "&w=100&h=100' target='_blank'>Thumbnail 100x100</a> | ";
            echo "<a href='simple_img_v3.php?src=" . urlencode($path) . "&w=200&h=200' target='_blank'>Thumbnail 200x200</a> | ";
            echo "<a href='simple_img_v3.php?src=" . urlencode($path) . "&w=300' target='_blank'>Ancho 300px</a>";
            if ($type === 'nueva') {
                echo " | <a href='simple_img_v3.php?src=" . urlencode($path) . "&w=200&h=200&f=webp' target='_blank'>WebP 200x200</a>";
            }
            echo " | <a href='stats_dashboard.php?image=" . urlencode($path) . "' target='_blank'>📊 Estadísticas</a>";
            echo "</div>";
        }
    } else {
        echo "<p>ℹ️ No se encontraron archivos de ejemplo. Sube algunas imágenes primero.</p>";
    }

    // Estado del sistema
    echo "<h2>⚙️ Estado del sistema:</h2>";
    echo "<ul>";
    echo "<li>GD Extension: " . (extension_loaded('gd') ? '✅ Disponible' : '❌ No disponible') . "</li>";
    echo "<li>WebP Support: " . (function_exists('imagewebp') ? '✅ Sí' : '❌ No') . "</li>";
    echo "<li>AVIF Support: " . (function_exists('imageavif') ? '✅ Sí' : '❌ No') . "</li>";
    echo "<li>SQLite Support: " . (class_exists('PDO') && in_array('sqlite', PDO::getAvailableDrivers()) ? '✅ Disponible' : '❌ No disponible') . "</li>";
    echo "<li>Uploads Directory: " . (is_dir('uploads') ? '✅ Existe' : '❌ No existe') . "</li>";
    echo "<li>Cache Directory: " . (is_dir('cache') ? '✅ Existe' : '❌ No existe') . "</li>";
    echo "<li>Stats Database: " . (file_exists(__DIR__ . '/storage/stats.db') ? '✅ Inicializada' : '⚠️ Se creará automáticamente') . "</li>";
    echo "</ul>";

    echo "</body>";
    echo "</html>";
    exit;
}

// Determinar ruta del archivo
function resolveFilePath($src)
{
    // Limpiar la ruta
    $src = ltrim($src, '/');
    $src = str_replace(['../', './'], '', $src); // Seguridad básica

    // Si contiene barras, es estructura nueva
    if (strpos($src, '/') !== false) {
        $fullPath = 'uploads/' . $src;
        if (file_exists($fullPath)) {
            return $fullPath;
        }
    }

    // Si no, buscar en estructura legacy
    $legacyPath = 'uploads/' . basename($src);
    if (file_exists($legacyPath)) {
        return $legacyPath;
    }

    // Buscar en legacy directory
    $legacyDirPath = 'uploads/legacy/' . basename($src);
    if (file_exists($legacyDirPath)) {
        return $legacyDirPath;
    }

    return false;
}

// Resolver ruta del archivo
$filePath = resolveFilePath($src);

if (!$filePath) {
    // Log de imagen no encontrada
    if ($stats) {
        $stats->logActivity(
            'image_view',
            'not_found',
            "Imagen no encontrada: $src",
            "Usuario intentó acceder a imagen inexistente. Rutas verificadas: uploads/$src, uploads/" . basename($src) . ", uploads/legacy/" . basename($src),
            $src
        );
    }

    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(404);
    echo "❌ Imagen no encontrada\n\n";
    echo "🔍 Archivo buscado: " . htmlspecialchars($src) . "\n\n";
    echo "💡 Verifica que:\n";
    echo "• El archivo existe en el servidor\n";
    echo "• La ruta esté escrita correctamente\n";
    echo "• No falten barras (/) en la ruta\n\n";
    echo "📝 Ejemplos de rutas válidas:\n";
    echo "• Nueva estructura: 2025/01/archivo.jpg\n";
    echo "• Legacy: archivo.jpg\n";
    echo "• Legacy migrado: legacy/archivo.jpg\n\n";
    echo "🔗 Para ver imágenes disponibles: simple_img_v3.php\n";
    exit;
}

// 🛡️ APLICAR SEGURIDAD
try {
    $security = new SecurityManager();
    $security->applySecurityChecks();
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Error de seguridad: " . $e->getMessage();
    exit;
}

// 📊 INICIALIZAR TRACKING DE ESTADÍSTICAS
$cacheHit = false;
$processingTimeMs = null;

try {
    $stats = new StatsManager();
} catch (Exception $e) {
    // Si hay error con stats, continuar sin tracking
    $stats = null;
    error_log("Stats error: " . $e->getMessage());
}

// Si no hay redimensionado, servir original
if (!$w && !$h) {
    $mimeType = mime_content_type($filePath);
    header('Content-Type: ' . $mimeType);
    header('Cache-Control: public, max-age=31536000');

    // 📊 REGISTRAR VISUALIZACIÓN (original)
    if ($stats) {
        $stats->recordView($src, [
            'width' => null,
            'height' => null,
            'format' => 'original',
            'quality' => null,
            'cache_hit' => false,
            'processing_time_ms' => null
        ]);

        // Log de visualización exitosa
        $stats->logActivity(
            'image_view',
            'success',
            "Imagen servida (original): $src",
            "Tipo MIME: $mimeType. Archivo: $filePath",
            $src,
            filesize($filePath)
        );
    }

    readfile($filePath);
    exit;
}

// Verificar GD
if (!extension_loaded('gd')) {
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);
    echo "Error: Extensión GD no disponible";
    exit;
}

// Crear directorio cache si no existe
if (!is_dir('cache')) {
    mkdir('cache', 0755, true);
}

// Generar cache key único
$cacheKey = md5($src . $w . $h . $f . $q);
$cacheDir = 'cache';

// Determinar extensión de salida
$outputExt = 'jpg';
if ($f === 'webp' && function_exists('imagewebp')) {
    $outputExt = 'webp';
} elseif ($f === 'avif' && function_exists('imageavif')) {
    $outputExt = 'avif';
}

$cachePath = $cacheDir . '/' . $cacheKey . '.' . $outputExt;

// Si existe en cache, servir directamente
if (file_exists($cachePath)) {
    $contentType = 'image/' . $outputExt;
    header('Content-Type: ' . $contentType);
    header('Cache-Control: public, max-age=31536000');
    header('ETag: "' . $cacheKey . '"');

    $cacheHit = true;
    $processingTimeMs = round((microtime(true) - $startTime) * 1000, 2);

    // 📊 REGISTRAR VISUALIZACIÓN (cache hit)
    if ($stats) {
        $stats->recordView($src, [
            'width' => $w,
            'height' => $h,
            'format' => $f,
            'quality' => $q,
            'cache_hit' => true,
            'processing_time_ms' => $processingTimeMs
        ]);

        // Log de cache hit
        $stats->logActivity(
            'image_view',
            'success',
            "Imagen servida (cache): $src",
            "Dimensiones: {$w}x{$h}, Formato: $f, Calidad: $q, Tiempo: {$processingTimeMs}ms (CACHE HIT)",
            $src,
            filesize($cachePath)
        );
    }

    readfile($cachePath);
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
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($filePath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($filePath);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($filePath);
            break;
        default:
            throw new Exception("Formato de imagen no soportado: " . $mimeType);
    }

    if (!$sourceImage) {
        throw new Exception("No se pudo crear imagen desde archivo");
    }

    // Calcular nuevas dimensiones manteniendo aspecto
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
    if (!$resizedImage) {
        throw new Exception("No se pudo crear imagen redimensionada");
    }

    // Preservar transparencia para PNG
    if ($mimeType === 'image/png') {
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
        imagefill($resizedImage, 0, 0, $transparent);
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

    // Guardar en cache según formato
    $saveSuccess = false;
    if ($outputExt === 'webp' && function_exists('imagewebp')) {
        $saveSuccess = imagewebp($resizedImage, $cachePath, $q);
        $contentType = 'image/webp';
    } elseif ($outputExt === 'avif' && function_exists('imageavif')) {
        $saveSuccess = imageavif($resizedImage, $cachePath, $q);
        $contentType = 'image/avif';
    } else {
        $saveSuccess = imagejpeg($resizedImage, $cachePath, $q);
        $contentType = 'image/jpeg';
    }

    if (!$saveSuccess) {
        throw new Exception("Error al guardar imagen en cache");
    }

    // Calcular tiempo de procesamiento
    $processingTimeMs = round((microtime(true) - $startTime) * 1000, 2);

    // Servir imagen
    header('Content-Type: ' . $contentType);
    header('Cache-Control: public, max-age=31536000');
    header('ETag: "' . $cacheKey . '"');

    // 📊 REGISTRAR VISUALIZACIÓN (procesada)
    if ($stats) {
        $stats->recordView($src, [
            'width' => $w,
            'height' => $h,
            'format' => $f,
            'quality' => $q,
            'cache_hit' => false,
            'processing_time_ms' => $processingTimeMs
        ]);

        // Log de procesamiento exitoso
        $stats->logActivity(
            'image_view',
            'success',
            "Imagen procesada y servida: $src",
            "Dimensiones: {$w}x{$h}, Formato: $f, Calidad: $q, Tiempo: {$processingTimeMs}ms (PROCESADA)",
            $src,
            filesize($cachePath)
        );
    }

    readfile($cachePath);

    // Limpiar memoria
    imagedestroy($sourceImage);
    imagedestroy($resizedImage);
} catch (Exception $e) {
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);

    $errorMsg = "❌ Error procesando imagen: " . $e->getMessage();
    echo $errorMsg . "\n\n";
    echo "🔍 Archivo: $src\n";
    echo "📐 Dimensiones solicitadas: {$w}x{$h}\n";
    echo "🎨 Formato solicitado: $f\n";
    echo "⚙️ Calidad solicitada: $q\n\n";
    echo "💡 Posibles causas:\n";
    echo "• Archivo corrupto o no es una imagen válida\n";
    echo "• Formato de imagen no soportado\n";
    echo "• Problemas de memoria del servidor\n";
    echo "• Permisos insuficientes\n\n";
    echo "🔗 Intenta con otra imagen o contacta al administrador\n";

    // 📊 REGISTRAR ERROR
    if ($stats) {
        try {
            $stats->recordView($src, [
                'width' => $w,
                'height' => $h,
                'format' => $f,
                'quality' => $q,
                'cache_hit' => false,
                'processing_time_ms' => null
            ]);

            // Log detallado del error de procesamiento
            $stats->logActivity(
                'image_view',
                'error',
                "Error procesando imagen: $src",
                "Error: " . $e->getMessage() . ". Parámetros: {$w}x{$h}, formato: $f, calidad: $q. Archivo: $filePath",
                $src
            );
        } catch (Exception $statsError) {
            // Ignorar errores de stats
            error_log("Stats error while logging image processing error: " . $statsError->getMessage());
        }
    }
}
