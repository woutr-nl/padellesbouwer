<?php
/**
 * Les Class
 * Beheert lessen en les_items
 */

require_once __DIR__ . '/../includes/init.php';

class Les {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDatabaseConnection();
    }
    
    /**
     * Nieuwe les aanmaken
     * @param array $data
     * @return int|false
     */
    public function create($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO lessen (titel, bedoeling, slag, niveaufactor, beschrijving, auteur_id, club_id, is_openbaar, is_actief) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['titel'],
                $data['bedoeling'],
                $data['slag'] ?? null,
                $data['niveaufactor'] ?? null,
                $data['beschrijving'] ?? null,
                $data['auteur_id'],
                $data['club_id'] ?? null,
                $data['is_openbaar'] ?? true,
                $data['is_actief'] ?? true
            ]);
            
            return $this->pdo->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Create lesson error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Les ophalen op ID
     * @param int $id
     * @return array|null
     */
    public function getById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT l.*, u.naam as auteur_naam
                FROM lessen l
                JOIN users u ON l.auteur_id = u.id
                WHERE l.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Get lesson error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Alle lessen ophalen voor een gebruiker
     * @param int $userId
     * @return array
     */
    public function getByUser($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT l.*, u.naam as auteur_naam, c.naam as club_naam
                FROM lessen l
                JOIN users u ON l.auteur_id = u.id
                LEFT JOIN clubs c ON l.club_id = c.id
                WHERE l.auteur_id = ?
                ORDER BY l.datum_aanmaak DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get lessons by user error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Alle lessen ophalen voor een gebruiker inclusief club lessen
     * @param int $userId
     * @return array
     */
    public function getByUserWithClubs($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT l.*, u.naam as auteur_naam, c.naam as club_naam
                FROM lessen l
                JOIN users u ON l.auteur_id = u.id
                LEFT JOIN clubs c ON l.club_id = c.id
                LEFT JOIN club_gebruikers cg ON l.club_id = cg.club_id
                WHERE l.auteur_id = ? OR (cg.user_id = ? AND l.is_openbaar = 1 AND l.is_actief = 1)
                ORDER BY l.datum_aanmaak DESC
            ");
            $stmt->execute([$userId, $userId]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get lessons by user with clubs error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Alle lessen ophalen (voor admins)
     * @return array
     */
    public function getAll() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT l.*, u.naam as auteur_naam
                FROM lessen l
                JOIN users u ON l.auteur_id = u.id
                ORDER BY l.datum_aanmaak DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get all lessons error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Les bijwerken
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        try {
            $allowedFields = ['titel', 'bedoeling', 'slag', 'niveaufactor', 'beschrijving', 'club_id', 'is_openbaar', 'is_actief'];
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
            $sql = "UPDATE lessen SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            
            return $stmt->execute($values);
            
        } catch (PDOException $e) {
            error_log("Update lesson error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Les verwijderen
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM lessen WHERE id = ?");
            return $stmt->execute([$id]);
            
        } catch (PDOException $e) {
            error_log("Delete lesson error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Les items ophalen voor een les
     * @param int $lesId
     * @return array
     */
    public function getItems($lesId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM les_items 
                WHERE les_id = ? 
                ORDER BY z_index ASC, id ASC
            ");
            $stmt->execute([$lesId]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get lesson items error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Les item toevoegen
     * @param int $lesId
     * @param array $data
     * @return int|false
     */
    public function addItem($lesId, $data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO les_items (les_id, type, x, y, rotation, extra_data, z_index) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $lesId,
                $data['type'],
                $data['x'],
                $data['y'],
                $data['rotation'] ?? 0,
                json_encode($data['extra_data'] ?? []),
                $data['z_index'] ?? 0
            ]);
            
            return $this->pdo->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Add lesson item error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Les item bijwerken
     * @param int $itemId
     * @param array $data
     * @return bool
     */
    public function updateItem($itemId, $data) {
        try {
            $allowedFields = ['type', 'x', 'y', 'rotation', 'extra_data', 'z_index'];
            $updates = [];
            $values = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    if ($field === 'extra_data') {
                        $updates[] = "$field = ?";
                        $values[] = json_encode($value);
                    } else {
                        $updates[] = "$field = ?";
                        $values[] = $value;
                    }
                }
            }
            
            if (empty($updates)) {
                return false;
            }
            
            $values[] = $itemId;
            $sql = "UPDATE les_items SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            
            return $stmt->execute($values);
            
        } catch (PDOException $e) {
            error_log("Update lesson item error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Les item verwijderen
     * @param int $itemId
     * @return bool
     */
    public function deleteItem($itemId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM les_items WHERE id = ?");
            return $stmt->execute([$itemId]);
            
        } catch (PDOException $e) {
            error_log("Delete lesson item error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Alle items van een les verwijderen
     * @param int $lesId
     * @return bool
     */
    public function deleteAllItems($lesId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM les_items WHERE les_id = ?");
            return $stmt->execute([$lesId]);
            
        } catch (PDOException $e) {
            error_log("Delete all lesson items error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Controleer of gebruiker eigenaar is van les
     * @param int $lesId
     * @param int $userId
     * @return bool
     */
    public function isOwner($lesId, $userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM lessen WHERE id = ? AND auteur_id = ?");
            $stmt->execute([$lesId, $userId]);
            return (bool) $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Check lesson ownership error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Zoek lessen
     * @param string $search
     * @return array
     */
    public function search($search) {
        try {
            $searchTerm = "%$search%";
            $stmt = $this->pdo->prepare("
                SELECT l.*, u.naam as auteur_naam
                FROM lessen l
                JOIN users u ON l.auteur_id = u.id
                WHERE l.titel LIKE ? OR l.beschrijving LIKE ? OR l.slag LIKE ?
                ORDER BY l.datum_aanmaak DESC
            ");
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Search lessons error: " . $e->getMessage());
            return [];
        }
    }
} 