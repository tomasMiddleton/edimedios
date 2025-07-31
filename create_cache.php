<?php
// Establecer codificación UTF-8
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Crear Directorio Cache</title>";
echo "</head>";
echo "<body>";
echo "<h1>Crear Directorio Cache</h1>";

if (is_dir('cache')) {
    echo "<p>✅ Directorio cache ya existe</p>";
} else {
    if (mkdir('cache', 0755)) {
        echo "<p>✅ Directorio cache creado exitosamente</p>";
    } else {
        echo "<p>❌ Error al crear directorio cache</p>";
    }
}

echo "<p><a href='simple_img.php'>← Volver al sistema de optimización</a></p>";
echo "</body>";
echo "</html>";
