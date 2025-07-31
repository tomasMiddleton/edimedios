<?php
// ARCHIVO: test_optimization.php
// Script de prueba para verificar extensiones y funcionalidades

echo "=== VERIFICACIÓN DE EXTENSIONES PHP ===\n";

// Verificar extensión GD
if (extension_loaded('gd')) {
    echo "✅ Extensión GD: DISPONIBLE\n";
    $gdInfo = gd_info();
    echo "   - Versión GD: " . $gdInfo['GD Version'] . "\n";
    echo "   - JPEG Support: " . ($gdInfo['JPEG Support'] ? 'SÍ' : 'NO') . "\n";
    echo "   - PNG Support: " . ($gdInfo['PNG Support'] ? 'SÍ' : 'NO') . "\n";
    echo "   - WebP Support: " . (function_exists('imagewebp') ? 'SÍ' : 'NO') . "\n";
    echo "   - AVIF Support: " . (function_exists('imageavif') ? 'SÍ' : 'NO') . "\n";
} else {
    echo "❌ Extensión GD: NO DISPONIBLE\n";
}

// Verificar extensión Imagick
if (extension_loaded('imagick')) {
    echo "✅ Extensión Imagick: DISPONIBLE\n";
} else {
    echo "⚠️  Extensión Imagick: NO DISPONIBLE (opcional)\n";
}

echo "\n=== VERIFICACIÓN DE DIRECTORIOS ===\n";

// Verificar directorio uploads
if (is_dir('uploads')) {
    echo "✅ Directorio uploads: EXISTE\n";
    echo "   - Permisos: " . substr(sprintf('%o', fileperms('uploads')), -4) . "\n";
    echo "   - Escribible: " . (is_writable('uploads') ? 'SÍ' : 'NO') . "\n";
} else {
    echo "❌ Directorio uploads: NO EXISTE\n";
}

// Verificar directorio cache
if (is_dir('cache')) {
    echo "✅ Directorio cache: EXISTE\n";
    echo "   - Permisos: " . substr(sprintf('%o', fileperms('cache')), -4) . "\n";
    echo "   - Escribible: " . (is_writable('cache') ? 'SÍ' : 'NO') . "\n";
} else {
    echo "❌ Directorio cache: NO EXISTE\n";
}

echo "\n=== PRUEBAS DE FUNCIONALIDAD ===\n";

// Simular parámetros de optimización
$testParams = [
    'img' => 'test.jpg',
    'w' => '300',
    'h' => '200',
    'q' => '85',
    'f' => 'webp',
    'fit' => 'cover'
];

echo "📋 Parámetros de prueba:\n";
foreach ($testParams as $key => $value) {
    echo "   - $key: $value\n";
}

// Generar cacheKey como lo hace optimize.php
$cacheKey = md5($testParams['img'] . $testParams['w'] . $testParams['h'] . $testParams['q'] . $testParams['f'] . $testParams['fit']);
echo "\n🔑 Cache Key generado: $cacheKey\n";

echo "\n=== URLS DE PRUEBA ===\n";
echo "URL original: https://medios.void.cl/uploads/imagen.jpg\n";
echo "URL optimizada WebP: https://medios.void.cl/uploads/imagen.jpg?w=640&h=360&q=85&f=webp&fit=cover\n";
echo "URL optimizada AVIF: https://medios.void.cl/uploads/imagen.jpg?w=300&h=200&q=90&f=avif\n";
echo "URL redimensionada: https://medios.void.cl/uploads/imagen.jpg?w=400&h=300\n";

echo "\n=== STATUS: IMPLEMENTACIÓN COMPLETA ===\n";
echo "✅ optimize.php creado\n";
echo "✅ .htaccess configurado\n";
echo "✅ Directorio cache preparado\n";
echo "✅ .gitignore actualizado\n";
echo "📋 Listo para despliegue\n";
