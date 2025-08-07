<?php
require_once '../../config/database.php';
require_once '../includes/company.class.php';
require_once '../includes/Manufacturer.class.php';

// Check if user is logged in
if (!Manufacturer::isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$manufacturer_id = Manufacturer::getCurrentUserId();

// Debug: Log user information
error_log("Manufacturer ID: " . $manufacturer_id);
error_log("User logged in: " . (Manufacturer::isLoggedIn() ? "Yes" : "No"));

// Initialize Company class
$company = new Company();
$companyData = $company->getCompanySettings($manufacturer_id);
$businessTypes = $company->getBusinessTypes();
$provinces = $company->getProvinces();

// Handle case where company data is not found or query fails
if (!$companyData || !is_array($companyData)) {
    $companyData = [
        'business_name' => '',
        'business_type' => '',
        'registration_number' => '',
        'business_address' => '',
        'district_province' => '',
        'contact_number' => '',
        'business_email' => '',
        'website' => '',
        'facebook_page' => '',
        'linkedin_page' => '',
        'number_of_employees' => '',
        'business_logo' => '',
        'years_in_operation' => '',
        'certifications' => '',
        'description_bio' => ''
    ];
}

$message = '';
$messageType = '';

// Debug: Log all POST requests
error_log("POST request received: " . print_r($_POST, true));
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_company':
                // Debug: Log the POST data
                error_log("POST data received: " . print_r($_POST, true));
                
                $companyFormData = [
                    'business_name' => trim($_POST['business_name'] ?? ''),
                    'business_type' => trim($_POST['business_type'] ?? ''),
                    'registration_number' => trim($_POST['registration_number'] ?? ''),
                    'business_address' => trim($_POST['business_address'] ?? ''),
                    'district_province' => trim($_POST['district_province'] ?? ''),
                    'contact_number' => trim($_POST['contact_number'] ?? ''),
                    'business_email' => trim($_POST['business_email'] ?? ''),
                    'website' => trim($_POST['website'] ?? ''),
                    'facebook_page' => trim($_POST['facebook_page'] ?? ''),
                    'linkedin_page' => trim($_POST['linkedin_page'] ?? ''),
                    'number_of_employees' => !empty($_POST['number_of_employees']) ? (int)$_POST['number_of_employees'] : null,
                    'years_in_operation' => !empty($_POST['years_in_operation']) ? (int)$_POST['years_in_operation'] : null,
                    'certifications' => trim($_POST['certifications'] ?? ''),
                    'description_bio' => trim($_POST['description_bio'] ?? '')
                ];
                
                // Debug: Log the form data
                error_log("Company form data: " . print_r($companyFormData, true));
                error_log("Manufacturer ID: " . $manufacturer_id);
                
                $errors = $company->validateCompanyData($companyFormData);
                if (empty($errors)) {
                    $result = $company->saveCompanySettings($manufacturer_id, $companyFormData);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';
                    
                    // Debug: Log the result
                    error_log("Save result: " . print_r($result, true));
                    
                    if ($result['success']) {
                        $updatedData = $company->getCompanySettings($manufacturer_id);
                        if ($updatedData && is_array($updatedData)) {
                            $companyData = $updatedData;
                        }
                    }
                } else {
                    $message = implode('<br>', $errors);
                    $messageType = 'error';
                    // Debug: Log validation errors
                    error_log("Validation errors: " . print_r($errors, true));
                }
                break;

            case 'upload_logo':
                if (isset($_FILES['business_logo']) && $_FILES['business_logo']['error'] === UPLOAD_ERR_OK) {
                    $result = $company->uploadBusinessLogo($_FILES['business_logo'], $manufacturer_id);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';
                    if ($result['success']) {
                        $updatedData = $company->getCompanySettings($manufacturer_id);
                        if ($updatedData && is_array($updatedData)) {
                            $companyData = $updatedData;
                        }
                    }
                } else {
                    $message = 'Please select a valid image file';
                    $messageType = 'error';
                }
                break;

            case 'delete_logo':
                $result = $company->deleteBusinessLogo($manufacturer_id);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                if ($result['success']) {
                    $updatedData = $company->getCompanySettings($manufacturer_id);
                    if ($updatedData && is_array($updatedData)) {
                        $companyData = $updatedData;
                    }
                }
                break;
        }
    }
}

// Include header
require_once '../components/header.php';
?>

<!-- Include company-specific CSS and JS -->
<link rel="stylesheet" href="../assets/css/company.css">
<script src="../assets/js/company.js" defer></script>

<?php
?>

<div class="company-container">
    <?php include '../components/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="company-header">
            <h1><i class="fas fa-building me-2"></i>Company Settings</h1>
            <p>Manage your business information and company profile</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Company Information</h5>
                    </div>
                    <div class="card-body">
                        <!-- Company Logo Section -->
                        <div class="text-center mb-4">
                            <div class="company-logo-container">
                                <?php 
                                $logoSrc = '../assets/images/default-company-logo.png';
                                if (isset($companyData['business_logo']) && $companyData['business_logo'] && file_exists('../' . $companyData['business_logo'])) {
                                    $logoSrc = '../' . $companyData['business_logo'];
                                }
                                ?>
                                <img src="<?php echo $logoSrc; ?>" 
                                     alt="Company Logo" class="company-logo" id="companyLogo"
                                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDE1MCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxNTAiIGhlaWdodD0iMTUwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik0yNSAzMEgxMjVWMTAwSDEyNVYxMjBIMjVWMzBaIiBmaWxsPSIjQ0NDIi8+CjxwYXRoIGQ9Ik0yNSAxMjBIMTI1VjEwMEgyNVYxMjBaIiBmaWxsPSIjQ0NDIi8+Cjwvc3ZnPgo='">
                                <label for="logoUpload" class="logo-upload-btn">
                                    <i class="fas fa-camera"></i>
                                </label>
                            </div>
                            <form method="POST" enctype="multipart/form-data" id="logoForm" style="display: none;">
                                <input type="hidden" name="action" value="upload_logo">
                                <input type="file" name="business_logo" id="logoUpload" accept="image/*">
                            </form>
                            <div class="mt-2">
                                <small class="text-muted">Click the camera icon to upload your company logo</small>
                            </div>
                        </div>

                        <!-- Company Form -->
                        <form method="POST" class="company-form">
                            <input type="hidden" name="action" value="save_company">
                            
                            <div class="form-section">
                                <div class="section-header">
                                    <h6><i class="fas fa-building me-2"></i>Basic Information</h6>
                                    <p class="text-muted">Essential business details</p>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="business_name" class="form-label">
                                            <i class="fas fa-building me-1"></i>Business Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="business_name" name="business_name" 
                                               value="<?php echo htmlspecialchars($companyData['business_name'] ?? ''); ?>" 
                                               placeholder="Enter your business name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="business_type" class="form-label">
                                            <i class="fas fa-industry me-1"></i>Business Type <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="business_type" name="business_type" required>
                                            <option value="">Select business type</option>
                                            <?php foreach ($businessTypes as $value => $label): ?>
                                                <option value="<?php echo $value; ?>" <?php echo ($companyData['business_type'] ?? '') === $value ? 'selected' : ''; ?>>
                                                    <?php echo $label; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="registration_number" class="form-label">
                                            <i class="fas fa-id-card me-1"></i>Registration Number
                                        </label>
                                        <input type="text" class="form-control" id="registration_number" name="registration_number" 
                                               value="<?php echo htmlspecialchars($companyData['registration_number'] ?? ''); ?>" 
                                               placeholder="e.g., REG123456789">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="years_in_operation" class="form-label">
                                            <i class="fas fa-calendar-alt me-1"></i>Years in Operation
                                        </label>
                                        <input type="number" class="form-control" id="years_in_operation" name="years_in_operation" 
                                               value="<?php echo htmlspecialchars($companyData['years_in_operation'] ?? ''); ?>" 
                                               placeholder="e.g., 10" min="0" max="100">
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="section-header">
                                    <h6><i class="fas fa-map-marker-alt me-2"></i>Location & Contact</h6>
                                    <p class="text-muted">Business address and contact information</p>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="business_address" class="form-label">
                                            <i class="fas fa-map-marker-alt me-1"></i>Business Address <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" id="business_address" name="business_address" rows="3" 
                                                  placeholder="Enter your complete business address" required><?php echo htmlspecialchars($companyData['business_address'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="district_province" class="form-label">
                                            <i class="fas fa-map me-1"></i>District/Province <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="district_province" name="district_province" required>
                                            <option value="">Select province</option>
                                            <?php foreach ($provinces as $value => $label): ?>
                                                <option value="<?php echo $value; ?>" <?php echo ($companyData['district_province'] ?? '') === $value ? 'selected' : ''; ?>>
                                                    <?php echo $label; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="contact_number" class="form-label">
                                            <i class="fas fa-phone me-1"></i>Contact Number <span class="text-danger">*</span>
                                        </label>
                                        <input type="tel" class="form-control" id="contact_number" name="contact_number" 
                                               value="<?php echo htmlspecialchars($companyData['contact_number'] ?? ''); ?>" 
                                               placeholder="+94 11 234 5678" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="business_email" class="form-label">
                                            <i class="fas fa-envelope me-1"></i>Business Email <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" class="form-control" id="business_email" name="business_email" 
                                               value="<?php echo htmlspecialchars($companyData['business_email'] ?? ''); ?>" 
                                               placeholder="info@yourcompany.com" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="number_of_employees" class="form-label">
                                            <i class="fas fa-users me-1"></i>Number of Employees
                                        </label>
                                        <input type="number" class="form-control" id="number_of_employees" name="number_of_employees" 
                                               value="<?php echo htmlspecialchars($companyData['number_of_employees'] ?? ''); ?>" 
                                               placeholder="e.g., 150" min="1">
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="section-header">
                                    <h6><i class="fas fa-globe me-2"></i>Online Presence</h6>
                                    <p class="text-muted">Website and social media links</p>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="website" class="form-label">
                                            <i class="fas fa-globe me-1"></i>Website
                                        </label>
                                        <input type="url" class="form-control" id="website" name="website" 
                                               value="<?php echo htmlspecialchars($companyData['website'] ?? ''); ?>" 
                                               placeholder="https://www.yourcompany.com">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="facebook_page" class="form-label">
                                            <i class="fab fa-facebook me-1"></i>Facebook Page
                                        </label>
                                        <input type="url" class="form-control" id="facebook_page" name="facebook_page" 
                                               value="<?php echo htmlspecialchars($companyData['facebook_page'] ?? ''); ?>" 
                                               placeholder="https://facebook.com/yourcompany">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="linkedin_page" class="form-label">
                                            <i class="fab fa-linkedin me-1"></i>LinkedIn Page
                                        </label>
                                        <input type="url" class="form-control" id="linkedin_page" name="linkedin_page" 
                                               value="<?php echo htmlspecialchars($companyData['linkedin_page'] ?? ''); ?>" 
                                               placeholder="https://linkedin.com/company/yourcompany">
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="section-header">
                                    <h6><i class="fas fa-certificate me-2"></i>Additional Information</h6>
                                    <p class="text-muted">Certifications and company description</p>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="certifications" class="form-label">
                                            <i class="fas fa-award me-1"></i>Certifications
                                        </label>
                                        <textarea class="form-control" id="certifications" name="certifications" rows="2" 
                                                  placeholder="e.g., ISO 9001:2015, Fair Trade Certified, GOTS Certified"><?php echo htmlspecialchars($companyData['certifications'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="description_bio" class="form-label">
                                            <i class="fas fa-file-alt me-1"></i>Company Description / Bio
                                        </label>
                                        <textarea class="form-control" id="description_bio" name="description_bio" rows="4" 
                                                  placeholder="Tell us about your company, your expertise, and what makes you unique..."><?php echo htmlspecialchars($companyData['description_bio'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Save Company Settings
                                </button>
                                <button type="reset" class="btn btn-outline-secondary btn-lg ms-2">
                                    <i class="fas fa-undo me-2"></i>Reset Form
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../components/footer.php'; ?> 