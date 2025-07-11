<?php
/**
 * Hoofdbestand voor initialisatie
 * Bevat sessiebeheer, database connectie en beveiligingsfuncties
 */

// Start sessie
session_start();

// Database configuratie laden
require_once __DIR__ . '/../config/database.php';

// Database initialiseren
initializeDatabase();

// Autoloader voor classes
spl_autoload_register(function ($class) {
    $classFile = __DIR__ . '/../classes/' . $class . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

/**
 * CSRF token genereren
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF token valideren
 * @param string $token
 * @return bool
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Gebruiker is ingelogd
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Gebruiker heeft admin rechten
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

/**
 * Gebruiker kan lessen bewerken (admin of trainer)
 * @return bool
 */
function canEditLessons() {
    return isLoggedIn() && in_array($_SESSION['user_role'], ['admin', 'trainer']);
}

/**
 * Huidige gebruiker ID
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Huidige gebruiker rol
 * @return string|null
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * XSS bescherming
 * @param string $data
 * @return string
 */
function escapeHTML($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect met bericht
 * @param string $url
 * @param string $message
 * @param string $type
 */
function redirect($url, $message = '', $type = 'info') {
    if ($message) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
    header("Location: $url");
    exit;
}

/**
 * Bericht ophalen en wissen
 * @return array|null
 */
function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = [
            'text' => $_SESSION['message'],
            'type' => $_SESSION['message_type'] ?? 'info'
        ];
        unset($_SESSION['message'], $_SESSION['message_type']);
        return $message;
    }
    return null;
}

/**
 * Wachtwoord hashen
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Wachtwoord verifiëren
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * JSON response sturen
 * @param array $data
 * @param int $statusCode
 */
function sendJSONResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Error response sturen
 * @param string $message
 * @param int $statusCode
 */
function sendErrorResponse($message, $statusCode = 400) {
    sendJSONResponse(['error' => $message], $statusCode);
}

/**
 * Success response sturen
 * @param array $data
 */
function sendSuccessResponse($data = []) {
    sendJSONResponse(['success' => true, 'data' => $data]);
} 