<?php
/**
 * Download Logs Script
 * Allows downloading logs as text file
 */

$baseDir = __DIR__ . '/logs';

// Get client ID
$clientId = isset($_GET['client']) ? trim($_GET['client']) : '';

if (empty($clientId)) {
    die('Missing client ID');
}

// Sanitize
$clientId = preg_replace('/[^a-zA-Z0-9\-_:]/', '', $clientId);
$logFile = $baseDir . '/' . $clientId . '/log.txt';

if (!file_exists($logFile)) {
    die('No logs found for this client');
}

// Set headers for download
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="' . $clientId . '_logs_' . date('Y-m-d') . '.txt"');
header('Content-Length: ' . filesize($logFile));

// Output file
readfile($logFile);
exit();
?>
