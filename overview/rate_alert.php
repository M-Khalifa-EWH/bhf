<?php
require_once __DIR__ . '/../config/mysql.php';

$conn = MySQLDatabase::connect();


$sql = "
SELECT 'HOTEL' AS source, provider, agency, rate, startt_rounded
FROM hotel_brand
WHERE startt_rounded = (
    SELECT MAX(startt_rounded) FROM hotel_brand
)
AND rate < 0.7

UNION ALL

SELECT 'FLIGHT' AS source, provider, agency, rate, startt_rounded
FROM flight_brand
WHERE startt_rounded = (
    SELECT MAX(startt_rounded) FROM hotel_brand
)
AND rate < 0.7

ORDER BY rate ASC;
";

$stmt = $conn->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


$issues = [];

foreach ($rows as $row) {
    $issues[] = [
    'label' => $row['source'] . ' - ' . $row['provider'] . ' (' . $row['agency'] . ')',
    'rate'  => $row['rate']
];
}


$lastTime = !empty($rows)
    ? date('H:i:s', strtotime($rows[0]['startt_rounded']))
    : '';



if (!empty($issues)) {

    echo '<div style="
        background:#fef2f2;
        border-left:3px solid #dc2626;
        padding:8px 12px;
        border-radius:8px;
        display:flex;
        flex-direction:column;
        gap:6px;
        font-weight:350;
    ">';

   
    echo '<strong style="color:#b91c1c;">⚠ Low Rate Detected</strong>';

    
    echo '<div style="display:flex; flex-wrap:wrap; gap:7px;">';

    foreach ($issues as $item) {
        echo '<span style="
            background:white;
            border-radius:6px;
            padding:4px 8px;
            display:flex;
            align-items:center;
            gap:4px;
            box-shadow:0 1px 2px rgba(0,0,0,0.1);
        ">
            <span style="color:red;">●</span>
            <strong>' . $item['label'] . '</strong>
            <span style="font-size:20px; opacity:0.7; color:red;">
                (' . $item['rate'] . ')
            </span>
        </span>';
    }

    echo '</div>';

    

    echo '</div>';

} else {

    echo '<div style="
        background:#dcfce7;
        border-left:4px solid #22c55e;
        padding:6px 10px;
        border-radius:10px;
        display:flex;
        justify-content:space-between;
        font-family:sans-serif;
    ">
        <span>✅ All rates are above 70%.</span>
        <span style="font-size:10px; opacity:0.7;">
            ⏱ ' . $lastTime . '
        </span>
    </div>';
}