<?php
require_once '../includes/init.php';

// Controleer of gebruiker is ingelogd en admin is
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Geen toegang']);
    exit;
}

require_once '../classes/Club.php';
require_once '../classes/User.php';

$club = new Club();
$user = new User();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clubId = (int)$_POST['club_id'] ?? 0;
    $userId = (int)$_POST['user_id'] ?? 0;
    $role = $_POST['role'] ?? 'viewer';
    
    // Valideer input
    if (!$clubId || !$userId) {
        $response['message'] = 'Ongeldige club of gebruiker ID';
    } elseif (!in_array($role, ['viewer', 'trainer', 'eigenaar'])) {
        $response['message'] = 'Ongeldige rol';
    } else {
        // Controleer of club en gebruiker bestaan
        $clubData = $club->getById($clubId);
        $userData = $user->getById($userId);
        
        if (!$clubData || !$userData) {
            $response['message'] = 'Club of gebruiker niet gevonden';
        } else {
            // Controleer of gebruiker al lid is
            if ($club->isUserMember($clubId, $userId)) {
                $response['message'] = 'Gebruiker is al lid van deze club';
            } else {
                // Voeg gebruiker toe aan club
                if ($club->addUser($clubId, $userId, $role)) {
                    $response['success'] = true;
                    $response['message'] = 'Gebruiker succesvol toegevoegd aan club';
                } else {
                    $response['message'] = 'Fout bij toevoegen van gebruiker aan club';
                }
            }
        }
    }
} else {
    $response['message'] = 'Ongeldige request methode';
}

header('Content-Type: application/json');
echo json_encode($response); 