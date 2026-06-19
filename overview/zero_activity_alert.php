<?php
require_once __DIR__ . '/../config/mysql.php';

$conn = MySQLDatabase::connect();

// ✅ Query (مصَحح)
$sql = "
SELECT
    SUM(usr='1000') AS EV,
    SUM(usr='12001') AS C24DIRECT,
    SUM(usr='12100') AS HC,
    SUM(usr='12300') AS INVIA,
    SUM(usr='10090') AS AMA,
    MAX(created_at) AS last_created
FROM bastix
WHERE datum >= NOW() - INTERVAL 10 MINUTE
AND startt IS NOT NULL
AND endd IS NOT NULL
AND success = 1
";

$row = $conn->query($sql)->fetch(PDO::FETCH_ASSOC);


$lastCreated = $row['last_created'] 
    ? date('H:i:s', strtotime($row['last_created'])) 
    : 'N/A';

$users = [
    'EV'        => '1000 EV',
    'C24DIRECT' => '12001 C24 Direkt',
    'HC'        => '12100 HC',
    'INVIA'     => '12300 INVIA',
    'AMA'       => '10090 AMA'
];

$inactive = [];

foreach ($users as $key => $label) {
    if ((int)$row[$key] === 0) {
        $inactive[] = $label;
    }
}

// ✅ Render HTML
if (!empty($inactive)) {

    echo '<div style="
        background:#e0f2fe;
        border-left:4px solid #0284c7;
        padding:10px 14px;
        border-radius:8px;
        display:flex;
        align-items:center;
        gap:15px;
        font-weight:350;
    ">';

    
    echo '<div style="display:flex; gap:10px; flex-wrap:wrap;">';
    foreach ($inactive as $u) {
        echo '<span style="display:flex; align-items:center; gap:6px;">
            <span style="color:red;">●</span>
            <span><strong>' . $u . '</strong></span>
        </span>';
    }
    echo '</div>';

    
    echo '<span style="
        margin-left:10px;
        font-size:13px;
        opacity:0.7;
        white-space:nowrap;
    ">
        ⏱ '.$lastCreated.'
    </span>';

    echo '</div>';

} else {
    echo '<div style="
        background:#dcfce7;
        border-left:4px solid #22c55e;
        padding:10px 14px;
        border-radius:8px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        font-weight:500;
    ">
        <span>✅ All users active</span>
        <span style="font-size:13px; opacity:0.7;">⏱ '.$lastCreated.'</span>
    </div>';
}
