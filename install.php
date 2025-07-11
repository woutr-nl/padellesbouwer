<?php
/**
 * Padelles Beheersysteem - Installatie Script
 * 
 * Dit script installeert de database en maakt de eerste admin gebruiker aan.
 * Verwijder dit bestand na succesvolle installatie.
 */

// Controleer of het systeem al geïnstalleerd is
if (file_exists('config/database.php')) {
    die('Het systeem is al geïnstalleerd. Verwijder install.php voor veiligheid.');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? '');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';
    
    $admin_name = trim($_POST['admin_name'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_password = $_POST['admin_password'] ?? '';
    $admin_password_confirm = $_POST['admin_password_confirm'] ?? '';
    
    // Validatie
    if (empty($db_host) || empty($db_name) || empty($db_user) || empty($admin_name) || empty($admin_email) || empty($admin_password)) {
        $error = 'Vul alle velden in.';
    } elseif ($admin_password !== $admin_password_confirm) {
        $error = 'Wachtwoorden komen niet overeen.';
    } elseif (strlen($admin_password) < 6) {
        $error = 'Wachtwoord moet minimaal 6 karakters lang zijn.';
    } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Voer een geldig email adres in.';
    } else {
        try {
            // Database connectie testen
            $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Database aanmaken
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$db_name`");
            
            // Database configuratie bestand aanmaken
            $config_content = "<?php
/**
 * Database Configuration
 * Centrale database configuratie voor het Padelles Beheersysteem
 */

// Database instellingen
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('DB_CHARSET', 'utf8mb4');

// PDO opties
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci\"
]);

/**
 * Database connectie functie
 * @return PDO
 */
function getDatabaseConnection() {
    static \$pdo = null;
    
    if (\$pdo === null) {
        try {
            \$dsn = \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET;
            \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, DB_OPTIONS);
        } catch (PDOException \$e) {
            die(\"Database connectie mislukt: \" . \$e->getMessage());
        }
    }
    
    return \$pdo;
}

/**
 * Database initialisatie - maak tabellen aan als ze niet bestaan
 */
function initializeDatabase() {
    \$pdo = getDatabaseConnection();
    
    // Users tabel
    \$sql = \"CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        naam VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        wachtwoord_hash VARCHAR(255) NOT NULL,
        rol ENUM('admin', 'trainer', 'viewer') DEFAULT 'viewer',
        datum_aanmaak TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )\";
    \$pdo->exec(\$sql);
    
    // Lessen tabel
    \$sql = \"CREATE TABLE IF NOT EXISTS lessen (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titel VARCHAR(255) NOT NULL,
        bedoeling ENUM('Scoren', 'Opbouwen', 'Voorkomen van scoren', 'Neutraal spelen', 'Uitlokken') NOT NULL,
        slag VARCHAR(100),
        niveaufactor ENUM('Positionering', 'Vastheid', 'Precisie', 'Rotatie', 'Variatie', 'Vaart', 'Anticipatie', 'Onder druk spelen', 'Tempo', 'Camouflage'),
        beschrijving TEXT,
        auteur_id INT NOT NULL,
        datum_aanmaak TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (auteur_id) REFERENCES users(id) ON DELETE CASCADE
    )\";
    \$pdo->exec(\$sql);
    
    // Les_items tabel
    \$sql = \"CREATE TABLE IF NOT EXISTS les_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        les_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        x DECIMAL(10,2) NOT NULL,
        y DECIMAL(10,2) NOT NULL,
        rotation DECIMAL(5,2) DEFAULT 0,
        extra_data JSON,
        z_index INT DEFAULT 0,
        datum_aanmaak TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (les_id) REFERENCES lessen(id) ON DELETE CASCADE
    )\";
    \$pdo->exec(\$sql);
    
    // Indexen voor betere performance
    \$pdo->exec(\"CREATE INDEX IF NOT EXISTS idx_lessen_auteur ON lessen(auteur_id)\");
    \$pdo->exec(\"CREATE INDEX IF NOT EXISTS idx_les_items_les ON les_items(les_id)\");
    \$pdo->exec(\"CREATE INDEX IF NOT EXISTS idx_les_items_z_index ON les_items(les_id, z_index)\");
}
";
            
            file_put_contents('config/database.php', $config_content);
            
            // Database initialiseren
            require_once 'config/database.php';
            initializeDatabase();
            
            // Admin gebruiker aanmaken
            require_once 'includes/init.php';
            require_once 'classes/User.php';
            
            $user = new User();
            if ($user->register($admin_name, $admin_email, $admin_password, 'admin')) {
                $success = 'Installatie succesvol voltooid! Je kunt nu inloggen met je admin account.';
            } else {
                $error = 'Fout bij het aanmaken van de admin gebruiker.';
            }
            
        } catch (PDOException $e) {
            $error = 'Database fout: ' . $e->getMessage();
        } catch (Exception $e) {
            $error = 'Installatie fout: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installatie - Padelles Beheersysteem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-table-tennis fa-3x text-primary mb-3"></i>
                            <h2 class="card-title">Padelles Beheersysteem</h2>
                            <p class="text-muted">Installatie Wizard</p>
                        </div>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                            <hr>
                            <a href="login.php" class="btn btn-success">
                                <i class="fas fa-sign-in-alt"></i> Ga naar Login
                            </a>
                        </div>
                        <?php else: ?>
                        
                        <form method="POST" action="install.php">
                            <h5 class="mb-3">Database Instellingen</h5>
                            
                            <div class="mb-3">
                                <label for="db_host" class="form-label">Database Host</label>
                                <input type="text" class="form-control" id="db_host" name="db_host" 
                                       value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="db_name" class="form-label">Database Naam</label>
                                <input type="text" class="form-control" id="db_name" name="db_name" 
                                       value="<?= htmlspecialchars($_POST['db_name'] ?? 'padelles_db') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="db_user" class="form-label">Database Gebruiker</label>
                                <input type="text" class="form-control" id="db_user" name="db_user" 
                                       value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="db_pass" class="form-label">Database Wachtwoord</label>
                                <input type="password" class="form-control" id="db_pass" name="db_pass" 
                                       value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
                            </div>
                            
                            <hr>
                            
                            <h5 class="mb-3">Admin Account</h5>
                            
                            <div class="mb-3">
                                <label for="admin_name" class="form-label">Naam</label>
                                <input type="text" class="form-control" id="admin_name" name="admin_name" 
                                       value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="admin_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                       value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="admin_password" class="form-label">Wachtwoord</label>
                                <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                                <div class="form-text">Minimaal 6 karakters</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="admin_password_confirm" class="form-label">Wachtwoord Bevestigen</label>
                                <input type="password" class="form-control" id="admin_password_confirm" name="admin_password_confirm" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-cog"></i> Installeren
                                </button>
                            </div>
                        </form>
                        
                        <?php endif; ?>
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