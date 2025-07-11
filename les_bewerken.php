<?php
require_once 'includes/init.php';

// Controleer of gebruiker lessen kan bewerken
if (!canEditLessons()) {
    redirect('index.php', 'Je hebt geen rechten om lessen te bewerken.', 'danger');
}

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

// Controleer of gebruiker eigenaar is van de les (of admin)
if (!isAdmin() && !$les->isOwner($lesId, getCurrentUserId())) {
    redirect('index.php', 'Je hebt geen rechten om deze les te bewerken.', 'danger');
}

// Les items ophalen
$lesItems = $les->getItems($lesId);

$page_title = 'Les Bewerken: ' . $lesData['titel'];

// Include header
include 'includes/header.php';
?>
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-edit"></i> Les Bewerken
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6><?= escapeHTML($lesData['titel']) ?></h6>
                        <p class="text-muted small">
                            <strong>Bedoeling:</strong> <?= escapeHTML($lesData['bedoeling']) ?><br>
                            <?php if ($lesData['slag']): ?>
                            <strong>Slag:</strong> <?= escapeHTML($lesData['slag']) ?><br>
                            <?php endif; ?>
                            <?php if ($lesData['niveaufactor']): ?>
                            <strong>Niveaufactor:</strong> <?= escapeHTML($lesData['niveaufactor']) ?><br>
                            <?php endif; ?>
                        </p>
                        
                        <hr>
                        
                        <h6>Toolbox</h6>
                        <div class="toolbox">
                            <div class="mb-2 d-flex flex-wrap gap-1">
                                <button type="button" class="preset-color-btn btn btn-sm" style="background:#4ecdc4; border:1px solid #ccc; width:28px; height:28px;" data-color="#4ecdc4"></button>
                                <button type="button" class="preset-color-btn btn btn-sm" style="background:#ff6b6b; border:1px solid #ccc; width:28px; height:28px;" data-color="#ff6b6b"></button>
                                <button type="button" class="preset-color-btn btn btn-sm" style="background:#ffa500; border:1px solid #ccc; width:28px; height:28px;" data-color="#ffa500"></button>
                                <button type="button" class="preset-color-btn btn btn-sm" style="background:#ffe066; border:1px solid #ccc; width:28px; height:28px;" data-color="#ffe066"></button>
                                <button type="button" class="preset-color-btn btn btn-sm" style="background:#5f27cd; border:1px solid #ccc; width:28px; height:28px;" data-color="#5f27cd"></button>
                                <button type="button" class="preset-color-btn btn btn-sm" style="background:#222f3e; border:1px solid #ccc; width:28px; height:28px;" data-color="#222f3e"></button>
                            </div>
                            <div class="mb-3">
                                <label for="colorPicker" class="form-label">Kleur:</label>
                                <input type="color" id="colorPicker" class="form-control form-control-color" value="#4ecdc4" title="Kies een kleur">
                            </div>
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
                            <div class="row mb-2">
  <div class="d-flex gap-1">
    <button type="button" class="btn btn-outline-warning btn-sm flex-fill" data-tool="pion" title="Pion">
      <i class="fas fa-circle"></i><br><small>Pion</small>
    </button>
    <button type="button" class="btn btn-outline-info btn-sm flex-fill" data-tool="flapje" title="Flapje">
      <i class="fas fa-square-full"></i><br><small>Flapje</small>
    </button>
    <button type="button" class="btn btn-outline-primary btn-sm flex-fill" data-tool="hoekflapje" title="Hoek Flapje">
      <i class="fas fa-object-group"></i><br><small>Hoek</small>
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
                                <div class="col-12">
                                    <button type="button" class="btn btn-outline-secondary btn-sm w-100 mb-2" 
                                            data-tool="curved" title="Gebogen Lijn">
                                        <i class="fas fa-wave-square"></i><br>
                                        <small>Curved</small>
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
                        
                        <!-- Zichtbaarheid Instellingen -->
                        <h6>Zichtbaarheid</h6>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="visibility" id="visibility_private" value="private" 
                                       <?= (!$lesData['is_openbaar']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="visibility_private">
                                    <i class="fas fa-lock"></i> Privé (alleen voor jou zichtbaar)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="visibility" id="visibility_public" value="public" 
                                       <?= ($lesData['is_openbaar']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="visibility_public">
                                    <i class="fas fa-globe"></i> Openbaar (voor iedereen zichtbaar)
                                </label>
                            </div>
                        </div>
                        
                        <!-- Club Zichtbaarheid -->
                        <?php
                        $user = new User();
                        $userClubs = $user->getClubs(getCurrentUserId());
                        ?>
                        <?php if (!empty($userClubs)): ?>
                        <div class="mb-3">
                            <label for="club_id" class="form-label">Zichtbaar bij club:</label>
                            <select class="form-select" id="club_id" name="club_id">
                                <option value="">Geen club</option>
                                <?php foreach ($userClubs as $club): ?>
                                <option value="<?= $club['id'] ?>" <?= ($lesData['club_id'] == $club['id']) ? 'selected' : '' ?>>
                                    <?= escapeHTML($club['naam']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Kies een club om de les zichtbaar te maken voor andere leden van die club.
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success" id="editorSaveBtn">
                                <i class="fas fa-save"></i> Les opslaan
                            </button>
                            <a href="les_bekijken.php?id=<?= $lesId ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Bekijken
                            </a>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Terug
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Canvas Area -->
            <div class="col-md-9">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-paint-brush"></i> Visuele Editor
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
                            <button type="button" class="btn btn-outline-info btn-sm ms-2" id="legendBtn" title="Toelichting & Legenda">
                                <i class="fas fa-question-circle"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="canvas-container">
                            <canvas id="padelCanvas" width="800" height="600"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Beschrijving Sectie -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-edit"></i> Uitgebreide Beschrijving
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="descriptionForm">
                            <div class="mb-3">
                                <label for="beschrijving" class="form-label">Beschrijving</label>
                                <textarea class="form-control" id="beschrijving" name="beschrijving" rows="6" placeholder="Voeg hier een uitgebreide beschrijving van de les toe..."><?= escapeHTML($lesData['beschrijving'] ?? '') ?></textarea>
                                <div class="form-text">Voeg hier extra details, instructies of notities toe voor de les.</div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Beschrijving Opslaan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Success Modal -->
    <div class="modal fade" id="saveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Opgeslagen!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Je les is succesvol opgeslagen.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Legenda Modal -->
    <div class="modal fade" id="legendModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-question-circle"></i> Legenda & Uitleg</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <h6>Tools</h6>
            <div class="row mb-3">
              <div class="col-4 text-center"><i class="fas fa-user fa-lg"></i><br><small>Speler</small></div>
              <div class="col-4 text-center"><i class="fas fa-basketball-ball fa-lg"></i><br><small>Ballenmand</small></div>
              <div class="col-4 text-center"><i class="fas fa-circle fa-lg"></i><br><small>Pion</small></div>
              <div class="col-4 text-center"><i class="fas fa-square-full fa-lg"></i><br><small>Flapje</small></div>
              <div class="col-4 text-center"><i class="fas fa-object-group fa-lg"></i><br><small>Hoekflapje</small></div>
              <div class="col-4 text-center"><i class="fas fa-flag fa-lg"></i><br><small>Flapje (oud)</small></div>
              <div class="col-4 text-center"><i class="fas fa-minus fa-lg"></i><br><small>Lijn</small></div>
              <div class="col-4 text-center"><i class="fas fa-wave-square fa-lg"></i><br><small>Gebogen lijn</small></div>
            </div>
            <h6>Werking</h6>
            <ul>
              <li><b>Item toevoegen:</b> Kies een tool en klik op het veld</li>
              <li><b>Item selecteren:</b> Klik op een bestaand item</li>
              <li><b>Item verwijderen:</b> Selecteer een item en druk op <kbd>Delete</kbd> of gebruik het prullenbak-icoon</li>
              <li><b>Kleur wijzigen:</b> Rechtermuisklik op een item &rarr; kies een kleur</li>
              <li><b>Dupliceren:</b> Rechtermuisklik op een item &rarr; kies "Dupliceren"</li>
              <li><b>Deselecteren:</b> Rechtermuisklik op een lege plek</li>
            </ul>
            <h6>Sneltoetsen</h6>
            <ul>
              <li><kbd>Delete</kbd>: geselecteerd item verwijderen</li>
              <li><kbd>Ctrl</kbd> + muiswiel: in-/uitzoomen</li>
            </ul>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Sluiten</button>
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
$custom_js = ['assets/js/editor.js'];

// Include footer
include 'includes/footer.php';
?> 