<?php
// Use a more flexible path resolution
$configPath = __DIR__ . '/../../config/database.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    // Fallback for when called from root directory
    require_once 'config/database.php';
}

class Company {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        if (!$pdo) {
            throw new Exception('Database connection not available');
        }
        $this->pdo = $pdo;
    }
    
    /**
     * Get company settings for a manufacturer
     */
    public function getCompanySettings($manufacturerId) {
        try {
            if (!$this->pdo) {
                error_log("Database connection not available in getCompanySettings");
                return false;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT * FROM company_settings 
                WHERE manufacturer_id = ?
            ");
            $stmt->execute([$manufacturerId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Return false if no data found, or the actual data if found
            return $result ? $result : false;
            
        } catch (PDOException $e) {
            error_log("Error getting company settings: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Unexpected error in getCompanySettings: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create or update company settings
     */
    public function saveCompanySettings($manufacturerId, $data) {
        try {
            error_log("Starting saveCompanySettings for manufacturer ID: " . $manufacturerId);
            error_log("Data to save: " . print_r($data, true));
            
            // Check if settings exist
            $existing = $this->getCompanySettings($manufacturerId);
            error_log("Existing settings: " . ($existing ? "Found" : "Not found"));
            
            if ($existing) {
                // Update existing settings
                error_log("Updating existing settings");
                $stmt = $this->pdo->prepare("
                    UPDATE company_settings SET
                        business_name = ?,
                        business_type = ?,
                        registration_number = ?,
                        business_address = ?,
                        district_province = ?,
                        contact_number = ?,
                        business_email = ?,
                        website = ?,
                        facebook_page = ?,
                        linkedin_page = ?,
                        number_of_employees = ?,
                        years_in_operation = ?,
                        certifications = ?,
                        description_bio = ?,
                        updated_at = NOW()
                    WHERE manufacturer_id = ?
                ");
                
                $params = [
                    $data['business_name'],
                    $data['business_type'],
                    $data['registration_number'],
                    $data['business_address'],
                    $data['district_province'],
                    $data['contact_number'],
                    $data['business_email'],
                    $data['website'],
                    $data['facebook_page'],
                    $data['linkedin_page'],
                    $data['number_of_employees'],
                    $data['years_in_operation'],
                    $data['certifications'],
                    $data['description_bio'],
                    $manufacturerId
                ];
                
                error_log("Update parameters: " . print_r($params, true));
                $result = $stmt->execute($params);
                error_log("Update result: " . ($result ? "Success" : "Failed"));
                
            } else {
                // Create new settings
                error_log("Creating new settings");
                $stmt = $this->pdo->prepare("
                    INSERT INTO company_settings (
                        manufacturer_id, business_name, business_type, registration_number,
                        business_address, district_province, contact_number, business_email,
                        website, facebook_page, linkedin_page, number_of_employees,
                        years_in_operation, certifications, description_bio
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $params = [
                    $manufacturerId,
                    $data['business_name'],
                    $data['business_type'],
                    $data['registration_number'],
                    $data['business_address'],
                    $data['district_province'],
                    $data['contact_number'],
                    $data['business_email'],
                    $data['website'],
                    $data['facebook_page'],
                    $data['linkedin_page'],
                    $data['number_of_employees'],
                    $data['years_in_operation'],
                    $data['certifications'],
                    $data['description_bio']
                ];
                
                error_log("Insert parameters: " . print_r($params, true));
                $result = $stmt->execute($params);
                error_log("Insert result: " . ($result ? "Success" : "Failed"));
            }
            
            if ($result) {
                error_log("Database operation successful");
                return ['success' => true, 'message' => 'Company settings saved successfully'];
            } else {
                error_log("Database operation failed");
                $errorInfo = $stmt->errorInfo();
                error_log("PDO Error Info: " . print_r($errorInfo, true));
                return ['success' => false, 'message' => 'Failed to save company settings'];
            }
            
        } catch (PDOException $e) {
            error_log("PDO Error saving company settings: " . $e->getMessage());
            error_log("PDO Error Code: " . $e->getCode());
            return ['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("Unexpected error saving company settings: " . $e->getMessage());
            return ['success' => false, 'message' => 'An unexpected error occurred'];
        }
    }
    
    /**
     * Upload business logo
     */
    public function uploadBusinessLogo($file, $manufacturerId) {
        try {
            // Validate file
            if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'message' => 'File upload error'];
            }
            
            // Check file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes)) {
                return ['success' => false, 'message' => 'Only JPG, PNG, and GIF files are allowed'];
            }
            
            // Check file size (max 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                return ['success' => false, 'message' => 'File size must be less than 5MB'];
            }
            
            // Create upload directory
            $uploadDir = '../assets/images/company_logos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . $manufacturerId . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Update database
                $relativePath = 'assets/images/company_logos/' . $filename;
                $stmt = $this->pdo->prepare("
                    UPDATE company_settings 
                    SET business_logo = ?, updated_at = NOW() 
                    WHERE manufacturer_id = ?
                ");
                
                if ($stmt->execute([$relativePath, $manufacturerId])) {
                    return ['success' => true, 'message' => 'Logo uploaded successfully', 'path' => $relativePath];
                } else {
                    // Remove uploaded file if database update fails
                    unlink($filepath);
                    return ['success' => false, 'message' => 'Failed to update database'];
                }
            } else {
                return ['success' => false, 'message' => 'Failed to move uploaded file'];
            }
            
        } catch (Exception $e) {
            error_log("Error uploading business logo: " . $e->getMessage());
            return ['success' => false, 'message' => 'Upload error occurred'];
        }
    }
    
    /**
     * Validate company data
     */
    public function validateCompanyData($data) {
        $errors = [];
        
        // Required fields
        if (empty(trim($data['business_name']))) {
            $errors[] = 'Business name is required';
        }
        
        if (empty(trim($data['business_type']))) {
            $errors[] = 'Business type is required';
        }
        
        if (empty(trim($data['business_address']))) {
            $errors[] = 'Business address is required';
        }
        
        if (empty(trim($data['district_province']))) {
            $errors[] = 'District/Province is required';
        }
        
        if (empty(trim($data['contact_number']))) {
            $errors[] = 'Contact number is required';
        }
        
        if (empty(trim($data['business_email']))) {
            $errors[] = 'Business email is required';
        }
        
        // Email validation
        if (!empty($data['business_email']) && !filter_var($data['business_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid business email format';
        }
        
        // URL validation for website and social links
        if (!empty($data['website']) && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid website URL format';
        }
        
        if (!empty($data['facebook_page']) && !filter_var($data['facebook_page'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid Facebook page URL format';
        }
        
        if (!empty($data['linkedin_page']) && !filter_var($data['linkedin_page'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid LinkedIn page URL format';
        }
        
        // Number validation
        if (!empty($data['number_of_employees']) && (!is_numeric($data['number_of_employees']) || $data['number_of_employees'] < 1)) {
            $errors[] = 'Number of employees must be a positive number';
        }
        
        if (!empty($data['years_in_operation']) && (!is_numeric($data['years_in_operation']) || $data['years_in_operation'] < 0)) {
            $errors[] = 'Years in operation must be a non-negative number';
        }
        
        return $errors;
    }
    
    /**
     * Get business types for dropdown
     */
    public function getBusinessTypes() {
        return [
            'Garment Manufacturer' => 'Garment Manufacturer',
            'Textile Exporter' => 'Textile Exporter',
            'Fashion Designer' => 'Fashion Designer',
            'Clothing Retailer' => 'Clothing Retailer',
            'Fabric Supplier' => 'Fabric Supplier',
            'Accessories Manufacturer' => 'Accessories Manufacturer',
            'Footwear Manufacturer' => 'Footwear Manufacturer',
            'Leather Goods Manufacturer' => 'Leather Goods Manufacturer',
            'Other' => 'Other'
        ];
    }
    
    /**
     * Get Sri Lankan provinces for dropdown
     */
    public function getProvinces() {
        return [
            'Western Province' => 'Western Province',
            'Central Province' => 'Central Province',
            'Southern Province' => 'Southern Province',
            'Northern Province' => 'Northern Province',
            'Eastern Province' => 'Eastern Province',
            'North Western Province' => 'North Western Province',
            'North Central Province' => 'North Central Province',
            'Uva Province' => 'Uva Province',
            'Sabaragamuwa Province' => 'Sabaragamuwa Province'
        ];
    }
    
    /**
     * Delete business logo
     */
    public function deleteBusinessLogo($manufacturerId) {
        try {
            // Get current logo path
            $settings = $this->getCompanySettings($manufacturerId);
            if ($settings && $settings['business_logo']) {
                $logoPath = '../' . $settings['business_logo'];
                if (file_exists($logoPath)) {
                    unlink($logoPath);
                }
            }
            
            // Update database
            $stmt = $this->pdo->prepare("
                UPDATE company_settings 
                SET business_logo = NULL, updated_at = NOW() 
                WHERE manufacturer_id = ?
            ");
            
            return ['success' => $stmt->execute([$manufacturerId]), 'message' => 'Logo deleted successfully'];
            
        } catch (Exception $e) {
            error_log("Error deleting business logo: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error deleting logo'];
        }
    }
}
?> 