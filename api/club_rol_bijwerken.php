<?php
require_once '../includes/init.php';

// Controleer of gebruiker is ingelogd en admin is
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Geen toegang']);
    exit;
}

require_once '../classes/Club.php';

$club = new Club();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clubId = (int)$_POST['club_id'] ?? 0;
    $userId = (int)$_POST['user_id'] ?? 0;
    $role = $_POST['role'] ?? '';
    
    // Valideer input
    if (!$clubId || !$userId) {
        $response['message'] = 'Ongeldige club of gebruiker ID';
    } elseif (!in_array($role, ['viewer', 'trainer', 'eigenaar'])) {
        $response['message'] = 'Ongeldige rol';
    } else {
        // Controleer of gebruiker lid is van de club
        if (!$club->isUserMember($clubId, $userId)) {
            $response['message'] = 'Gebruiker is geen lid van deze club';
        } else {
            // Update rol
            if ($club->updateUserRole($clubId, $userId, $role)) {
                $response['success'] = true;
                $response['message'] = 'Rol succesvol bijgewerkt';
            } else {
                $response['message'] = 'Fout bij bijwerken van rol';
            }
        }
    }
} else {
    $response['message'] = 'Ongeldige request methode';
}

header('Content-Type: application/json');
echo json_encode($response); 