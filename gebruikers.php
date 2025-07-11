<?php
require_once 'includes/init.php';

// Controleer of gebruiker is admin
if (!isAdmin()) {
    redirect('index.php', 'Je hebt geen toegang tot deze pagina.', 'danger');
}

$user = new User();
$club = new Club();

$message = '';
$error = '';

// Verwerk acties
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_user':
            $userId = (int)$_POST['user_id'];
            $updateData = [
                'naam' => trim($_POST['naam']),
                'email' => trim($_POST['email']),
                'rol' => $_POST['rol']
            ];
            
            if ($user->update($userId, $updateData)) {
                $message = 'Gebruiker succesvol bijgewerkt!';
            } else {
                $error = 'Fout bij bijwerken van gebruiker.';
            }
            break;
            
        case 'delete_user':
            $userId = (int)$_POST['user_id'];
            if ($user->delete($userId)) {
                $message = 'Gebruiker succesvol verwijderd!';
            } else {
                $error = 'Fout bij verwijderen van gebruiker.';
            }
            break;
            
        case 'add_user':
            $naam = trim($_POST['naam']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $rol = $_POST['rol'];
            
            // Controleer of email al bestaat
            $existingUser = $user->getByEmail($email);
            if ($existingUser) {
                $error = 'Een gebruiker met dit e-mailadres bestaat al.';
            } else {
                if ($user->register($naam, $email, $password, $rol)) {
                    $message = 'Gebruiker succesvol aangemaakt!';
                } else {
                    $error = 'Fout bij aanmaken van gebruiker.';
                }
            }
            break;
            
        case 'add_to_club':
            $userId = (int)$_POST['user_id'];
            $clubId = (int)$_POST['club_id'];
            $role = $_POST['club_role'];
            
            if ($club->addUser($clubId, $userId, $role)) {
                $message = 'Gebruiker succesvol toegevoegd aan club!';
            } else {
                $error = 'Fout bij toevoegen van gebruiker aan club.';
            }
            break;
            
        case 'remove_from_club':
            $userId = (int)$_POST['user_id'];
            $clubId = (int)$_POST['club_id'];
            
            if ($club->removeUser($clubId, $userId)) {
                $message = 'Gebruiker succesvol verwijderd uit club!';
            } else {
                $error = 'Fout bij verwijderen van gebruiker uit club.';
            }
            break;
            
        case 'update_club_role':
            $userId = (int)$_POST['user_id'];
            $clubId = (int)$_POST['club_id'];
            $role = $_POST['club_role'];
            
            if ($club->updateUserRole($clubId, $userId, $role)) {
                $message = 'Club rol succesvol bijgewerkt!';
            } else {
                $error = 'Fout bij bijwerken van club rol.';
            }
            break;
            
        case 'create_club':
            $clubData = [
                'naam' => trim($_POST['naam']),
                'beschrijving' => trim($_POST['beschrijving'] ?? ''),
                'adres' => trim($_POST['adres'] ?? ''),
                'telefoon' => trim($_POST['telefoon'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'website' => trim($_POST['website'] ?? '')
            ];
            
            if ($club->create($clubData)) {
                $message = 'Club succesvol aangemaakt!';
            } else {
                $error = 'Fout bij aanmaken van club.';
            }
            break;
            
        case 'update_club':
            $clubId = (int)$_POST['club_id'];
            $clubData = [
                'naam' => trim($_POST['naam']),
                'beschrijving' => trim($_POST['beschrijving'] ?? ''),
                'adres' => trim($_POST['adres'] ?? ''),
                'telefoon' => trim($_POST['telefoon'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'website' => trim($_POST['website'] ?? '')
            ];
            
            if ($club->update($clubId, $clubData)) {
                $message = 'Club succesvol bijgewerkt!';
            } else {
                $error = 'Fout bij bijwerken van club.';
            }
            break;
            
        case 'delete_club':
            $clubId = (int)$_POST['club_id'];
            if ($club->delete($clubId)) {
                $message = 'Club succesvol verwijderd!';
            } else {
                $error = 'Fout bij verwijderen van club.';
            }
            break;
    }
}

$users = $user->getAll();
$clubs = $club->getAll();

$page_title = 'Gebruikersbeheer';

// Include header
include 'includes/header.php';
?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-users"></i> Beheer</h1>
            <div>
                <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-user-plus"></i> Nieuwe Gebruiker
                </button>
                <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#addClubModal">
                    <i class="fas fa-plus"></i> Nieuwe Club
                </button>
            </div>
        </div>
        
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

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="managementTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                    <i class="fas fa-users"></i> Gebruikers (<?php echo count($users); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="clubs-tab" data-bs-toggle="tab" data-bs-target="#clubs" type="button" role="tab">
                    <i class="fas fa-building"></i> Clubs (<?php echo count($clubs); ?>)
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="managementTabsContent">
            <!-- Gebruikers Tab -->
            <div class="tab-pane fade show active" id="users" role="tabpanel">
                <div class="row">
                    <?php foreach ($users as $userData): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="user-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5><?php echo htmlspecialchars($userData['naam']); ?></h5>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($userData['email']); ?>
                                </p>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-calendar"></i> Lid sinds <?php echo date('d-m-Y', strtotime($userData['datum_aanmaak'])); ?>
                                </p>
                                <span class="badge bg-<?php echo $userData['rol'] === 'admin' ? 'danger' : ($userData['rol'] === 'trainer' ? 'primary' : 'secondary'); ?>">
                                    <?php echo ucfirst($userData['rol']); ?>
                                </span>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-cog"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editUserModal" 
                                           data-user='<?php echo json_encode($userData); ?>'>
                                        <i class="fas fa-edit"></i> Bewerken
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#clubModal" 
                                           data-user-id="<?php echo $userData['id']; ?>" data-user-name="<?php echo htmlspecialchars($userData['naam']); ?>">
                                        <i class="fas fa-users"></i> Club Beheer
                                    </a></li>
                                    <?php if ($userData['id'] != $_SESSION['user_id']): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteUser(<?php echo $userData['id']; ?>, '<?php echo htmlspecialchars($userData['naam']); ?>')">
                                        <i class="fas fa-trash"></i> Verwijderen
                                    </a></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Club lidmaatschappen -->
                        <?php 
                        $userClubs = $user->getClubs($userData['id']);
                        if (!empty($userClubs)): 
                        ?>
                            <div class="mt-3">
                                <small class="text-muted">Club lidmaatschappen:</small><br>
                                <?php foreach ($userClubs as $clubData): ?>
                                    <span class="badge bg-info club-badge">
                                        <?php echo htmlspecialchars($clubData['naam']); ?> 
                                        (<?php echo ucfirst($clubData['user_rol']); ?>)
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
                </div>
            </div>

            <!-- Clubs Tab -->
            <div class="tab-pane fade" id="clubs" role="tabpanel">
                <div class="row">
                    <?php foreach ($clubs as $clubData): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="user-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5><?php echo htmlspecialchars($clubData['naam']); ?></h5>
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($clubData['adres'] ?: 'Geen adres'); ?>
                                        </p>
                                        <p class="text-muted mb-2">
                                            <i class="fas fa-calendar"></i> Aangemaakt op <?php echo date('d-m-Y', strtotime($clubData['datum_aanmaak'])); ?>
                                        </p>
                                        <?php if ($clubData['telefoon']): ?>
                                            <p class="text-muted mb-1">
                                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($clubData['telefoon']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($clubData['email']): ?>
                                            <p class="text-muted mb-1">
                                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($clubData['email']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editClubModal" 
                                                   data-club='<?php echo json_encode($clubData); ?>'>
                                                <i class="fas fa-edit"></i> Bewerken
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#clubMembersModal" 
                                                   data-club-id="<?php echo $clubData['id']; ?>" data-club-name="<?php echo htmlspecialchars($clubData['naam']); ?>">
                                                <i class="fas fa-users"></i> Leden Beheer
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteClub(<?php echo $clubData['id']; ?>, '<?php echo htmlspecialchars($clubData['naam']); ?>')">
                                                <i class="fas fa-trash"></i> Verwijderen
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- Club beschrijving -->
                                <?php if ($clubData['beschrijving']): ?>
                                    <div class="mt-3">
                                        <small class="text-muted"><?php echo htmlspecialchars($clubData['beschrijving']); ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gebruiker Bewerken</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        
                        <div class="mb-3">
                            <label for="edit_naam" class="form-label">Naam</label>
                            <input type="text" class="form-control" id="edit_naam" name="naam" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">E-mail</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_rol" class="form-label">Rol</label>
                            <select class="form-select" id="edit_rol" name="rol" required>
                                <option value="viewer">Viewer</option>
                                <option value="trainer">Trainer</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                        <button type="submit" class="btn btn-primary">Bijwerken</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Club Management Modal -->
    <div class="modal fade" id="clubModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Club Beheer voor <span id="club_user_name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="club_user_id">
                    
                    <!-- Add to Club -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6><i class="fas fa-plus"></i> Toevoegen aan Club</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="action" value="add_to_club">
                                <input type="hidden" name="user_id" id="add_club_user_id">
                                
                                <div class="col-md-6">
                                    <label for="club_id" class="form-label">Club</label>
                                    <select class="form-select" name="club_id" required>
                                        <option value="">Selecteer club...</option>
                                        <?php foreach ($clubs as $clubData): ?>
                                            <option value="<?php echo $clubData['id']; ?>">
                                                <?php echo htmlspecialchars($clubData['naam']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="club_role" class="form-label">Rol</label>
                                    <select class="form-select" name="club_role" required>
                                        <option value="viewer">Viewer</option>
                                        <option value="trainer">Trainer</option>
                                        <option value="eigenaar">Eigenaar</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-plus"></i> Toevoegen
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Current Club Memberships -->
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="fas fa-list"></i> Huidige Club Lidmaatschappen</h6>
                        </div>
                        <div class="card-body">
                            <div id="club_memberships">
                                <!-- Dynamisch geladen -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nieuwe Gebruiker</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_user">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="new_naam" class="form-label">Naam</label>
                            <input type="text" class="form-control" id="new_naam" name="naam" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_email" class="form-label">E-mail</label>
                            <input type="email" class="form-control" id="new_email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Wachtwoord</label>
                            <input type="password" class="form-control" id="new_password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_rol" class="form-label">Rol</label>
                            <select class="form-select" id="new_rol" name="rol" required>
                                <option value="viewer">Viewer</option>
                                <option value="trainer">Trainer</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                        <button type="submit" class="btn btn-success">Toevoegen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Club Modal -->
    <div class="modal fade" id="editClubModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Club Bewerken</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_club">
                        <input type="hidden" name="club_id" id="edit_club_id">
                        
                        <div class="mb-3">
                            <label for="edit_club_naam" class="form-label">Naam</label>
                            <input type="text" class="form-control" id="edit_club_naam" name="naam" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_club_beschrijving" class="form-label">Beschrijving</label>
                            <textarea class="form-control" id="edit_club_beschrijving" name="beschrijving" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_club_adres" class="form-label">Adres</label>
                            <input type="text" class="form-control" id="edit_club_adres" name="adres">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_club_telefoon" class="form-label">Telefoon</label>
                                    <input type="text" class="form-control" id="edit_club_telefoon" name="telefoon">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_club_email" class="form-label">E-mail</label>
                                    <input type="email" class="form-control" id="edit_club_email" name="email">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_club_website" class="form-label">Website</label>
                            <input type="url" class="form-control" id="edit_club_website" name="website">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                        <button type="submit" class="btn btn-primary">Bijwerken</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Club Modal -->
    <div class="modal fade" id="addClubModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nieuwe Club</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_club">
                        
                        <div class="mb-3">
                            <label for="new_club_naam" class="form-label">Naam *</label>
                            <input type="text" class="form-control" id="new_club_naam" name="naam" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_club_beschrijving" class="form-label">Beschrijving</label>
                            <textarea class="form-control" id="new_club_beschrijving" name="beschrijving" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_club_adres" class="form-label">Adres</label>
                            <input type="text" class="form-control" id="new_club_adres" name="adres">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_club_telefoon" class="form-label">Telefoon</label>
                                    <input type="text" class="form-control" id="new_club_telefoon" name="telefoon">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_club_email" class="form-label">E-mail</label>
                                    <input type="email" class="form-control" id="new_club_email" name="email">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_club_website" class="form-label">Website</label>
                            <input type="url" class="form-control" id="new_club_website" name="website">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                        <button type="submit" class="btn btn-success">Aanmaken</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Club Members Modal -->
    <div class="modal fade" id="clubMembersModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Leden Beheer voor <span id="club_members_name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="club_members_id">
                    
                    <!-- Add Member to Club -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6><i class="fas fa-user-plus"></i> Lid Toevoegen</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="action" value="add_to_club">
                                <input type="hidden" name="club_id" id="add_member_club_id">
                                
                                <div class="col-md-6">
                                    <label for="member_user_id" class="form-label">Gebruiker</label>
                                    <select class="form-select" name="user_id" required>
                                        <option value="">Selecteer gebruiker...</option>
                                        <?php foreach ($users as $userData): ?>
                                            <option value="<?php echo $userData['id']; ?>">
                                                <?php echo htmlspecialchars($userData['naam']); ?> (<?php echo htmlspecialchars($userData['email']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="member_role" class="form-label">Rol</label>
                                    <select class="form-select" name="club_role" required>
                                        <option value="viewer">Viewer</option>
                                        <option value="trainer">Trainer</option>
                                        <option value="eigenaar">Eigenaar</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-plus"></i> Toevoegen
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Current Club Members -->
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="fas fa-list"></i> Huidige Leden</h6>
                        </div>
                        <div class="card-body">
                            <div id="club_members_list">
                                <!-- Dynamisch geladen -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
// Custom JavaScript voor deze pagina
$custom_js = [];

// Include footer
include 'includes/footer.php';
?> 