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

// Controleer CSRF token
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Ongeldige CSRF token']);
    exit;
}

// Controleer of het een POST request is
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Alleen POST requests toegestaan']);
    exit;
}

// Haal les ID op
$lesId = (int) ($_POST['les_id'] ?? 0);

if ($lesId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ongeldige les ID']);
    exit;
}

require_once '../classes/Les.php';
$les = new Les();

// Controleer of les bestaat
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

// Haal form data op
$beschrijving = trim($_POST['beschrijving'] ?? '');

// Update les gegevens
try {
    $updateData = [
        'beschrijving' => $beschrijving
    ];
    
    $success = $les->update($lesId, $updateData);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Les beschrijving succesvol opgeslagen',
            'data' => $updateData
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Fout bij opslaan van les beschrijving']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database fout: ' . $e->getMessage()]);
}
?> 