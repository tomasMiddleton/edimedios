<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PHP Info</h1>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Date: " . date('Y-m-d H:i:s') . "</p>";

if (extension_loaded('gd')) {
    echo "<p>GD: DISPONIBLE</p>";
} else {
    echo "<p>GD: NO DISPONIBLE</p>";
}

echo "<p>Working Directory: " . getcwd() . "</p>";
echo "<p>Script Name: " . __FILE__ . "</p>";

if (is_dir('uploads')) {
    echo "<p>Directorio uploads: EXISTE</p>";
} else {
    echo "<p>Directorio uploads: NO EXISTE</p>";
}

if (is_dir('cache')) {
    echo "<p>Directorio cache: EXISTE</p>";
} else {
    echo "<p>Directorio cache: NO EXISTE</p>";
}

echo "<h2>Archivos en directorio actual:</h2>";
$files = scandir('.');
echo "<ul>";
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "<li>" . $file . "</li>";
    }
}
echo "</ul>";
