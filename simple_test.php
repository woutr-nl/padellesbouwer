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

// Test: Voeg een test item toe
if (isset($_GET['add_test'])) {
    $testItem = [
        'type' => 'speler',
        'x' => 100.5,
        'y' => 200.3,
        'rotation' => 0,
        'extra_data' => ['test' => true],
        'z_index' => 0
    ];
    
    $itemId = $les->addItem($lesId, $testItem);
    if ($itemId) {
        echo "Test item toegevoegd met ID: $itemId<br>";
    } else {
        echo "Fout bij toevoegen test item<br>";
    }
}

// Test: Verwijder alle items
if (isset($_GET['clear'])) {
    $les->deleteAllItems($lesId);
    echo "Alle items verwijderd<br>";
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
    <title>Simple Test - Padelles Beheersysteem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Simple Test</h1>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Database Test</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Les ID:</strong> <?= $lesId ?></p>
                        <p><strong>Les Titel:</strong> <?= escapeHTML($lesData['titel']) ?></p>
                        <p><strong>Aantal items in database:</strong> <?= count($lesItems) ?></p>
                        
                        <hr>
                        
                        <h6>Database Items:</h6>
                        <?php if (empty($lesItems)): ?>
                            <p class="text-muted">Geen items gevonden</p>
                        <?php else: ?>
                            <ul>
                            <?php foreach ($lesItems as $item): ?>
                                <li>
                                    <strong><?= $item['type'] ?></strong> 
                                    op positie (<?= $item['x'] ?>, <?= $item['y'] ?>)
                                    <br>
                                    <small>ID: <?= $item['id'] ?> | Extra: <?= htmlspecialchars($item['extra_data']) ?></small>
                                </li>
                            <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <div class="btn-group">
                            <a href="?id=<?= $lesId ?>&add_test=1" class="btn btn-success">Test Item Toevoegen</a>
                            <a href="?id=<?= $lesId ?>&clear=1" class="btn btn-danger">Alles Wissen</a>
                            <a href="?id=<?= $lesId ?>" class="btn btn-primary">Herladen</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>JavaScript Test</h5>
                    </div>
                    <div class="card-body">
                        <div id="jsOutput">JavaScript output verschijnt hier...</div>
                        <hr>
                        <button type="button" class="btn btn-info" onclick="testJavaScript()">Test JavaScript</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>API Test</h5>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-warning" onclick="testAPI()">Test API Call</button>
                        <div id="apiOutput" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Les data voor JavaScript
        const lesData = {
            id: <?= $lesId ?>,
            titel: <?= json_encode($lesData['titel']) ?>,
            items: <?= json_encode($lesItems) ?>
        };
        
        function testJavaScript() {
            const output = document.getElementById('jsOutput');
            output.innerHTML = `
                <h6>JavaScript Test Resultaten:</h6>
                <p><strong>lesData:</strong> ${JSON.stringify(lesData, null, 2)}</p>
                <p><strong>Aantal items:</strong> ${lesData.items.length}</p>
                <p><strong>Items:</strong></p>
                <ul>
                    ${lesData.items.map(item => `
                        <li>${item.type} op (${item.x}, ${item.y}) - ID: ${item.id}</li>
                    `).join('')}
                </ul>
            `;
        }
        
        async function testAPI() {
            const output = document.getElementById('apiOutput');
            output.innerHTML = 'Testing API...';
            
            try {
                const response = await fetch('api/les_item_toevoegen.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        les_id: lesData.id,
                        item: {
                            type: 'speler',
                            x: 150.0,
                            y: 250.0,
                            rotation: 0,
                            extra_data: {},
                            z_index: 0
                        },
                        csrf_token: document.querySelector('meta[name="csrf-token"]').content
                    })
                });
                
                const data = await response.json();
                output.innerHTML = `
                    <h6>API Test Resultaten:</h6>
                    <p><strong>Status:</strong> ${response.status}</p>
                    <p><strong>Response:</strong> ${JSON.stringify(data, null, 2)}</p>
                `;
            } catch (error) {
                output.innerHTML = `
                    <h6>API Test Fout:</h6>
                    <p><strong>Error:</strong> ${error.message}</p>
                `;
            }
        }
        
        // Auto-run JavaScript test
        document.addEventListener('DOMContentLoaded', function() {
            testJavaScript();
        });
    </script>
</body>
</html> 