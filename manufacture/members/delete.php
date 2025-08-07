<?php
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../includes/Member.class.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: team.php');
    exit();
}

// Check if member_id is provided
if (!isset($_POST['member_id']) || empty($_POST['member_id'])) {
    $_SESSION['member_message'] = 'Invalid member ID';
    header('Location: team.php');
    exit();
}

$member_id = (int)$_POST['member_id'];

// Initialize Member class
$member = new Member($pdo);

// Delete the member
$result = $member->deleteMember($member_id, $user_id);

if ($result['success']) {
    $_SESSION['member_message'] = $result['message'];
} else {
    $_SESSION['member_message'] = $result['message'];
}

// Redirect back to team page
header('Location: team.php');
exit();
?>
