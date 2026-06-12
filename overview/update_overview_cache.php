<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Berlin');

require_once __DIR__ . '/../config/mysql.php';
require_once __DIR__ . '/helpers.php';

$conn = MySQLDatabase::connect();
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function getSourceData(string $type): array {
    $_GET['type'] = $type;
    return include __DIR__ . '/getOverviewData_source.php';
}

// Delete old cache data except today's from all four tables
function cleanOldCache(PDO $conn, array $tables) {
    foreach ($tables as $table) {
        $stmt = $conn->prepare("DELETE FROM $table WHERE DATE(updated_at) != CURDATE()");
        $stmt->execute();
        echo "🧹 Old data deleted from `$table`\n";
    }
}

// Define your cache tables
$cacheTables = ['ewh_ewh', 'ewh_fv', 'ewh_ev', 'ewh_hlm'];

// Step 1: Clean old data
cleanOldCache($conn, $cacheTables);

// Step 2: Update with fresh cache
saveToCache($conn, 'ewh_ewh', getSourceData('ewh'));
saveToCache($conn, 'ewh_fv', getSourceData('fv'));
saveToCache($conn, 'ewh_ev', getSourceData('ev'));
saveToCache($conn, 'ewh_hlm', getSourceData('hlm'));

echo "✅ All caches updated successfully.\n";
