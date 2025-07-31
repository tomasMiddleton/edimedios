<?php
// ARCHIVO: simple_test.php
// Test básico para verificar que PHP funciona

echo "<!DOCTYPE html>";
echo "<html><head><title>Test PHP</title></head><body>";
echo "<h1>✅ PHP está funcionando</h1>";
echo "<p><strong>Fecha/Hora:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Versión PHP:</strong> " . phpversion() . "</p>";

// Test de extensiones básicas
echo "<h2>Extensiones disponibles:</h2>";
echo "<ul>";
echo "<li>GD: " . (extension_loaded('gd') ? '✅ SÍ' : '❌ NO') . "</li>";
echo "<li>cURL: " . (extension_loaded('curl') ? '✅ SÍ' : '❌ NO') . "</li>";
echo "<li>JSON: " . (extension_loaded('json') ? '✅ SÍ' : '❌ NO') . "</li>";
echo "</ul>";

// Test de directorios
echo "<h2>Directorios:</h2>";
echo "<ul>";
echo "<li>uploads/: " . (is_dir('uploads') ? '✅ Existe' : '❌ No existe') . "</li>";
echo "<li>cache/: " . (is_dir('cache') ? '✅ Existe' : '❌ No existe') . "</li>";
echo "</ul>";

// Test de archivos
echo "<h2>Archivos clave:</h2>";
echo "<ul>";
echo "<li>optimize.php: " . (file_exists('optimize.php') ? '✅ Existe' : '❌ No existe') . "</li>";
echo "<li>img.php: " . (file_exists('img.php') ? '✅ Existe' : '❌ No existe') . "</li>";
echo "<li>.htaccess: " . (file_exists('.htaccess') ? '✅ Existe' : '❌ No existe') . "</li>";
echo "</ul>";

echo "<h2>Información del servidor:</h2>";
echo "<ul>";
echo "<li><strong>Servidor:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido') . "</li>";
echo "<li><strong>REQUEST_URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'No definido') . "</li>";
echo "<li><strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'No definido') . "</li>";
echo "</ul>";

echo "<h2>URLs de prueba:</h2>";
echo "<p>Prueba estas URLs para verificar el sistema:</p>";
echo "<ul>";
echo "<li><a href='img.php?src=dbdc084939e778491a168dfbd94f14ba.jpg&w=100&h=100' target='_blank'>Thumbnail 100x100</a></li>";
echo "<li><a href='img.php?src=dbdc084939e778491a168dfbd94f14ba.jpg&w=200&h=200&f=webp' target='_blank'>Thumbnail 200x200 WebP</a></li>";
echo "<li><a href='uploads/dbdc084939e778491a168dfbd94f14ba.jpg' target='_blank'>Imagen original</a></li>";
echo "</ul>";

echo "</body></html>";
