<?php
/**
 * Club Class
 * Beheert clubs en club-gebruiker relaties
 */

require_once __DIR__ . '/../includes/init.php';

class Club {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDatabaseConnection();
    }
    
    /**
     * Club aanmaken
     * @param array $data
     * @return int|false
     */
    public function create($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO clubs (naam, beschrijving, adres, telefoon, email, website) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['naam'],
                $data['beschrijving'] ?? '',
                $data['adres'] ?? '',
                $data['telefoon'] ?? '',
                $data['email'] ?? '',
                $data['website'] ?? ''
            ]);
            
            return $this->pdo->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Club create error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Club ophalen op ID
     * @param int $id
     * @return array|null
     */
    public function getById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM clubs WHERE id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Get club error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Alle clubs ophalen
     * @return array
     */
    public function getAll() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM clubs ORDER BY naam
            ");
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get all clubs error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Club bijwerken
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        try {
            $allowedFields = ['naam', 'beschrijving', 'adres', 'telefoon', 'email', 'website'];
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
            $sql = "UPDATE clubs SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            
            return $stmt->execute($values);
            
        } catch (PDOException $e) {
            error_log("Update club error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Club verwijderen
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM clubs WHERE id = ?");
            return $stmt->execute([$id]);
            
        } catch (PDOException $e) {
            error_log("Delete club error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gebruiker toevoegen aan club
     * @param int $clubId
     * @param int $userId
     * @param string $rol
     * @return bool
     */
    public function addUser($clubId, $userId, $rol = 'viewer') {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO club_gebruikers (club_id, user_id, rol) 
                VALUES (?, ?, ?)
            ");
            
            return $stmt->execute([$clubId, $userId, $rol]);
            
        } catch (PDOException $e) {
            error_log("Add user to club error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gebruiker verwijderen uit club
     * @param int $clubId
     * @param int $userId
     * @return bool
     */
    public function removeUser($clubId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM club_gebruikers 
                WHERE club_id = ? AND user_id = ?
            ");
            
            return $stmt->execute([$clubId, $userId]);
            
        } catch (PDOException $e) {
            error_log("Remove user from club error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gebruiker rol bijwerken in club
     * @param int $clubId
     * @param int $userId
     * @param string $rol
     * @return bool
     */
    public function updateUserRole($clubId, $userId, $rol) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE club_gebruikers 
                SET rol = ? 
                WHERE club_id = ? AND user_id = ?
            ");
            
            return $stmt->execute([$rol, $clubId, $userId]);
            
        } catch (PDOException $e) {
            error_log("Update user role error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gebruikers van een club ophalen
     * @param int $clubId
     * @return array
     */
    public function getUsers($clubId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.naam, u.email, u.rol as user_rol, cg.rol as club_rol, cg.datum_toegevoegd
                FROM club_gebruikers cg
                JOIN users u ON cg.user_id = u.id
                WHERE cg.club_id = ?
                ORDER BY u.naam
            ");
            $stmt->execute([$clubId]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get club users error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clubs van een gebruiker ophalen
     * @param int $userId
     * @return array
     */
    public function getClubsByUser($userId) {
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
     * @param int $clubId
     * @param int $userId
     * @return bool
     */
    public function isUserMember($clubId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id FROM club_gebruikers 
                WHERE club_id = ? AND user_id = ?
            ");
            $stmt->execute([$clubId, $userId]);
            return $stmt->fetch() !== false;
            
        } catch (PDOException $e) {
            error_log("Check user membership error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gebruiker rol in club ophalen
     * @param int $clubId
     * @param int $userId
     * @return string|null
     */
    public function getUserRole($clubId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT rol FROM club_gebruikers 
                WHERE club_id = ? AND user_id = ?
            ");
            $stmt->execute([$clubId, $userId]);
            $result = $stmt->fetch();
            return $result ? $result['rol'] : null;
            
        } catch (PDOException $e) {
            error_log("Get user role error: " . $e->getMessage());
            return null;
        }
    }
} 