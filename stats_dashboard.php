<?php
// ARCHIVO: stats_dashboard.php
// Dashboard de estadísticas de imágenes con gráficos y métricas

require_once(__DIR__ . '/lib/StatsManager.php');
require_once(__DIR__ . '/lib/SecurityManager.php');

try {
    // Cargar sistema de seguridad
    $security = new SecurityManager();
    $security->applyCORS(); // Solo CORS para dashboard, no bloqueos

    $stats = new StatsManager();
    $generalStats = $stats->getGeneralStats();
    $topImages = $stats->getTopImages(15);
    $periodStats = $stats->getStatsByPeriod(30);
    $formatStats = $stats->getFormatStats();

    // Obtener logs de actividad
    $activityLogs = $stats->getActivityLogs(30);
    $logStats = $stats->getLogStats();

    // Obtener configuración de seguridad
    $securityConfig = $security->getConfig();

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
                <a href="simple_img_v3.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-image"></i> Optimizador
                </a>
                <a href="#" class="btn btn-outline-light btn-sm" onclick="showFileManager()">
                    <i class="fas fa-trash"></i> Gestión
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

            <!-- Logs de actividad recientes -->
            <div class="row">
                <div class="col-12">
                    <div class="chart-container">
                        <h4><i class="fas fa-list-alt"></i> Log de Actividad Reciente</h4>

                        <!-- Filtros de logs -->
                        <div class="mb-3">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary btn-sm active" onclick="filterLogs('all')">Todos</button>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="filterLogs('success')">Éxitos</button>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="filterLogs('error')">Errores</button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="filterLogs('upload')">Uploads</button>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="filterLogs('image_view')">Visualizaciones</button>
                            </div>
                        </div>

                        <?php if (empty($activityLogs)): ?>
                            <p class="text-muted">No hay logs de actividad aún.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tiempo</th>
                                            <th>Tipo</th>
                                            <th>Estado</th>
                                            <th>Mensaje</th>
                                            <th>Archivo</th>
                                            <th>IP</th>
                                        </tr>
                                    </thead>
                                    <tbody id="logsTableBody">
                                        <?php foreach ($activityLogs as $log): ?>
                                            <tr class="log-row" data-type="<?= htmlspecialchars($log['activity_type']) ?>" data-status="<?= htmlspecialchars($log['status']) ?>">
                                                <td>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?= htmlspecialchars($log['activity_type']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClass = '';
                                                    $statusIcon = '';
                                                    switch ($log['status']) {
                                                        case 'success':
                                                        case 'completed':
                                                            $statusClass = 'bg-success';
                                                            $statusIcon = 'fa-check';
                                                            break;
                                                        case 'error':
                                                        case 'failed':
                                                            $statusClass = 'bg-danger';
                                                            $statusIcon = 'fa-times';
                                                            break;
                                                        case 'not_found':
                                                            $statusClass = 'bg-warning';
                                                            $statusIcon = 'fa-question';
                                                            break;
                                                        default:
                                                            $statusClass = 'bg-info';
                                                            $statusIcon = 'fa-info';
                                                    }
                                                    ?>
                                                    <span class="badge <?= $statusClass ?>">
                                                        <i class="fas <?= $statusIcon ?>"></i>
                                                        <?= htmlspecialchars($log['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="log-message" title="<?= htmlspecialchars($log['details'] ?: $log['message']) ?>">
                                                        <?= htmlspecialchars(substr($log['message'], 0, 100)) ?><?= strlen($log['message']) > 100 ? '...' : '' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($log['file_path']): ?>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars(basename($log['file_path'])) ?>
                                                            <?php if ($log['file_size']): ?>
                                                                <br><span class="badge bg-light text-dark"><?= $stats->formatFileSize($log['file_size']) ?></span>
                                                            <?php endif; ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <small class="text-muted">-</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars(substr($log['ip_address'] ?: 'unknown', 0, 15)) ?>
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
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

    <!-- Modal de gestión de archivos -->
    <div class="modal fade" id="fileManagerModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-trash"></i> Gestión de Archivos y Logs
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Pestañas de gestión -->
                    <ul class="nav nav-tabs" id="managementTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="files-tab" data-bs-toggle="tab" data-bs-target="#files"
                                type="button" role="tab">
                                <i class="fas fa-file-image"></i> Archivos
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs"
                                type="button" role="tab">
                                <i class="fas fa-list-alt"></i> Logs
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security"
                                type="button" role="tab">
                                <i class="fas fa-shield-alt"></i> Seguridad
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="managementTabContent">
                        <!-- Pestaña de archivos -->
                        <div class="tab-pane fade show active" id="files" role="tabpanel">
                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6>Lista de Archivos</h6>
                                        <small class="text-muted">Gestiona archivos subidos y huérfanos</small>
                                    </div>
                                    <div>
                                        <select class="form-select form-select-sm" id="fileTypeFilter" onchange="loadFiles()">
                                            <option value="all">Todos los archivos</option>
                                            <option value="uploads">Solo con registros BD</option>
                                            <option value="orphaned">Solo archivos huérfanos</option>
                                        </select>
                                    </div>
                                </div>

                                <div id="filesLoading" class="text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>

                                <div id="filesContent" style="display: none;">
                                    <div id="filesStats" class="alert alert-info"></div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Archivo</th>
                                                    <th>Tamaño</th>
                                                    <th>Fecha</th>
                                                    <th>Vistas</th>
                                                    <th>Estado</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="filesTableBody">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pestaña de logs -->
                        <div class="tab-pane fade" id="logs" role="tabpanel">
                            <div class="mt-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Eliminar Logs por Tipo</h6>
                                        <div class="mb-3">
                                            <select class="form-select" id="logTypeSelect">
                                                <option value="">Seleccionar tipo</option>
                                                <option value="upload">Uploads</option>
                                                <option value="image_view">Visualizaciones</option>
                                                <option value="file_delete">Eliminaciones</option>
                                                <option value="system">Sistema</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <select class="form-select" id="logStatusSelect">
                                                <option value="">Todos los estados</option>
                                                <option value="success">Éxitos</option>
                                                <option value="error">Errores</option>
                                                <option value="warning">Advertencias</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <input type="number" class="form-control" id="logDaysInput"
                                                placeholder="Más antiguos que X días (opcional)">
                                        </div>
                                        <button class="btn btn-warning" onclick="deleteLogsByType()">
                                            <i class="fas fa-trash"></i> Eliminar Logs Filtrados
                                        </button>
                                    </div>

                                    <div class="col-md-6">
                                        <h6 class="text-danger">⚠️ Zona Peligrosa</h6>
                                        <div class="alert alert-danger">
                                            <strong>Eliminar TODOS los logs</strong><br>
                                            <small>Esta acción es IRREVERSIBLE</small>
                                        </div>
                                        <div class="mb-3">
                                            <input type="text" class="form-control" id="confirmationCodeInput"
                                                placeholder="Código de confirmación">
                                            <small class="text-muted">
                                                Código requerido: <code id="requiredCode">DELETE_ALL_LOGS_<?= date('Ymd') ?></code>
                                            </small>
                                        </div>
                                        <button class="btn btn-danger" onclick="deleteAllLogs()">
                                            <i class="fas fa-exclamation-triangle"></i> ELIMINAR TODOS LOS LOGS
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pestaña de seguridad -->
                        <div class="tab-pane fade" id="security" role="tabpanel">
                            <div class="mt-3">
                                <h6>🛡️ Configuración de Seguridad</h6>

                                <!-- CORS Configuration -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">🌐 CORS (Cross-Origin Resource Sharing)</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Estado:</label>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="corsEnabled"
                                                        <?= ($securityConfig['cors']['enabled'] ?? false) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="corsEnabled">
                                                        CORS Habilitado
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Orígenes permitidos:</label>
                                                <div id="allowedOrigins">
                                                    <?php foreach ($securityConfig['cors']['allowed_origins'] ?? [] as $origin): ?>
                                                        <div class="input-group mb-2">
                                                            <input type="text" class="form-control" value="<?= htmlspecialchars($origin) ?>">
                                                            <button class="btn btn-outline-danger" type="button" onclick="removeOrigin(this)">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <button class="btn btn-outline-primary btn-sm" onclick="addOrigin()">
                                                    <i class="fas fa-plus"></i> Agregar origen
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rate Limiting -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">⚡ Rate Limiting</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="rateLimitEnabled"
                                                        <?= ($securityConfig['rate_limiting']['enabled'] ?? false) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="rateLimitEnabled">
                                                        Rate Limiting Habilitado
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Requests por minuto:</label>
                                                <input type="number" class="form-control" id="requestsPerMinute"
                                                    value="<?= $securityConfig['rate_limiting']['requests_per_minute'] ?? 60 ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Requests por hora:</label>
                                                <input type="number" class="form-control" id="requestsPerHour"
                                                    value="<?= $securityConfig['rate_limiting']['requests_per_hour'] ?? 1000 ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Upload Security -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">📁 Seguridad de Uploads</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Tamaño máximo (MB):</label>
                                                <input type="number" class="form-control" id="maxFileSize"
                                                    value="<?= $securityConfig['file_upload']['max_file_size_mb'] ?? 100 ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="blockExecutable"
                                                        <?= ($securityConfig['file_upload']['block_executable_content'] ?? true) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="blockExecutable">
                                                        Bloquear contenido ejecutable
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <label class="form-label">Extensiones permitidas:</label>
                                            <input type="text" class="form-control" id="allowedExtensions"
                                                value="<?= implode(', ', $securityConfig['file_upload']['allowed_extensions'] ?? []) ?>">
                                            <small class="text-muted">Separadas por comas</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Emergency Mode -->
                                <div class="card border-warning">
                                    <div class="card-header bg-warning">
                                        <h6 class="mb-0">🚨 Modo de Emergencia</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="maintenanceMode"
                                                <?= ($securityConfig['emergency']['maintenance_mode'] ?? false) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="maintenanceMode">
                                                Modo Mantenimiento
                                            </label>
                                        </div>
                                        <div class="mt-2">
                                            <label class="form-label">Mensaje de mantenimiento:</label>
                                            <textarea class="form-control" id="maintenanceMessage" rows="2"><?= htmlspecialchars($securityConfig['emergency']['maintenance_message'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <button class="btn btn-primary" onclick="saveSecurityConfig()">
                                        <i class="fas fa-save"></i> Guardar Configuración
                                    </button>
                                    <small class="text-muted ms-2">Los cambios se aplicarán inmediatamente</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
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

        // Función para filtrar logs
        function filterLogs(type) {
            const rows = document.querySelectorAll('.log-row');
            const buttons = document.querySelectorAll('.btn-group .btn');

            // Actualizar botones
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            // Filtrar filas
            rows.forEach(row => {
                if (type === 'all') {
                    row.style.display = '';
                } else if (type === 'success') {
                    row.style.display = (row.dataset.status === 'success' || row.dataset.status === 'completed') ? '' : 'none';
                } else if (type === 'error') {
                    row.style.display = (row.dataset.status === 'error' || row.dataset.status === 'failed' || row.dataset.status === 'not_found') ? '' : 'none';
                } else {
                    row.style.display = row.dataset.type === type ? '' : 'none';
                }
            });
        }

        // Función para mostrar modal de gestión
        function showFileManager() {
            const modal = new bootstrap.Modal(document.getElementById('fileManagerModal'));
            modal.show();
            loadFiles(); // Cargar archivos al abrir
        }

        // Cargar lista de archivos
        function loadFiles() {
            const type = document.getElementById('fileTypeFilter').value;
            document.getElementById('filesLoading').style.display = 'block';
            document.getElementById('filesContent').style.display = 'none';

            fetch(`file_manager.php?action=list&type=${type}&limit=50`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayFiles(data.files, data.stats);
                    } else {
                        alert('Error cargando archivos: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión');
                })
                .finally(() => {
                    document.getElementById('filesLoading').style.display = 'none';
                    document.getElementById('filesContent').style.display = 'block';
                });
        }

        // Mostrar archivos en tabla
        function displayFiles(files, stats) {
            const statsDiv = document.getElementById('filesStats');
            const tbody = document.getElementById('filesTableBody');

            // Mostrar estadísticas
            statsDiv.innerHTML = `
                 <strong>📊 Resumen:</strong> 
                 ${stats.total_files} archivos, 
                 ${stats.formatted_size} total, 
                 ${stats.total_views} visualizaciones
             `;

            // Limpiar tabla
            tbody.innerHTML = '';

            // Agregar archivos
            files.forEach(file => {
                const row = document.createElement('tr');
                const statusBadge = file.type === 'database' ?
                    '<span class="badge bg-success">En BD</span>' :
                    '<span class="badge bg-warning">Huérfano</span>';

                const existsBadge = file.exists ?
                    '<span class="badge bg-success">Existe</span>' :
                    '<span class="badge bg-danger">Falta</span>';

                row.innerHTML = `
                     <td>
                         <small title="${file.path}">${file.original_name}</small><br>
                         <code style="font-size: 0.8em;">${file.path}</code>
                     </td>
                     <td>${formatFileSize(file.size)}</td>
                     <td><small>${file.upload_date}</small></td>
                     <td>${file.view_count}</td>
                     <td>
                         ${statusBadge}<br>
                         ${existsBadge}
                     </td>
                     <td>
                         <button class="btn btn-danger btn-sm" onclick="deleteFile('${file.path}', '${file.original_name}')">
                             <i class="fas fa-trash"></i>
                         </button>
                     </td>
                 `;
                tbody.appendChild(row);
            });
        }

        // Eliminar archivo específico
        function deleteFile(filePath, fileName) {
            if (!confirm(`¿Eliminar completamente "${fileName}"?\n\nEsto eliminará:\n- Archivo físico\n- Registros de base de datos\n- Logs relacionados\n\n⚠️ Esta acción es IRREVERSIBLE`)) {
                return;
            }

            fetch('file_manager.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete_file',
                        file_path: filePath,
                        confirm: true
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ' + data.message);
                        loadFiles(); // Recargar lista
                    } else {
                        alert('❌ Error: ' + (data.message || data.error));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión');
                });
        }

        // Eliminar logs por tipo
        function deleteLogsByType() {
            const activityType = document.getElementById('logTypeSelect').value;
            const status = document.getElementById('logStatusSelect').value;
            const olderThanDays = document.getElementById('logDaysInput').value;

            if (!activityType) {
                alert('Selecciona un tipo de actividad');
                return;
            }

            const filters = [];
            if (status) filters.push(`estado: ${status}`);
            if (olderThanDays) filters.push(`más antiguos que ${olderThanDays} días`);

            const filterText = filters.length ? ` (${filters.join(', ')})` : '';

            if (!confirm(`¿Eliminar logs de tipo "${activityType}"${filterText}?\n\n⚠️ Esta acción es IRREVERSIBLE`)) {
                return;
            }

            const payload = {
                action: 'delete_logs',
                activity_type: activityType
            };
            if (status) payload.status = status;
            if (olderThanDays) payload.older_than_days = parseInt(olderThanDays);

            fetch('file_manager.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ' + data.message);
                        location.reload(); // Recargar página para actualizar logs
                    } else {
                        alert('❌ Error: ' + (data.message || data.error));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión');
                });
        }

        // Eliminar TODOS los logs
        function deleteAllLogs() {
            const confirmationCode = document.getElementById('confirmationCodeInput').value;
            const expectedCode = 'DELETE_ALL_LOGS_<?= date('Ymd') ?>';

            if (!confirmationCode) {
                alert(`Ingresa el código de confirmación: ${expectedCode}`);
                return;
            }

            if (confirmationCode !== expectedCode) {
                alert('Código de confirmación incorrecto');
                return;
            }

            if (!confirm('⚠️ ¿ELIMINAR TODOS LOS LOGS?\n\nEsta acción eliminará TODOS los registros de logs de la base de datos.\n\n🚨 ESTA ACCIÓN ES COMPLETAMENTE IRREVERSIBLE 🚨')) {
                return;
            }

            fetch('file_manager.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete_all_logs',
                        confirmation_code: confirmationCode
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('⚠️ ' + data.message);
                        location.reload(); // Recargar página
                    } else {
                        alert('❌ Error: ' + (data.message || data.error));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión');
                });
        }

        // Formatear tamaño de archivo
        function formatFileSize(bytes) {
            const units = ['B', 'KB', 'MB', 'GB'];
            let size = bytes;
            let unitIndex = 0;

            while (size >= 1024 && unitIndex < units.length - 1) {
                size /= 1024;
                unitIndex++;
            }

            return `${size.toFixed(2)} ${units[unitIndex]}`;
        }

        // Gestión de orígenes CORS
        function addOrigin() {
            const container = document.getElementById('allowedOrigins');
            const div = document.createElement('div');
            div.className = 'input-group mb-2';
            div.innerHTML = `
                 <input type="text" class="form-control" placeholder="https://ejemplo.com">
                 <button class="btn btn-outline-danger" type="button" onclick="removeOrigin(this)">
                     <i class="fas fa-times"></i>
                 </button>
             `;
            container.appendChild(div);
        }

        function removeOrigin(button) {
            button.parentElement.remove();
        }

        // Guardar configuración de seguridad
        function saveSecurityConfig() {
            const config = {
                cors: {
                    enabled: document.getElementById('corsEnabled').checked,
                    allowed_origins: Array.from(document.querySelectorAll('#allowedOrigins input')).map(input => input.value).filter(v => v),
                    allowed_methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                    allowed_headers: ['Origin', 'X-Requested-With', 'Content-Type', 'Accept', 'Authorization'],
                    allow_credentials: false,
                    max_age: 3600
                },
                rate_limiting: {
                    enabled: document.getElementById('rateLimitEnabled').checked,
                    requests_per_minute: parseInt(document.getElementById('requestsPerMinute').value),
                    requests_per_hour: parseInt(document.getElementById('requestsPerHour').value),
                    blocked_duration_minutes: 15
                },
                file_upload: {
                    max_file_size_mb: parseInt(document.getElementById('maxFileSize').value),
                    allowed_extensions: document.getElementById('allowedExtensions').value.split(',').map(ext => ext.trim()),
                    block_executable_content: document.getElementById('blockExecutable').checked
                },
                emergency: {
                    maintenance_mode: document.getElementById('maintenanceMode').checked,
                    maintenance_message: document.getElementById('maintenanceMessage').value
                }
            };

            fetch('security_manager.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'update_config',
                        config: config
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Configuración de seguridad guardada correctamente');
                    } else {
                        alert('❌ Error: ' + (data.message || data.error));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión');
                });
        }
    </script>
</body>

</html>