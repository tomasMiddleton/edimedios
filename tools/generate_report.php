<?php
// ARCHIVO: tools/generate_report.php
// Generador de reportes de uso y estadísticas

// Inicializar tiempo de solicitud
define('REQUEST_START_TIME', microtime(true));

// Cargar dependencias
require_once(__DIR__ . '/../lib/FileHandler.php');
require_once(__DIR__ . '/../lib/Logger.php');

// Cargar configuración
$config = include(__DIR__ . '/../config/config.php');
$fileHandler = new FileHandler($config);
$logger = new Logger($config['logging']);

// Registrar acceso
$logger->logAccess('tools/generate_report.php', 'GET');

// Obtener datos
$files = $fileHandler->listFiles();
$logStats = $logger->getLogStats();

// Procesar estadísticas detalladas
$detailedStats = processDetailedStats($files, $logStats);

// Headers para descarga de PDF
header('Content-Type: text/html; charset=utf-8');

function processDetailedStats($files, $logStats)
{
    $stats = [
        'summary' => [
            'total_files' => count($files),
            'total_size' => array_sum(array_column($files, 'size')),
            'average_size' => count($files) ? array_sum(array_column($files, 'size')) / count($files) : 0,
            'report_date' => date('Y-m-d H:i:s'),
            'report_period' => 'Desde el inicio'
        ],
        'by_format' => [],
        'by_size' => ['small' => 0, 'medium' => 0, 'large' => 0, 'xlarge' => 0],
        'by_date' => [],
        'system_info' => [
            'php_version' => phpversion(),
            'gd_version' => function_exists('gd_info') ? gd_info()['GD Version'] : 'No disponible',
            'webp_support' => function_exists('imagewebp'),
            'avif_support' => function_exists('imageavif'),
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'disk_free' => disk_free_space('.'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ],
        'performance' => [
            'cache_hits' => 0, // Placeholder
            'optimization_ratio' => 0, // Placeholder
            'storage_efficiency' => 0 // Placeholder
        ],
        'security' => [
            'validation_errors' => 0, // Placeholder
            'blocked_uploads' => 0, // Placeholder
            'security_events' => 0 // Placeholder
        ]
    ];

    // Análisis por formato
    foreach ($files as $file) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!isset($stats['by_format'][$ext])) {
            $stats['by_format'][$ext] = ['count' => 0, 'size' => 0];
        }
        $stats['by_format'][$ext]['count']++;
        $stats['by_format'][$ext]['size'] += $file['size'];

        // Análisis por tamaño
        $sizeMB = $file['size'] / (1024 * 1024);
        if ($sizeMB < 0.5) {
            $stats['by_size']['small']++;
        } elseif ($sizeMB < 2) {
            $stats['by_size']['medium']++;
        } elseif ($sizeMB < 10) {
            $stats['by_size']['large']++;
        } else {
            $stats['by_size']['xlarge']++;
        }

        // Análisis temporal
        $date = date('Y-m', $file['modified']);
        $stats['by_date'][$date] = ($stats['by_date'][$date] ?? 0) + 1;
    }

    return $stats;
}

function formatBytes($bytes)
{
    if ($bytes === 0) return '0 B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

function getStatusBadge($value, $type = 'boolean')
{
    if ($type === 'boolean') {
        return $value ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-danger">No</span>';
    }
    return '<span class="badge bg-info">' . $value . '</span>';
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Estadísticas - EDI Medios</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            .card {
                break-inside: avoid;
            }

            .chart-container {
                height: 300px !important;
            }
        }

        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin: 1rem 0;
        }

        .table-section {
            margin: 2rem 0;
        }

        .section-header {
            background: #e9ecef;
            padding: 1rem;
            margin: 1.5rem 0 1rem 0;
            border-left: 4px solid #007bff;
        }
    </style>
</head>

<body>
    <div class="container my-4">
        <!-- Header del Reporte -->
        <div class="report-header text-center">
            <h1><i class="fas fa-chart-line me-2"></i>Reporte de Estadísticas EDI Medios</h1>
            <p class="mb-0">Generado el <?php echo $detailedStats['summary']['report_date']; ?></p>
            <div class="no-print mt-3">
                <button class="btn btn-light" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Imprimir PDF
                </button>
                <a href="../dashboard.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i>Volver al Dashboard
                </a>
            </div>
        </div>

        <!-- Resumen Ejecutivo -->
        <div class="section-header">
            <h2><i class="fas fa-summary me-2"></i>Resumen Ejecutivo</h2>
        </div>

        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <h4 class="text-primary"><?php echo number_format($detailedStats['summary']['total_files']); ?></h4>
                    <p class="mb-0">Archivos Totales</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <h4 class="text-success"><?php echo formatBytes($detailedStats['summary']['total_size']); ?></h4>
                    <p class="mb-0">Espacio Utilizado</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <h4 class="text-warning"><?php echo formatBytes($detailedStats['summary']['average_size']); ?></h4>
                    <p class="mb-0">Tamaño Promedio</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <h4 class="text-info"><?php echo count($detailedStats['by_format']); ?></h4>
                    <p class="mb-0">Formatos Diferentes</p>
                </div>
            </div>
        </div>

        <!-- Análisis por Formato -->
        <div class="section-header">
            <h3><i class="fas fa-file-image me-2"></i>Distribución por Formato</h3>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="chart-container">
                    <canvas id="formatChart"></canvas>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Formato</th>
                                <th>Cantidad</th>
                                <th>Tamaño Total</th>
                                <th>Promedio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detailedStats['by_format'] as $format => $data): ?>
                                <tr>
                                    <td><span class="badge bg-primary"><?php echo strtoupper($format); ?></span></td>
                                    <td><?php echo number_format($data['count']); ?></td>
                                    <td><?php echo formatBytes($data['size']); ?></td>
                                    <td><?php echo formatBytes($data['size'] / $data['count']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Análisis por Tamaño -->
        <div class="section-header">
            <h3><i class="fas fa-chart-bar me-2"></i>Distribución por Tamaño</h3>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="chart-container">
                    <canvas id="sizeChart"></canvas>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Categoría</th>
                                <th>Cantidad</th>
                                <th>Porcentaje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Pequeños (&lt;0.5MB)</td>
                                <td><?php echo $detailedStats['by_size']['small']; ?></td>
                                <td><?php echo $detailedStats['summary']['total_files'] ? round(($detailedStats['by_size']['small'] / $detailedStats['summary']['total_files']) * 100, 1) : 0; ?>%</td>
                            </tr>
                            <tr>
                                <td>Medianos (0.5-2MB)</td>
                                <td><?php echo $detailedStats['by_size']['medium']; ?></td>
                                <td><?php echo $detailedStats['summary']['total_files'] ? round(($detailedStats['by_size']['medium'] / $detailedStats['summary']['total_files']) * 100, 1) : 0; ?>%</td>
                            </tr>
                            <tr>
                                <td>Grandes (2-10MB)</td>
                                <td><?php echo $detailedStats['by_size']['large']; ?></td>
                                <td><?php echo $detailedStats['summary']['total_files'] ? round(($detailedStats['by_size']['large'] / $detailedStats['summary']['total_files']) * 100, 1) : 0; ?>%</td>
                            </tr>
                            <tr>
                                <td>Extra Grandes (&gt;10MB)</td>
                                <td><?php echo $detailedStats['by_size']['xlarge']; ?></td>
                                <td><?php echo $detailedStats['summary']['total_files'] ? round(($detailedStats['by_size']['xlarge'] / $detailedStats['summary']['total_files']) * 100, 1) : 0; ?>%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Información del Sistema -->
        <div class="section-header">
            <h3><i class="fas fa-server me-2"></i>Información del Sistema</h3>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Versión PHP:</strong></td>
                        <td><?php echo getStatusBadge($detailedStats['system_info']['php_version'], 'text'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Versión GD:</strong></td>
                        <td><?php echo getStatusBadge($detailedStats['system_info']['gd_version'], 'text'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Soporte WebP:</strong></td>
                        <td><?php echo getStatusBadge($detailedStats['system_info']['webp_support']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Soporte AVIF:</strong></td>
                        <td><?php echo getStatusBadge($detailedStats['system_info']['avif_support']); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-lg-6">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Límite de memoria:</strong></td>
                        <td><?php echo getStatusBadge($detailedStats['system_info']['memory_limit'], 'text'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tamaño máximo de archivo:</strong></td>
                        <td><?php echo getStatusBadge($detailedStats['system_info']['upload_max_filesize'], 'text'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Espacio libre en disco:</strong></td>
                        <td><?php echo getStatusBadge(formatBytes($detailedStats['system_info']['disk_free']), 'text'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Servidor:</strong></td>
                        <td><?php echo getStatusBadge($detailedStats['system_info']['server_software'], 'text'); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Tendencias Temporales -->
        <?php if (!empty($detailedStats['by_date'])): ?>
            <div class="section-header">
                <h3><i class="fas fa-calendar me-2"></i>Tendencias Temporales</h3>
            </div>

            <div class="chart-container">
                <canvas id="timeChart"></canvas>
            </div>
        <?php endif; ?>

        <!-- Recomendaciones -->
        <div class="section-header">
            <h3><i class="fas fa-lightbulb me-2"></i>Recomendaciones</h3>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle me-2"></i>Análisis y Sugerencias</h5>
                    <ul class="mb-0">
                        <?php
                        $recommendations = [];

                        // Análisis de formatos
                        $jpegCount = $detailedStats['by_format']['jpg']['count'] ?? 0;
                        $webpCount = $detailedStats['by_format']['webp']['count'] ?? 0;

                        if ($jpegCount > $webpCount && function_exists('imagewebp')) {
                            $recommendations[] = "Considera convertir más imágenes a formato WebP para mejorar la eficiencia de almacenamiento.";
                        }

                        // Análisis de tamaños
                        $largeFiles = $detailedStats['by_size']['large'] + $detailedStats['by_size']['xlarge'];
                        if ($largeFiles > $detailedStats['summary']['total_files'] * 0.3) {
                            $recommendations[] = "Un alto porcentaje de archivos son grandes. Considera implementar compresión automática.";
                        }

                        // Análisis de espacio
                        $diskUsagePercent = (1 - ($detailedStats['system_info']['disk_free'] / ($detailedStats['system_info']['disk_free'] + $detailedStats['summary']['total_size']))) * 100;
                        if ($diskUsagePercent > 80) {
                            $recommendations[] = "El uso de disco está alto. Considera limpiar archivos antiguos o aumentar el espacio de almacenamiento.";
                        }

                        if (empty($recommendations)) {
                            $recommendations[] = "El sistema está funcionando de manera óptima. Continúa monitoreando el rendimiento regularmente.";
                        }

                        foreach ($recommendations as $rec):
                        ?>
                            <li><?php echo $rec; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-4 text-muted">
            <hr>
            <p>Reporte generado por EDI Medios v1.1.0 el <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Datos para gráficos
        const formatData = <?php echo json_encode($detailedStats['by_format']); ?>;
        const sizeData = <?php echo json_encode($detailedStats['by_size']); ?>;
        const timeData = <?php echo json_encode($detailedStats['by_date']); ?>;

        // Gráfico de formatos
        const formatCtx = document.getElementById('formatChart').getContext('2d');
        new Chart(formatCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(formatData).map(f => f.toUpperCase()),
                datasets: [{
                    data: Object.values(formatData).map(d => d.count),
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribución por Formato'
                    }
                }
            }
        });

        // Gráfico de tamaños
        const sizeCtx = document.getElementById('sizeChart').getContext('2d');
        new Chart(sizeCtx, {
            type: 'bar',
            data: {
                labels: ['Pequeños', 'Medianos', 'Grandes', 'Extra Grandes'],
                datasets: [{
                    label: 'Cantidad de Archivos',
                    data: [sizeData.small, sizeData.medium, sizeData.large, sizeData.xlarge],
                    backgroundColor: ['#36A2EB', '#FFCE56', '#FF6384', '#FF9F40']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribución por Tamaño'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico temporal (si hay datos)
        <?php if (!empty($detailedStats['by_date'])): ?>
            const timeCtx = document.getElementById('timeChart').getContext('2d');
            new Chart(timeCtx, {
                type: 'line',
                data: {
                    labels: Object.keys(timeData),
                    datasets: [{
                        label: 'Archivos Subidos',
                        data: Object.values(timeData),
                        borderColor: '#36A2EB',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Tendencia de Uploads por Mes'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        <?php endif; ?>

        // Ajustar gráficos para impresión
        window.addEventListener('beforeprint', function() {
            Chart.helpers.each(Chart.instances, function(chart) {
                chart.resize();
            });
        });
    </script>
</body>

</html>