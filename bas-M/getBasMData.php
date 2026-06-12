<?php
require_once __DIR__ . '/../config/mysql.php';
$connect = MySQLDatabase::connect();

function fetchQuery($connect, $query) {
    return $connect->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

function createChartData($data, &$counts, &$averages) {
    foreach ($data as $row) {
        $counts[$row['mn']] = $row['bas'];
        $averages[$row['mn']] = round($row['avg'], 1);
    }
}

function generateMinuteKeys($minutes = 60) {
    $keys = [];
    $now = new DateTime();
    for ($i = $minutes - 1; $i >= 0; $i--) {
        $time = clone $now;
        $time->modify("-$i minutes");
        $keys[$time->format('H:i')] = 0;
    }
    return $keys;
}

// Initialize minute keys
$minuteKeys = generateMinuteKeys();
$basmnbas = $basmnAMA = $basmnHC = $basmnC24Bhub = $basmnC24Direct = $basmnINVIA = $basmnEV = $avgmin = $minuteKeys;

// Fetch per-minute data
$basminute = fetchQuery($connect, "
    SELECT
        SUM(CASE WHEN usr = '10090' THEN 1 ELSE 0 END) AS `10090 AMA`,
        SUM(CASE WHEN usr = '12100' THEN 1 ELSE 0 END) AS `12100 HC`,
        SUM(CASE WHEN usr = '12000' THEN 1 ELSE 0 END) AS `12000 C24 BHUB`,
        SUM(CASE WHEN usr = '12001' THEN 1 ELSE 0 END) AS `12001 C24 Direkt`,
        SUM(CASE WHEN usr = '12300' THEN 1 ELSE 0 END) AS `12300 INVIA`,
        SUM(CASE WHEN usr = '1000' THEN 1 ELSE 0 END) AS `1000 EV`,
        COUNT(bas) AS `ALL`,
        DATE_FORMAT(datum, '%H:%i') AS mn
    FROM bastix
    WHERE datum >= DATE_SUB(NOW(), INTERVAL 60 MINUTE)
    GROUP BY mn
    ORDER BY mn ASC
");

foreach ($basminute as $row) {
    $mn = $row['mn'];
    $basmnbas[$mn] = $row['ALL'];
    $basmnAMA[$mn] = $row['10090 AMA'];
    $basmnHC[$mn] = $row['12100 HC'];
    $basmnC24Bhub[$mn] = $row['12000 C24 BHUB'];
    $basmnC24Direct[$mn] = $row['12001 C24 Direkt'];
    $basmnINVIA[$mn] = $row['12300 INVIA'];
    $basmnEV[$mn] = $row['1000 EV'];
}

$avgminute = fetchQuery($connect, "
    SELECT
        DATE_FORMAT(datum, '%H:%i') AS Mn,
        AVG(TIMESTAMPDIFF(SECOND, startt, endd)) AS avg
    FROM bastix
    WHERE datum > DATE_SUB(NOW(), INTERVAL 60 MINUTE)
        AND startt IS NOT NULL AND endd IS NOT NULL AND success = 1
    GROUP BY Mn
");

foreach ($avgminute as $row) {
    $avgmin[$row['Mn']] = round($row['avg'], 1);
}

$chartData = [];
$now = new DateTime();
$start = (clone $now)->modify('-23 hours');
$interval = 345;

for ($i = 0; $i < 4; $i++) {
    $from = (clone $start)->modify("+" . ($i * $interval) . " minutes");
    $to = (clone $start)->modify("+" . (($i + 1) * $interval) . " minutes");
    $rangeKey = "chunk_$i";
    $query = "
        SELECT COUNT(bas) AS bas,
               DATE_FORMAT(datum, '%Y-%m-%d %H:%i') AS mn,
               AVG(TIMESTAMPDIFF(SECOND, startt, endd)) AS avg
        FROM bastix
        WHERE datum BETWEEN '" . $from->format('Y-m-d H:i:s') . "' AND '" . $to->format('Y-m-d H:i:s') . "'
              AND marke = 'EWH'
              AND startt IS NOT NULL AND endd IS NOT NULL AND success = 1
        GROUP BY mn
        ORDER BY mn
    ";
    $result = fetchQuery($connect, $query);
    $counts = $averages = [];
    createChartData($result, $counts, $averages);
    $chartData[$rangeKey] = ['count' => $counts, 'avg' => $averages];
}

// إعادة ترتيب الـ chunks
$chartData = array_reverse($chartData);
$temp = array_values($chartData);
$chartData = [
    'chunk_3' => $temp[0],
    'chunk_1' => $temp[1],
    'chunk_2' => $temp[2],
    'chunk_0' => $temp[3],
];

ksort($basmnbas);
ksort($basmnEV);
ksort($basmnC24Bhub);
ksort($basmnC24Direct);
ksort($basmnHC);
ksort($basmnINVIA);
ksort($basmnAMA);
ksort($avgmin);

$response = [
    'labels' => array_keys($basmnAMA),
    'basmnbas' => array_values($basmnbas),
    'basmnAMA' => array_values($basmnAMA),
    'basmnHC' => array_values($basmnHC),
    'basmnC24Bhub' => array_values($basmnC24Bhub),
    'basmnC24Direct' => array_values($basmnC24Direct),
    'basmnINVIA' => array_values($basmnINVIA),
    'basmnEV' => array_values($basmnEV),
    'avgmin' => array_values($avgmin),
    'avgmin_labels' => array_keys($avgmin),
    'chunks' => $chartData
];

file_put_contents(__DIR__ . '/bas_chart_data.json', json_encode($response));
