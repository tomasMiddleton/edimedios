<?php
// ARCHIVO: lib/SecurityManager.php
// Gestión de seguridad, CORS, rate limiting y validaciones

class SecurityManager
{
    private $config;
    private $configPath;
    private $rateLimitData = [];

    public function __construct($configPath = null)
    {
        $this->configPath = $configPath ?: __DIR__ . '/../config/security.json';
        $this->loadConfig();
        $this->initializeRateLimit();
    }

    /**
     * Cargar configuración de seguridad
     */
    private function loadConfig()
    {
        if (!file_exists($this->configPath)) {
            throw new Exception("Archivo de configuración de seguridad no encontrado: " . $this->configPath);
        }

        $configContent = file_get_contents($this->configPath);
        $this->config = json_decode($configContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error en configuración de seguridad: " . json_last_error_msg());
        }
    }

    /**
     * Obtener configuración completa o específica
     */
    public function getConfig($section = null)
    {
        if ($section) {
            return $this->config[$section] ?? null;
        }
        return $this->config;
    }

    /**
     * Actualizar configuración
     */
    public function updateConfig($section, $data, $updatedBy = 'system')
    {
        $this->config[$section] = $data;
        $this->config['last_updated'] = date('c');
        $this->config['updated_by'] = $updatedBy;

        $result = file_put_contents(
            $this->configPath,
            json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        if ($result === false) {
            throw new Exception("No se pudo guardar la configuración de seguridad");
        }

        $this->logSecurityEvent('config_updated', "Sección '$section' actualizada", [
            'section' => $section,
            'updated_by' => $updatedBy
        ]);

        return true;
    }

    /**
     * Aplicar headers CORS
     */
    public function applyCORS()
    {
        $corsConfig = $this->config['cors'] ?? [];

        if (!($corsConfig['enabled'] ?? false)) {
            return false;
        }

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowedOrigins = $corsConfig['allowed_origins'] ?? ['*'];

        // Verificar origen
        if ($origin && $this->isOriginAllowed($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");

            if ($corsConfig['allow_credentials'] ?? false) {
                header('Access-Control-Allow-Credentials: true');
            }
        } elseif (in_array('*', $allowedOrigins)) {
            header('Access-Control-Allow-Origin: *');
        } else {
            // Origen no permitido
            if ($origin) {
                $this->logSecurityEvent('cors_violation', "Origen no permitido: $origin", [
                    'origin' => $origin,
                    'allowed_origins' => $allowedOrigins
                ]);
            }
            return false;
        }

        // Headers adicionales
        $allowedMethods = implode(', ', $corsConfig['allowed_methods'] ?? ['GET', 'POST']);
        $allowedHeaders = implode(', ', $corsConfig['allowed_headers'] ?? ['Content-Type']);

        header("Access-Control-Allow-Methods: $allowedMethods");
        header("Access-Control-Allow-Headers: $allowedHeaders");
        header("Access-Control-Max-Age: " . ($corsConfig['max_age'] ?? 3600));

        return true;
    }

    /**
     * Verificar si origen está permitido
     */
    private function isOriginAllowed($origin, $allowedOrigins)
    {
        foreach ($allowedOrigins as $allowed) {
            if ($allowed === '*' || $allowed === $origin) {
                return true;
            }

            // Soporte para wildcards
            if (strpos($allowed, '*') !== false) {
                $pattern = str_replace('*', '.*', preg_quote($allowed, '/'));
                if (preg_match("/^$pattern$/i", $origin)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Verificar rate limiting
     */
    public function checkRateLimit($identifier = null)
    {
        $rateLimitConfig = $this->config['rate_limiting'] ?? [];

        if (!($rateLimitConfig['enabled'] ?? false)) {
            return true;
        }

        $identifier = $identifier ?: $this->getClientIdentifier();
        $currentTime = time();

        // Inicializar datos del cliente si no existen
        if (!isset($this->rateLimitData[$identifier])) {
            $this->rateLimitData[$identifier] = [
                'requests_minute' => [],
                'requests_hour' => [],
                'blocked_until' => 0
            ];
        }

        $clientData = &$this->rateLimitData[$identifier];

        // Verificar si está bloqueado
        if ($clientData['blocked_until'] > $currentTime) {
            $this->logSecurityEvent('rate_limit_blocked', "Cliente bloqueado: $identifier", [
                'identifier' => $identifier,
                'blocked_until' => date('Y-m-d H:i:s', $clientData['blocked_until'])
            ]);
            return false;
        }

        // Limpiar requests antiguos
        $clientData['requests_minute'] = array_filter(
            $clientData['requests_minute'],
            function ($time) use ($currentTime) {
                return $time > ($currentTime - 60);
            }
        );

        $clientData['requests_hour'] = array_filter(
            $clientData['requests_hour'],
            function ($time) use ($currentTime) {
                return $time > ($currentTime - 3600);
            }
        );

        // Verificar límites
        $requestsPerMinute = $rateLimitConfig['requests_per_minute'] ?? 60;
        $requestsPerHour = $rateLimitConfig['requests_per_hour'] ?? 1000;

        if (
            count($clientData['requests_minute']) >= $requestsPerMinute ||
            count($clientData['requests_hour']) >= $requestsPerHour
        ) {

            // Bloquear cliente
            $blockDuration = ($rateLimitConfig['blocked_duration_minutes'] ?? 15) * 60;
            $clientData['blocked_until'] = $currentTime + $blockDuration;

            $this->logSecurityEvent('rate_limit_exceeded', "Rate limit excedido: $identifier", [
                'identifier' => $identifier,
                'requests_minute' => count($clientData['requests_minute']),
                'requests_hour' => count($clientData['requests_hour']),
                'blocked_duration' => $blockDuration
            ]);

            return false;
        }

        // Registrar request actual
        $clientData['requests_minute'][] = $currentTime;
        $clientData['requests_hour'][] = $currentTime;

        return true;
    }

    /**
     * Obtener identificador único del cliente
     */
    private function getClientIdentifier()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return hash('sha256', $ip . '|' . $userAgent);
    }

    /**
     * Validar upload de archivo
     */
    public function validateFileUpload($file)
    {
        $uploadConfig = $this->config['file_upload'] ?? [];
        $errors = [];

        // Verificar tamaño
        $maxSize = ($uploadConfig['max_file_size_mb'] ?? 100) * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $errors[] = "Archivo demasiado grande. Máximo: " . ($maxSize / 1024 / 1024) . "MB";
        }

        // Verificar extensión
        $allowedExtensions = $uploadConfig['allowed_extensions'] ?? ['jpg', 'png'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = "Extensión no permitida. Permitidas: " . implode(', ', $allowedExtensions);
        }

        // Verificar MIME type
        $allowedMimeTypes = $uploadConfig['allowed_mime_types'] ?? ['image/jpeg', 'image/png'];
        if (!in_array($file['type'], $allowedMimeTypes)) {
            $errors[] = "Tipo de archivo no permitido. Tipo detectado: " . $file['type'];
        }

        // Verificar contenido ejecutable
        if ($uploadConfig['block_executable_content'] ?? true) {
            if ($this->hasExecutableContent($file['tmp_name'])) {
                $errors[] = "Archivo contiene contenido ejecutable y fue rechazado por seguridad";
            }
        }

        if (!empty($errors)) {
            $this->logSecurityEvent('file_upload_rejected', "Upload rechazado", [
                'filename' => $file['name'],
                'size' => $file['size'],
                'type' => $file['type'],
                'errors' => $errors
            ]);
        }

        return empty($errors);
    }

    /**
     * Detectar contenido ejecutable en archivo
     */
    private function hasExecutableContent($filePath)
    {
        if (!file_exists($filePath)) {
            return false;
        }

        // Leer primeros bytes del archivo
        $handle = fopen($filePath, 'rb');
        $header = fread($handle, 1024);
        fclose($handle);

        // Detectar headers ejecutables comunes
        $executableSignatures = [
            "\x4D\x5A", // PE executable
            "\x7F\x45\x4C\x46", // ELF executable
            "#!/", // Shebang script
            "<?php", // PHP script
            "<script", // JavaScript
        ];

        foreach ($executableSignatures as $signature) {
            if (strpos($header, $signature) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar IP whitelist/blacklist
     */
    public function checkIPAccess($ip = null)
    {
        $apiConfig = $this->config['api_security'] ?? [];
        $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? '');

        // Verificar blacklist
        $blacklist = $apiConfig['ip_blacklist'] ?? [];
        if (in_array($ip, $blacklist)) {
            $this->logSecurityEvent('ip_blocked', "IP en blacklist: $ip", ['ip' => $ip]);
            return false;
        }

        // Verificar whitelist (si está configurada)
        $whitelist = $apiConfig['ip_whitelist'] ?? [];
        if (!empty($whitelist) && !in_array($ip, $whitelist)) {
            $this->logSecurityEvent('ip_not_whitelisted', "IP no está en whitelist: $ip", ['ip' => $ip]);
            return false;
        }

        return true;
    }

    /**
     * Verificar User-Agent
     */
    public function checkUserAgent($userAgent = null)
    {
        $apiConfig = $this->config['api_security'] ?? [];
        $userAgent = $userAgent ?: ($_SERVER['HTTP_USER_AGENT'] ?? '');

        $blockedAgents = $apiConfig['block_user_agents'] ?? [];

        foreach ($blockedAgents as $blocked) {
            if (stripos($userAgent, $blocked) !== false) {
                $this->logSecurityEvent('user_agent_blocked', "User-Agent bloqueado", [
                    'user_agent' => $userAgent,
                    'blocked_pattern' => $blocked
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Verificar modo mantenimiento
     */
    public function checkMaintenanceMode()
    {
        $emergencyConfig = $this->config['emergency'] ?? [];

        if ($emergencyConfig['maintenance_mode'] ?? false) {
            $message = $emergencyConfig['maintenance_message'] ?? 'Sistema en mantenimiento';

            http_response_code(503);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'maintenance_mode',
                'message' => $message,
                'retry_after' => '3600' // 1 hora
            ]);
            exit;
        }
    }

    /**
     * Aplicar todas las validaciones de seguridad
     */
    public function applySecurityChecks()
    {
        // Modo mantenimiento
        $this->checkMaintenanceMode();

        // CORS
        $this->applyCORS();

        // Rate limiting
        if (!$this->checkRateLimit()) {
            http_response_code(429);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'rate_limit_exceeded',
                'message' => 'Demasiadas solicitudes. Intente más tarde.',
                'retry_after' => '900' // 15 minutos
            ]);
            exit;
        }

        // IP access
        if (!$this->checkIPAccess()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'access_denied',
                'message' => 'Acceso denegado desde esta IP'
            ]);
            exit;
        }

        // User Agent
        if (!$this->checkUserAgent()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'user_agent_blocked',
                'message' => 'User-Agent no permitido'
            ]);
            exit;
        }

        return true;
    }

    /**
     * Registrar evento de seguridad
     */
    private function logSecurityEvent($type, $message, $details = [])
    {
        $loggingConfig = $this->config['logging'] ?? [];

        if (!($loggingConfig['log_security_events'] ?? true)) {
            return;
        }

        $logEntry = [
            'timestamp' => date('c'),
            'type' => $type,
            'message' => $message,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'details' => $details
        ];

        $logFile = __DIR__ . '/../logs/security.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents(
            $logFile,
            json_encode($logEntry) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }

    /**
     * Inicializar rate limiting desde archivo
     */
    private function initializeRateLimit()
    {
        $cacheFile = __DIR__ . '/../temp/rate_limit.json';

        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            $this->rateLimitData = $data ?: [];
        }

        // Limpiar datos antiguos al inicializar
        $this->cleanupRateLimitData();

        // Guardar datos actualizados
        register_shutdown_function([$this, 'saveRateLimitData']);
    }

    /**
     * Limpiar datos antiguos de rate limiting
     */
    private function cleanupRateLimitData()
    {
        $currentTime = time();

        foreach ($this->rateLimitData as $identifier => &$data) {
            // Limpiar requests antiguos
            $data['requests_minute'] = array_filter(
                $data['requests_minute'] ?? [],
                function ($time) use ($currentTime) {
                    return $time > ($currentTime - 60);
                }
            );

            $data['requests_hour'] = array_filter(
                $data['requests_hour'] ?? [],
                function ($time) use ($currentTime) {
                    return $time > ($currentTime - 3600);
                }
            );

            // Eliminar entradas vacías
            if (
                empty($data['requests_minute']) &&
                empty($data['requests_hour']) &&
                ($data['blocked_until'] ?? 0) <= $currentTime
            ) {
                unset($this->rateLimitData[$identifier]);
            }
        }
    }

    /**
     * Guardar datos de rate limiting
     */
    public function saveRateLimitData()
    {
        $cacheFile = __DIR__ . '/../temp/rate_limit.json';
        $cacheDir = dirname($cacheFile);

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        file_put_contents($cacheFile, json_encode($this->rateLimitData), LOCK_EX);
    }
}
