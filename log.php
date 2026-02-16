<?php
/**
 * Log Receiver Endpoint
 * Receives logs from clients and stores them
 */

// Configuration
$baseDir = __DIR__ . '/logs';
$maxDataSize = 10 * 1024 * 1024;  // 10 MB

// Enable CORS for cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

// Ensure base directory exists
if (!file_exists($baseDir)) {
    if (!mkdir($baseDir, 0755, true)) {
        http_response_code(500);
        die(json_encode(['status' => 'error', 'message' => 'Cannot create logs directory']));
    }
}

// Get POST data
$clientId = isset($_POST['client_id']) ? trim($_POST['client_id']) : '';
$logData = isset($_POST['data']) ? $_POST['data'] : '';

// Validate input
if (empty($clientId)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing client_id']);
    exit();
}

if (empty($logData)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit();
}

// Optional size limit
if (strlen($logData) > $maxDataSize) {
    http_response_code(413);
    echo json_encode(['status' => 'error', 'message' => 'Data too large']);
    exit();
}

// Sanitize client ID (allow only safe characters)
$clientId = preg_replace('/[^a-zA-Z0-9\-_:]/', '', $clientId);
if (empty($clientId)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid client_id']);
    exit();
}

// Create client directory if needed
$clientDir = $baseDir . '/' . $clientId;
if (!file_exists($clientDir)) {
    if (!mkdir($clientDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Cannot create client directory']);
        exit();
    }
}

// Log file path
$logFile = $clientDir . '/log.txt';

// Write data with exclusive lock
$fp = fopen($logFile, 'a');
if (!$fp) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Cannot open log file']);
    exit();
}

if (flock($fp, LOCK_EX)) {
    fwrite($fp, $logData);
    fwrite($fp, "\n--- END OF UPLOAD " . date('Y-m-d H:i:s') . " ---\n");
    flock($fp, LOCK_UN);
    fclose($fp);
    
    echo json_encode(['status' => 'success', 'message' => 'Logs saved']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Could not lock file']);
    fclose($fp);
    exit();
}
?>
