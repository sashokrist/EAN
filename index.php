<?php
// Simple Excel reader without external dependencies
function readExcelFile($filename) {
    $data = [];
    
    // Check if file exists
    if (!file_exists($filename)) {
        error_log("Excel file not found: $filename");
        return $data;
    }
    
    $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Try CSV first
    if ($fileExtension === 'csv') {
        $handle = fopen($filename, 'r');
        if ($handle !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                $data[] = $row;
            }
            fclose($handle);
        }
        return $data;
    }
    
    // For Excel files (.xlsx), try the ZIP approach
    if ($fileExtension === 'xlsx') {
        if (!class_exists('ZipArchive')) {
            error_log("ZipArchive class not available");
            return $data;
        }
        
        $zip = new ZipArchive();
        if ($zip->open($filename) === TRUE) {
            $sharedStrings = [];
            
            // Read shared strings
            if ($zip->locateName('xl/sharedStrings.xml') !== false) {
                $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
                if ($sharedStringsXml !== false) {
                    $sharedStringsDoc = new DOMDocument();
                    $sharedStringsDoc->loadXML($sharedStringsXml);
                    $siElements = $sharedStringsDoc->getElementsByTagName('si');
                    foreach ($siElements as $si) {
                        $tElements = $si->getElementsByTagName('t');
                        if ($tElements->length > 0) {
                            $sharedStrings[] = $tElements->item(0)->textContent;
                        }
                    }
                }
            }
            
            // Read worksheet
            if ($zip->locateName('xl/worksheets/sheet1.xml') !== false) {
                $worksheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
                if ($worksheetXml !== false) {
                    $worksheetDoc = new DOMDocument();
                    $worksheetDoc->loadXML($worksheetXml);
                    $rowElements = $worksheetDoc->getElementsByTagName('row');
                    
                    foreach ($rowElements as $row) {
                        $rowData = [];
                        $cellElements = $row->getElementsByTagName('c');
                        
                        foreach ($cellElements as $cell) {
                            $cellValue = '';
                            $vElements = $cell->getElementsByTagName('v');
                            if ($vElements->length > 0) {
                                $cellValue = $vElements->item(0)->textContent;
                                
                                // Check if it's a shared string
                                $tAttribute = $cell->getAttribute('t');
                                if ($tAttribute === 's' && isset($sharedStrings[$cellValue])) {
                                    $cellValue = $sharedStrings[$cellValue];
                                }
                            }
                            $rowData[] = $cellValue;
                        }
                        if (!empty($rowData)) {
                            $data[] = $rowData;
                        }
                    }
                }
            }
            $zip->close();
        } else {
            error_log("Could not open Excel file: $filename");
        }
    }
    
    // If Excel reading failed, try to create sample data for testing
    if (empty($data)) {
        error_log("Excel reading failed, creating sample data");
        $data = [
            ['Име на продукт', 'Бранд', 'Код', 'Баркод', 'Локация'],
            ['DY6329', 'Samsung', 'DY6329', '1234567890123', 'Склад А'],
            ['iPhone 15', 'Apple', 'IP15', '9876543210987', 'Склад Б'],
            ['Galaxy S24', 'Samsung', 'GS24', '5555555555555', 'Склад А']
        ];
    }
    
    return $data;
}

// Read inventory data
$inventoryData = readExcelFile('Inventory.xlsx');

// Handle AJAX search request
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = strtolower(trim($_GET['search']));
    $results = [];
    
    // Split search term into individual words
    $searchWords = array_filter(explode(' ', $searchTerm), function($word) {
        return strlen(trim($word)) > 0;
    });
    
    foreach ($inventoryData as $index => $row) {
        if ($index === 0) continue; // Skip header row
        
        $found = false;
        
        // If only one word, use original exact match logic
        if (count($searchWords) === 1) {
            foreach ($row as $cell) {
                if (strpos(strtolower($cell), $searchWords[0]) !== false) {
                    $found = true;
                    break;
                }
            }
        } else {
            // For multiple words, check if ALL words appear in ANY cell
            $allWordsFound = true;
            foreach ($searchWords as $word) {
                $wordFound = false;
                foreach ($row as $cell) {
                    if (strpos(strtolower($cell), $word) !== false) {
                        $wordFound = true;
                        break;
                    }
                }
                if (!$wordFound) {
                    $allWordsFound = false;
                    break;
                }
            }
            $found = $allWordsFound;
        }
        
        if ($found) {
            $results[] = $row;
        }
    }
    
    
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Инвентарна система - Търсене на продукти</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .search-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .search-box {
            background: white;
            border-radius: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 0.5rem 1rem;
            margin-bottom: 1rem;
        }
        .search-input {
            border: none;
            outline: none;
            font-size: 1.1rem;
            padding: 0.5rem;
            width: 100%;
        }
        .search-btn {
            background: #667eea;
            border: none;
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            transition: all 0.3s ease;
        }
        .search-btn:hover {
            background: #5a6fd8;
            transform: scale(1.05);
        }
        .results-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .product-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        .product-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .product-name {
            font-weight: bold;
            color: #2c3e50;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .product-brand {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        .product-code {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        .product-barcode {
            color: #28a745;
            font-weight: 500;
            margin-bottom: 0.3rem;
        }
        .product-location {
            background: linear-gradient(135deg, #1976d2, #42a5f5);
            color: white;
            padding: 0.8rem 1.2rem;
            border-radius: 20px;
            font-size: 1.1rem;
            font-weight: 600;
            display: inline-block;
            box-shadow: 0 2px 8px rgba(25, 118, 210, 0.3);
            margin-top: 0.5rem;
            text-align: center;
            min-width: 120px;
        }
        .no-results {
            text-align: center;
            color: #6c757d;
            padding: 2rem;
            font-size: 1.1rem;
        }
        .loading {
            text-align: center;
            padding: 2rem;
            color: #667eea;
        }
        .stats {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stats-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .search-container {
                padding: 1rem 0;
            }
            .search-input {
                font-size: 1rem;
            }
            .product-card {
                padding: 0.8rem;
            }
            .product-name {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="search-container">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <div class="row justify-content-center">
                        <div class="col-md-8 col-lg-6">
                            <div class="search-box d-flex align-items-center">
                                <input type="text" id="searchInput" class="search-input" placeholder="Търси по име, бранд, код или баркод...">
                                <button class="search-btn ms-2" onclick="performSearch()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div class="text-center mt-2">
                                <span id="resultsCount" class="text-white fw-bold" style="font-size: 1.1rem;"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">

        <div class="row">
            <div class="col-12">
                <div class="results-container">
                    <div id="searchResults">
                        <div class="text-center text-muted">
                            <i class="fas fa-search fa-3x mb-3"></i>
                            <h4>Започнете търсенето</h4>
                            <p>Въведете име на продукт, бранд, код или баркод за да видите резултатите</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let searchTimeout;
        
        // Auto-search as user types
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch();
            }, 300);
        });
        
        // Search on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
        
        function performSearch() {
            const searchTerm = document.getElementById('searchInput').value.trim();
            const resultsContainer = document.getElementById('searchResults');
            
            if (searchTerm === '') {
                resultsContainer.innerHTML = `
                    <div class="text-center text-muted">
                        <i class="fas fa-search fa-3x mb-3"></i>
                        <h4>Започнете търсенето</h4>
                        <p>Въведете име на продукт, бранд, код или баркод за да видите резултатите</p>
                    </div>
                `;
                document.getElementById('resultsCount').textContent = '';
                return;
            }
            
            // Show loading
            resultsContainer.innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                    <div>Търсене...</div>
                </div>
            `;
            document.getElementById('resultsCount').textContent = '';
            
            // Perform AJAX search
            fetch(`?search=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    displayResults(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultsContainer.innerHTML = `
                        <div class="no-results">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <div>Възникна грешка при търсенето</div>
                        </div>
                    `;
                });
        }
        
        function displayResults(results) {
            const resultsContainer = document.getElementById('searchResults');
            
            if (results.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-search fa-2x mb-2"></i>
                        <div>Няма намерени резултати</div>
                        <small>Опитайте с различни ключови думи</small>
                    </div>
                `;
                return;
            }
            
            let html = '';
            results.forEach((row, index) => {
                // Debug: log the row data
                console.log('Row data:', row);
                
                // Handle different possible Excel structures
                // Try to find the most likely columns for each field
                let name = 'Неизвестен продукт';
                let brand = 'Неизвестен бранд';
                let code = 'Няма код';
                let barcode = 'Няма баркод';
                let location = 'Неизвестна локация';
                
                // If row is an array, use it directly
                if (Array.isArray(row)) {
                    code = row[0] || 'Няма код';        // Код (Code)
                    name = row[1] || 'Неизвестен продукт';  // Име (Name)
                    barcode = row[2] || 'Няма баркод';  // EAN/Баркод (Barcode)
                    brand = row[3] || 'Неизвестен бранд';  // Бранд (Brand)
                    location = row[4] || 'Неизвестна локация'; // Площадка (Location)
                } else if (typeof row === 'object') {
                    // If row is an object, try to extract fields
                    code = row.code || row.Code || row.product_code || row[0] || 'Няма код';
                    name = row.name || row.Name || row.product || row.Product || row[1] || 'Неизвестен продукт';
                    barcode = row.barcode || row.Barcode || row[2] || 'Няма баркод';
                    brand = row.brand || row.Brand || row[3] || 'Неизвестен бранд';
                    location = row.location || row.Location || row[4] || 'Неизвестна локация';
                }
                
                // Clean up the data
                name = String(name).trim() || 'Неизвестен продукт';
                brand = String(brand).trim() || 'Неизвестен бранд';
                code = String(code).trim() || 'Няма код';
                barcode = String(barcode).trim() || 'Няма баркод';
                location = String(location).trim() || 'Неизвестна локация';
                
                html += `
                    <div class="product-card">
                        <div class="mb-1">
                            <span class="text-primary fw-bold">${code}</span>
                        </div>
                        <div class="mb-1">
                            <span class="text-dark">${name}</span>
                        </div>
                        <div class="mb-1">
                            <strong>EAN:</strong>
                            <span class="text-success ">${barcode}</span>
                        </div>
                        <div class="mb-1">
                            <strong>Бранд:</strong>
                            <span class="text-primary">${brand}</span>
                        </div>
                        <div class="mb-1">
                            <strong>LOC:</strong>
                            <span class="text-danger fw-bold">${location}</span>
                        </div>
                    </div>
                `;
            });
            
            resultsContainer.innerHTML = html;
            document.getElementById('resultsCount').textContent = `${results.length} намерени резултата`;
        }
        
        // Focus on search input when page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('searchInput').focus();
        });
    </script>
</body>
</html>
