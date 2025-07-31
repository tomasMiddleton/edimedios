<?php
// ARCHIVO: test_rewrite.php
// Test para verificar reglas de rewrite

echo "<h1>Test de Reglas de Rewrite</h1>";

echo "<h2>Información del servidor:</h2>";
echo "<p><strong>REQUEST_URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'No definido') . "</p>";
echo "<p><strong>QUERY_STRING:</strong> " . ($_SERVER['QUERY_STRING'] ?? 'No definido') . "</p>";
echo "<p><strong>SCRIPT_NAME:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'No definido') . "</p>";
echo "<p><strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'No definido') . "</p>";

echo "<h2>Variables GET recibidas:</h2>";
if (!empty($_GET)) {
    echo "<pre>";
    print_r($_GET);
    echo "</pre>";
} else {
    echo "<p>No se recibieron parámetros GET</p>";
}

echo "<h2>Variables SERVER:</h2>";
$serverVars = ['REQUEST_METHOD', 'REQUEST_URI', 'QUERY_STRING', 'SCRIPT_NAME', 'DOCUMENT_ROOT', 'HTTP_HOST'];
foreach ($serverVars as $var) {
    echo "<p><strong>{$var}:</strong> " . ($_SERVER[$var] ?? 'No definido') . "</p>";
}

echo "<h2>Estado de mod_rewrite:</h2>";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    echo "<p><strong>mod_rewrite:</strong> " . (in_array('mod_rewrite', $modules) ? '✅ Activo' : '❌ No activo') . "</p>";
} else {
    echo "<p>No se puede verificar mod_rewrite (función apache_get_modules no disponible)</p>";
}

echo "<h2>Test de acceso a archivos:</h2>";
$htaccessPath = __DIR__ . '/.htaccess';
echo "<p><strong>.htaccess existe:</strong> " . (file_exists($htaccessPath) ? '✅ SÍ' : '❌ NO') . "</p>";
echo "<p><strong>.htaccess legible:</strong> " . (is_readable($htaccessPath) ? '✅ SÍ' : '❌ NO') . "</p>";

if (file_exists($htaccessPath)) {
    echo "<h3>Contenido de .htaccess:</h3>";
    echo "<pre>" . htmlspecialchars(file_get_contents($htaccessPath)) . "</pre>";
}

echo "<h2>Links de prueba:</h2>";
echo "<p>Para probar si funciona el rewrite, intenta estos enlaces:</p>";
echo "<ul>";
echo "<li><a href='test_rewrite.php?test=1'>test_rewrite.php?test=1</a></li>";
echo "<li><a href='uploads/dbdc084939e778491a168dfbd94f14ba.jpg'>Imagen sin parámetros</a></li>";
echo "<li><a href='uploads/dbdc084939e778491a168dfbd94f14ba.jpg?w=100'>Imagen con parámetro w=100</a></li>";
echo "</ul>";

if (isset($_GET['test'])) {
    echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>✅ PHP está funcionando correctamente</strong><br>";
    echo "Parámetro test recibido: " . htmlspecialchars($_GET['test']);
    echo "</div>";
}

// Verificar si estamos siendo llamados desde optimize.php
if (isset($_GET['img'])) {
    echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>🔄 Posible redirección desde rewrite</strong><br>";
    echo "Parámetro img recibido: " . htmlspecialchars($_GET['img']);
    echo "</div>";
}
