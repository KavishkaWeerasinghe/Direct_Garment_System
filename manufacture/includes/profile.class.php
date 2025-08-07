<?php
require_once __DIR__ . '/../../config/database.php';

class Profile {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Get complete profile data for a manufacturer
     */
    public function getProfileData($manufacturerId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id,
                    first_name,
                    last_name,
                    email,
                    phone,
                    company_name,
                    profile_photo,
                    role,
                    nic_number,
                    created_at,
                    last_login,
                    email_verified
                FROM manufacturers 
                WHERE id = ?
            ");
            $stmt->execute([$manufacturerId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Update profile information
     */
    public function updateProfile($manufacturerId, $data) {
        try {
            $allowedFields = ['first_name', 'last_name', 'phone', 'nic_number'];
            $updateFields = [];
            $params = [];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($updateFields)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }

            $params[] = $manufacturerId;
            $sql = "UPDATE manufacturers SET " . implode(', ', $updateFields) . " WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'message' => 'Profile updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
        }
    }

    /**
     * Update profile photo
     */
    public function updateProfilePhoto($manufacturerId, $photoPath) {
        try {
            $stmt = $this->pdo->prepare("UPDATE manufacturers SET profile_photo = ? WHERE id = ?");
            $stmt->execute([$photoPath, $manufacturerId]);
            return ['success' => true, 'message' => 'Profile photo updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Photo update failed: ' . $e->getMessage()];
        }
    }

    /**
     * Change password
     */
    public function changePassword($manufacturerId, $currentPassword, $newPassword) {
        try {
            // Verify current password
            $stmt = $this->pdo->prepare("SELECT password FROM manufacturers WHERE id = ?");
            $stmt->execute([$manufacturerId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }

            // Validate new password
            if (strlen($newPassword) < 8) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters'];
            }

            if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/", $newPassword)) {
                return ['success' => false, 'message' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character'];
            }

            // Hash and update password
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $this->pdo->prepare("UPDATE manufacturers SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $manufacturerId]);

            return ['success' => true, 'message' => 'Password changed successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Password change failed: ' . $e->getMessage()];
        }
    }

    /**
     * Toggle two-factor authentication (placeholder - column doesn't exist yet)
     */
    public function toggleTwoFactor($manufacturerId, $enabled) {
        try {
            // For now, return success message since two_factor_enabled column doesn't exist
            return ['success' => true, 'message' => 'Two-factor authentication feature coming soon'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Two-factor toggle failed: ' . $e->getMessage()];
        }
    }

    /**
     * Record login activity
     */
    public function recordLoginActivity($manufacturerId, $ipAddress, $userAgent) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO manufacturing_login_history (manufacturer_id, ip_address, user_agent, login_time) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$manufacturerId, $ipAddress, $userAgent]);

            // Update last login time
            $stmt = $this->pdo->prepare("UPDATE manufacturers SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$manufacturerId]);

            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get login activity history
     */
    public function getLoginActivity($manufacturerId, $limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id,
                    ip_address,
                    user_agent,
                    login_time,
                    status
                FROM manufacturing_login_history 
                WHERE manufacturer_id = ? 
                ORDER BY login_time DESC 
                LIMIT ?
            ");
            $stmt->execute([$manufacturerId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Delete account (soft delete)
     */
    public function deleteAccount($manufacturerId, $password) {
        try {
            // Verify password
            $stmt = $this->pdo->prepare("SELECT password FROM manufacturers WHERE id = ?");
            $stmt->execute([$manufacturerId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Password is incorrect'];
            }

            // For now, just return success since is_deleted column doesn't exist yet
            return ['success' => true, 'message' => 'Account deletion feature coming soon'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Account deletion failed: ' . $e->getMessage()];
        }
    }

    /**
     * Upload profile photo
     */
    public function uploadProfilePhoto($file, $manufacturerId) {
        try {
            $uploadDir = __DIR__ . '/../assets/profile_photos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes)) {
                return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'];
            }

            if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
                return ['success' => false, 'message' => 'File size too large. Maximum 5MB allowed.'];
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $manufacturerId . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Update database
                $relativePath = 'assets/profile_photos/' . $filename;
                $result = $this->updateProfilePhoto($manufacturerId, $relativePath);
                
                if ($result['success']) {
                    return ['success' => true, 'message' => 'Profile photo uploaded successfully', 'path' => $relativePath];
                } else {
                    return $result;
                }
            } else {
                return ['success' => false, 'message' => 'Failed to upload file'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()];
        }
    }

    /**
     * Get profile statistics
     */
    public function getProfileStats($manufacturerId) {
        try {
            $stats = [];
            
            // Total login sessions
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total_logins FROM manufacturing_login_history WHERE manufacturer_id = ?");
            $stmt->execute([$manufacturerId]);
            $stats['total_logins'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_logins'];

            // Last 30 days logins
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as recent_logins 
                FROM manufacturing_login_history 
                WHERE manufacturer_id = ? AND login_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute([$manufacturerId]);
            $stats['recent_logins'] = $stmt->fetch(PDO::FETCH_ASSOC)['recent_logins'];

            // Account age
            $stmt = $this->pdo->prepare("
                SELECT DATEDIFF(NOW(), created_at) as account_age 
                FROM manufacturers 
                WHERE id = ?
            ");
            $stmt->execute([$manufacturerId]);
            $stats['account_age'] = $stmt->fetch(PDO::FETCH_ASSOC)['account_age'];

            return $stats;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Validate profile data
     */
    public function validateProfileData($data) {
        $errors = [];

        if (isset($data['first_name']) && (empty($data['first_name']) || strlen($data['first_name']) > 50)) {
            $errors[] = 'First name is required and must be less than 50 characters';
        }

        if (isset($data['last_name']) && (empty($data['last_name']) || strlen($data['last_name']) > 50)) {
            $errors[] = 'Last name is required and must be less than 50 characters';
        }

        if (isset($data['phone']) && !preg_match("/^[0-9+\-\s\(\)]{10,15}$/", $data['phone'])) {
            $errors[] = 'Invalid phone number format';
        }

        if (isset($data['nic_number']) && !empty($data['nic_number']) && !preg_match("/^[0-9]{9}[vVxX]$/", $data['nic_number'])) {
            $errors[] = 'Invalid NIC number format (e.g., 123456789V)';
        }

        return $errors;
    }
}
?>
