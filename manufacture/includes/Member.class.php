<?php
/**
 * Member Class
 * Handles team member management operations
 */
class Member {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all team members for a manufacturer
     */
    public function getTeamMembers($manufacturer_id) {
        $sql = "SELECT 
                    id,
                    first_name,
                    last_name,
                    email,
                    role,
                    is_active,
                    created_at,
                    last_login
                FROM team_members 
                WHERE manufacturer_id = ? 
                ORDER BY created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$manufacturer_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get a single team member by ID
     */
    public function getMemberById($member_id, $manufacturer_id) {
        $sql = "SELECT 
                    id,
                    first_name,
                    last_name,
                    email,
                    role,
                    is_active,
                    created_at,
                    last_login
                FROM team_members 
                WHERE id = ? AND manufacturer_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$member_id, $manufacturer_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Add a new team member
     */
    public function addMember($data, $manufacturer_id) {
        try {
            // First, check if manufacturer exists
            if (!$this->manufacturerExists($manufacturer_id)) {
                return ['success' => false, 'message' => 'Manufacturer not found'];
            }
            
            // Check if email already exists
            if ($this->emailExists($data['email'], $manufacturer_id)) {
                return ['success' => false, 'message' => 'Email already exists for this manufacturer'];
            }
            
            $sql = "INSERT INTO team_members (
                        manufacturer_id,
                        first_name,
                        last_name,
                        email,
                        password,
                        role,
                        is_active,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $manufacturer_id,
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $hashed_password,
                $data['role'],
                $data['is_active'] ?? 1
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Team member added successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to add team member'];
            }
        } catch (PDOException $e) {
            // Log the error for debugging
            error_log("Team member insertion error: " . $e->getMessage());
            
            // Check for specific foreign key constraint errors
            if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                return ['success' => false, 'message' => 'Manufacturer not found or invalid'];
            }
            
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update a team member
     */
    public function updateMember($member_id, $data, $manufacturer_id) {
        // Check if member exists and belongs to manufacturer
        $existing_member = $this->getMemberById($member_id, $manufacturer_id);
        if (!$existing_member) {
            return ['success' => false, 'message' => 'Member not found'];
        }
        
        // Check if email already exists for another member
        if ($this->emailExistsForOtherMember($data['email'], $member_id, $manufacturer_id)) {
            return ['success' => false, 'message' => 'Email already exists for another team member'];
        }
        
        $sql = "UPDATE team_members SET 
                    first_name = ?,
                    last_name = ?,
                    email = ?,
                    role = ?,
                    is_active = ?
                WHERE id = ? AND manufacturer_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['role'],
            $data['is_active'] ?? 1,
            $member_id,
            $manufacturer_id
        ]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Team member updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update team member'];
        }
    }
    
    /**
     * Delete a team member
     */
    public function deleteMember($member_id, $manufacturer_id) {
        // Check if member exists and belongs to manufacturer
        $existing_member = $this->getMemberById($member_id, $manufacturer_id);
        if (!$existing_member) {
            return ['success' => false, 'message' => 'Member not found'];
        }
        
        $sql = "DELETE FROM team_members WHERE id = ? AND manufacturer_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([$member_id, $manufacturer_id]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Team member removed successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to remove team member'];
        }
    }
    
    /**
     * Check if email exists for manufacturer
     */
    private function emailExists($email, $manufacturer_id) {
        $sql = "SELECT COUNT(*) FROM team_members WHERE email = ? AND manufacturer_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email, $manufacturer_id]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if email exists for another member
     */
    private function emailExistsForOtherMember($email, $member_id, $manufacturer_id) {
        $sql = "SELECT COUNT(*) FROM team_members WHERE email = ? AND manufacturer_id = ? AND id != ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email, $manufacturer_id, $member_id]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if manufacturer exists
     */
    private function manufacturerExists($manufacturer_id) {
        $sql = "SELECT COUNT(*) FROM manufacturers WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$manufacturer_id]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Get available roles
     */
    public function getAvailableRoles() {
        return [
            'admin' => 'Administrator',
            'manager' => 'Manager',
            'member' => 'Team Member'
        ];
    }
    
    /**
     * Update member's last login
     */
    public function updateLastLogin($member_id) {
        $sql = "UPDATE team_members SET last_login = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$member_id]);
    }
    
    /**
     * Get member activity statistics
     */
    public function getMemberActivity($manufacturer_id, $days = 30) {
        $sql = "SELECT 
                    tm.id,
                    tm.first_name,
                    tm.last_name,
                    tm.email,
                    tm.role,
                    tm.last_login,
                    COUNT(o.id) as total_orders_processed
                FROM team_members tm
                LEFT JOIN orders o ON tm.id = o.processed_by
                WHERE tm.manufacturer_id = ? 
                AND (o.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) OR o.created_at IS NULL)
                GROUP BY tm.id
                ORDER BY tm.last_login DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$manufacturer_id, $days]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
