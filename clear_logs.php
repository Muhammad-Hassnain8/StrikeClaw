<?php
/**
 * Clear Logs Script
 * Deletes logs for a specific client
 */

$baseDir = __DIR__ . '/logs';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

// Get client ID
$clientId = isset($_POST['client']) ? trim($_POST['client']) : '';

if (empty($clientId)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing client ID']);
    exit();
}

// Sanitize
$clientId = preg_replace('/[^a-zA-Z0-9\-_:]/', '', $clientId);
$logFile = $baseDir . '/' . $clientId . '/log.txt';

if (file_exists($logFile)) {
    if (unlink($logFile)) {
        echo json_encode(['status' => 'success', 'message' => 'Logs cleared']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete log file']);
    }
} else {
    echo json_encode(['status' => 'success', 'message' => 'No logs to clear']);
}
?>
