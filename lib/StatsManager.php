<?php
// ARCHIVO: lib/StatsManager.php
// Sistema de estadísticas con SQLite para tracking de imágenes

class StatsManager
{
    private $db;
    private $dbPath;

    public function __construct($dbPath = null)
    {
        $this->dbPath = $dbPath ?: __DIR__ . '/../storage/stats.db';
        $this->initDatabase();
    }

    /**
     * Inicializar base de datos SQLite
     */
    private function initDatabase()
    {
        // Crear directorio si no existe
        $dbDir = dirname($this->dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        try {
            $this->db = new PDO('sqlite:' . $this->dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Crear tablas si no existen
            $this->createTables();
        } catch (PDOException $e) {
            throw new Exception('Error conectando a base de datos: ' . $e->getMessage());
        }
    }

    /**
     * Crear tablas necesarias
     */
    private function createTables()
    {
        // Tabla de archivos subidos
        $sql_uploads = "
            CREATE TABLE IF NOT EXISTS uploads (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                filename VARCHAR(255) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                relative_path VARCHAR(500) NOT NULL,
                file_size INTEGER NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                extension VARCHAR(10) NOT NULL,
                year INTEGER NOT NULL,
                month INTEGER NOT NULL,
                upload_date DATETIME NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";

        // Tabla de visualizaciones/optimizaciones
        $sql_views = "
            CREATE TABLE IF NOT EXISTS image_views (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                image_path VARCHAR(500) NOT NULL,
                width INTEGER,
                height INTEGER,
                format VARCHAR(10),
                quality INTEGER,
                view_date DATETIME NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                referer TEXT,
                cache_hit BOOLEAN DEFAULT 0,
                processing_time_ms INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";

        // Tabla de estadísticas diarias (para rendimiento)
        $sql_daily_stats = "
            CREATE TABLE IF NOT EXISTS daily_stats (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                date DATE NOT NULL UNIQUE,
                total_views INTEGER DEFAULT 0,
                total_uploads INTEGER DEFAULT 0,
                unique_images INTEGER DEFAULT 0,
                avg_processing_time REAL DEFAULT 0,
                top_format VARCHAR(10),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";

        // Tabla de logs de actividad
        $sql_activity_logs = "
            CREATE TABLE IF NOT EXISTS activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                activity_type VARCHAR(50) NOT NULL,
                status VARCHAR(20) NOT NULL,
                message TEXT NOT NULL,
                details TEXT,
                file_path VARCHAR(500),
                file_size INTEGER,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";

        // Índices para mejor rendimiento
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_uploads_path ON uploads(relative_path)",
            "CREATE INDEX IF NOT EXISTS idx_uploads_date ON uploads(upload_date)",
            "CREATE INDEX IF NOT EXISTS idx_views_path ON image_views(image_path)",
            "CREATE INDEX IF NOT EXISTS idx_views_date ON image_views(view_date)",
            "CREATE INDEX IF NOT EXISTS idx_daily_stats_date ON daily_stats(date)",
            "CREATE INDEX IF NOT EXISTS idx_activity_logs_type ON activity_logs(activity_type)",
            "CREATE INDEX IF NOT EXISTS idx_activity_logs_status ON activity_logs(status)",
            "CREATE INDEX IF NOT EXISTS idx_activity_logs_date ON activity_logs(created_at)"
        ];

        // Ejecutar creación de tablas
        $this->db->exec($sql_uploads);
        $this->db->exec($sql_views);
        $this->db->exec($sql_daily_stats);
        $this->db->exec($sql_activity_logs);

        // Crear índices
        foreach ($indexes as $index) {
            $this->db->exec($index);
        }
    }

    /**
     * Registrar upload de archivo
     */
    public function recordUpload($data)
    {
        $sql = "
            INSERT INTO uploads (
                filename, original_name, relative_path, file_size, 
                mime_type, extension, year, month, upload_date, 
                ip_address, user_agent
            ) VALUES (
                :filename, :original_name, :relative_path, :file_size,
                :mime_type, :extension, :year, :month, :upload_date,
                :ip_address, :user_agent
            )
        ";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':filename' => $data['filename'],
            ':original_name' => $data['original_name'],
            ':relative_path' => $data['relative_path'],
            ':file_size' => $data['file_size'],
            ':mime_type' => $data['mime_type'],
            ':extension' => $data['extension'],
            ':year' => $data['year'],
            ':month' => $data['month'],
            ':upload_date' => $data['upload_date'],
            ':ip_address' => $data['ip_address'] ?? null,
            ':user_agent' => $data['user_agent'] ?? null
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Registrar visualización/optimización
     */
    public function recordView($imagePath, $params = [])
    {
        $sql = "
            INSERT INTO image_views (
                image_path, width, height, format, quality,
                view_date, ip_address, user_agent, referer,
                cache_hit, processing_time_ms
            ) VALUES (
                :image_path, :width, :height, :format, :quality,
                :view_date, :ip_address, :user_agent, :referer,
                :cache_hit, :processing_time_ms
            )
        ";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':image_path' => $imagePath,
            ':width' => $params['width'] ?? null,
            ':height' => $params['height'] ?? null,
            ':format' => $params['format'] ?? null,
            ':quality' => $params['quality'] ?? null,
            ':view_date' => date('Y-m-d H:i:s'),
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ':referer' => $_SERVER['HTTP_REFERER'] ?? null,
            ':cache_hit' => $params['cache_hit'] ?? false,
            ':processing_time_ms' => $params['processing_time_ms'] ?? null
        ]);

        // Actualizar estadísticas diarias
        $this->updateDailyStats();

        return $this->db->lastInsertId();
    }

    /**
     * Obtener estadísticas generales
     */
    public function getGeneralStats()
    {
        $stats = [];

        // Total de uploads
        $stmt = $this->db->query("SELECT COUNT(*) as total_uploads FROM uploads");
        $stats['total_uploads'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_uploads'];

        // Total de visualizaciones
        $stmt = $this->db->query("SELECT COUNT(*) as total_views FROM image_views");
        $stats['total_views'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_views'];

        // Imágenes únicas vistas
        $stmt = $this->db->query("SELECT COUNT(DISTINCT image_path) as unique_images FROM image_views");
        $stats['unique_images'] = $stmt->fetch(PDO::FETCH_ASSOC)['unique_images'];

        // Tamaño total de archivos
        $stmt = $this->db->query("SELECT SUM(file_size) as total_size FROM uploads");
        $stats['total_size'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_size'] ?? 0;

        // Promedio de visualizaciones por imagen
        $stats['avg_views_per_image'] = $stats['unique_images'] > 0
            ? round($stats['total_views'] / $stats['unique_images'], 2)
            : 0;

        // Uploads hoy
        $stmt = $this->db->query("SELECT COUNT(*) as today_uploads FROM uploads WHERE DATE(upload_date) = DATE('now')");
        $stats['today_uploads'] = $stmt->fetch(PDO::FETCH_ASSOC)['today_uploads'];

        // Views hoy
        $stmt = $this->db->query("SELECT COUNT(*) as today_views FROM image_views WHERE DATE(view_date) = DATE('now')");
        $stats['today_views'] = $stmt->fetch(PDO::FETCH_ASSOC)['today_views'];

        return $stats;
    }

    /**
     * Obtener imágenes más vistas
     */
    public function getTopImages($limit = 10)
    {
        $sql = "
            SELECT 
                v.image_path,
                COUNT(*) as view_count,
                MAX(v.view_date) as last_view,
                u.original_name,
                u.file_size,
                u.upload_date
            FROM image_views v
            LEFT JOIN uploads u ON v.image_path = u.relative_path
            GROUP BY v.image_path
            ORDER BY view_count DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener estadísticas por período
     */
    public function getStatsByPeriod($days = 30)
    {
        $sql = "
            SELECT 
                DATE(view_date) as date,
                COUNT(*) as views,
                COUNT(DISTINCT image_path) as unique_images
            FROM image_views 
            WHERE view_date >= DATE('now', '-{$days} days')
            GROUP BY DATE(view_date)
            ORDER BY date DESC
        ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener formatos más usados
     */
    public function getFormatStats()
    {
        $sql = "
            SELECT 
                COALESCE(format, 'original') as format,
                COUNT(*) as count,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM image_views), 2) as percentage
            FROM image_views
            GROUP BY format
            ORDER BY count DESC
        ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener estadísticas de un archivo específico
     */
    public function getImageStats($imagePath)
    {
        // Info básica del archivo
        $sql_file = "SELECT * FROM uploads WHERE relative_path = :path";
        $stmt = $this->db->prepare($sql_file);
        $stmt->execute([':path' => $imagePath]);
        $fileInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        // Estadísticas de visualizaciones
        $sql_views = "
            SELECT 
                COUNT(*) as total_views,
                COUNT(DISTINCT DATE(view_date)) as days_viewed,
                MAX(view_date) as last_view,
                MIN(view_date) as first_view,
                AVG(processing_time_ms) as avg_processing_time
            FROM image_views 
            WHERE image_path = :path
        ";

        $stmt = $this->db->prepare($sql_views);
        $stmt->execute([':path' => $imagePath]);
        $viewStats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'file' => $fileInfo,
            'views' => $viewStats
        ];
    }

    /**
     * Actualizar estadísticas diarias (optimización)
     */
    private function updateDailyStats()
    {
        $today = date('Y-m-d');

        $sql = "
            INSERT OR REPLACE INTO daily_stats (
                date, total_views, total_uploads, unique_images, 
                avg_processing_time, top_format
            )
            SELECT 
                :date as date,
                (SELECT COUNT(*) FROM image_views WHERE DATE(view_date) = :date) as total_views,
                (SELECT COUNT(*) FROM uploads WHERE DATE(upload_date) = :date) as total_uploads,
                (SELECT COUNT(DISTINCT image_path) FROM image_views WHERE DATE(view_date) = :date) as unique_images,
                (SELECT AVG(processing_time_ms) FROM image_views WHERE DATE(view_date) = :date AND processing_time_ms IS NOT NULL) as avg_processing_time,
                (SELECT format FROM image_views WHERE DATE(view_date) = :date AND format IS NOT NULL GROUP BY format ORDER BY COUNT(*) DESC LIMIT 1) as top_format
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':date' => $today]);
    }

    /**
     * Formatear tamaño de archivo
     */
    public function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Registrar actividad en log
     */
    public function logActivity($type, $status, $message, $details = null, $filePath = null, $fileSize = null)
    {
        $sql = "
            INSERT INTO activity_logs (
                activity_type, status, message, details, file_path, 
                file_size, ip_address, user_agent
            ) VALUES (
                :activity_type, :status, :message, :details, :file_path,
                :file_size, :ip_address, :user_agent
            )
        ";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':activity_type' => $type,
            ':status' => $status,
            ':message' => $message,
            ':details' => $details,
            ':file_path' => $filePath,
            ':file_size' => $fileSize,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Obtener logs de actividad recientes
     */
    public function getActivityLogs($limit = 50, $type = null, $status = null)
    {
        $sql = "
            SELECT * FROM activity_logs 
            WHERE 1=1
        ";

        $params = [];

        if ($type) {
            $sql .= " AND activity_type = :type";
            $params[':type'] = $type;
        }

        if ($status) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit";
        $params[':limit'] = $limit;

        $stmt = $this->db->prepare($sql);

        // Bind limit as integer
        foreach ($params as $key => $value) {
            if ($key === ':limit') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener estadísticas de logs por tipo
     */
    public function getLogStats()
    {
        $stats = [];

        // Total de logs por tipo
        $sql = "
            SELECT 
                activity_type,
                status,
                COUNT(*) as count
            FROM activity_logs 
            GROUP BY activity_type, status
            ORDER BY activity_type, status
        ";

        $stmt = $this->db->query($sql);
        $stats['by_type_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Logs de hoy
        $sql = "
            SELECT 
                activity_type,
                COUNT(*) as count
            FROM activity_logs 
            WHERE DATE(created_at) = DATE('now')
            GROUP BY activity_type
            ORDER BY count DESC
        ";

        $stmt = $this->db->query($sql);
        $stats['today'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Últimos 7 días
        $sql = "
            SELECT 
                DATE(created_at) as date,
                activity_type,
                status,
                COUNT(*) as count
            FROM activity_logs 
            WHERE created_at >= DATE('now', '-7 days')
            GROUP BY DATE(created_at), activity_type, status
            ORDER BY date DESC, activity_type
        ";

        $stmt = $this->db->query($sql);
        $stats['last_7_days'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $stats;
    }

    /**
     * Limpiar logs antiguos (opcional)
     */
    public function cleanOldLogs($daysToKeep = 90)
    {
        $sql = "
            DELETE FROM activity_logs 
            WHERE created_at < DATE('now', '-{$daysToKeep} days')
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Eliminar todos los logs (PELIGROSO)
     */
    public function deleteAllLogs($confirmationCode = null)
    {
        // Código de confirmación requerido para seguridad
        $expectedCode = 'DELETE_ALL_LOGS_' . date('Ymd');

        if ($confirmationCode !== $expectedCode) {
            throw new Exception("Código de confirmación incorrecto. Use: $expectedCode");
        }

        $sql = "DELETE FROM activity_logs";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $deletedCount = $stmt->rowCount();

        // Log de la eliminación masiva
        $this->logActivity(
            'system',
            'warning',
            "Eliminación masiva de logs ejecutada",
            "Se eliminaron $deletedCount registros de logs. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            null,
            null
        );

        return $deletedCount;
    }

    /**
     * Eliminar logs por criterios específicos
     */
    public function deleteLogsByType($activityType, $status = null, $olderThanDays = null)
    {
        $sql = "DELETE FROM activity_logs WHERE activity_type = :activity_type";
        $params = [':activity_type' => $activityType];

        if ($status) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }

        if ($olderThanDays) {
            $sql .= " AND created_at < DATE('now', '-{$olderThanDays} days')";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $deletedCount = $stmt->rowCount();

        // Log de la eliminación selectiva
        $this->logActivity(
            'system',
            'info',
            "Eliminación selectiva de logs",
            "Tipo: $activityType" . ($status ? ", Estado: $status" : "") .
                ($olderThanDays ? ", Más antiguos que: $olderThanDays días" : "") .
                ". Eliminados: $deletedCount registros",
            null,
            null
        );

        return $deletedCount;
    }

    /**
     * Eliminar archivo físico y sus registros
     */
    public function deleteFile($relativePath, $confirmDelete = false)
    {
        if (!$confirmDelete) {
            throw new Exception("Parámetro confirmDelete debe ser true para confirmar eliminación");
        }

        $result = [
            'file_deleted' => false,
            'uploads_deleted' => 0,
            'views_deleted' => 0,
            'logs_deleted' => 0,
            'errors' => []
        ];

        // Verificar que el archivo existe
        $possiblePaths = [
            'uploads/' . $relativePath,
            'uploads/' . basename($relativePath),
            'uploads/legacy/' . basename($relativePath)
        ];

        $filePath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $filePath = $path;
                break;
            }
        }

        if (!$filePath) {
            $result['errors'][] = "Archivo no encontrado: $relativePath";
            return $result;
        }

        // Obtener información del archivo antes de eliminarlo
        $fileSize = filesize($filePath);
        $fileInfo = [
            'path' => $filePath,
            'relative_path' => $relativePath,
            'size' => $fileSize
        ];

        try {
            // 1. Eliminar archivo físico
            if (unlink($filePath)) {
                $result['file_deleted'] = true;
            } else {
                $result['errors'][] = "No se pudo eliminar el archivo físico: $filePath";
            }

            // 2. Eliminar registros de uploads
            $sql = "DELETE FROM uploads WHERE relative_path = :path OR relative_path = :basename";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':path' => $relativePath,
                ':basename' => basename($relativePath)
            ]);
            $result['uploads_deleted'] = $stmt->rowCount();

            // 3. Eliminar registros de visualizaciones
            $sql = "DELETE FROM image_views WHERE image_path = :path OR image_path = :basename";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':path' => $relativePath,
                ':basename' => basename($relativePath)
            ]);
            $result['views_deleted'] = $stmt->rowCount();

            // 4. Eliminar logs relacionados al archivo
            $sql = "DELETE FROM activity_logs WHERE file_path = :path OR file_path = :basename";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':path' => $relativePath,
                ':basename' => basename($relativePath)
            ]);
            $result['logs_deleted'] = $stmt->rowCount();

            // 5. Log de la eliminación
            $this->logActivity(
                'file_delete',
                'success',
                "Archivo eliminado completamente: $relativePath",
                "Archivo físico: " . ($result['file_deleted'] ? 'eliminado' : 'no encontrado') .
                    ". Registros eliminados - Uploads: {$result['uploads_deleted']}, " .
                    "Views: {$result['views_deleted']}, Logs: {$result['logs_deleted']}",
                $relativePath,
                $fileSize
            );
        } catch (Exception $e) {
            $result['errors'][] = "Error durante eliminación: " . $e->getMessage();

            // Log del error
            $this->logActivity(
                'file_delete',
                'error',
                "Error eliminando archivo: $relativePath",
                "Error: " . $e->getMessage(),
                $relativePath,
                $fileSize ?? null
            );
        }

        return $result;
    }

    /**
     * Obtener lista de archivos para eliminación
     */
    public function getFilesForDeletion($type = 'all', $limit = 50)
    {
        $files = [];

        if ($type === 'all' || $type === 'uploads') {
            // Archivos con registros en BD
            $sql = "
                SELECT 
                    relative_path,
                    original_name,
                    file_size,
                    upload_date,
                    (SELECT COUNT(*) FROM image_views WHERE image_path = uploads.relative_path) as view_count
                FROM uploads 
                ORDER BY upload_date DESC 
                LIMIT :limit
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $dbFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($dbFiles as $file) {
                $files[] = [
                    'type' => 'database',
                    'path' => $file['relative_path'],
                    'original_name' => $file['original_name'],
                    'size' => $file['file_size'],
                    'upload_date' => $file['upload_date'],
                    'view_count' => $file['view_count'],
                    'exists' => $this->fileExists($file['relative_path'])
                ];
            }
        }

        if ($type === 'all' || $type === 'orphaned') {
            // Archivos físicos sin registros en BD (huérfanos)
            $this->scanOrphanedFiles($files, 'uploads', $limit);
        }

        return array_slice($files, 0, $limit);
    }

    /**
     * Verificar si archivo físico existe
     */
    private function fileExists($relativePath)
    {
        $possiblePaths = [
            'uploads/' . $relativePath,
            'uploads/' . basename($relativePath),
            'uploads/legacy/' . basename($relativePath)
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return false;
    }

    /**
     * Escanear archivos huérfanos (sin registros en BD)
     */
    private function scanOrphanedFiles(&$files, $directory, $maxFiles)
    {
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        $foundFiles = [];

        // Escanear directorio principal
        if (is_dir($directory)) {
            $dirFiles = glob($directory . '/*.{' . implode(',', $extensions) . '}', GLOB_BRACE);
            $foundFiles = array_merge($foundFiles, $dirFiles);
        }

        // Escanear subdirectorios (estructura organizada)
        $years = glob($directory . '/[0-9][0-9][0-9][0-9]', GLOB_ONLYDIR);
        foreach ($years as $yearDir) {
            $months = glob($yearDir . '/[0-9][0-9]', GLOB_ONLYDIR);
            foreach ($months as $monthDir) {
                $monthFiles = glob($monthDir . '/*.{' . implode(',', $extensions) . '}', GLOB_BRACE);
                $foundFiles = array_merge($foundFiles, $monthFiles);
            }
        }

        // Verificar cuáles no están en BD
        foreach (array_slice($foundFiles, 0, $maxFiles) as $file) {
            $relativePath = str_replace('uploads/', '', $file);

            // Verificar si existe en BD
            $sql = "SELECT COUNT(*) as count FROM uploads WHERE relative_path = :path OR relative_path = :basename";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':path' => $relativePath,
                ':basename' => basename($relativePath)
            ]);

            $inDatabase = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

            if (!$inDatabase) {
                $files[] = [
                    'type' => 'orphaned',
                    'path' => $relativePath,
                    'original_name' => basename($file),
                    'size' => filesize($file),
                    'upload_date' => date('Y-m-d H:i:s', filemtime($file)),
                    'view_count' => 0,
                    'exists' => $file
                ];
            }

            if (count($files) >= $maxFiles) break;
        }
    }

    /**
     * Cerrar conexión
     */
    public function __destruct()
    {
        $this->db = null;
    }
}
