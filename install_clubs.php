<?php
require_once 'includes/init.php';

// Controleer of gebruiker is ingelogd en admin is
if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_clubs'])) {
    try {
        $pdo = getDatabaseConnection();
        
        // Clubs tabel aanmaken
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS clubs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                naam VARCHAR(255) NOT NULL,
                beschrijving TEXT,
                adres VARCHAR(255),
                telefoon VARCHAR(50),
                email VARCHAR(255),
                website VARCHAR(255),
                datum_aanmaak TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                datum_bijgewerkt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Club gebruikers tabel aanmaken
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS club_gebruikers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                club_id INT NOT NULL,
                user_id INT NOT NULL,
                rol ENUM('eigenaar', 'trainer', 'viewer') DEFAULT 'viewer',
                datum_toegevoegd TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_club_user (club_id, user_id)
            )
        ");
        
        // Voeg club_id en zichtbaarheid toe aan lessen tabel
        $pdo->exec("ALTER TABLE lessen ADD COLUMN IF NOT EXISTS club_id INT NULL");
        $pdo->exec("ALTER TABLE lessen ADD COLUMN IF NOT EXISTS is_openbaar BOOLEAN DEFAULT TRUE");
        $pdo->exec("ALTER TABLE lessen ADD COLUMN IF NOT EXISTS is_actief BOOLEAN DEFAULT TRUE");
        
        // Voeg foreign key toe als deze nog niet bestaat
        try {
            $pdo->exec("ALTER TABLE lessen ADD CONSTRAINT fk_lessen_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE SET NULL");
        } catch (PDOException $e) {
            // Foreign key bestaat al
        }
        
        // Voeg voorbeeld clubs toe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM clubs");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("
                INSERT INTO clubs (naam, beschrijving, adres, telefoon, email) VALUES
                ('Padel Club Amsterdam', 'De beste padel club van Amsterdam', 'Padelstraat 1, Amsterdam', '020-1234567', 'info@padelclubamsterdam.nl'),
                ('Padel Club Rotterdam', 'Moderne padel faciliteiten in Rotterdam', 'Padelweg 15, Rotterdam', '010-9876543', 'info@padelclubrotterdam.nl'),
                ('Padel Club Den Haag', 'Professionele padel training in Den Haag', 'Padelbaan 8, Den Haag', '070-5551234', 'info@padelclubdenhaag.nl')
            ");
        }
        
        $message = 'Club functionaliteit succesvol geïnstalleerd!';
        
    } catch (PDOException $e) {
        $error = 'Fout bij installeren: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Functionaliteit Installeren - Padel Les Systeem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-table-tennis"></i> Padel Les Systeem
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="gebruikers.php">Gebruikers</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profiel.php">Mijn Profiel</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Uitloggen</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-cogs"></i> Club Functionaliteit Installeren</h3>
                    </div>
                    <div class="card-body">
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

                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> Wat wordt er geïnstalleerd?</h5>
                            <ul class="mb-0">
                                <li><strong>Clubs tabel:</strong> Voor het beheren van padel clubs</li>
                                <li><strong>Club gebruikers tabel:</strong> Voor koppeling tussen clubs en gebruikers</li>
                                <li><strong>Lessen uitbreiding:</strong> Club koppeling en zichtbaarheid opties</li>
                                <li><strong>Voorbeeld clubs:</strong> Drie voorbeeld clubs worden toegevoegd</li>
                            </ul>
                        </div>

                        <form method="POST">
                            <div class="d-grid gap-2">
                                <button type="submit" name="install_clubs" class="btn btn-primary btn-lg">
                                    <i class="fas fa-download"></i> Club Functionaliteit Installeren
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Terug naar Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 