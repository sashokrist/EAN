<?php
// Handle file upload
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excelFile'])) {
    $uploadDir = __DIR__;
    $allowedExtensions = ['xlsx', 'xls', 'csv'];
    $maxFileSize = 10 * 1024 * 1024; // 10MB
    
    $file = $_FILES['excelFile'];
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmpName = $file['tmp_name'];
    $fileError = $file['error'];
    
    // Check for upload errors
    if ($fileError !== UPLOAD_ERR_OK) {
        $message = 'Грешка при качване на файла.';
        $messageType = 'error';
    } else {
        // Check file extension
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            $message = 'Невалиден тип файл. Разрешени са само Excel файлове (.xlsx, .xls, .csv).';
            $messageType = 'error';
        } else {
            // Check file size
            if ($fileSize > $maxFileSize) {
                $message = 'Файлът е твърде голям. Максимален размер: 10MB.';
                $messageType = 'error';
            } else {
                // Move uploaded file to replace Inventory.xlsx
                $targetFile = $uploadDir . '/Inventory.xlsx';
                
                // Remove old file if exists
                if (file_exists($targetFile)) {
                    unlink($targetFile);
                }
                
                if (move_uploaded_file($fileTmpName, $targetFile)) {
                    $message = 'Файлът е качен успешно!';
                    $messageType = 'success';
                } else {
                    $message = 'Грешка при запазване на файла.';
                    $messageType = 'error';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Качване на файл - Инвентарна система</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .upload-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .upload-box {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .upload-area {
            border: 2px dashed #667eea;
            border-radius: 10px;
            padding: 3rem 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: #5a6fd8;
            background-color: #f8f9ff;
        }
        .upload-area.dragover {
            border-color: #28a745;
            background-color: #f0fff4;
        }
        .file-input {
            display: none;
        }
        .upload-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .upload-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .message {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .file-info {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .back-btn {
            background: #6c757d;
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 1rem;
        }
        .back-btn:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1 class="mb-4">
                        <i class="fas fa-cloud-upload-alt me-2"></i>
                        Качване на инвентарен файл
                    </h1>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left me-2"></i>Назад към търсенето
                </a>
                
                <div class="upload-box">
                    <?php if ($message): ?>
                        <div class="message <?php echo $messageType; ?>">
                            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form id="uploadForm" method="POST" enctype="multipart/form-data">
                        <div class="upload-area" id="uploadArea">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                            <h4>Плъзнете файла тук или кликнете за избор</h4>
                            <p class="text-muted">Поддържани формати: .xlsx, .xls, .csv</p>
                            <p class="text-muted">Максимален размер: 10MB</p>
                            <input type="file" id="fileInput" name="excelFile" class="file-input" accept=".xlsx,.xls,.csv" required>
                        </div>
                        
                        <div id="fileInfo" class="file-info" style="display: none;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-file-excel text-success me-2"></i>
                                <span id="fileName"></span>
                                <span id="fileSize" class="ms-auto text-muted"></span>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <button type="submit" class="upload-btn" id="uploadBtn" disabled>
                                <i class="fas fa-upload me-2"></i>Качи файл
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="upload-box">
                    <h5><i class="fas fa-info-circle me-2"></i>Инструкции:</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i>Файлът трябва да бъде в Excel формат (.xlsx, .xls) или CSV</li>
                        <li><i class="fas fa-check text-success me-2"></i>Първият ред трябва да съдържа заглавията на колоните</li>
                        <li><i class="fas fa-check text-success me-2"></i>Колоните трябва да са в ред: Код, Име, EAN/Баркод, Бранд, Площадка</li>
                        <li><i class="fas fa-check text-success me-2"></i>След качването файлът ще замени текущия инвентарен файл</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const uploadBtn = document.getElementById('uploadBtn');

        // Click to select file
        uploadArea.addEventListener('click', () => {
            fileInput.click();
        });

        // Drag and drop functionality
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect(files[0]);
            }
        });

        // File input change
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFileSelect(e.target.files[0]);
            }
        });

        function handleFileSelect(file) {
            // Validate file type
            const allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 
                                 'application/vnd.ms-excel', 
                                 'text/csv'];
            const allowedExtensions = ['xlsx', 'xls', 'csv'];
            const fileExtension = file.name.split('.').pop().toLowerCase();
            
            if (!allowedExtensions.includes(fileExtension)) {
                alert('Невалиден тип файл. Разрешени са само Excel файлове (.xlsx, .xls, .csv).');
                return;
            }
            
            // Validate file size (10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('Файлът е твърде голям. Максимален размер: 10MB.');
                return;
            }
            
            // Show file info
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileInfo.style.display = 'block';
            uploadBtn.disabled = false;
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Form submission
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Качване...';
        });
    </script>
</body>
</html>
