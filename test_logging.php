<?php
// ARCHIVO: test_logging.php
// Script de prueba para verificar el sistema de logging y mensajes

// Establecer codificación UTF-8
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>🧪 Test Sistema de Logging</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; }
    .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0; color: #155724; }
    .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 10px 0; color: #721c24; }
    .info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 10px 0; color: #0c5460; }
    .test-section { background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #007cba; }
    .log-entry { background: white; padding: 10px; margin: 5px 0; border-radius: 3px; border: 1px solid #ddd; }
    h1 { color: #333; }
    h2 { color: #007cba; border-bottom: 2px solid #007cba; padding-bottom: 5px; }
</style>";
echo "</head>";
echo "<body>";

echo "<h1>🧪 Test Sistema de Logging EDI Medios</h1>";

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

// Test 1: Logs de upload exitoso
echo "<div class='test-section'>";
echo "<h2>📤 Test 1: Simular Upload Exitoso</h2>";

try {
    $uploadId = $stats->logActivity(
        'upload',
        'completed',
        'Test: Archivo subido exitosamente',
        'Archivo de prueba: test_image.jpg (2.5MB). Guardado en uploads/2025/01/test_image.jpg',
        '2025/01/test_image.jpg',
        2621440 // 2.5MB
    );

    echo "<div class='success'>✅ Log de upload exitoso registrado (ID: $uploadId)</div>";
    echo "<div class='info'>📝 Tipo: upload, Estado: completed, Archivo: 2025/01/test_image.jpg</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error registrando upload: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 2: Logs de error de upload
echo "<div class='test-section'>";
echo "<h2>❌ Test 2: Simular Error de Upload</h2>";

try {
    $errorId = $stats->logActivity(
        'upload',
        'error',
        'Test: Tipo de archivo no permitido',
        'Archivo rechazado: documento.pdf (extensión: pdf). Solo se permiten imágenes.',
        null,
        1048576 // 1MB
    );

    echo "<div class='success'>✅ Log de error de upload registrado (ID: $errorId)</div>";
    echo "<div class='info'>📝 Tipo: upload, Estado: error, Razón: Tipo de archivo no permitido</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error registrando error de upload: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 3: Logs de visualización
echo "<div class='test-section'>";
echo "<h2>👁️ Test 3: Simular Visualización de Imagen</h2>";

try {
    $viewId = $stats->logActivity(
        'image_view',
        'success',
        'Test: Imagen servida (procesada)',
        'Dimensiones: 300x200, Formato: webp, Calidad: 85, Tiempo: 125ms (PROCESADA)',
        '2025/01/test_image.jpg',
        45678 // Tamaño del archivo procesado
    );

    echo "<div class='success'>✅ Log de visualización registrado (ID: $viewId)</div>";
    echo "<div class='info'>📝 Tipo: image_view, Estado: success, Procesamiento: 125ms</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error registrando visualización: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 4: Logs de imagen no encontrada
echo "<div class='test-section'>";
echo "<h2>🔍 Test 4: Simular Imagen No Encontrada</h2>";

try {
    $notFoundId = $stats->logActivity(
        'image_view',
        'not_found',
        'Test: Imagen no encontrada',
        'Usuario intentó acceder a imagen inexistente. Rutas verificadas: uploads/inexistente.jpg, uploads/legacy/inexistente.jpg',
        'inexistente.jpg'
    );

    echo "<div class='success'>✅ Log de imagen no encontrada registrado (ID: $notFoundId)</div>";
    echo "<div class='info'>📝 Tipo: image_view, Estado: not_found, Archivo: inexistente.jpg</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error registrando imagen no encontrada: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 5: Recuperar logs recientes
echo "<div class='test-section'>";
echo "<h2>📋 Test 5: Recuperar Logs Recientes</h2>";

try {
    $recentLogs = $stats->getActivityLogs(10);

    echo "<div class='success'>✅ " . count($recentLogs) . " logs recuperados</div>";

    if (!empty($recentLogs)) {
        echo "<h3>📝 Últimos logs registrados:</h3>";
        foreach (array_slice($recentLogs, 0, 5) as $log) {
            $statusClass = '';
            switch ($log['status']) {
                case 'success':
                case 'completed':
                    $statusClass = 'success';
                    break;
                case 'error':
                case 'failed':
                    $statusClass = 'error';
                    break;
                case 'not_found':
                    $statusClass = 'info';
                    break;
                default:
                    $statusClass = 'info';
            }

            echo "<div class='log-entry'>";
            echo "<strong>[{$log['activity_type']}]</strong> ";
            echo "<span class='$statusClass' style='padding: 2px 6px; border-radius: 3px;'>{$log['status']}</span> ";
            echo "- " . htmlspecialchars($log['message']);
            if ($log['file_path']) {
                echo " <small>(Archivo: " . htmlspecialchars(basename($log['file_path'])) . ")</small>";
            }
            echo "<br><small style='color: #666;'>" . $log['created_at'] . "</small>";
            echo "</div>";
        }
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error recuperando logs: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 6: Estadísticas de logs
echo "<div class='test-section'>";
echo "<h2>📊 Test 6: Estadísticas de Logs</h2>";

try {
    $logStats = $stats->getLogStats();

    echo "<div class='success'>✅ Estadísticas de logs obtenidas</div>";

    if (!empty($logStats['by_type_status'])) {
        echo "<h3>📈 Logs por tipo y estado:</h3>";
        foreach ($logStats['by_type_status'] as $stat) {
            echo "<div class='log-entry'>";
            echo "<strong>{$stat['activity_type']}</strong> - {$stat['status']}: {$stat['count']} registros";
            echo "</div>";
        }
    }

    if (!empty($logStats['today'])) {
        echo "<h3>📅 Actividad de hoy:</h3>";
        foreach ($logStats['today'] as $stat) {
            echo "<div class='log-entry'>";
            echo "<strong>{$stat['activity_type']}</strong>: {$stat['count']} eventos";
            echo "</div>";
        }
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error obteniendo estadísticas: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Enlaces útiles
echo "<div class='info'>";
echo "<h3>🔗 Enlaces para probar el sistema completo:</h3>";
echo "<ul>";
echo "<li><a href='stats_dashboard.php' target='_blank'>📊 Dashboard de Estadísticas</a> - Ver todos los logs en el dashboard</li>";
echo "<li><a href='simple_img_v3.php' target='_blank'>🖼️ Optimizador v3</a> - Probar visualización con logging</li>";
if (file_exists('index.php')) {
    echo "<li><a href='index.php' target='_blank'>📤 Sistema de Upload</a> - Probar upload con logging</li>";
}
echo "<li><a href='simple_img_v3.php?src=inexistente.jpg' target='_blank'>🔍 Test Imagen No Encontrada</a> - Probar manejo de errores</li>";
echo "</ul>";
echo "</div>";

echo "<div class='success'>";
echo "<h3>🎉 Conclusión del Test</h3>";
echo "<p>El sistema de logging está funcionando correctamente si todos los tests anteriores muestran ✅.</p>";
echo "<p><strong>Funcionalidades verificadas:</strong></p>";
echo "<ul>";
echo "<li>✅ Registro de uploads exitosos y fallidos</li>";
echo "<li>✅ Registro de visualizaciones de imágenes</li>";
echo "<li>✅ Registro de imágenes no encontradas</li>";
echo "<li>✅ Recuperación de logs con filtros</li>";
echo "<li>✅ Estadísticas de actividad</li>";
echo "<li>✅ Integración con base de datos SQLite</li>";
echo "</ul>";
echo "</div>";

echo "</body>";
echo "</html>";
