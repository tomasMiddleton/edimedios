<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDI Medios - Sistema de Carga y Optimización</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- FilePond -->
    <link href="https://unpkg.com/filepond/dist/filepond.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            margin-top: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }

        .card-header {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
        }

        .upload-area {
            border: 3px dashed #ddd;
            border-radius: 10px;
            padding: 3rem;
            text-align: center;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .upload-area.dragover {
            border-color: var(--secondary-color);
            background: rgba(52, 152, 219, 0.1);
            transform: scale(1.02);
        }

        .upload-icon {
            font-size: 4rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .file-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #ddd;
        }

        .progress-container {
            margin-top: 1rem;
        }

        .alert {
            border-radius: 10px;
        }

        .btn {
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
        }

        .stats-card {
            background: linear-gradient(45deg, var(--success-color), #2ecc71);
            color: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .feature-list {
            list-style: none;
            padding: 0;
        }

        .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .feature-list li:last-child {
            border-bottom: none;
        }

        .feature-list i {
            color: var(--success-color);
            width: 20px;
        }

        #fileList {
            max-height: 400px;
            overflow-y: auto;
        }

        .file-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            background: white;
        }

        .optimization-examples {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            .main-container {
                margin: 1rem 0.5rem;
            }

            .upload-area {
                padding: 2rem 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="container main-container">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h1 class="text-white mb-2">
                    <i class="fas fa-cloud-upload-alt"></i>
                    EDI Medios
                </h1>
                <p class="text-white-50">Sistema avanzado de carga y optimización de imágenes</p>
            </div>
        </div>

        <div class="row">
            <!-- Panel de Carga Principal -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">
                            <i class="fas fa-upload me-2"></i>
                            Cargar Archivos
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Área de Drag & Drop -->
                        <div id="uploadArea" class="upload-area">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <h4>Arrastra tus archivos aquí</h4>
                            <p class="text-muted">o haz clic para seleccionar</p>

                            <!-- FilePond -->
                            <input type="file"
                                id="filepond"
                                name="filepond"
                                multiple
                                accept="image/*">

                            <!-- Fallback para navegadores sin JS -->
                            <noscript>
                                <form method="POST" action="upload_secure.php" enctype="multipart/form-data">
                                    <input type="file" name="filepond" accept="image/*" class="form-control mb-2">
                                    <button type="submit" class="btn btn-primary">Subir Archivo</button>
                                </form>
                            </noscript>
                        </div>

                        <!-- Progreso -->
                        <div id="progressContainer" class="progress-container d-none">
                            <div class="progress">
                                <div id="progressBar"
                                    class="progress-bar progress-bar-striped progress-bar-animated"
                                    role="progressbar"
                                    style="width: 0%">
                                </div>
                            </div>
                            <small id="progressText" class="text-muted">Subiendo archivos...</small>
                        </div>

                        <!-- Alertas -->
                        <div id="alertContainer"></div>

                        <!-- Lista de Archivos -->
                        <div id="fileList" class="mt-3"></div>
                    </div>
                </div>

                <!-- Ejemplos de Optimización -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-magic me-2"></i>
                            Optimización Automática
                        </h5>
                    </div>
                    <div class="card-body">
                        <p>Tus imágenes se optimizan automáticamente usando parámetros URL:</p>

                        <div class="optimization-examples">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="fas fa-expand-arrows-alt text-primary"></i> Redimensionar</h6>
                                    <code>imagen.jpg?w=640&h=360</code>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="fas fa-compress text-success"></i> Optimizar WebP</h6>
                                    <code>imagen.jpg?w=640&f=webp&q=85</code>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <h6><i class="fas fa-rocket text-warning"></i> Formato AVIF</h6>
                                    <code>imagen.jpg?w=400&f=avif</code>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="fas fa-sliders-h text-info"></i> Calidad Custom</h6>
                                    <code>imagen.jpg?q=90&f=webp</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel Lateral -->
            <div class="col-lg-4">
                <!-- Estadísticas -->
                <div class="stats-card">
                    <h5><i class="fas fa-chart-bar me-2"></i>Estadísticas</h5>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="h4 mb-0" id="totalFiles">0</div>
                            <small>Archivos</small>
                        </div>
                        <div class="col-6">
                            <div class="h4 mb-0" id="totalSize">0 KB</div>
                            <small>Tamaño Total</small>
                        </div>
                    </div>
                </div>

                <!-- Características -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-star me-2"></i>
                            Características
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i> Drag & Drop múltiple</li>
                            <li><i class="fas fa-check"></i> Preview instantáneo</li>
                            <li><i class="fas fa-check"></i> Optimización WebP/AVIF</li>
                            <li><i class="fas fa-check"></i> Redimensionado dinámico</li>
                            <li><i class="fas fa-check"></i> Cache inteligente</li>
                            <li><i class="fas fa-check"></i> Validación de seguridad</li>
                            <li><i class="fas fa-check"></i> Logs detallados</li>
                        </ul>
                    </div>
                </div>

                <!-- Formatos Soportados -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-file-image me-2"></i>
                            Formatos Soportados
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-2">
                                <span class="badge bg-primary">JPEG</span>
                            </div>
                            <div class="col-6 mb-2">
                                <span class="badge bg-primary">PNG</span>
                            </div>
                            <div class="col-6 mb-2">
                                <span class="badge bg-success">WebP</span>
                            </div>
                            <div class="col-6 mb-2">
                                <span class="badge bg-warning">AVIF</span>
                            </div>
                            <div class="col-12">
                                <span class="badge bg-secondary">GIF</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enlace a versión clásica -->
                <div class="text-center mt-3">
                    <a href="submit.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-1"></i>
                        Versión Clásica
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="row mt-4">
            <div class="col-12 text-center">
                <p class="text-white-50">
                    <small>EDI Medios v1.1.0 - Sistema de optimización de imágenes</small>
                </p>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/filepond/dist/filepond.min.js"></script>

    <script>
        // Inicializar variables
        let totalFiles = 0;
        let totalSize = 0;
        let uploadedFiles = [];

        // Configurar FilePond
        const inputElement = document.querySelector('#filepond');
        const pond = FilePond.create(inputElement, {
            allowMultiple: true,
            maxFiles: 10,
            acceptedFileTypes: ['image/*'],
            server: {
                url: './upload_secure.php',
                process: {
                    url: '',
                    method: 'POST',
                    onload: (response) => {
                        handleUploadSuccess(response);
                        return response;
                    },
                    onerror: (response) => {
                        handleUploadError(response);
                        return response;
                    }
                }
            },
            labelIdle: 'Arrastra archivos aquí o <span class="filepond--label-action">busca</span>',
            labelFileProcessing: 'Subiendo...',
            labelFileProcessingComplete: 'Subida completa',
            labelFileProcessingAborted: 'Subida cancelada',
            labelFileProcessingRevert: 'Revertir',
            labelTapToCancel: 'toca para cancelar',
            labelTapToUndo: 'toca para deshacer',
        });

        // Funciones de manejo de respuesta
        function handleUploadSuccess(response) {
            try {
                // Intentar parsear como JSON
                const data = JSON.parse(response);
                if (data.files && data.files.length > 0) {
                    data.files.forEach(file => addFileToList(file));
                    updateStats();
                    showAlert('success', `${data.files.length} archivo(s) subido(s) exitosamente`);
                }
            } catch (e) {
                // Respuesta compatible con upload.php original (solo nombre de archivo)
                if (response && !response.startsWith('Error:')) {
                    const file = {
                        stored_name: response,
                        original_name: response,
                        size: 0
                    };
                    addFileToList(file);
                    updateStats();
                    showAlert('success', 'Archivo subido exitosamente');
                }
            }
        }

        function handleUploadError(response) {
            showAlert('danger', 'Error al subir archivo: ' + response);
        }

        function addFileToList(file) {
            uploadedFiles.push(file);
            const fileList = document.getElementById('fileList');

            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            fileItem.innerHTML = `
                <div class="row align-items-center">
                    <div class="col-2">
                        <img src="uploads/${file.stored_name}" 
                             class="file-preview" 
                             alt="Preview"
                             onerror="this.src='no_image_available.png'">
                    </div>
                    <div class="col-7">
                        <h6 class="mb-1">${file.original_name || file.stored_name}</h6>
                        <small class="text-muted">
                            ${formatFileSize(file.size || 0)} • 
                            ${new Date().toLocaleTimeString()}
                        </small>
                    </div>
                    <div class="col-3 text-end">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" 
                                    onclick="copyUrl('${file.stored_name}')">
                                <i class="fas fa-copy"></i>
                            </button>
                            <button class="btn btn-outline-success" 
                                    onclick="showOptimizations('${file.stored_name}')">
                                <i class="fas fa-magic"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            fileList.appendChild(fileItem);
        }

        function updateStats() {
            totalFiles = uploadedFiles.length;
            totalSize = uploadedFiles.reduce((sum, file) => sum + (file.size || 0), 0);

            document.getElementById('totalFiles').textContent = totalFiles;
            document.getElementById('totalSize').textContent = formatFileSize(totalSize);
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function showAlert(type, message) {
            const alertContainer = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            alertContainer.appendChild(alert);

            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }

        function copyUrl(fileName) {
            const url = window.location.origin + window.location.pathname.replace('/index.php', '') + '/uploads/' + fileName;
            navigator.clipboard.writeText(url).then(() => {
                showAlert('info', 'URL copiada al portapapeles');
            });
        }

        function showOptimizations(fileName) {
            const baseUrl = window.location.origin + window.location.pathname.replace('/index.php', '') + '/uploads/' + fileName;
            const optimizations = [
                `${baseUrl}?w=640&h=360`,
                `${baseUrl}?w=640&h=360&f=webp&q=85`,
                `${baseUrl}?w=400&f=avif`,
                `${baseUrl}?w=300&h=200&q=90`
            ];

            const modalContent = optimizations.map(url =>
                `<div class="mb-2">
                    <input type="text" class="form-control" value="${url}" readonly>
                </div>`
            ).join('');

            // Mostrar modal simple con las URLs
            showAlert('info', `URLs de optimización generadas para ${fileName}`);
        }

        // Drag & Drop mejorado
        const uploadArea = document.getElementById('uploadArea');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            uploadArea.classList.add('dragover');
        }

        function unhighlight(e) {
            uploadArea.classList.remove('dragover');
        }
    </script>
</body>

</html>