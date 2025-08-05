<?php
// ARCHIVO: test_security.php
// Script de prueba para verificar el sistema de seguridad

// Establecer codificación UTF-8
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>🛡️ Test Sistema de Seguridad</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; }
    .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0; color: #155724; }
    .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 10px 0; color: #721c24; }
    .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 10px 0; color: #856404; }
    .info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 10px 0; color: #0c5460; }
    .test-section { background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #007cba; }
    .config-item { background: white; padding: 10px; margin: 5px 0; border-radius: 3px; border: 1px solid #ddd; }
    h1 { color: #333; }
    h2 { color: #007cba; border-bottom: 2px solid #007cba; padding-bottom: 5px; }
    .security { background: #f8f9fa; border: 1px solid #007cba; padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #28a745; }
    code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
</style>";
echo "</head>";
echo "<body>";

echo "<h1>🛡️ Test Sistema de Seguridad EDI Medios</h1>";

// Verificar que el sistema esté disponible
try {
    require_once(__DIR__ . '/lib/SecurityManager.php');
    $security = new SecurityManager();
    echo "<div class='success'>✅ Sistema de seguridad cargado correctamente</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error cargando sistema: " . $e->getMessage() . "</div>";
    echo "</body></html>";
    exit;
}

// Test 1: Verificar configuración
echo "<div class='test-section'>";
echo "<h2>⚙️ Test 1: Verificar Configuración de Seguridad</h2>";

try {
    $config = $security->getConfig();

    echo "<div class='success'>✅ Configuración cargada correctamente</div>";
    echo "<div class='info'>";
    echo "<h3>📋 Configuración actual:</h3>";

    // CORS
    $corsConfig = $config['cors'] ?? [];
    echo "<div class='config-item'>";
    echo "<strong>🌐 CORS:</strong> " . ($corsConfig['enabled'] ? '✅ Habilitado' : '❌ Deshabilitado') . "<br>";
    echo "<strong>Orígenes permitidos:</strong> " . implode(', ', $corsConfig['allowed_origins'] ?? []) . "<br>";
    echo "<strong>Métodos:</strong> " . implode(', ', $corsConfig['allowed_methods'] ?? []);
    echo "</div>";

    // Rate Limiting
    $rlConfig = $config['rate_limiting'] ?? [];
    echo "<div class='config-item'>";
    echo "<strong>⚡ Rate Limiting:</strong> " . ($rlConfig['enabled'] ? '✅ Habilitado' : '❌ Deshabilitado') . "<br>";
    echo "<strong>Requests por minuto:</strong> " . ($rlConfig['requests_per_minute'] ?? 'N/A') . "<br>";
    echo "<strong>Requests por hora:</strong> " . ($rlConfig['requests_per_hour'] ?? 'N/A');
    echo "</div>";

    // File Upload
    $fuConfig = $config['file_upload'] ?? [];
    echo "<div class='config-item'>";
    echo "<strong>📁 Upload:</strong><br>";
    echo "<strong>Tamaño máximo:</strong> " . ($fuConfig['max_file_size_mb'] ?? 'N/A') . " MB<br>";
    echo "<strong>Extensiones:</strong> " . implode(', ', $fuConfig['allowed_extensions'] ?? []) . "<br>";
    echo "<strong>Bloquear ejecutables:</strong> " . ($fuConfig['block_executable_content'] ? '✅ Sí' : '❌ No');
    echo "</div>";

    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error verificando configuración: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 2: Probar CORS
echo "<div class='test-section'>";
echo "<h2>🌐 Test 2: Verificar CORS</h2>";

try {
    $corsConfig = $config['cors'] ?? [];
    $allowedOrigins = $corsConfig['allowed_origins'] ?? [];

    echo "<div class='success'>✅ Sistema CORS disponible</div>";
    echo "<div class='info'>";
    echo "<h3>🧪 Pruebas de CORS:</h3>";

    $testOrigins = [
        'https://medios.void.cl',
        'https://www.medios.void.cl',
        'http://localhost:3000',
        'https://evil-site.com',
        'https://subdomain.medios.void.cl'
    ];

    foreach ($testOrigins as $testOrigin) {
        $isAllowed = false;
        foreach ($allowedOrigins as $allowed) {
            if ($allowed === '*' || $allowed === $testOrigin) {
                $isAllowed = true;
                break;
            }
            if (strpos($allowed, '*') !== false) {
                $pattern = str_replace('*', '.*', preg_quote($allowed, '/'));
                if (preg_match("/^$pattern$/i", $testOrigin)) {
                    $isAllowed = true;
                    break;
                }
            }
        }

        $statusClass = $isAllowed ? 'success' : 'warning';
        $statusIcon = $isAllowed ? '✅' : '❌';

        echo "<div class='config-item'>";
        echo "<strong>Origen:</strong> <code>$testOrigin</code> ";
        echo "<span class='$statusClass' style='padding: 2px 6px; border-radius: 3px;'>$statusIcon " . ($isAllowed ? 'Permitido' : 'Bloqueado') . "</span>";
        echo "</div>";
    }

    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error probando CORS: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 3: Probar Rate Limiting
echo "<div class='test-section'>";
echo "<h2>⚡ Test 3: Verificar Rate Limiting</h2>";

try {
    $rlConfig = $config['rate_limiting'] ?? [];

    echo "<div class='success'>✅ Sistema de Rate Limiting disponible</div>";
    echo "<div class='info'>";
    echo "<h3>🧪 Pruebas de Rate Limiting:</h3>";

    if ($rlConfig['enabled'] ?? false) {
        // Simular múltiples requests
        $testIdentifier = 'test_client_' . time();
        $passedRequests = 0;
        $blockedRequests = 0;

        for ($i = 0; $i < 5; $i++) {
            if ($security->checkRateLimit($testIdentifier)) {
                $passedRequests++;
            } else {
                $blockedRequests++;
            }
        }

        echo "<div class='config-item'>";
        echo "<strong>Requests permitidos:</strong> $passedRequests<br>";
        echo "<strong>Requests bloqueados:</strong> $blockedRequests<br>";
        echo "<strong>Límite por minuto:</strong> " . ($rlConfig['requests_per_minute'] ?? 'N/A');
        echo "</div>";
    } else {
        echo "<div class='warning'>⚠️ Rate Limiting está deshabilitado</div>";
    }

    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error probando Rate Limiting: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 4: Verificar APIs de gestión
echo "<div class='test-section'>";
echo "<h2>🔌 Test 4: Verificar API de Gestión</h2>";

// Hacer petición a security_manager.php
$securityManagerUrl = 'security_manager.php?action=get_config';
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents($securityManagerUrl, false, $context);

if ($response !== false) {
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "<div class='success'>✅ API de security_manager.php funciona correctamente</div>";
        echo "<div class='info'>";
        echo "<h4>📊 Información de la API:</h4>";
        echo "<ul>";
        echo "<li>Versión: " . ($data['version'] ?? 'N/A') . "</li>";
        echo "<li>Última actualización: " . ($data['last_updated'] ?? 'N/A') . "</li>";
        echo "<li>Configuración cargada: ✅</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='error'>❌ API responde pero con error: " . ($data['error'] ?? 'Error desconocido') . "</div>";
    }
} else {
    echo "<div class='error'>❌ No se pudo conectar con security_manager.php</div>";
    echo "<div class='info'>Verifica que el archivo security_manager.php esté en el mismo directorio</div>";
}
echo "</div>";

// Test 5: Verificar archivos del sistema
echo "<div class='test-section'>";
echo "<h2>📁 Test 5: Verificar Archivos del Sistema</h2>";

$requiredFiles = [
    'config/security.json' => 'Configuración de seguridad',
    'lib/SecurityManager.php' => 'Clase principal de seguridad',
    'security_manager.php' => 'API de gestión de seguridad'
];

$allFilesExist = true;

foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        echo "<div class='config-item'>";
        echo "✅ <strong>$file</strong> - $description";
        echo "</div>";
    } else {
        echo "<div class='config-item'>";
        echo "❌ <strong>$file</strong> - $description <span style='color: red;'>(FALTA)</span>";
        echo "</div>";
        $allFilesExist = false;
    }
}

if ($allFilesExist) {
    echo "<div class='success'>✅ Todos los archivos del sistema están presentes</div>";
} else {
    echo "<div class='error'>❌ Faltan archivos del sistema de seguridad</div>";
}
echo "</div>";

// Enlaces útiles
echo "<div class='info'>";
echo "<h3>🔗 Enlaces para usar el sistema completo:</h3>";
echo "<ul>";
echo "<li><a href='stats_dashboard.php' target='_blank'>📊 Dashboard de Estadísticas</a> - Ver dashboard con gestión de seguridad</li>";
echo "<li><a href='security_manager.php?action=get_config' target='_blank'>⚙️ API de Configuración</a> - Ver configuración JSON</li>";
echo "<li><a href='simple_img_v3.php' target='_blank'>🖼️ Optimizador v3</a> - Probar con seguridad aplicada</li>";
if (file_exists('index.php')) {
    echo "<li><a href='index.php' target='_blank'>📤 Sistema de Upload</a> - Probar upload con validación</li>";
}
echo "</ul>";
echo "</div>";

echo "<div class='security'>";
echo "<h3>🛡️ Resumen del Sistema de Seguridad</h3>";
echo "<p>El sistema de seguridad EDI Medios incluye:</p>";
echo "<ul>";
echo "<li>✅ <strong>CORS configurable</strong> - Control de acceso cross-origin</li>";
echo "<li>✅ <strong>Rate Limiting</strong> - Prevención de abuso</li>";
echo "<li>✅ <strong>Validación de uploads</strong> - Archivos seguros</li>";
echo "<li>✅ <strong>IP Whitelist/Blacklist</strong> - Control de acceso por IP</li>";
echo "<li>✅ <strong>User-Agent filtering</strong> - Bloqueo de bots</li>";
echo "<li>✅ <strong>Modo mantenimiento</strong> - Control de emergencia</li>";
echo "<li>✅ <strong>Logs de seguridad</strong> - Auditoría completa</li>";
echo "<li>✅ <strong>Configuración dinámica</strong> - Cambios sin reinicio</li>";
echo "</ul>";
echo "<p><strong>🚀 Próximo paso:</strong> Configurar desde el dashboard de estadísticas → Gestión → Seguridad</p>";
echo "</div>";

echo "</body>";
echo "</html>";
