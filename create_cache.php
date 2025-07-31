<?php
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
