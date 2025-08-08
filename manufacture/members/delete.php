<?php
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../includes/Member.class.php';

// Get the current manufacturer ID
$user_id = $_SESSION['manufacturer_id'] ?? ($user_data['id'] ?? null);

// Validate that we have a valid user_id
if (!$user_id) {
    die("Error: No valid manufacturer ID found. Please log in again.");
}

// Initialize Member class
$member = new Member($pdo);

// Handle POST request for deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_POST['member_id'] ?? null;
    
    if ($member_id) {
        $result = $member->deleteMember($member_id, $user_id);
        
        if ($result['success']) {
            $_SESSION['member_message'] = $result['message'];
        } else {
            $_SESSION['member_message'] = $result['message'];
        }
    } else {
        $_SESSION['member_message'] = 'Invalid member ID';
    }
    
    // Redirect back to team page
    header('Location: team.php');
    exit();
} else {
    // If not POST, redirect to team page
    header('Location: team.php');
    exit();
}
?>
