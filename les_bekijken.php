<?php
require_once 'includes/init.php';

require_once 'classes/Les.php';

$lesId = (int) ($_GET['id'] ?? 0);

if ($lesId <= 0) {
    redirect('index.php', 'Ongeldige les ID.', 'danger');
}

$les = new Les();
$lesData = $les->getById($lesId);

if (!$lesData) {
    redirect('index.php', 'Les niet gevonden.', 'danger');
}

// Les items ophalen
$lesItems = $les->getItems($lesId);

$page_title = 'Les Bekijken: ' . $lesData['titel'];

// Include header
include 'includes/header.php';
?>
        <div class="row">
            <!-- Les Informatie -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle"></i> Les Informatie
                        </h5>
                    </div>
                    <div class="card-body">
                        <h4><?= escapeHTML($lesData['titel']) ?></h4>
                        
                        <div class="mb-3">
                            <strong>Bedoeling:</strong>
                            <span class="badge bg-<?= getBedoelingColor($lesData['bedoeling']) ?>">
                                <?= escapeHTML($lesData['bedoeling']) ?>
                            </span>
                        </div>
                        
                        <?php if ($lesData['slag']): ?>
                        <div class="mb-3">
                            <strong>Slag:</strong> <?= escapeHTML($lesData['slag']) ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($lesData['niveaufactor']): ?>
                        <div class="mb-3">
                            <strong>Niveaufactor:</strong> <?= escapeHTML($lesData['niveaufactor']) ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($lesData['beschrijving']): ?>
                        <div class="mb-3">
                            <strong>Beschrijving:</strong>
                            <p class="text-muted"><?= nl2br(escapeHTML($lesData['beschrijving'])) ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <strong>Auteur:</strong> <?= escapeHTML($lesData['auteur_naam']) ?>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Datum:</strong> <?= date('d/m/Y H:i', strtotime($lesData['datum_aanmaak'])) ?>
                        </div>
                        
                        <hr>
                        
                        <div class="d-grid gap-2">
                            <?php if (canEditLessons() && ($les->isOwner($lesId, getCurrentUserId()) || isAdmin())): ?>
                            <a href="les_bewerken.php?id=<?= $lesId ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Bewerken
                            </a>
                            <?php endif; ?>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Terug naar Dashboard
                            </a>
                            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                                <i class="fas fa-print"></i> Printen
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Items Overzicht -->
                <!-- <?php if (!empty($lesItems)): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-list"></i> Items Overzicht
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php
                            $itemCounts = [];
                            foreach ($lesItems as $item) {
                                $itemCounts[$item['type']] = ($itemCounts[$item['type']] ?? 0) + 1;
                            }
                            ?>
                            <?php foreach ($itemCounts as $type => $count): ?>
                            <div class="col-6 mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-<?= getItemIcon($type) ?>"></i>
                                    <?= ucfirst($type) ?>: <?= $count ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?> -->
            </div>
            
            <!-- Visuele Weergave -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-eye"></i> Visuele Weergave
                        </h5>
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

<?php
// Les data voor JavaScript
echo "<script>
    window.lesData = {
        id: $lesId,
        titel: " . json_encode($lesData['titel']) . ",
        items: " . json_encode($lesItems) . "
    };
    console.log('lesData defined:', window.lesData);
</script>";

// Custom JavaScript voor deze pagina
$custom_js = ['assets/js/viewer.js'];

// Include footer
include 'includes/footer.php';

/**
 * Helper functie voor bedoeling kleuren
 */
function getBedoelingColor($bedoeling) {
    $colors = [
        'Scoren' => 'success',
        'Opbouwen' => 'primary',
        'Voorkomen van scoren' => 'warning',
        'Neutraal spelen' => 'secondary',
        'Uitlokken' => 'info'
    ];
    return $colors[$bedoeling] ?? 'secondary';
}

/**
 * Helper functie voor item iconen
 */
function getItemIcon($type) {
    $icons = [
        'speler' => 'user',
        'ballenmand' => 'basketball-ball',
        'pion' => 'circle',
        'flapje' => 'flag',
        'lijn' => 'minus'
    ];
    return $icons[$type] ?? 'circle';
}
?> 