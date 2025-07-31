<?php
// ARCHIVO: migrate_files.php
// Script para migrar archivos existentes a estructura organizada
// ⚠️ EJECUTAR SOLO UNA VEZ Y CON BACKUP

set_time_limit(300); // 5 minutos máximo

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Migrar Archivos a Estructura Organizada</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
    .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .file-item { background: #f8f9fa; padding: 8px; margin: 5px 0; border-radius: 3px; font-family: monospace; }
    .progress { width: 100%; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden; margin: 10px 0; }
    .progress-bar { height: 100%; background: #007cba; transition: width 0.3s ease; }
</style>";
echo "</head>";
echo "<body>";

echo "<h1>🗂️ Migrar Archivos a Estructura Organizada</h1>";

if (!isset($_GET['action'])) {
    // Mostrar información y confirmación
    echo "<div class='warning'>";
    echo "<h3>⚠️ IMPORTANTE - Leer antes de continuar</h3>";
    echo "<ul>";
    echo "<li><strong>Hacer backup completo</strong> de la carpeta uploads/ antes de continuar</li>";
    echo "<li>Este script mueve archivos de <code>uploads/archivo.jpg</code> a <code>uploads/legacy/archivo.jpg</code></li>";
    echo "<li>Los nuevos uploads usarán <code>uploads/YYYY/MM/archivo.jpg</code></li>";
    echo "<li>El script es <strong>irreversible</strong> sin backup</li>";
    echo "<li>Se mantendrá retrocompatibilidad total</li>";
    echo "</ul>";
    echo "</div>";

    // Analizar archivos existentes
    $uploadsDir = 'uploads';
    if (!is_dir($uploadsDir)) {
        echo "<div class='error'>❌ Directorio uploads/ no existe</div>";
        echo "</body></html>";
        exit;
    }

    $files = [];
    $totalSize = 0;
    $handle = opendir($uploadsDir);

    while (($file = readdir($handle)) !== false) {
        if ($file === '.' || $file === '..') continue;

        $fullPath = $uploadsDir . '/' . $file;
        if (is_file($fullPath)) {
            $size = filesize($fullPath);
            $files[] = [
                'name' => $file,
                'size' => $size,
                'date' => date('Y-m-d H:i:s', filemtime($fullPath))
            ];
            $totalSize += $size;
        }
    }
    closedir($handle);

    if (empty($files)) {
        echo "<div class='success'>✅ No hay archivos para migrar. La carpeta uploads/ ya está organizada.</div>";
        echo "</body></html>";
        exit;
    }

    echo "<h2>📊 Análisis de archivos existentes</h2>";
    echo "<p><strong>Archivos encontrados:</strong> " . count($files) . "</p>";
    echo "<p><strong>Tamaño total:</strong> " . formatBytes($totalSize) . "</p>";

    echo "<h3>📁 Archivos a migrar:</h3>";
    echo "<div style='max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;'>";
    foreach (array_slice($files, 0, 20) as $file) {
        echo "<div class='file-item'>";
        echo $file['name'] . " (" . formatBytes($file['size']) . " - " . $file['date'] . ")";
        echo "</div>";
    }
    if (count($files) > 20) {
        echo "<p><em>... y " . (count($files) - 20) . " archivos más</em></p>";
    }
    echo "</div>";

    echo "<h2>🎯 Acción a realizar</h2>";
    echo "<p>Los archivos se moverán a:</p>";
    echo "<div class='file-item'>uploads/legacy/[nombre_archivo]</div>";

    echo "<div class='warning'>";
    echo "<h3>✅ Antes de continuar:</h3>";
    echo "<ol>";
    echo "<li>Haz backup de la carpeta uploads/</li>";
    echo "<li>Verifica que tienes permisos de escritura</li>";
    echo "<li>Asegúrate de que no hay uploads en progreso</li>";
    echo "</ol>";
    echo "</div>";

    echo "<p>";
    echo "<a href='migrate_files.php?action=migrate' onclick='return confirm(\"¿Estás seguro? Esta acción es irreversible sin backup.\")' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Iniciar migración</a>";
    echo " ";
    echo "<a href='migrate_files.php?action=simulate' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Simular migración</a>";
    echo "</p>";
} elseif ($_GET['action'] === 'simulate') {
    // Simular migración
    echo "<h2>🧪 Simulación de migración</h2>";
    echo "<p>Esto muestra lo que pasaría <strong>sin realizar cambios reales</strong>:</p>";

    $uploadsDir = 'uploads';
    $legacyDir = $uploadsDir . '/legacy';

    echo "<div class='success'>✅ Se crearía directorio: $legacyDir</div>";

    $files = glob($uploadsDir . '/*');
    $moved = 0;

    foreach ($files as $file) {
        if (is_file($file)) {
            $filename = basename($file);
            $newPath = $legacyDir . '/' . $filename;
            echo "<div class='file-item'>📁 $file → $newPath</div>";
            $moved++;
        }
    }

    echo "<div class='success'>";
    echo "<h3>📊 Resumen de simulación:</h3>";
    echo "<ul>";
    echo "<li>Archivos a mover: $moved</li>";
    echo "<li>Directorio destino: $legacyDir</li>";
    echo "<li>Estado: <strong>SIMULACIÓN - Sin cambios reales</strong></li>";
    echo "</ul>";
    echo "</div>";

    echo "<p><a href='migrate_files.php'>← Volver</a></p>";
} elseif ($_GET['action'] === 'migrate') {
    // Realizar migración real
    echo "<h2>🚀 Iniciando migración real</h2>";

    $uploadsDir = 'uploads';
    $legacyDir = $uploadsDir . '/legacy';

    try {
        // Crear directorio legacy si no existe
        if (!is_dir($legacyDir)) {
            if (!mkdir($legacyDir, 0755, true)) {
                throw new Exception("No se pudo crear el directorio $legacyDir");
            }
            echo "<div class='success'>✅ Directorio creado: $legacyDir</div>";
        }

        // Obtener lista de archivos a migrar
        $files = [];
        $handle = opendir($uploadsDir);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..' || is_dir($uploadsDir . '/' . $file)) continue;
            $files[] = $file;
        }
        closedir($handle);

        if (empty($files)) {
            echo "<div class='success'>✅ No hay archivos para migrar</div>";
        } else {
            echo "<div class='progress'><div class='progress-bar' id='progressBar' style='width: 0%'></div></div>";
            echo "<div id='status'>Iniciando migración...</div>";

            $total = count($files);
            $moved = 0;
            $errors = 0;

            foreach ($files as $index => $file) {
                $oldPath = $uploadsDir . '/' . $file;
                $newPath = $legacyDir . '/' . $file;

                // Verificar que el archivo destino no existe
                if (file_exists($newPath)) {
                    echo "<div class='error'>⚠️ Saltar $file (ya existe en legacy)</div>";
                    continue;
                }

                // Mover archivo
                if (rename($oldPath, $newPath)) {
                    echo "<div class='success'>✅ Movido: $file</div>";
                    $moved++;
                } else {
                    echo "<div class='error'>❌ Error moviendo: $file</div>";
                    $errors++;
                }

                // Actualizar progreso
                $progress = (($index + 1) / $total) * 100;
                echo "<script>document.getElementById('progressBar').style.width = '{$progress}%';</script>";
                echo "<script>document.getElementById('status').innerHTML = 'Procesando " . ($index + 1) . " de $total archivos...';</script>";

                // Flush output para mostrar progreso en tiempo real
                if (ob_get_level()) ob_flush();
                flush();

                // Pequeña pausa para evitar sobrecarga
                usleep(50000); // 50ms
            }

            echo "<div class='success'>";
            echo "<h3>🎉 Migración completada</h3>";
            echo "<ul>";
            echo "<li><strong>Archivos movidos:</strong> $moved</li>";
            echo "<li><strong>Errores:</strong> $errors</li>";
            echo "<li><strong>Total procesados:</strong> $total</li>";
            echo "</ul>";
            echo "</div>";

            if ($moved > 0) {
                echo "<div class='warning'>";
                echo "<h3>📋 Próximos pasos:</h3>";
                echo "<ol>";
                echo "<li>Verifica que los archivos están en <code>uploads/legacy/</code></li>";
                echo "<li>Prueba que <code>simple_img_v2.php</code> puede acceder a los archivos legacy</li>";
                echo "<li>Configura el nuevo sistema de upload con <code>upload_organized.php</code></li>";
                echo "<li>Los nuevos archivos se guardarán en <code>uploads/YYYY/MM/</code></li>";
                echo "</ol>";
                echo "</div>";
            }
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    }

    echo "<p><a href='migrate_files.php'>← Volver</a></p>";
}

echo "</body>";
echo "</html>";

function formatBytes($size, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }

    return round($size, $precision) . ' ' . $units[$i];
}
