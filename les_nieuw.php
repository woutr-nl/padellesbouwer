<?php
require_once 'includes/init.php';

// Controleer of gebruiker lessen kan bewerken
if (!canEditLessons()) {
    redirect('index.php', 'Je hebt geen rechten om lessen aan te maken.', 'danger');
}

require_once 'classes/Les.php';
require_once 'classes/Club.php';

$error = '';
$success = '';
$formData = [
    'titel' => '',
    'bedoeling' => '',
    'slag' => '',
    'niveaufactor' => '',
    'beschrijving' => '',
    'club_id' => '',
    'is_openbaar' => true
];

// Clubs ophalen voor de huidige gebruiker
$club = new Club();
$userClubs = $club->getClubsByUser(getCurrentUserId());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'titel' => trim($_POST['titel'] ?? ''),
        'bedoeling' => $_POST['bedoeling'] ?? '',
        'slag' => trim($_POST['slag'] ?? ''),
        'niveaufactor' => $_POST['niveaufactor'] ?? '',
        'beschrijving' => trim($_POST['beschrijving'] ?? ''),
        'club_id' => $_POST['club_id'] ?? '',
        'is_openbaar' => isset($_POST['is_openbaar'])
    ];
    
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // CSRF validatie
    if (!validateCSRFToken($csrf_token)) {
        $error = 'Ongeldige beveiligingstoken. Probeer het opnieuw.';
    } elseif (empty($formData['titel']) || empty($formData['bedoeling'])) {
        $error = 'Vul alle verplichte velden in.';
    } else {
        $les = new Les();
        
        $lesData = [
            'titel' => $formData['titel'],
            'bedoeling' => $formData['bedoeling'],
            'slag' => $formData['slag'] ?: null,
            'niveaufactor' => $formData['niveaufactor'] ?: null,
            'beschrijving' => $formData['beschrijving'] ?: null,
            'auteur_id' => getCurrentUserId(),
            'club_id' => $formData['club_id'] ?: null,
            'is_openbaar' => $formData['is_openbaar']
        ];
        
        $lesId = $les->create($lesData);
        
        if ($lesId) {
            redirect("les_bewerken.php?id=$lesId", 'Les succesvol aangemaakt! Je kunt nu de visuele editor gebruiken.', 'success');
        } else {
            $error = 'Er is een fout opgetreden bij het aanmaken van de les.';
        }
    }
}

// Bedoeling opties
$bedoelingen = [
    'Scoren' => 'Scoren',
    'Opbouwen' => 'Opbouwen', 
    'Voorkomen van scoren' => 'Voorkomen van scoren',
    'Neutraal spelen' => 'Neutraal spelen',
    'Uitlokken' => 'Uitlokken'
];

// Niveaufactor opties
$niveaufactoren = [
    'Positionering' => 'Positionering',
    'Vastheid' => 'Vastheid',
    'Precisie' => 'Precisie',
    'Rotatie' => 'Rotatie',
    'Variatie' => 'Variatie',
    'Vaart' => 'Vaart',
    'Anticipatie' => 'Anticipatie',
    'Onder druk spelen' => 'Onder druk spelen',
    'Tempo' => 'Tempo',
    'Camouflage' => 'Camouflage'
];

$page_title = 'Nieuwe Les';

// Include header
include 'includes/header.php';
?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-plus"></i> Nieuwe Les Aanmaken
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?= escapeHTML($error) ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle"></i> <?= escapeHTML($success) ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="les_nieuw.php">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="titel" class="form-label">
                                            <i class="fas fa-heading"></i> Titel *
                                        </label>
                                        <input type="text" class="form-control" id="titel" name="titel" 
                                               value="<?= escapeHTML($formData['titel']) ?>" required>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="bedoeling" class="form-label">
                                                    <i class="fas fa-bullseye"></i> Bedoeling *
                                                </label>
                                                <select class="form-select" id="bedoeling" name="bedoeling" required>
                                                    <option value="">Selecteer bedoeling</option>
                                                    <?php foreach ($bedoelingen as $value => $label): ?>
                                                    <option value="<?= $value ?>" <?= $formData['bedoeling'] === $value ? 'selected' : '' ?>>
                                                        <?= escapeHTML($label) ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="niveaufactor" class="form-label">
                                                    <i class="fas fa-chart-line"></i> Niveaufactor
                                                </label>
                                                <select class="form-select" id="niveaufactor" name="niveaufactor">
                                                    <option value="">Selecteer niveaufactor</option>
                                                    <?php foreach ($niveaufactoren as $value => $label): ?>
                                                    <option value="<?= $value ?>" <?= $formData['niveaufactor'] === $value ? 'selected' : '' ?>>
                                                        <?= escapeHTML($label) ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="slag" class="form-label">
                                            <i class="fas fa-table-tennis"></i> Slag
                                        </label>
                                        <input type="text" class="form-control" id="slag" name="slag" 
                                               value="<?= escapeHTML($formData['slag']) ?>" 
                                               placeholder="bijv. Forehand, Backhand, Volley, etc.">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="beschrijving" class="form-label">
                                            <i class="fas fa-align-left"></i> Beschrijving
                                        </label>
                                        <textarea class="form-control" id="beschrijving" name="beschrijving" 
                                                  rows="4" placeholder="Beschrijf de les, doelstellingen, etc."><?= escapeHTML($formData['beschrijving']) ?></textarea>
                                    </div>
                                    
                                    <?php if (!empty($userClubs)): ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="club_id" class="form-label">
                                                    <i class="fas fa-users"></i> Club
                                                </label>
                                                <select class="form-select" id="club_id" name="club_id">
                                                    <option value="">Geen club (privé les)</option>
                                                    <?php foreach ($userClubs as $clubData): ?>
                                                    <option value="<?= $clubData['id'] ?>" <?= $formData['club_id'] == $clubData['id'] ? 'selected' : '' ?>>
                                                        <?= escapeHTML($clubData['naam']) ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="text-muted">Selecteer een club om de les beschikbaar te maken voor andere clubleden</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    <i class="fas fa-eye"></i> Zichtbaarheid
                                                </label>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="is_openbaar" name="is_openbaar" 
                                                           <?= $formData['is_openbaar'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="is_openbaar">
                                                        Les is openbaar voor clubleden
                                                    </label>
                                                </div>
                                                <small class="text-muted">Als uitgevinkt, is de les alleen zichtbaar voor jou</small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-info-circle"></i> Informatie
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="small text-muted">
                                                <strong>Bedoeling:</strong> Het hoofddoel van de les
                                            </p>
                                            <p class="small text-muted">
                                                <strong>Niveaufactor:</strong> Het aspect waarop wordt gefocust
                                            </p>
                                            <p class="small text-muted">
                                                <strong>Slag:</strong> De specifieke slag die wordt getraind
                                            </p>
                                            <p class="small text-muted">
                                                <strong>Beschrijving:</strong> Gedetailleerde uitleg van de les
                                            </p>
                                            <?php if (!empty($userClubs)): ?>
                                            <hr>
                                            <p class="small text-muted">
                                                <strong>Club:</strong> Koppel de les aan een club voor gedeelde toegang
                                            </p>
                                            <p class="small text-muted">
                                                <strong>Zichtbaarheid:</strong> Bepaal of de les openbaar is voor clubleden
                                            </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Terug naar Dashboard
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Les Aanmaken en Editor Openen
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
// Include footer
include 'includes/footer.php';
?> 