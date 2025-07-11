<?php
require_once 'includes/init.php';

// Classes laden
require_once 'classes/User.php';
require_once 'classes/Les.php';

$user = new User();
$les = new Les();

// Huidige gebruiker ophalen
$currentUser = $user->getById(getCurrentUserId());

// Lessen ophalen
if (isAdmin()) {
    $lessen = $les->getAll();
} else {
    $lessen = $les->getByUserWithClubs(getCurrentUserId());
}

$message = getMessage();
$page_title = 'Dashboard';

// Include header
include 'includes/header.php';
?>

        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h1 class="card-title">
                            <i class="fas fa-home"></i> Welkom, <?= escapeHTML($_SESSION['user_name']) ?>!
                        </h1>
                        <p class="card-text">
                            Beheer je padel-lessen en maak gebruik van de visuele editor om je trainingen te visualiseren.
                        </p>
                        <?php if (canEditLessons()): ?>
                        <a href="les_nieuw.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nieuwe Les Aanmaken
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-clipboard-list fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Totaal Lessen</h5>
                        <h2 class="text-primary"><?= count($lessen) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-calendar-alt fa-3x text-info mb-3"></i>
                        <h5 class="card-title">Laatste Login</h5>
                        <h2 class="text-info"><?= date('d/m') ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Lessons -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i> 
                            <?= isAdmin() ? 'Alle Lessen' : 'Mijn Lessen' ?>
                        </h5>
                        <div class="input-group" style="max-width: 300px;">
                            <input type="text" class="form-control" id="searchInput" placeholder="Zoek lessen...">
                            <button class="btn btn-outline-secondary" type="button" id="searchBtn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($lessen)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Geen lessen gevonden</h5>
                            <?php if (canEditLessons()): ?>
                            <p class="text-muted">Maak je eerste les aan om te beginnen!</p>
                            <a href="les_nieuw.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Eerste Les Aanmaken
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Titel</th>
                                        <th>Bedoeling</th>
                                        <th>Slag</th>
                                        <th>Niveaufactor</th>
                                        <?php if (isAdmin()): ?>
                                        <th>Auteur</th>
                                        <?php endif; ?>
                                        <th>Datum</th>
                                        <th>Acties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lessen as $les_item): ?>
                                    <tr>
                                        <td>
                                            <strong><?= escapeHTML($les_item['titel']) ?></strong>
                                            <?php if (isset($les_item['club_naam']) && $les_item['club_naam']): ?>
                                                <br><small class="text-muted">
                                                    <i class="fas fa-users"></i> <?= escapeHTML($les_item['club_naam']) ?>
                                                    <?php if (isset($les_item['is_openbaar']) && !$les_item['is_openbaar']): ?>
                                                        <span class="badge bg-warning">Privé</span>
                                                    <?php endif; ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= getBedoelingColor($les_item['bedoeling']) ?>">
                                                <?= escapeHTML($les_item['bedoeling']) ?>
                                            </span>
                                        </td>
                                        <td><?= escapeHTML($les_item['slag'] ?: '-') ?></td>
                                        <td><?= escapeHTML($les_item['niveaufactor'] ?: '-') ?></td>
                                        <?php if (isAdmin()): ?>
                                        <td><?= escapeHTML($les_item['auteur_naam']) ?></td>
                                        <?php endif; ?>
                                        <td><?= date('d/m/Y', strtotime($les_item['datum_aanmaak'])) ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="les_bekijken.php?id=<?= $les_item['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="Bekijken">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if (canEditLessons() && ($les->isOwner($les_item['id'], getCurrentUserId()) || isAdmin())): ?>
                                                <a href="les_bewerken.php?id=<?= $les_item['id'] ?>" 
                                                   class="btn btn-sm btn-outline-warning" title="Bewerken">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteLesson(<?= $les_item['id'] ?>)" title="Verwijderen">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
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
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Les Verwijderen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Weet je zeker dat je deze les wilt verwijderen? Deze actie kan niet ongedaan worden gemaakt.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Verwijderen</button>
                </div>
            </div>
        </div>
    </div>

<?php
// Custom JavaScript voor deze pagina
$custom_js = ['assets/js/dashboard.js'];

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
?> 