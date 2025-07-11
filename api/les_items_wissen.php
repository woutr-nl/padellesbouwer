<?php
require_once '../includes/init.php';

// Alleen POST requests toestaan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Alleen POST requests toegestaan', 405);
}

// Controleer of gebruiker is ingelogd
if (!isLoggedIn()) {
    sendErrorResponse('Niet ingelogd', 401);
}

// Controleer of gebruiker lessen kan bewerken
if (!canEditLessons()) {
    sendErrorResponse('Geen rechten om lessen te bewerken', 403);
}

// JSON input lezen
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendErrorResponse('Ongeldige JSON input');
}

$les_id = (int) ($input['les_id'] ?? 0);
$csrf_token = $input['csrf_token'] ?? '';

// CSRF validatie
if (!validateCSRFToken($csrf_token)) {
    sendErrorResponse('Ongeldige beveiligingstoken', 403);
}

if ($les_id <= 0) {
    sendErrorResponse('Ongeldige les ID');
}

require_once '../classes/Les.php';
$les = new Les();

// Controleer of gebruiker eigenaar is van de les (of admin)
if (!isAdmin() && !$les->isOwner($les_id, getCurrentUserId())) {
    sendErrorResponse('Geen rechten om deze les te bewerken', 403);
}

// Alle items verwijderen
if ($les->deleteAllItems($les_id)) {
    sendSuccessResponse(['message' => 'Alle items succesvol gewist']);
} else {
    sendErrorResponse('Fout bij het wissen van de items');
}
?> 