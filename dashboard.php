<?php
// ARCHIVO: dashboard.php
// Dashboard de gestión de archivos y estadísticas

// Inicializar tiempo de solicitud
define('REQUEST_START_TIME', microtime(true));

// Cargar dependencias
require_once(__DIR__ . '/lib/FileHandler.php');
require_once(__DIR__ . '/lib/Logger.php');

// Cargar configuración
$config = include(__DIR__ . '/config/config.php');
$fileHandler = new FileHandler($config);
$logger = new Logger($config['logging']);

// Registrar acceso
$logger->logAccess('dashboard.php', 'GET');

// Obtener datos
$files = $fileHandler->listFiles();
$logStats = $logger->getLogStats();

// Procesar estadísticas
$stats = [
    'total_files' => count($files),
    'total_size' => array_sum(array_column($files, 'size')),
    'formats' => [],
    'recent_uploads' => [],
    'size_distribution' => ['small' => 0, 'medium' => 0, 'large' => 0]
];

foreach ($files as $file) {
    // Contar formatos
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $stats['formats'][$ext] = ($stats['formats'][$ext] ?? 0) + 1;

    // Distribución por tamaño
    $sizeMB = $file['size'] / (1024 * 1024);
    if ($sizeMB < 1) {
        $stats['size_distribution']['small']++;
    } elseif ($sizeMB < 5) {
        $stats['size_distribution']['medium']++;
    } else {
        $stats['size_distribution']['large']++;
    }
}

// Archivos recientes (últimos 10)
usort($files, function ($a, $b) {
    return $b['modified'] - $a['modified'];
});
$stats['recent_uploads'] = array_slice($files, 0, 10);

// Información del sistema
$systemInfo = [
    'php_version' => phpversion(),
    'gd_version' => function_exists('gd_info') ? gd_info()['GD Version'] : 'No disponible',
    'webp_support' => function_exists('imagewebp'),
    'avif_support' => function_exists('imageavif'),
    'disk_space' => disk_free_space('.'),
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize')
];

function formatBytes($bytes)
{
    if ($bytes === 0) return '0 B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

function getStatusIcon($value)
{
    return $value ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>';
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EDI Medios</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #8e44ad;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            margin-bottom: 2rem;
        }

        .card-header {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
        }

        .stats-card {
            text-align: center;
            padding: 2rem;
            margin-bottom: 1rem;
            border-radius: 15px;
            color: white;
        }

        .stats-card.primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        }

        .stats-card.success {
            background: linear-gradient(45deg, var(--success-color), #2ecc71);
        }

        .stats-card.warning {
            background: linear-gradient(45deg, var(--warning-color), #e67e22);
        }

        .stats-card.danger {
            background: linear-gradient(45deg, var(--danger-color), #c0392b);
        }

        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            max-height: 500px;
            overflow-y: auto;
        }

        .file-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 1rem;
            background: white;
            text-align: center;
            transition: transform 0.2s;
        }

        .file-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .file-preview {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .nav-link {
            color: var(--primary-color);
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin: 0.2rem 0;
            transition: all 0.3s;
        }

        .nav-link:hover,
        .nav-link.active {
            background: var(--secondary-color);
            color: white;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .table {
            background: white;
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-3">
            <h4 class="text-center mb-4">
                <i class="fas fa-tachometer-alt text-primary"></i>
                Dashboard
            </h4>
            <nav class="nav flex-column">
                <a class="nav-link active" href="#overview">
                    <i class="fas fa-chart-pie me-2"></i>Resumen
                </a>
                <a class="nav-link" href="#files">
                    <i class="fas fa-folder me-2"></i>Archivos
                </a>
                <a class="nav-link" href="#stats">
                    <i class="fas fa-chart-bar me-2"></i>Estadísticas
                </a>
                <a class="nav-link" href="#system">
                    <i class="fas fa-server me-2"></i>Sistema
                </a>
                <a class="nav-link" href="#logs">
                    <i class="fas fa-list me-2"></i>Logs
                </a>
                <hr>
                <a class="nav-link" href="index.php">
                    <i class="fas fa-upload me-2"></i>Subir Archivos
                </a>
                <a class="nav-link" href="submit.php">
                    <i class="fas fa-arrow-left me-2"></i>Versión Clásica
                </a>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="text-white">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard EDI Medios
                </h1>
                <p class="text-white-50">Panel de administración y estadísticas</p>
            </div>
        </div>

        <!-- Estadísticas Principales -->
        <div class="row mb-4" id="overview">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card primary">
                    <div class="h2"><?php echo $stats['total_files']; ?></div>
                    <div>Archivos Totales</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card success">
                    <div class="h2"><?php echo formatBytes($stats['total_size']); ?></div>
                    <div>Espacio Usado</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card warning">
                    <div class="h2"><?php echo count($stats['formats']); ?></div>
                    <div>Formatos Diferentes</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card danger">
                    <div class="h2"><?php echo formatBytes($systemInfo['disk_space']); ?></div>
                    <div>Espacio Libre</div>
                </div>
            </div>
        </div>

        <!-- Archivos Recientes -->
        <div class="row" id="files">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Archivos Recientes
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="file-grid">
                            <?php foreach ($stats['recent_uploads'] as $file): ?>
                                <div class="file-card">
                                    <img src="uploads/<?php echo $file['name']; ?>"
                                        class="file-preview"
                                        alt="Preview"
                                        onerror="this.src='no_image_available.png'">
                                    <div class="small">
                                        <strong><?php echo substr($file['name'], 0, 15) . '...'; ?></strong><br>
                                        <span class="text-muted"><?php echo formatBytes($file['size']); ?></span><br>
                                        <span class="text-muted"><?php echo date('d/m H:i', $file['modified']); ?></span>
                                    </div>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-outline-primary"
                                            onclick="copyUrl('<?php echo $file['name']; ?>')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger"
                                            onclick="deleteFile('<?php echo $file['name']; ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row" id="stats">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Distribución por Formato</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="formatChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Distribución por Tamaño</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="sizeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información del Sistema -->
        <div class="row" id="system">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-server me-2"></i>
                            Información del Sistema
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Versión PHP:</strong></td>
                                <td><?php echo $systemInfo['php_version']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Versión GD:</strong></td>
                                <td><?php echo $systemInfo['gd_version']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Soporte WebP:</strong></td>
                                <td><?php echo getStatusIcon($systemInfo['webp_support']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Soporte AVIF:</strong></td>
                                <td><?php echo getStatusIcon($systemInfo['avif_support']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Límite de memoria:</strong></td>
                                <td><?php echo $systemInfo['memory_limit']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Tamaño máximo:</strong></td>
                                <td><?php echo $systemInfo['upload_max_filesize']; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-tools me-2"></i>
                            Herramientas de Mantenimiento
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" onclick="clearCache()">
                                <i class="fas fa-broom me-2"></i>
                                Limpiar Cache
                            </button>
                            <button class="btn btn-outline-warning" onclick="optimizeDatabase()">
                                <i class="fas fa-database me-2"></i>
                                Optimizar Metadata
                            </button>
                            <button class="btn btn-outline-info" onclick="generateReport()">
                                <i class="fas fa-file-pdf me-2"></i>
                                Generar Reporte
                            </button>
                            <button class="btn btn-outline-danger" onclick="cleanOldFiles()">
                                <i class="fas fa-trash-alt me-2"></i>
                                Limpiar Archivos Antiguos
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs -->
        <div class="row" id="logs">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Estadísticas de Logs
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-primary"><?php echo $logStats['total_files']; ?></div>
                                    <small>Archivos de Log</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-success"><?php echo formatBytes($logStats['total_size']); ?></div>
                                    <small>Tamaño Total</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-warning"><?php echo $logStats['last_modified'] ? date('d/m H:i', $logStats['last_modified']) : 'N/A'; ?></div>
                                    <small>Última Actividad</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-info"><?php echo count($logStats['files_by_level']); ?></div>
                                    <small>Niveles Activos</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Datos para gráficos
        const formatData = <?php echo json_encode($stats['formats']); ?>;
        const sizeData = <?php echo json_encode($stats['size_distribution']); ?>;

        // Gráfico de formatos
        const formatCtx = document.getElementById('formatChart').getContext('2d');
        new Chart(formatCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(formatData),
                datasets: [{
                    data: Object.values(formatData),
                    backgroundColor: ['#3498db', '#2ecc71', '#f39c12', '#e74c3c', '#9b59b6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Gráfico de tamaños
        const sizeCtx = document.getElementById('sizeChart').getContext('2d');
        new Chart(sizeCtx, {
            type: 'bar',
            data: {
                labels: ['Pequeños (<1MB)', 'Medianos (1-5MB)', 'Grandes (>5MB)'],
                datasets: [{
                    label: 'Archivos',
                    data: [sizeData.small, sizeData.medium, sizeData.large],
                    backgroundColor: ['#2ecc71', '#f39c12', '#e74c3c']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Funciones de utilidad
        function copyUrl(fileName) {
            const url = window.location.origin + window.location.pathname.replace('/dashboard.php', '') + '/uploads/' + fileName;
            navigator.clipboard.writeText(url).then(() => {
                alert('URL copiada al portapapeles');
            });
        }

        function deleteFile(fileName) {
            if (confirm('¿Estás seguro de que quieres eliminar este archivo?')) {
                fetch(`api/files.php/${fileName}`, {
                        method: 'DELETE'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Archivo eliminado exitosamente');
                            location.reload();
                        } else {
                            alert('Error al eliminar archivo: ' + data.error);
                        }
                    });
            }
        }

        function clearCache() {
            if (confirm('¿Limpiar todo el cache de imágenes?')) {
                fetch('tools/cache_manager.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'clear_cache'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message || 'Cache limpiado');
                    });
            }
        }

        function optimizeDatabase() {
            alert('Funcionalidad de optimización próximamente');
        }

        function generateReport() {
            window.open('tools/generate_report.php', '_blank');
        }

        function cleanOldFiles() {
            if (confirm('¿Eliminar archivos no accedidos en 30 días?')) {
                alert('Funcionalidad próximamente');
            }
        }

        // Navegación suave
        document.querySelectorAll('.nav-link[href^="#"]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }

                // Actualizar enlace activo
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>

</html>