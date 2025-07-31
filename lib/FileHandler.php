<?php
// ARCHIVO: lib/FileHandler.php
// Clase para manejo seguro y robusto de archivos

require_once(__DIR__ . '/Logger.php');

class FileHandler
{
    private $config;
    private $logger;

    public function __construct($config = null)
    {
        $this->config = $config ?: include(__DIR__ . '/../config/config.php');
        $this->logger = new Logger($this->config['logging']);
    }

    /**
     * Valida un archivo subido
     */
    public function validateFile($file)
    {
        $errors = [];

        // Verificar si hay errores de PHP
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->getUploadError($file['error']);
        }

        // Verificar tamaño
        if ($file['size'] > $this->config['upload']['max_file_size']) {
            $errors[] = "Archivo demasiado grande. Máximo: " .
                $this->formatBytes($this->config['upload']['max_file_size']);
        }

        // Verificar extensión
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->config['upload']['allowed_extensions'])) {
            $errors[] = "Extensión no permitida. Permitidas: " .
                implode(', ', $this->config['upload']['allowed_extensions']);
        }

        // Verificar MIME type real
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->config['upload']['allowed_mime_types'])) {
            $errors[] = "Tipo de archivo no permitido. Detectado: " . $mimeType;
        }

        // Verificar que no sea un archivo ejecutable
        if ($this->isExecutableFile($file['tmp_name'])) {
            $errors[] = "Archivo potencialmente peligroso detectado";
        }

        return $errors;
    }

    /**
     * Procesa la carga de un archivo
     */
    public function uploadFile($file, $customName = null)
    {
        // Validar archivo
        $errors = $this->validateFile($file);
        if (!empty($errors)) {
            $this->logger->error("Archivo rechazado", [
                'file' => $file['name'],
                'errors' => $errors,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            throw new Exception("Errores de validación: " . implode('; ', $errors));
        }

        // Generar nombre único y seguro
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = $customName ?: $this->generateSecureFileName($file['name']);
        $fileName = $this->sanitizeFileName($fileName) . '.' . $extension;

        // Crear directorio si no existe
        $uploadDir = $this->config['upload']['upload_directory'];
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filePath = $uploadDir . $fileName;

        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception("Error al mover el archivo");
        }

        // Registrar la carga
        $fileInfo = [
            'original_name' => $file['name'],
            'stored_name' => $fileName,
            'size' => $file['size'],
            'mime_type' => $file['type'],
            'path' => $filePath,
            'uploaded_at' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];

        $this->logger->info("Archivo subido exitosamente", $fileInfo);

        // Guardar metadata
        $this->saveFileMetadata($fileName, $fileInfo);

        return $fileInfo;
    }

    /**
     * Genera un nombre de archivo seguro
     */
    private function generateSecureFileName($originalName)
    {
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        return md5($baseName . $timestamp . $random);
    }

    /**
     * Sanitiza un nombre de archivo
     */
    private function sanitizeFileName($fileName)
    {
        // Remover caracteres peligrosos
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
        // Evitar nombres de archivo peligrosos
        $dangerous = ['.htaccess', '.htpasswd', 'index.php', 'config.php'];
        if (in_array(strtolower($fileName), $dangerous)) {
            $fileName = 'safe_' . $fileName;
        }
        return $fileName;
    }

    /**
     * Verifica si un archivo es ejecutable
     */
    private function isExecutableFile($filePath)
    {
        $content = file_get_contents($filePath, false, null, 0, 1024);

        // Buscar patrones de archivos ejecutables
        $patterns = [
            '/^<\?php/i',
            '/^#!/i',
            '/MZ/', // PE header
            '/\x7fELF/', // ELF header
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtiene el mensaje de error de upload
     */
    private function getUploadError($errorCode)
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return "El archivo es demasiado grande (límite PHP)";
            case UPLOAD_ERR_FORM_SIZE:
                return "El archivo es demasiado grande (límite formulario)";
            case UPLOAD_ERR_PARTIAL:
                return "El archivo se subió parcialmente";
            case UPLOAD_ERR_NO_FILE:
                return "No se subió ningún archivo";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Falta directorio temporal";
            case UPLOAD_ERR_CANT_WRITE:
                return "Error de escritura en disco";
            case UPLOAD_ERR_EXTENSION:
                return "Carga detenida por extensión";
            default:
                return "Error desconocido";
        }
    }

    /**
     * Formatea bytes en formato legible
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Guarda metadata del archivo
     */
    private function saveFileMetadata($fileName, $metadata)
    {
        $metadataDir = 'storage/metadata/';
        if (!is_dir($metadataDir)) {
            mkdir($metadataDir, 0755, true);
        }

        $metadataFile = $metadataDir . md5($fileName) . '.json';
        file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
    }

    /**
     * Obtiene metadata de un archivo
     */
    public function getFileMetadata($fileName)
    {
        $metadataFile = 'storage/metadata/' . md5($fileName) . '.json';
        if (file_exists($metadataFile)) {
            return json_decode(file_get_contents($metadataFile), true);
        }
        return null;
    }

    /**
     * Lista todos los archivos con metadata
     */
    public function listFiles()
    {
        $files = [];
        $uploadDir = $this->config['upload']['upload_directory'];

        if (is_dir($uploadDir)) {
            foreach (glob($uploadDir . '*') as $filePath) {
                if (is_file($filePath)) {
                    $fileName = basename($filePath);
                    $metadata = $this->getFileMetadata($fileName);

                    $files[] = [
                        'name' => $fileName,
                        'path' => $filePath,
                        'size' => filesize($filePath),
                        'modified' => filemtime($filePath),
                        'metadata' => $metadata
                    ];
                }
            }
        }

        return $files;
    }
}
