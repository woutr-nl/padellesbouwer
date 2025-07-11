<?php
require_once 'includes/init.php';

// Redirect als al ingelogd
if (isLoggedIn()) {
    redirect('index.php');
}

require_once 'classes/User.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // CSRF validatie
    if (!validateCSRFToken($csrf_token)) {
        $error = 'Ongeldige beveiligingstoken. Probeer het opnieuw.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Vul alle velden in.';
    } else {
        $user = new User();
        
        if ($user->login($email, $password)) {
            redirect('index.php', 'Succesvol ingelogd!', 'success');
        } else {
            $error = 'Ongeldige email of wachtwoord.';
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
    <title>Inloggen - Padelles Beheersysteem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-table-tennis fa-3x text-primary mb-3"></i>
                            <h2 class="card-title">Padelles Beheersysteem</h2>
                            <p class="text-muted">Log in om je lessen te beheren</p>
                        </div>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?= escapeHTML($error) ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="login.php">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i> Email
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= escapeHTML($email) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i> Wachtwoord
                                </label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt"></i> Inloggen
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-0">Nog geen account?</p>
                            <a href="register.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus"></i> Registreren
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