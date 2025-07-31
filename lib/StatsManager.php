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

        // Índices para mejor rendimiento
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_uploads_path ON uploads(relative_path)",
            "CREATE INDEX IF NOT EXISTS idx_uploads_date ON uploads(upload_date)",
            "CREATE INDEX IF NOT EXISTS idx_views_path ON image_views(image_path)",
            "CREATE INDEX IF NOT EXISTS idx_views_date ON image_views(view_date)",
            "CREATE INDEX IF NOT EXISTS idx_daily_stats_date ON daily_stats(date)"
        ];

        // Ejecutar creación de tablas
        $this->db->exec($sql_uploads);
        $this->db->exec($sql_views);
        $this->db->exec($sql_daily_stats);

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
     * Cerrar conexión
     */
    public function __destruct()
    {
        $this->db = null;
    }
}
