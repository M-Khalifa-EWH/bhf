<?php
// getOverviewData_ev.php - reads from ewh_ev_cache
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Berlin');

require_once __DIR__ . '/../config/mysql.php';

$conn = MySQLDatabase::connect();
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch the latest cached data from DB
$sql = "SELECT data_json FROM ewh_ev ORDER BY updated_at DESC LIMIT 1";
$json = $conn->query($sql)->fetchColumn();

// Output as JSON
header('Content-Type: application/json');
echo $json;
