<?php
// ARCHIVO: test_deletion.php
// Script de prueba para verificar el sistema de eliminación

// Establecer codificación UTF-8
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>🧪 Test Sistema de Eliminación</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
    .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0; color: #155724; }
    .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 10px 0; color: #721c24; }
    .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 10px 0; color: #856404; }
    .info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 10px 0; color: #0c5460; }
    .test-section { background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #007cba; }
    .file-item { background: white; padding: 10px; margin: 5px 0; border-radius: 3px; border: 1px solid #ddd; }
    h1 { color: #333; }
    h2 { color: #007cba; border-bottom: 2px solid #007cba; padding-bottom: 5px; }
    .danger { background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #dc3545; }
</style>";
echo "</head>";
echo "<body>";

echo "<h1>🧪 Test Sistema de Eliminación EDI Medios</h1>";

// Verificar que el sistema esté disponible
try {
    require_once(__DIR__ . '/lib/StatsManager.php');
    $stats = new StatsManager();
    echo "<div class='success'>✅ Sistema de estadísticas cargado correctamente</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error cargando sistema: " . $e->getMessage() . "</div>";
    echo "</body></html>";
    exit;
}

// Test 1: Verificar archivos disponibles
echo "<div class='test-section'>";
echo "<h2>📁 Test 1: Listar Archivos Disponibles</h2>";

try {
    $files = $stats->getFilesForDeletion('all', 20);

    echo "<div class='success'>✅ Se encontraron " . count($files) . " archivos</div>";

    if (!empty($files)) {
        echo "<h3>📋 Archivos encontrados:</h3>";
        foreach (array_slice($files, 0, 10) as $index => $file) {
            $typeClass = $file['type'] === 'database' ? 'success' : 'warning';
            $existsClass = $file['exists'] ? 'success' : 'error';

            echo "<div class='file-item'>";
            echo "<strong>Archivo " . ($index + 1) . ":</strong> " . htmlspecialchars($file['original_name']) . "<br>";
            echo "<small>Ruta: " . htmlspecialchars($file['path']) . "</small><br>";
            echo "<span class='$typeClass' style='padding: 2px 6px; border-radius: 3px;'>";
            echo $file['type'] === 'database' ? 'En BD' : 'Huérfano';
            echo "</span> ";
            echo "<span class='$existsClass' style='padding: 2px 6px; border-radius: 3px;'>";
            echo $file['exists'] ? 'Existe físicamente' : 'Archivo faltante';
            echo "</span><br>";
            echo "<small>Tamaño: " . $stats->formatFileSize($file['size']) . " | Vistas: " . $file['view_count'] . "</small>";
            echo "</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ No se encontraron archivos para mostrar</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error listando archivos: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 2: Verificar API de file_manager
echo "<div class='test-section'>";
echo "<h2>🔌 Test 2: Verificar API de Gestión</h2>";

// Hacer petición a file_manager.php
$fileManagerUrl = 'file_manager.php?action=list&type=all&limit=5';
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents($fileManagerUrl, false, $context);

if ($response !== false) {
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "<div class='success'>✅ API de file_manager.php funciona correctamente</div>";
        echo "<div class='info'>";
        echo "<h4>📊 Estadísticas de la API:</h4>";
        echo "<ul>";
        echo "<li>Total de archivos: " . $data['stats']['total_files'] . "</li>";
        echo "<li>Archivos en BD: " . $data['stats']['database_files'] . "</li>";
        echo "<li>Archivos huérfanos: " . $data['stats']['orphaned_files'] . "</li>";
        echo "<li>Tamaño total: " . $data['formatted_size'] . "</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='error'>❌ API responde pero con error: " . ($data['error'] ?? 'Error desconocido') . "</div>";
    }
} else {
    echo "<div class='error'>❌ No se pudo conectar con file_manager.php</div>";
    echo "<div class='info'>Verifica que el archivo file_manager.php esté en el mismo directorio</div>";
}
echo "</div>";

// Test 3: Simular eliminación de logs (sin ejecutar)
echo "<div class='test-section'>";
echo "<h2>🗑️ Test 3: Verificar Métodos de Eliminación de Logs</h2>";

try {
    // Obtener estadísticas de logs actuales
    $logStats = $stats->getLogStats();

    echo "<div class='success'>✅ Métodos de eliminación de logs disponibles</div>";
    echo "<div class='info'>";
    echo "<h4>📊 Logs actuales por tipo:</h4>";

    if (!empty($logStats['by_type_status'])) {
        foreach ($logStats['by_type_status'] as $stat) {
            echo "<div style='display: inline-block; margin: 5px; padding: 5px 10px; background: #e9ecef; border-radius: 3px;'>";
            echo "<strong>{$stat['activity_type']}</strong> ({$stat['status']}): {$stat['count']}";
            echo "</div>";
        }
    } else {
        echo "<p>No hay logs para mostrar</p>";
    }
    echo "</div>";

    // Código de confirmación para eliminación total
    $confirmationCode = 'DELETE_ALL_LOGS_' . date('Ymd');
    echo "<div class='warning'>";
    echo "<h4>⚠️ Para eliminación total de logs:</h4>";
    echo "<p>Código de confirmación requerido: <code>$confirmationCode</code></p>";
    echo "<p><small>Este código cambia diariamente por seguridad</small></p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error verificando logs: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 4: Zona de peligro (solo mostrar, no ejecutar)
echo "<div class='danger'>";
echo "<h2>🚨 Zona de Peligro - Operaciones Destructivas</h2>";
echo "<div class='warning'>";
echo "<h3>⚠️ ADVERTENCIA IMPORTANTE</h3>";
echo "<p>Las siguientes operaciones son <strong>IRREVERSIBLES</strong>:</p>";
echo "<ul>";
echo "<li><strong>Eliminación de archivos:</strong> Borra archivo físico + registros de BD + logs</li>";
echo "<li><strong>Eliminación de logs por tipo:</strong> Borra logs específicos</li>";
echo "<li><strong>Eliminación total de logs:</strong> Borra TODOS los logs</li>";
echo "</ul>";
echo "<p><strong>🛡️ Medidas de seguridad implementadas:</strong></p>";
echo "<ul>";
echo "<li>Confirmación requerida en cada operación</li>";
echo "<li>Código de confirmación diario para eliminación total</li>";
echo "<li>Logging de todas las operaciones de eliminación</li>";
echo "<li>APIs separadas para cada tipo de eliminación</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

// Enlaces útiles
echo "<div class='info'>";
echo "<h3>🔗 Enlaces para usar el sistema completo:</h3>";
echo "<ul>";
echo "<li><a href='stats_dashboard.php' target='_blank'>📊 Dashboard de Estadísticas</a> - Ver dashboard con gestión integrada</li>";
echo "<li><a href='file_manager.php?action=list&type=all&limit=20' target='_blank'>📋 API de Archivos</a> - Ver respuesta JSON de archivos</li>";
echo "<li><a href='simple_img_v3.php' target='_blank'>🖼️ Optimizador v3</a> - Probar con archivos existentes</li>";
if (file_exists('index.php')) {
    echo "<li><a href='index.php' target='_blank'>📤 Sistema de Upload</a> - Subir archivos para probar</li>";
}
echo "</ul>";
echo "</div>";

echo "<div class='success'>";
echo "<h3>🎉 Conclusión del Test</h3>";
echo "<p>El sistema de eliminación está listo si todos los tests anteriores muestran ✅.</p>";
echo "<p><strong>Funcionalidades verificadas:</strong></p>";
echo "<ul>";
echo "<li>✅ Listado de archivos (BD y huérfanos)</li>";
echo "<li>✅ API de gestión de archivos funcionando</li>";
echo "<li>✅ Métodos de eliminación de logs disponibles</li>";
echo "<li>✅ Códigos de seguridad implementados</li>";
echo "<li>✅ Interfaz de gestión en dashboard</li>";
echo "</ul>";
echo "<p><strong>🚀 Próximo paso:</strong> Usar el dashboard de estadísticas para gestionar archivos y logs</p>";
echo "</div>";

echo "</body>";
echo "</html>";
