<?php
// ARCHIVO: init_stats.php
// Script de inicialización del sistema de estadísticas
// Verifica y configura la base de datos SQLite

// Establecer codificación UTF-8
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>🔧 Inicializar Sistema de Estadísticas</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
    .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0; color: #155724; }
    .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 10px 0; color: #721c24; }
    .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 10px 0; color: #856404; }
    .info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 10px 0; color: #0c5460; }
    .step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007cba; }
    .code { background: #f8f8f8; padding: 10px; border-radius: 3px; font-family: monospace; margin: 5px 0; }
    h1 { color: #333; }
    h2 { color: #007cba; border-bottom: 2px solid #007cba; padding-bottom: 5px; }
</style>";
echo "</head>";
echo "<body>";

echo "<h1>🔧 Inicializar Sistema de Estadísticas EDI Medios</h1>";

// Verificar requisitos
echo "<h2>📋 Verificación de requisitos</h2>";

$errors = [];
$warnings = [];
$success = [];

// 1. Verificar PHP
if (version_compare(PHP_VERSION, '5.5.0', '>=')) {
    $success[] = "✅ PHP " . PHP_VERSION . " (compatible)";
} else {
    $errors[] = "❌ PHP " . PHP_VERSION . " es muy antiguo. Se requiere PHP 5.5 o superior.";
}

// 2. Verificar PDO
if (class_exists('PDO')) {
    $success[] = "✅ PDO disponible";

    // Verificar SQLite
    if (in_array('sqlite', PDO::getAvailableDrivers())) {
        $success[] = "✅ SQLite driver disponible";
    } else {
        $errors[] = "❌ SQLite driver no disponible en PDO";
    }
} else {
    $errors[] = "❌ PDO no disponible";
}

// 3. Verificar directorios
$directories = ['storage', 'lib'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $success[] = "✅ Directorio '$dir' existe";
    } elseif (mkdir($dir, 0755, true)) {
        $success[] = "✅ Directorio '$dir' creado";
    } else {
        $errors[] = "❌ No se pudo crear directorio '$dir'";
    }
}

// 4. Verificar permisos de escritura
$testFile = 'storage/test_write.tmp';
if (file_put_contents($testFile, 'test')) {
    $success[] = "✅ Permisos de escritura en 'storage/'";
    unlink($testFile);
} else {
    $errors[] = "❌ Sin permisos de escritura en 'storage/'";
}

// 5. Verificar archivos necesarios
$requiredFiles = [
    'lib/StatsManager.php' => 'Clase de manejo de estadísticas',
    'upload_organized_v2.php' => 'Sistema de upload con tracking',
    'simple_img_v3.php' => 'Optimizador con tracking',
    'stats_dashboard.php' => 'Dashboard de estadísticas'
];

foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        $success[] = "✅ $file ($description)";
    } else {
        $warnings[] = "⚠️ $file no encontrado ($description)";
    }
}

// Mostrar resultados
foreach ($success as $msg) {
    echo "<div class='success'>$msg</div>";
}

foreach ($warnings as $msg) {
    echo "<div class='warning'>$msg</div>";
}

foreach ($errors as $msg) {
    echo "<div class='error'>$msg</div>";
}

// Si hay errores críticos, parar aquí
if (!empty($errors)) {
    echo "<div class='error'>";
    echo "<h3>❌ No se puede continuar</h3>";
    echo "<p>Resuelve los errores críticos antes de continuar.</p>";
    echo "</div>";
    echo "</body></html>";
    exit;
}

// Intentar inicializar el sistema
echo "<h2>🚀 Inicializando sistema de estadísticas</h2>";

try {
    // Verificar si StatsManager existe
    if (!file_exists('lib/StatsManager.php')) {
        throw new Exception("Archivo lib/StatsManager.php no encontrado");
    }

    require_once('lib/StatsManager.php');

    echo "<div class='step'>";
    echo "<h3>Paso 1: Conectando a base de datos</h3>";

    $stats = new StatsManager();
    echo "<div class='success'>✅ Base de datos SQLite inicializada correctamente</div>";
    echo "<div class='info'>📍 Ubicación: storage/stats.db</div>";
    echo "</div>";

    echo "<div class='step'>";
    echo "<h3>Paso 2: Verificando estructura de tablas</h3>";

    // Obtener estadísticas básicas para verificar que todo funciona
    $generalStats = $stats->getGeneralStats();

    echo "<div class='success'>✅ Tablas creadas y funcionales</div>";
    echo "<div class='info'>";
    echo "<h4>📊 Estado actual del sistema:</h4>";
    echo "<ul>";
    echo "<li><strong>Total de uploads registrados:</strong> " . number_format($generalStats['total_uploads']) . "</li>";
    echo "<li><strong>Total de visualizaciones:</strong> " . number_format($generalStats['total_views']) . "</li>";
    echo "<li><strong>Imágenes únicas vistas:</strong> " . number_format($generalStats['unique_images']) . "</li>";
    echo "<li><strong>Tamaño total almacenado:</strong> " . $stats->formatFileSize($generalStats['total_size']) . "</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";

    echo "<div class='step'>";
    echo "<h3>Paso 3: Probando funcionalidades</h3>";

    // Probar inserción de datos de prueba (solo si no hay datos)
    if ($generalStats['total_uploads'] == 0) {
        echo "<div class='info'>💡 Base de datos vacía. El sistema está listo para recibir datos reales.</div>";
    } else {
        echo "<div class='success'>✅ Sistema ya contiene datos de producción</div>";
    }

    echo "</div>";

    // Mostrar enlaces útiles
    echo "<div class='success'>";
    echo "<h3>🎉 ¡Sistema inicializado correctamente!</h3>";
    echo "<p>El sistema de estadísticas está listo para usar.</p>";
    echo "<h4>🔗 Enlaces útiles:</h4>";
    echo "<ul>";
    echo "<li><a href='stats_dashboard.php' target='_blank'>📊 Dashboard de Estadísticas</a> - Ver métricas y gráficos</li>";
    echo "<li><a href='simple_img_v3.php' target='_blank'>🖼️ Optimizador v3</a> - Sistema con tracking</li>";
    if (file_exists('index.php')) {
        echo "<li><a href='index.php' target='_blank'>📤 Subir archivos</a> - Interface de upload</li>";
    }
    echo "<li><a href='upload_organized_v2.php' target='_blank'>🔧 API de Upload v2</a> - Endpoint con tracking</li>";
    echo "</ul>";
    echo "</div>";

    echo "<div class='info'>";
    echo "<h4>📈 Próximos pasos:</h4>";
    echo "<ol>";
    echo "<li><strong>Subir algunas imágenes</strong> usando el sistema de upload</li>";
    echo "<li><strong>Visualizar imágenes</strong> usando el optimizador v3</li>";
    echo "<li><strong>Revisar estadísticas</strong> en el dashboard</li>";
    echo "<li><strong>Integrar en tus aplicaciones</strong> usando las nuevas APIs</li>";
    echo "</ol>";
    echo "</div>";

    echo "<div class='warning'>";
    echo "<h4>⚙️ Configuración recomendada:</h4>";
    echo "<div class='code'>";
    echo "# Agregar a .gitignore:<br>";
    echo "storage/stats.db<br>";
    echo "storage/metadata/<br>";
    echo "</div>";
    echo "<p>Esto evitará que la base de datos se incluya en el control de versiones.</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Error durante la inicialización</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Solución:</strong> Verifica que todos los archivos necesarios existan y que tengas permisos de escritura.</p>";
    echo "</div>";
}

echo "</body>";
echo "</html>";
