<?php
require_once __DIR__ . '/includes/Manufacturer.class.php';
header('Content-Type: application/json');

$email = $_GET['email'] ?? '';

if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit();
}

$manufacturer = new Manufacturer();

try {
    // Check if email exists
    $stmt = $manufacturer->pdo->prepare("SELECT * FROM manufacturers WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Email not found']);
        exit();
    }

    // Generate new OTP
    $otp = rand(100000, 999999);
    $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Update the verification code
    $updateStmt = $manufacturer->pdo->prepare("UPDATE manufacturers SET verification_code = ?, code_expiry = ? WHERE email = ?");
    $updateStmt->execute([$otp, $expiry, $email]);

    // Send OTP email
    $emailSent = $manufacturer->sendVerificationEmail($email, $otp);
    
    if (!$emailSent) {
        throw new Exception('Failed to send email');
    }

    echo json_encode(['success' => true, 'message' => 'New verification code sent']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>