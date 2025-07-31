<?php
// ARCHIVO: lib/Logger.php
// Sistema de logging robusto para EDI Medios

class Logger
{
    private $config;
    private $logDir;

    public function __construct($config)
    {
        $this->config = $config;
        $this->logDir = $config['log_directory'];

        // Crear directorio de logs si no existe
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    /**
     * Log de nivel DEBUG
     */
    public function debug($message, $context = [])
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * Log de nivel INFO
     */
    public function info($message, $context = [])
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * Log de nivel WARNING
     */
    public function warning($message, $context = [])
    {
        $this->log('WARNING', $message, $context);
    }

    /**
     * Log de nivel ERROR
     */
    public function error($message, $context = [])
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * Método principal de logging
     */
    private function log($level, $message, $context = [])
    {
        // Verificar si el nivel está habilitado
        if (!$this->shouldLog($level)) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'cli',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'cli'
        ];

        // Formatear entrada de log
        $logLine = sprintf(
            "[%s] %s: %s %s\n",
            $timestamp,
            $level,
            $message,
            !empty($context) ? json_encode($context) : ''
        );

        // Escribir a archivo
        $this->writeToFile($level, $logLine);

        // Rotar logs si es necesario
        if ($this->config['log_rotation']) {
            $this->rotateLogsIfNeeded();
        }
    }

    /**
     * Verifica si se debe registrar el nivel de log
     */
    private function shouldLog($level)
    {
        $levels = ['DEBUG' => 1, 'INFO' => 2, 'WARNING' => 3, 'ERROR' => 4];
        $currentLevel = $levels[$this->config['log_level']] ?? 2;
        $messageLevel = $levels[$level] ?? 2;

        return $messageLevel >= $currentLevel;
    }

    /**
     * Escribe al archivo de log
     */
    private function writeToFile($level, $logLine)
    {
        $fileName = strtolower($level) . '_' . date('Y-m-d') . '.log';
        $filePath = $this->logDir . $fileName;

        file_put_contents($filePath, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Rota los logs antiguos
     */
    private function rotateLogsIfNeeded()
    {
        $maxFiles = $this->config['max_log_files'] ?? 30;
        $logFiles = glob($this->logDir . '*.log');

        if (count($logFiles) > $maxFiles) {
            // Ordenar por fecha de modificación
            usort($logFiles, function ($a, $b) {
                return filemtime($a) - filemtime($b);
            });

            // Eliminar archivos más antiguos
            $filesToDelete = array_slice($logFiles, 0, count($logFiles) - $maxFiles);
            foreach ($filesToDelete as $file) {
                unlink($file);
            }
        }
    }

    /**
     * Log específico para uploads
     */
    public function logUpload($fileName, $fileSize, $success = true, $error = null)
    {
        if (!$this->config['log_uploads']) {
            return;
        }

        $message = $success ? "Archivo subido exitosamente" : "Error al subir archivo";
        $context = [
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'success' => $success,
            'error' => $error
        ];

        if ($success) {
            $this->info($message, $context);
        } else {
            $this->error($message, $context);
        }
    }

    /**
     * Log de acceso
     */
    public function logAccess($endpoint, $method = 'GET', $responseCode = 200)
    {
        if (!$this->config['log_access']) {
            return;
        }

        $this->info("Acceso registrado", [
            'endpoint' => $endpoint,
            'method' => $method,
            'response_code' => $responseCode,
            'execution_time' => $this->getExecutionTime()
        ]);
    }

    /**
     * Log de seguridad
     */
    public function logSecurity($event, $severity = 'WARNING', $details = [])
    {
        $this->log($severity, "Evento de seguridad: " . $event, $details);
    }

    /**
     * Obtiene el tiempo de ejecución
     */
    private function getExecutionTime()
    {
        if (defined('REQUEST_START_TIME')) {
            return round((microtime(true) - REQUEST_START_TIME) * 1000, 2) . 'ms';
        }
        return 'unknown';
    }

    /**
     * Obtiene estadísticas de logs
     */
    public function getLogStats()
    {
        $stats = [
            'total_files' => 0,
            'total_size' => 0,
            'last_modified' => null,
            'files_by_level' => []
        ];

        $logFiles = glob($this->logDir . '*.log');
        foreach ($logFiles as $file) {
            $stats['total_files']++;
            $stats['total_size'] += filesize($file);

            $modified = filemtime($file);
            if (!$stats['last_modified'] || $modified > $stats['last_modified']) {
                $stats['last_modified'] = $modified;
            }

            // Extraer nivel del nombre del archivo
            $level = strtoupper(explode('_', basename($file))[0]);
            $stats['files_by_level'][$level] = ($stats['files_by_level'][$level] ?? 0) + 1;
        }

        return $stats;
    }
}
