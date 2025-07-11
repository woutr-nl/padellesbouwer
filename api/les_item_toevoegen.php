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
$item = $input['item'] ?? null;
$csrf_token = $input['csrf_token'] ?? '';

// CSRF validatie
if (!validateCSRFToken($csrf_token)) {
    sendErrorResponse('Ongeldige beveiligingstoken', 403);
}

if ($les_id <= 0 || !$item) {
    sendErrorResponse('Ongeldige parameters');
}

require_once '../classes/Les.php';
$les = new Les();

// Controleer of gebruiker eigenaar is van de les (of admin)
if (!isAdmin() && !$les->isOwner($les_id, getCurrentUserId())) {
    sendErrorResponse('Geen rechten om deze les te bewerken', 403);
}

// Valideer item data
$requiredFields = ['type', 'x', 'y'];
foreach ($requiredFields as $field) {
    if (!isset($item[$field])) {
        sendErrorResponse("Ontbrekend veld: $field");
    }
}

// Item toevoegen
$itemData = [
    'type' => $item['type'],
    'x' => (float) $item['x'],
    'y' => (float) $item['y'],
    'rotation' => (float) ($item['rotation'] ?? 0),
    'extra_data' => $item['extra_data'] ?? [],
    'z_index' => (int) ($item['z_index'] ?? 0)
];

$itemId = $les->addItem($les_id, $itemData);

if ($itemId) {
    sendSuccessResponse([
        'item_id' => $itemId,
        'message' => 'Item succesvol toegevoegd'
    ]);
} else {
    sendErrorResponse('Fout bij het toevoegen van het item');
}
?> 