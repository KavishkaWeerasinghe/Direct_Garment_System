<?php
// Disable error reporting to prevent HTML errors in JSON response
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../includes/db_connection.php';

// Function to log errors
function logError($message) {
    $logFile = '../logs/member_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    error_log($logMessage, 3, $logFile);
}

// Set JSON header for all responses
header('Content-Type: application/json');

// Function to send JSON response
function sendJsonResponse($success, $message = '', $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Verify database connection
if (!isset($conn) || $conn->connect_error) {
    logError("Database connection failed: " . ($conn->connect_error ?? "Connection not established"));
    sendJsonResponse(false, 'Database connection failed');
}

// Debug session data
logError("Session data: " . print_r($_SESSION, true));

// Verify manufacturer authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Manufacture') {
    logError("Authentication failed: Invalid user role or not logged in. Session data: " . print_r($_SESSION, true));
    sendJsonResponse(false, 'Not authenticated. Please log in as a manufacturer.');
}

// Get the current manufacturer's ID from session
$manufacture_id = $_SESSION['user_id'];

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_members':
        try {
            // Debug log
            logError("Getting members for manufacturer ID: " . $manufacture_id);
            
            $stmt = $conn->prepare("
                SELECT m.id, m.manufacture_id, m.staff_id, m.status, u.name, u.email, u.role, u.profile_photo 
                FROM member m
                JOIN users u ON m.staff_id = u.id
                WHERE m.manufacture_id = ? 
                ORDER BY u.name ASC
            ");
            
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            
            $stmt->bind_param("i", $manufacture_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute statement: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            if (!$result) {
                throw new Exception("Failed to get result: " . $stmt->error);
            }
            
            $members = [];
            while ($row = $result->fetch_assoc()) {
                $members[] = $row;
            }
            
            logError("Found " . count($members) . " members");
            sendJsonResponse(true, '', $members);
            
        } catch (Exception $e) {
            logError("Error getting members: " . $e->getMessage());
            sendJsonResponse(false, 'Error retrieving members: ' . $e->getMessage());
        }
        break;

    case 'get_member':
        try {
            $id = $_GET['id'] ?? 0;
            
            $stmt = $conn->prepare("
                SELECT m.id, m.manufacture_id, m.staff_id, m.status, u.name, u.email, u.role
                FROM member m
                JOIN users u ON m.staff_id = u.id
                WHERE m.id = ? AND m.manufacture_id = ?
            ");
            $stmt->bind_param("ii", $id, $manufacture_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($member = $result->fetch_assoc()) {
                sendJsonResponse(true, '', $member);
            } else {
                sendJsonResponse(false, 'Member not found');
            }
        } catch (Exception $e) {
            sendJsonResponse(false, 'Error getting member: ' . $e->getMessage());
        }
        break;

    case 'add':
        try {
            logError("Starting add member operation");
            logError("POST data: " . print_r($_POST, true));

            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'staff';
            $status = $_POST['status'] ?? 'active';

            logError("Parsed data - Name: $name, Email: $email, Role: $role, Status: $status");

            if (empty($name) || empty($email) || empty($password)) {
                throw new Exception('All fields are required');
            }

            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare email check statement: " . $conn->error);
            }
            
            $stmt->bind_param("s", $email);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute email check: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                throw new Exception('Email already exists');
            }

            // Start transaction
            $conn->begin_transaction();
            logError("Transaction started");

            try {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                logError("Password hashed successfully");

                // Insert into users table
                $stmt = $conn->prepare("
                    INSERT INTO users (name, email, password, role) 
                    VALUES (?, ?, ?, 'Staff')
                ");
                
                if (!$stmt) {
                    logError("SQL Error: " . $conn->error);
                    throw new Exception("Failed to prepare user insert statement: " . $conn->error);
                }
                
                $stmt->bind_param("sss", $name, $email, $hashed_password);
                if (!$stmt->execute()) {
                    logError("Execute Error: " . $stmt->error);
                    throw new Exception("Failed to insert user: " . $stmt->error);
                }
                
                $staff_id = $conn->insert_id;
                logError("User inserted successfully with ID: $staff_id");

                // Insert into member table with status
                $stmt = $conn->prepare("
                    INSERT INTO member (manufacture_id, staff_id, status) 
                    VALUES (?, ?, ?)
                ");
                
                if (!$stmt) {
                    throw new Exception("Failed to prepare member insert statement: " . $conn->error);
                }
                
                $stmt->bind_param("iis", $manufacture_id, $staff_id, $status);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert member: " . $stmt->error);
                }

                $conn->commit();
                logError("Transaction committed successfully");
                sendJsonResponse(true, 'Member added successfully');
            } catch (Exception $e) {
                $conn->rollback();
                logError("Transaction rolled back due to error: " . $e->getMessage());
                throw $e;
            }
        } catch (Exception $e) {
            logError("Error in add member operation: " . $e->getMessage());
            logError("Stack trace: " . $e->getTraceAsString());
            sendJsonResponse(false, $e->getMessage());
        }
        break;

    case 'update':
        try {
            $id = $_POST['id'] ?? 0;
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = 'staff'; // Always set to staff
            $status = $_POST['status'] ?? 'active';

            // Debug log
            logError("Update data received - ID: $id, Name: $name, Email: $email, Status: $status");

            if (empty($id) || empty($name) || empty($email)) {
                throw new Exception('Required fields cannot be empty');
            }

            // Get staff_id from member table
            $stmt = $conn->prepare("SELECT staff_id FROM member WHERE id = ? AND manufacture_id = ?");
            $stmt->bind_param("ii", $id, $manufacture_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $member = $result->fetch_assoc();

            if (!$member) {
                throw new Exception('Member not found');
            }

            $staff_id = $member['staff_id'];

            // Check if email exists for other users
            $stmt = $conn->prepare("
                SELECT id FROM users 
                WHERE email = ? AND id != ?
            ");
            $stmt->bind_param("si", $email, $staff_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception('Email already exists');
            }

            // Start transaction
            $conn->begin_transaction();

            try {
                // Update users table
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET name = ?, email = ?, role = ? 
                    WHERE id = ?
                ");
                $stmt->bind_param("sssi", $name, $email, $role, $staff_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update user');
                }

                // Update member table status
                $stmt = $conn->prepare("
                    UPDATE member 
                    SET status = ? 
                    WHERE id = ? AND manufacture_id = ?
                ");
                $stmt->bind_param("sii", $status, $id, $manufacture_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update member status');
                }

                $conn->commit();
                sendJsonResponse(true, 'Member updated successfully');
            } catch (Exception $e) {
                $conn->rollback();
                throw new Exception('Error updating member: ' . $e->getMessage());
            }
        } catch (Exception $e) {
            sendJsonResponse(false, $e->getMessage());
        }
        break;

    case 'delete':
        try {
            $id = $_POST['id'] ?? 0;
            
            if (empty($id)) {
                throw new Exception('Member ID is required');
            }

            // Get staff_id from member table
            $stmt = $conn->prepare("SELECT staff_id FROM member WHERE id = ? AND manufacture_id = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            
            $stmt->bind_param("ii", $id, $manufacture_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute statement: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $member = $result->fetch_assoc();

            if (!$member) {
                throw new Exception('Member not found');
            }

            $staff_id = $member['staff_id'];

            // Don't allow deleting the last admin
            $stmt = $conn->prepare("
                SELECT COUNT(*) as admin_count 
                FROM users u
                JOIN member m ON u.id = m.staff_id
                WHERE m.manufacture_id = ? AND u.role = 'admin'
            ");
            $stmt->bind_param("i", $manufacture_id);
            $stmt->execute();
            $admin_count = $stmt->get_result()->fetch_assoc()['admin_count'];

            if ($admin_count <= 1) {
                $stmt = $conn->prepare("
                    SELECT role 
                    FROM users 
                    WHERE id = ?
                ");
                $stmt->bind_param("i", $staff_id);
                $stmt->execute();
                $member_role = $stmt->get_result()->fetch_assoc()['role'];

                if ($member_role === 'admin') {
                    throw new Exception('Cannot delete the last admin member');
                }
            }

            // Start transaction
            $conn->begin_transaction();

            try {
                // Delete from member table
                $stmt = $conn->prepare("DELETE FROM member WHERE id = ? AND manufacture_id = ?");
                if (!$stmt) {
                    throw new Exception("Failed to prepare member delete statement: " . $conn->error);
                }
                
                $stmt->bind_param("ii", $id, $manufacture_id);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to delete from member table: " . $stmt->error);
                }

                // Delete from users table
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                if (!$stmt) {
                    throw new Exception("Failed to prepare user delete statement: " . $conn->error);
                }
                
                $stmt->bind_param("i", $staff_id);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to delete from users table: " . $stmt->error);
                }

                $conn->commit();
                sendJsonResponse(true, 'Member deleted successfully');
            } catch (Exception $e) {
                $conn->rollback();
                throw new Exception('Error deleting member: ' . $e->getMessage());
            }
        } catch (Exception $e) {
            sendJsonResponse(false, $e->getMessage());
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$conn->close();
sendJsonResponse(false, 'Invalid action');
?>