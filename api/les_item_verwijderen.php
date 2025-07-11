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

$item_id = (int) ($input['item_id'] ?? 0);
$csrf_token = $input['csrf_token'] ?? '';

// CSRF validatie
if (!validateCSRFToken($csrf_token)) {
    sendErrorResponse('Ongeldige beveiligingstoken', 403);
}

if ($item_id <= 0) {
    sendErrorResponse('Ongeldige item ID');
}

require_once '../classes/Les.php';
$les = new Les();

// Item verwijderen
if ($les->deleteItem($item_id)) {
    sendSuccessResponse(['message' => 'Item succesvol verwijderd']);
} else {
    sendErrorResponse('Fout bij het verwijderen van het item');
}
?> 