<?php
require_once 'includes/init.php';

require_once 'classes/Les.php';

$lesId = (int) ($_GET['id'] ?? 1);
$les = new Les();
$lesData = $les->getById($lesId);

if (!$lesData) {
    die('Les niet gevonden');
}

// Les items ophalen
$lesItems = $les->getItems($lesId);

$page_title = 'Simple Editor';

// Include header
include 'includes/header.php';
?>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Simple Editor</h5>
                    </div>
                    <div class="card-body">
                        <h6><?= escapeHTML($lesData['titel']) ?></h6>
                        <p>Les ID: <?= $lesId ?></p>
                        <p>Items: <span id="itemCount">0</span></p>
                        
                        <hr>
                        
                        <h6>Tools</h6>
                        <div class="btn-group-vertical w-100">
                            <button type="button" class="btn btn-outline-primary mb-2" data-tool="speler">👤 Speler</button>
                            <button type="button" class="btn btn-outline-success mb-2" data-tool="ballenmand">⚽ Ballenmand</button>
                            <button type="button" class="btn btn-outline-warning mb-2" data-tool="pion">🔺 Pion</button>
                            <button type="button" class="btn btn-outline-danger mb-2" data-tool="flapje">🚩 Flapje</button>
                        </div>
                        
                        <hr>
                        
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success" id="saveBtn">💾 Opslaan</button>
                            <button type="button" class="btn btn-info" id="reloadBtn">🔄 Herladen</button>
                            <button type="button" class="btn btn-danger" id="clearBtn">🗑️ Wissen</button>
                        </div>
                        
                        <hr>
                        
                        <div id="status">Klaar</div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Canvas</h5>
                    </div>
                    <div class="card-body">
                        <div style="position: relative; display: inline-block;">
                            <canvas id="canvas" width="600" height="400"></canvas>
                            <div id="items" style="position: absolute; top: 0; left: 0; width: 600px; height: 400px; pointer-events: none;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
// Les data voor JavaScript
echo "<script>
    const lesData = {
        id: $lesId,
        titel: " . json_encode($lesData['titel']) . ",
        items: " . json_encode($lesItems) . "
    };
    
    let currentTool = null;
    let items = [];
    let nextId = 1;
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Simple Editor Started');
        console.log('lesData:', lesData);
        
        loadItems();
        setupEventListeners();
        drawCanvas();
        drawItems();
        updateItemCount();
    });
    
    function loadItems() {
        items = lesData.items.map(item => ({
            ...item,
            id: item.id || nextId++
        }));
        nextId = Math.max(...items.map(item => item.id), 0) + 1;
        console.log('Loaded items:', items);
        console.log('Items array length:', items.length);
    }
    
    function setupEventListeners() {
        // Tool selection
        document.querySelectorAll('[data-tool]').forEach(btn => {
            btn.addEventListener('click', function() {
                currentTool = this.dataset.tool;
                document.querySelectorAll('[data-tool]').forEach(b => b.classList.remove('btn-primary'));
                this.classList.add('btn-primary');
                console.log('Selected tool:', currentTool);
            });
        });
        
        // Canvas click
        document.getElementById('canvas').addEventListener('click', function(e) {
            if (!currentTool) {
                alert('Selecteer eerst een tool');
                return;
            }
            
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            addItem(x, y);
        });
        
        // Buttons
        document.getElementById('saveBtn').addEventListener('click', saveItems);
        document.getElementById('reloadBtn').addEventListener('click', () => location.reload());
        document.getElementById('clearBtn').addEventListener('click', clearItems);
    }
    
    function addItem(x, y) {
        const item = {
            id: nextId++,
            type: currentTool,
            x: x,
            y: y,
            rotation: 0,
            extra_data: {},
            z_index: items.length
        };
        
        items.push(item);
        console.log('Added item:', item);
        
        drawItems();
        updateItemCount();
        saveItem(item);
    }
    
    function drawCanvas() {
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        
        // Clear canvas
        ctx.fillStyle = '#90EE90';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // Draw court lines
        ctx.strokeStyle = '#fff';
        ctx.lineWidth = 2;
        ctx.strokeRect(50, 50, 500, 300);
        
        // Center line
        ctx.beginPath();
        ctx.moveTo(300, 50);
        ctx.lineTo(300, 350);
        ctx.stroke();
    }
    
    function drawItems() {
        console.log('Drawing items:', items.length, 'items');
        const container = document.getElementById('items');
        container.innerHTML = '';
        
        items.forEach((item, index) => {
            console.log(`Drawing item ${index}:`, item);
            const div = document.createElement('div');
            div.className = `item ${item.type}`;
            div.style.left = (item.x - 10) + 'px';
            div.style.top = (item.y - 10) + 'px';
            div.textContent = item.type.charAt(0).toUpperCase();
            div.title = `${item.type} (${item.x}, ${item.y})`;
            container.appendChild(div);
        });
    }
    
    function updateItemCount() {
        document.getElementById('itemCount').textContent = items.length;
    }
    
    async function saveItem(item) {
        try {
            document.getElementById('status').textContent = 'Opslaan...';
            
            const response = await fetch('api/les_item_toevoegen.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    les_id: lesData.id,
                    item: item,
                    csrf_token: document.querySelector('meta[name=\"csrf-token\"]').content
                })
            });
            
            const data = await response.json();
            console.log('Save response:', data);
            
            if (data.success) {
                document.getElementById('status').textContent = 'Opgeslagen: ' + item.type;
            } else {
                document.getElementById('status').textContent = 'Fout: ' + data.error;
            }
        } catch (error) {
            console.error('Save error:', error);
            document.getElementById('status').textContent = 'Fout: ' + error.message;
        }
    }
    
    async function saveItems() {
        try {
            document.getElementById('status').textContent = 'Alles opslaan...';
            
            const response = await fetch('api/les_opslaan.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    les_id: lesData.id,
                    items: items,
                    csrf_token: document.querySelector('meta[name=\"csrf-token\"]').content
                })
            });
            
            const data = await response.json();
            console.log('Save all response:', data);
            
            if (data.success) {
                document.getElementById('status').textContent = 'Alles opgeslagen!';
            } else {
                document.getElementById('status').textContent = 'Fout: ' + data.error;
            }
        } catch (error) {
            console.error('Save all error:', error);
            document.getElementById('status').textContent = 'Fout: ' + error.message;
        }
    }
    
    async function clearItems() {
        if (!confirm('Weet je zeker dat je alle items wilt wissen?')) {
            return;
        }
        
        try {
            document.getElementById('status').textContent = 'Wissen...';
            
            const response = await fetch('api/les_items_wissen.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    les_id: lesData.id,
                    csrf_token: document.querySelector('meta[name=\"csrf-token\"]').content
                })
            });
            
            const data = await response.json();
            console.log('Clear response:', data);
            
            if (data.success) {
                items = [];
                drawItems();
                updateItemCount();
                document.getElementById('status').textContent = 'Gewist!';
            } else {
                document.getElementById('status').textContent = 'Fout: ' + data.error;
            }
        } catch (error) {
            console.error('Clear error:', error);
            document.getElementById('status').textContent = 'Fout: ' + error.message;
        }
    }
</script>";

// Custom JavaScript voor deze pagina
$custom_js = [];

// Include footer
include 'includes/footer.php';
?> 