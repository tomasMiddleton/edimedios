<?php
// ARCHIVO: stats_dashboard.php
// Dashboard de estadísticas de imágenes con gráficos y métricas

require_once(__DIR__ . '/lib/StatsManager.php');

try {
    $stats = new StatsManager();
    $generalStats = $stats->getGeneralStats();
    $topImages = $stats->getTopImages(15);
    $periodStats = $stats->getStatsByPeriod(30);
    $formatStats = $stats->getFormatStats();

    // Si se especifica una imagen, obtener sus estadísticas
    $imageStats = null;
    $imagePath = $_GET['image'] ?? '';
    if ($imagePath) {
        $imageStats = $stats->getImageStats($imagePath);
    }
} catch (Exception $e) {
    $error = "Error cargando estadísticas: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Dashboard de Estadísticas - EDI Medios</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .metric-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .metric-label {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .image-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .image-item:last-child {
            border-bottom: none;
        }

        .image-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .image-detail {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chart-bar"></i>
                📊 Dashboard de Estadísticas - EDI Medios
            </a>
            <div>
                <a href="index.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-upload"></i> Subir archivos
                </a>
                <a href="simple_img_v3.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-image"></i> Optimizador
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php else: ?>

            <?php if ($imageStats): ?>
                <!-- Estadísticas de imagen específica -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="image-detail">
                            <h2><i class="fas fa-image"></i> Estadísticas de imagen</h2>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>📁 Información del archivo</h5>
                                    <ul class="list-unstyled">
                                        <?php if ($imageStats['file']): ?>
                                            <li><strong>Nombre original:</strong> <?= htmlspecialchars($imageStats['file']['original_name']) ?></li>
                                            <li><strong>Archivo actual:</strong> <?= htmlspecialchars($imageStats['file']['filename']) ?></li>
                                            <li><strong>Tamaño:</strong> <?= $stats->formatFileSize($imageStats['file']['file_size']) ?></li>
                                            <li><strong>Tipo:</strong> <?= htmlspecialchars($imageStats['file']['mime_type']) ?></li>
                                            <li><strong>Subido:</strong> <?= $imageStats['file']['upload_date'] ?></li>
                                        <?php else: ?>
                                            <li><em>Archivo legacy (sin metadatos detallados)</em></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5>📊 Estadísticas de visualización</h5>
                                    <ul class="list-unstyled">
                                        <li><strong>Total de visualizaciones:</strong> <?= number_format($imageStats['views']['total_views']) ?></li>
                                        <li><strong>Días con visualizaciones:</strong> <?= $imageStats['views']['days_viewed'] ?></li>
                                        <li><strong>Primera visualización:</strong> <?= $imageStats['views']['first_view'] ?: 'N/A' ?></li>
                                        <li><strong>Última visualización:</strong> <?= $imageStats['views']['last_view'] ?: 'N/A' ?></li>
                                        <li><strong>Tiempo promedio de procesamiento:</strong> <?= $imageStats['views']['avg_processing_time'] ? round($imageStats['views']['avg_processing_time'], 2) . ' ms' : 'N/A' ?></li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Vista previa de la imagen -->
                            <div class="mt-3">
                                <h5>🖼️ Vista previa</h5>
                                <div class="d-flex gap-3 flex-wrap">
                                    <div>
                                        <p class="mb-1"><small>Original</small></p>
                                        <img src="simple_img_v3.php?src=<?= urlencode($imagePath) ?>"
                                            alt="Original"
                                            style="max-width: 150px; max-height: 150px; border-radius: 5px;">
                                    </div>
                                    <div>
                                        <p class="mb-1"><small>Thumbnail 100x100</small></p>
                                        <img src="simple_img_v3.php?src=<?= urlencode($imagePath) ?>&w=100&h=100"
                                            alt="Thumbnail"
                                            style="width: 100px; height: 100px; border-radius: 5px;">
                                    </div>
                                    <div>
                                        <p class="mb-1"><small>WebP 200x200</small></p>
                                        <img src="simple_img_v3.php?src=<?= urlencode($imagePath) ?>&w=200&h=200&f=webp"
                                            alt="WebP"
                                            style="width: 100px; height: 100px; border-radius: 5px;">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <a href="stats_dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Volver al dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Métricas generales -->
            <div class="stats-grid">
                <div class="metric-card">
                    <div class="metric-value"><?= number_format($generalStats['total_uploads']) ?></div>
                    <div class="metric-label">
                        <i class="fas fa-upload"></i> Total de archivos subidos
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-value"><?= number_format($generalStats['total_views']) ?></div>
                    <div class="metric-label">
                        <i class="fas fa-eye"></i> Total de visualizaciones
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-value"><?= number_format($generalStats['unique_images']) ?></div>
                    <div class="metric-label">
                        <i class="fas fa-images"></i> Imágenes únicas vistas
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-value"><?= $stats->formatFileSize($generalStats['total_size']) ?></div>
                    <div class="metric-label">
                        <i class="fas fa-hdd"></i> Tamaño total almacenado
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-value"><?= $generalStats['avg_views_per_image'] ?></div>
                    <div class="metric-label">
                        <i class="fas fa-chart-line"></i> Promedio vistas/imagen
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-value"><?= number_format($generalStats['today_uploads']) ?></div>
                    <div class="metric-label">
                        <i class="fas fa-calendar-day"></i> Uploads hoy
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Gráfico de visualizaciones por día -->
                <div class="col-lg-8">
                    <div class="chart-container">
                        <h4><i class="fas fa-chart-area"></i> Visualizaciones últimos 30 días</h4>
                        <canvas id="viewsChart" height="100"></canvas>
                    </div>
                </div>

                <!-- Gráfico de formatos -->
                <div class="col-lg-4">
                    <div class="chart-container">
                        <h4><i class="fas fa-chart-pie"></i> Formatos más usados</h4>
                        <canvas id="formatsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Imágenes más vistas -->
            <div class="row">
                <div class="col-12">
                    <div class="chart-container">
                        <h4><i class="fas fa-trophy"></i> Top 15 - Imágenes más vistas</h4>
                        <?php if (empty($topImages)): ?>
                            <p class="text-muted">No hay datos de visualizaciones aún.</p>
                        <?php else: ?>
                            <?php foreach ($topImages as $image): ?>
                                <div class="image-item">
                                    <img src="simple_img_v3.php?src=<?= urlencode($image['image_path']) ?>&w=60&h=60"
                                        alt="Thumbnail"
                                        class="image-thumbnail"
                                        onerror="this.src='no_image_available.png'">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?= htmlspecialchars($image['original_name'] ?: basename($image['image_path'])) ?>
                                                </h6>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($image['image_path']) ?>
                                                    <?php if ($image['file_size']): ?>
                                                        • <?= $stats->formatFileSize($image['file_size']) ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-primary fs-6">
                                                    <?= number_format($image['view_count']) ?> visualizaciones
                                                </span>
                                                <div>
                                                    <small class="text-muted">
                                                        Última vista: <?= date('d/m/Y H:i', strtotime($image['last_view'])) ?>
                                                    </small>
                                                </div>
                                                <div class="mt-1">
                                                    <a href="stats_dashboard.php?image=<?= urlencode($image['image_path']) ?>"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-chart-bar"></i> Ver detalles
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Gráfico de visualizaciones por día
        const viewsCtx = document.getElementById('viewsChart').getContext('2d');
        const viewsData = <?= json_encode(array_reverse($periodStats)) ?>;

        new Chart(viewsCtx, {
            type: 'line',
            data: {
                labels: viewsData.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('es-ES', {
                        month: 'short',
                        day: 'numeric'
                    });
                }),
                datasets: [{
                    label: 'Visualizaciones',
                    data: viewsData.map(item => item.views),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Imágenes únicas',
                    data: viewsData.map(item => item.unique_images),
                    borderColor: '#764ba2',
                    backgroundColor: 'rgba(118, 75, 162, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });

        // Gráfico de formatos
        const formatsCtx = document.getElementById('formatsChart').getContext('2d');
        const formatsData = <?= json_encode($formatStats) ?>;

        new Chart(formatsCtx, {
            type: 'doughnut',
            data: {
                labels: formatsData.map(item => item.format || 'Original'),
                datasets: [{
                    data: formatsData.map(item => item.count),
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#f5576c',
                        '#4facfe',
                        '#43e97b'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>

</html>