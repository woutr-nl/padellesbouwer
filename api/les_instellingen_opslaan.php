<?php
require_once '../includes/init.php';

// Controleer of gebruiker is ingelogd
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Niet ingelogd']);
    exit;
}

// Controleer of gebruiker lessen kan bewerken
if (!canEditLessons()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Geen rechten om lessen te bewerken']);
    exit;
}

// Alleen POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Alleen POST requests toegestaan']);
    exit;
}

// CSRF token controleren
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Ongeldige CSRF token']);
    exit;
}

// Data ophalen
$lesId = (int) ($_POST['les_id'] ?? 0);
$visibility = $_POST['visibility'] ?? 'public';
$clubId = $_POST['club_id'] ?? null;

if ($lesId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ongeldige les ID']);
    exit;
}

// Les ophalen en controleren
require_once '../classes/Les.php';
$les = new Les();
$lesData = $les->getById($lesId);

if (!$lesData) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Les niet gevonden']);
    exit;
}

// Controleer of gebruiker eigenaar is van de les (of admin)
if (!isAdmin() && !$les->isOwner($lesId, getCurrentUserId())) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Geen rechten om deze les te bewerken']);
    exit;
}

// Club validatie
if ($clubId) {
    $clubId = (int) $clubId;
    
    // Controleer of gebruiker lid is van de club
    require_once '../classes/User.php';
    $user = new User();
    
    if (!$user->isMemberOfClub(getCurrentUserId(), $clubId)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Je bent geen lid van deze club']);
        exit;
    }
}

// Data voorbereiden voor update
$updateData = [
    'is_openbaar' => ($visibility === 'public') ? 1 : 0,
    'club_id' => $clubId ?: null
];

// Les bijwerken
if ($les->update($lesId, $updateData)) {
    echo json_encode([
        'success' => true, 
        'message' => 'Les instellingen succesvol opgeslagen'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Fout bij opslaan van les instellingen'
    ]);
}
?> 