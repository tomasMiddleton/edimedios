<?php
// ARCHIVO: config/config.php
// Configuración centralizada del sistema EDI Medios

return [
    // Configuración de carga de archivos
    'upload' => [
        'max_file_size' => 50 * 1024 * 1024, // 50MB en bytes
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'],
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/avif'
        ],
        'upload_directory' => 'uploads/',
        'temp_directory' => 'temp/',
        'max_files_per_request' => 10
    ],

    // Configuración de optimización
    'optimization' => [
        'cache_directory' => 'cache/',
        'cache_max_age' => 31536000, // 1 año
        'default_quality' => 85,
        'max_width' => 4000,
        'max_height' => 4000,
        'auto_optimize' => true,
        'formats' => [
            'webp' => ['enabled' => true, 'quality' => 85],
            'avif' => ['enabled' => true, 'quality' => 80],
            'jpeg' => ['enabled' => true, 'quality' => 85]
        ]
    ],

    // Configuración de seguridad
    'security' => [
        'enable_csrf' => true,
        'enable_rate_limiting' => true,
        'max_requests_per_minute' => 60,
        'log_uploads' => true,
        'log_errors' => true,
        'quarantine_suspicious' => true,
        'virus_scan' => false // Requiere ClamAV
    ],

    // Configuración de logs
    'logging' => [
        'log_directory' => 'logs/',
        'log_level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
        'log_rotation' => true,
        'max_log_files' => 30,
        'log_uploads' => true,
        'log_access' => true,
        'log_errors' => true
    ],

    // Configuración de la interfaz
    'ui' => [
        'theme' => 'modern',
        'show_thumbnails' => true,
        'max_thumbnail_size' => 200,
        'enable_preview' => true,
        'enable_batch_upload' => true,
        'enable_drag_drop' => true
    ],

    // Configuración de la API
    'api' => [
        'enable_api' => true,
        'api_key_required' => false,
        'rate_limit_api' => 120, // requests per minute
        'enable_cors' => true,
        'allowed_origins' => ['*']
    ],

    // Configuración de base de datos (opcional)
    'database' => [
        'enabled' => false,
        'type' => 'sqlite', // sqlite, mysql, postgresql
        'path' => 'storage/database.sqlite',
        'host' => 'localhost',
        'username' => '',
        'password' => '',
        'database' => 'edimedios'
    ],

    // Configuración del sistema
    'system' => [
        'version' => '1.1.0',
        'maintenance_mode' => false,
        'debug_mode' => false,
        'timezone' => 'America/Santiago',
        'locale' => 'es_ES',
        'cleanup_temp_files' => true,
        'cleanup_old_cache' => true,
        'cache_cleanup_days' => 30
    ]
];
