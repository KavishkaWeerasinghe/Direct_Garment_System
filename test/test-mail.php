<?php
require_once __DIR__ . '/../manufacture/includes/Manufacturer.class.php';

class EmailTest {
    public function runTests() {
        $manufacturer = new Manufacturer();
        $testEmail = 'rajithasampathv@gmail.com';
        
        echo "<h1>Email Sending Tests</h1>";
        
        // Test 1: Valid email
        echo "<h2>Test 1: Valid Email</h2>";
        $result = $manufacturer->testEmailSending($testEmail);
        $this->printResult($result);
        
        // Test 2: Invalid email
        echo "<h2>Test 2: Invalid Email</h2>";
        $result = $manufacturer->testEmailSending('invalid-email');
        $this->printResult($result, false);
    }
    
    private function printResult($result, $shouldPass = true) {
        if ($result === $shouldPass) {
            echo "<div style='color: green;'>Test passed</div>";
        } else {
            echo "<div style='color: red;'>Test failed</div>";
        }
        
        // Show logs
        $logFile = __DIR__ . '/../../mail_debug.log';
        if (file_exists($logFile)) {
            echo "<h3>Debug Log</h3>";
            echo "<pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
        }
    }
}

// Run tests
$test = new EmailTest();
$test->runTests();
?>