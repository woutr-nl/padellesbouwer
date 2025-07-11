<?php
require_once 'includes/init.php';

$user = new User();
$club = new Club();

$currentUser = $user->getById($_SESSION['user_id']);
$userClubs = $user->getClubs($_SESSION['user_id']);

$message = '';
$error = '';

// Verwerk formulier voor profiel update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $updateData = [
            'naam' => trim($_POST['naam']),
            'email' => trim($_POST['email'])
        ];
        
        if ($user->update($_SESSION['user_id'], $updateData)) {
            $_SESSION['user_name'] = $updateData['naam'];
            $_SESSION['user_email'] = $updateData['email'];
            $message = 'Profiel succesvol bijgewerkt!';
            $currentUser = $user->getById($_SESSION['user_id']); // Refresh data
        } else {
            $error = 'Fout bij bijwerken van profiel.';
        }
    } elseif ($_POST['action'] === 'change_password') {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Controleer of huidige wachtwoord correct is
        if (!$user->login($_SESSION['user_email'], $currentPassword)) {
            $error = 'Huidige wachtwoord is incorrect.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Nieuwe wachtwoorden komen niet overeen.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Nieuw wachtwoord moet minimaal 6 karakters lang zijn.';
        } else {
            if ($user->changePassword($_SESSION['user_id'], $newPassword)) {
                $message = 'Wachtwoord succesvol gewijzigd!';
            } else {
                $error = 'Fout bij wijzigen van wachtwoord.';
            }
        }
    }
}

$page_title = 'Mijn Profiel';

// Include header
include 'includes/header.php';
?>
        <div class="row">
            <div class="col-md-8">
                <h1><i class="fas fa-user-circle"></i> Mijn Profiel</h1>
                
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Profiel Informatie -->
                <div class="profile-section">
                    <h3><i class="fas fa-info-circle"></i> Profiel Informatie</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="naam" class="form-label">Naam</label>
                                    <input type="text" class="form-control" id="naam" name="naam" 
                                           value="<?php echo htmlspecialchars($currentUser['naam']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">E-mail</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Rol</label>
                                    <input type="text" class="form-control" value="<?php echo ucfirst($currentUser['rol']); ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Lid sinds</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo date('d-m-Y', strtotime($currentUser['datum_aanmaak'])); ?>" readonly>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Profiel Bijwerken
                        </button>
                    </form>
                </div>

                <!-- Wachtwoord Wijzigen -->
                <div class="profile-section">
                    <h3><i class="fas fa-lock"></i> Wachtwoord Wijzigen</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Huidige Wachtwoord</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nieuw Wachtwoord</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Bevestig Nieuw Wachtwoord</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key"></i> Wachtwoord Wijzigen
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Club Lidmaatschappen -->
                <div class="profile-section">
                    <h3><i class="fas fa-users"></i> Mijn Clubs</h3>
                    
                    <?php if (empty($userClubs)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Je bent nog niet lid van een club.
                        </div>
                    <?php else: ?>
                        <?php foreach ($userClubs as $clubData): ?>
                            <div class="club-card">
                                <h5><?php echo htmlspecialchars($clubData['naam']); ?></h5>
                                <p class="text-muted small mb-2">
                                    <?php echo htmlspecialchars($clubData['adres']); ?>
                                </p>
                                <span class="badge bg-<?php echo $clubData['user_rol'] === 'eigenaar' ? 'danger' : ($clubData['user_rol'] === 'trainer' ? 'primary' : 'secondary'); ?> role-badge">
                                    <?php echo ucfirst($clubData['user_rol']); ?>
                                </span>
                                <?php if ($clubData['telefoon']): ?>
                                    <br><small class="text-muted">
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($clubData['telefoon']); ?>
                                    </small>
                                <?php endif; ?>
                                <?php if ($clubData['email']): ?>
                                    <br><small class="text-muted">
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($clubData['email']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Snelle Statistieken -->
                <div class="profile-section">
                    <h3><i class="fas fa-chart-bar"></i> Statistieken</h3>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border rounded p-3">
                                <h4 class="text-primary"><?php echo count($userClubs); ?></h4>
                                <small class="text-muted">Clubs</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3">
                                <h4 class="text-success"><?php echo ucfirst($currentUser['rol']); ?></h4>
                                <small class="text-muted">Rol</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
// Include footer
include 'includes/footer.php';
?> 