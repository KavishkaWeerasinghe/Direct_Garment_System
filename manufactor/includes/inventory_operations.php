<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db_connection.php';

// Function to log errors
function logError($message) {
    $log_dir = dirname(__FILE__) . '/logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    $log_file = $log_dir . '/inventory_errors.log';
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, $log_file);
}

// Get all products for dropdown
function getProducts() {
    global $conn;
    $products = [];
    
    $query = "SELECT id, product_name FROM product ORDER BY product_name ASC";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    } else {
        logError("Error fetching products: " . $conn->error);
    }
    
    return $products;
}

// Generate batch number
function generateBatchNumber() {
    global $conn;
    
    try {
        // Get the last batch ID and increment it
        $query = "SELECT MAX(CAST(SUBSTRING_INDEX(batch_id, '-', -1) AS UNSIGNED)) as last_id FROM inventory";
        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception("Error getting last batch ID: " . $conn->error);
        }
        
        $row = $result->fetch_assoc();
        $next_id = ($row['last_id'] ?? 0) + 1;
        
        // Format: BATCH-YYYYMMDD-XXXX (where XXXX is the incremental ID)
        return 'BATCH-' . date('Ymd') . '-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        logError("Error generating batch number: " . $e->getMessage());
        throw $e;
    }
}

// Add new batch
function addBatch($batchData) {
    global $conn;
    
    try {
        // Log incoming data
        logError("Adding batch with data: " . json_encode($batchData));
        
        // Validate required fields
        $required = ['product_id', 'quantity', 'manufacture_date', 'expiry_date'];
        foreach ($required as $field) {
            if (empty($batchData[$field])) {
                throw new Exception("$field is required");
            }
        }
        
        // Validate dates
        if (strtotime($batchData['manufacture_date']) > strtotime($batchData['expiry_date'])) {
            throw new Exception("Manufacture date cannot be after expiry date");
        }
        
        // Calculate status based on quantity and expiry date
        $status = 'available';
        $currentDate = date('Y-m-d');
        
        if ($batchData['expiry_date'] < $currentDate) {
            $status = 'expired';
        } elseif ($batchData['quantity'] < 10) {
            $status = 'low_stock';
        }
        
        // Generate batch number
        $batch_number = generateBatchNumber();
        logError("Generated batch number: " . $batch_number);
        
        // Insert batch
        $stmt = $conn->prepare("INSERT INTO inventory 
            (product_id, batch_id, quantity, manufacture_date, expiry_date, status) 
            VALUES (?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("isisss", 
            $batchData['product_id'],
            $batch_number,
            $batchData['quantity'],
            $batchData['manufacture_date'],
            $batchData['expiry_date'],
            $status
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        logError("Batch added successfully with ID: " . $stmt->insert_id);
        
        return [
            'success' => true,
            'message' => 'Batch added successfully',
            'batch_id' => $stmt->insert_id
        ];
        
    } catch (Exception $e) {
        logError("Error in addBatch: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Update batch
function updateBatch($batchData) {
    global $conn;
    
    try {
        // Validate required fields
        $required = ['batch_id', 'product_id', 'quantity', 'manufacture_date', 'expiry_date'];
        foreach ($required as $field) {
            if (empty($batchData[$field])) {
                throw new Exception("$field is required");
            }
        }
        
        // Calculate status based on quantity and expiry date
        $status = 'available';
        $currentDate = date('Y-m-d');
        
        if ($batchData['expiry_date'] < $currentDate) {
            $status = 'expired';
        } elseif ($batchData['quantity'] < 10) {
            $status = 'low_stock';
        }
        
        // Update batch
        $stmt = $conn->prepare("UPDATE inventory SET 
            product_id = ?,
            quantity = ?,
            manufacture_date = ?,
            expiry_date = ?,
            status = ?
            WHERE batch_id = ?");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("isssss", 
            $batchData['product_id'],
            $batchData['quantity'],
            $batchData['manufacture_date'],
            $batchData['expiry_date'],
            $status,
            $batchData['batch_id']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        return [
            'success' => true,
            'message' => 'Batch updated successfully'
        ];
        
    } catch (Exception $e) {
        logError("Error in updateBatch: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Delete batch
function deleteBatch($batch_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("DELETE FROM inventory WHERE batch_id = ?");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $batch_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        return [
            'success' => true,
            'message' => 'Batch deleted successfully'
        ];
        
    } catch (Exception $e) {
        logError("Error in deleteBatch: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Get all batches with product names
function getBatches() {
    global $conn;
    $batches = [];
    
    $query = "SELECT i.*, p.product_name 
              FROM inventory i
              JOIN product p ON i.product_id = p.id
              ORDER BY i.batch_id DESC";
    
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $batches[] = $row;
        }
    } else {
        logError("Error fetching batches: " . $conn->error);
    }
    
    return $batches;
}

// Get single batch by ID
function getBatch($batch_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT i.*, p.product_name 
                               FROM inventory i
                               JOIN product p ON i.product_id = p.id
                               WHERE i.batch_id = ?");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $batch_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $batch = $result->fetch_assoc();
        
        if (!$batch) {
            logError("Batch not found with ID: " . $batch_id);
            throw new Exception("Batch not found");
        }
        
        // Log the batch data for debugging
        logError("Retrieved batch data: " . json_encode($batch));
        
        return $batch;
        
    } catch (Exception $e) {
        logError("Error in getBatch: " . $e->getMessage());
        return null;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    try {
        if (!isset($_POST['action'])) {
            throw new Exception("No action specified");
        }
        
        // Log the incoming request
        logError("Received POST request with action: " . $_POST['action']);
        logError("POST data: " . json_encode($_POST));
        
        switch ($_POST['action']) {
            case 'add':
                $batchData = [
                    'product_id' => intval($_POST['product_id']),
                    'quantity' => intval($_POST['quantity']),
                    'manufacture_date' => $_POST['manufacture_date'],
                    'expiry_date' => $_POST['expiry_date']
                ];
                
                $response = addBatch($batchData);
                break;
                
            case 'update':
                $batchData = [
                    'batch_id' => $_POST['batch_id'],
                    'product_id' => intval($_POST['product_id']),
                    'quantity' => intval($_POST['quantity']),
                    'manufacture_date' => $_POST['manufacture_date'],
                    'expiry_date' => $_POST['expiry_date']
                ];
                
                $response = updateBatch($batchData);
                break;
                
            case 'delete':
                if (!isset($_POST['batch_id'])) {
                    throw new Exception("Batch ID is required");
                }
                
                $response = deleteBatch($_POST['batch_id']);
                break;
                
            default:
                throw new Exception("Invalid action");
        }
        
    } catch (Exception $e) {
        logError("Error processing request: " . $e->getMessage());
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
        
        switch ($_GET['action']) {
            case 'get_batches':
                echo json_encode(getBatches());
                break;
                
            case 'get_products':
                echo json_encode(getProducts());
                break;
                
            case 'get_batch':
                if (!isset($_GET['batch_id'])) {
                    echo json_encode(['success' => false, 'message' => 'Batch ID required']);
                    break;
                }
                echo json_encode(getBatch($_GET['batch_id']));
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        exit;
    }
}
?>