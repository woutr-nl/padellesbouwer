<?php
require_once 'includes/init.php';

// Redirect als al ingelogd
if (isLoggedIn()) {
    redirect('index.php');
}

require_once 'classes/User.php';

$error = '';
$success = '';
$formData = [
    'naam' => '',
    'email' => '',
    'password' => '',
    'password_confirm' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'naam' => trim($_POST['naam'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? ''
    ];
    
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // CSRF validatie
    if (!validateCSRFToken($csrf_token)) {
        $error = 'Ongeldige beveiligingstoken. Probeer het opnieuw.';
    } elseif (empty($formData['naam']) || empty($formData['email']) || empty($formData['password'])) {
        $error = 'Vul alle verplichte velden in.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Voer een geldig email adres in.';
    } elseif (strlen($formData['password']) < 6) {
        $error = 'Wachtwoord moet minimaal 6 karakters lang zijn.';
    } elseif ($formData['password'] !== $formData['password_confirm']) {
        $error = 'Wachtwoorden komen niet overeen.';
    } else {
        $user = new User();
        
        if ($user->register($formData['naam'], $formData['email'], $formData['password'])) {
            $success = 'Account succesvol aangemaakt! Je kunt nu inloggen.';
            $formData = ['naam' => '', 'email' => '', 'password' => '', 'password_confirm' => ''];
        } else {
            $error = 'Email adres is al in gebruik.';
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registreren - Padelles Beheersysteem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-table-tennis fa-3x text-primary mb-3"></i>
                            <h2 class="card-title">Account Aanmaken</h2>
                            <p class="text-muted">Registreer je voor het Padelles Beheersysteem</p>
                        </div>
                        
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
                        
                        <form method="POST" action="register.php">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            
                            <div class="mb-3">
                                <label for="naam" class="form-label">
                                    <i class="fas fa-user"></i> Naam *
                                </label>
                                <input type="text" class="form-control" id="naam" name="naam" 
                                       value="<?= escapeHTML($formData['naam']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i> Email *
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= escapeHTML($formData['email']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i> Wachtwoord *
                                </label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       value="<?= escapeHTML($formData['password']) ?>" required>
                                <div class="form-text">Minimaal 6 karakters</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">
                                    <i class="fas fa-lock"></i> Wachtwoord Bevestigen *
                                </label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" 
                                       value="<?= escapeHTML($formData['password_confirm']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terms" required>
                                    <label class="form-check-label" for="terms">
                                        Ik ga akkoord met de <a href="#" class="text-decoration-none">gebruiksvoorwaarden</a>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus"></i> Account Aanmaken
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-0">Heb je al een account?</p>
                            <a href="login.php" class="btn btn-outline-primary">
                                <i class="fas fa-sign-in-alt"></i> Inloggen
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <small class="text-muted">
                        &copy; <?= date('Y') ?> Padelles Beheersysteem. Alle rechten voorbehouden.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 