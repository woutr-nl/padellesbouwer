<?php
require_once 'includes/init.php';

// Controleer of gebruiker is ingelogd
if (!isLoggedIn()) {
    redirect('login.php');
}

require_once 'classes/Les.php';

$lesId = (int) ($_GET['id'] ?? 1);
$les = new Les();
$lesData = $les->getById($lesId);

if (!$lesData) {
    die('Les niet gevonden');
}

// Les items ophalen
$lesItems = $les->getItems($lesId);

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $csrf_token ?>">
    <title>Debug Editor - Padelles Beheersysteem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Debug Editor</h5>
                    </div>
                    <div class="card-body">
                        <h6><?= escapeHTML($lesData['titel']) ?></h6>
                        <p>Les ID: <?= $lesId ?></p>
                        <p>Items in database: <?= count($lesItems) ?></p>
                        
                        <hr>
                        
                        <h6>Database Items</h6>
                        <div style="max-height: 200px; overflow-y: auto; font-size: 12px;">
                            <?php foreach ($lesItems as $item): ?>
                            <div class="border-bottom p-1">
                                <strong><?= $item['type'] ?></strong> 
                                (<?= $item['x'] ?>, <?= $item['y'] ?>)
                                <br>
                                <small>ID: <?= $item['id'] ?> | Extra: <?= htmlspecialchars($item['extra_data']) ?></small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <hr>
                        
                        <h6>Toolbox</h6>
                        <div class="toolbox">
                            <div class="row">
                                <div class="col-6">
                                    <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2" 
                                            data-tool="speler" title="Speler">
                                        <i class="fas fa-user"></i><br>
                                        <small>Speler</small>
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button type="button" class="btn btn-outline-success btn-sm w-100 mb-2" 
                                            data-tool="ballenmand" title="Ballenmand">
                                        <i class="fas fa-basketball-ball"></i><br>
                                        <small>Ballenmand</small>
                                    </button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <button type="button" class="btn btn-outline-warning btn-sm w-100 mb-2" 
                                            data-tool="pion" title="Pion">
                                        <i class="fas fa-circle"></i><br>
                                        <small>Pion</small>
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button type="button" class="btn btn-outline-info btn-sm w-100 mb-2" 
                                            data-tool="flapje" title="Flapje">
                                        <i class="fas fa-flag"></i><br>
                                        <small>Flapje</small>
                                    </button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button type="button" class="btn btn-outline-secondary btn-sm w-100 mb-2" 
                                            data-tool="lijn" title="Lijn">
                                        <i class="fas fa-minus"></i><br>
                                        <small>Lijn</small>
                                    </button>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-6">
                                    <button type="button" class="btn btn-outline-danger btn-sm w-100" 
                                            id="deleteBtn" title="Verwijder Selectie">
                                        <i class="fas fa-trash"></i><br>
                                        <small>Verwijder</small>
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button type="button" class="btn btn-outline-dark btn-sm w-100" 
                                            id="clearBtn" title="Alles Wissen">
                                        <i class="fas fa-eraser"></i><br>
                                        <small>Wissen</small>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success" id="saveBtn">
                                <i class="fas fa-save"></i> Opslaan
                            </button>
                            <button type="button" class="btn btn-info" id="reloadBtn">
                                <i class="fas fa-refresh"></i> Herladen
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Terug
                            </a>
                        </div>
                        
                        <hr>
                        
                        <div id="debugInfo">
                            <h6>Debug Info</h6>
                            <div id="itemCount">Items: 0</div>
                            <div id="lastAction">Geen actie</div>
                            <div id="consoleOutput" style="max-height: 150px; overflow-y: auto; font-size: 11px; background: #f8f9fa; padding: 5px; border: 1px solid #dee2e6;"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Debug Canvas</h5>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="zoomInBtn" title="Inzoomen">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="zoomOutBtn" title="Uitzoomen">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="resetZoomBtn" title="Zoom Reset">
                                <i class="fas fa-expand-arrows-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="canvas-container">
                            <canvas id="padelCanvas" width="800" height="600"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Les data voor JavaScript
        window.lesData = {
            id: <?= $lesId ?>,
            titel: <?= json_encode($lesData['titel']) ?>,
            items: <?= json_encode($lesItems) ?>
        };
        
        // Debug functie
        function updateDebugInfo() {
            document.getElementById('itemCount').textContent = 'Items: ' + window.editor.items.length;
        }
        
        // Console override voor debug output
        const originalConsoleLog = console.log;
        const originalConsoleError = console.error;
        const originalConsoleWarn = console.warn;
        
        function addToDebugOutput(message, type = 'log') {
            const output = document.getElementById('consoleOutput');
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'error' ? 'red' : type === 'warn' ? 'orange' : 'black';
            output.innerHTML += `<div style="color: ${color};">[${timestamp}] ${message}</div>`;
            output.scrollTop = output.scrollHeight;
        }
        
        console.log = function(...args) {
            originalConsoleLog.apply(console, args);
            addToDebugOutput(args.join(' '), 'log');
        };
        
        console.error = function(...args) {
            originalConsoleError.apply(console, args);
            addToDebugOutput(args.join(' '), 'error');
        };
        
        console.warn = function(...args) {
            originalConsoleWarn.apply(console, args);
            addToDebugOutput(args.join(' '), 'warn');
        };
        
        // Override saveItem voor debug
        const originalSaveItem = window.PadelEditor.prototype.saveItem;
        window.PadelEditor.prototype.saveItem = async function(item) {
            document.getElementById('lastAction').textContent = 'Opslaan: ' + item.type + ' op (' + item.x + ', ' + item.y + ')';
            await originalSaveItem.call(this, item);
            updateDebugInfo();
        };
    </script>
    <script src="assets/js/editor.js"></script>
    <script>
        // Override editor initialization
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== DEBUG EDITOR STARTED ===');
            console.log('lesData:', window.lesData);
            
            window.editor = new PadelEditor();
            updateDebugInfo();
            
            // Add reload button functionality
            document.getElementById('reloadBtn').addEventListener('click', function() {
                location.reload();
            });
        });
    </script>
</body>
</html> 