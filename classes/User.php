<?php
/**
 * User Class
 * Beheert gebruikersauthenticatie en gebruikersbeheer
 */

require_once __DIR__ . '/../includes/init.php';

class User {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDatabaseConnection();
    }
    
    /**
     * Gebruiker registreren
     * @param string $naam
     * @param string $email
     * @param string $password
     * @param string $rol
     * @return bool
     */
    public function register($naam, $email, $password, $rol = 'viewer') {
        try {
            // Controleer of email al bestaat
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                return false; // Email bestaat al
            }
            
            // Hash wachtwoord en voeg gebruiker toe
            $hashedPassword = hashPassword($password);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO users (naam, email, wachtwoord_hash, rol) 
                VALUES (?, ?, ?, ?)
            ");
            
            return $stmt->execute([$naam, $email, $hashedPassword, $rol]);
            
        } catch (PDOException $e) {
            error_log("User registration error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gebruiker inloggen
     * @param string $email
     * @param string $password
     * @return bool
     */
    public function login($email, $password) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, naam, email, wachtwoord_hash, rol 
                FROM users 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['wachtwoord_hash'])) {
                // Sessie instellen
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['naam'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['rol'];
                return true;
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("User login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gebruiker uitloggen
     */
    public function logout() {
        session_destroy();
    }
    
    /**
     * Gebruiker ophalen op ID
     * @param int $id
     * @return array|null
     */
    public function getById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, naam, email, rol, datum_aanmaak 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Alle gebruikers ophalen (admin only)
     * @return array
     */
    public function getAll() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, naam, email, rol, datum_aanmaak 
                FROM users 
                ORDER BY naam
            ");
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Gebruiker bijwerken
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        try {
            $allowedFields = ['naam', 'email', 'rol'];
            $updates = [];
            $values = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "$field = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($updates)) {
                return false;
            }
            
            $values[] = $id;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            
            return $stmt->execute($values);
            
        } catch (PDOException $e) {
            error_log("Update user error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Wachtwoord wijzigen
     * @param int $id
     * @param string $newPassword
     * @return bool
     */
    public function changePassword($id, $newPassword) {
        try {
            $hashedPassword = hashPassword($newPassword);
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET wachtwoord_hash = ? 
                WHERE id = ?
            ");
            
            return $stmt->execute([$hashedPassword, $id]);
            
        } catch (PDOException $e) {
            error_log("Change password error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gebruiker verwijderen
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            return $stmt->execute([$id]);
            
        } catch (PDOException $e) {
            error_log("Delete user error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gebruiker ophalen op email
     * @param string $email
     * @return array|null
     */
    public function getByEmail($email) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, naam, email, rol, datum_aanmaak 
                FROM users 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Get user by email error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Clubs van een gebruiker ophalen
     * @param int $userId
     * @return array
     */
    public function getClubs($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.*, cg.rol as user_rol
                FROM club_gebruikers cg
                JOIN clubs c ON cg.club_id = c.id
                WHERE cg.user_id = ?
                ORDER BY c.naam
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get user clubs error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Controleer of gebruiker lid is van club
     * @param int $userId
     * @param int $clubId
     * @return bool
     */
    public function isMemberOfClub($userId, $clubId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id FROM club_gebruikers 
                WHERE user_id = ? AND club_id = ?
            ");
            $stmt->execute([$userId, $clubId]);
            return $stmt->fetch() !== false;
            
        } catch (PDOException $e) {
            error_log("Check club membership error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gebruiker rol in club ophalen
     * @param int $userId
     * @param int $clubId
     * @return string|null
     */
    public function getClubRole($userId, $clubId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT rol FROM club_gebruikers 
                WHERE user_id = ? AND club_id = ?
            ");
            $stmt->execute([$userId, $clubId]);
            $result = $stmt->fetch();
            return $result ? $result['rol'] : null;
            
        } catch (PDOException $e) {
            error_log("Get club role error: " . $e->getMessage());
            return null;
        }
    }
} 