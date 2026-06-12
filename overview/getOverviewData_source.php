<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Berlin');

require_once __DIR__ . '/../config/mysql.php';
require_once __DIR__ . '/helpers.php';

$conn = MySQLDatabase::connect();
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$type = $_GET['type'] ?? 'ewh';

// === Filters for bastix
switch ($type) {
    case 'fv':
        $usrFilter = "usr = '1000'";
        $agencyFilter = "agency NOT LIKE '%_hlm'";
        break;
    case 'ev':
        $usrFilter = "usr != '1000'";
        $agencyFilter = "agency NOT LIKE '%_hlm'";
        break;
    case 'hlm':
        $usrFilter = "";
        $agencyFilter = "agency LIKE '%_hlm'";
        break;
    case 'ewh':
    default:
        $usrFilter = "";
        $agencyFilter = "";
        break;
}

// === Filters for tix
switch ($type) {
    case 'fv':
        $retailOrgFilter = "retailOrg = '2'";
        break;
    case 'ev':
        $retailOrgFilter = "retailOrg != '2'";
        break;
    case 'hlm':
    case 'ewh':
    default:
        $retailOrgFilter = "";
        break;
}

// === Time ranges
$timeRanges = [
    'day'   => "datum >= CURDATE()",
    'hour'  => "datum >= NOW() - INTERVAL 1 HOUR",
    'hour2' => "datum BETWEEN NOW() - INTERVAL 2 HOUR AND NOW() - INTERVAL 1 HOUR"
];

$data = [];
$key = $type;

// === bastix data
foreach ($timeRanges as $label => $condition) {
    $whereParts = [$condition];
    if ($agencyFilter) $whereParts[] = $agencyFilter;
    if ($usrFilter) $whereParts[] = $usrFilter;
    $where = implode(' AND ', $whereParts);

    $data['bas'][$label][$key]  = fetchValue($conn, "SELECT COUNT(bas) FROM bastix WHERE $where");
    $data['fail'][$label][$key] = fetchValue($conn, "SELECT COUNT(bas) FROM bastix WHERE $where AND success = 0");
    $data['jump'][$label][$key] = fetchValue($conn, "SELECT COUNT(DISTINCT bas) FROM bastix WHERE $where AND PriceDeviation > 10 AND success = 1");
    $data['avg'][$label][$key]  = fetchValue($conn, "SELECT AVG(TIMESTAMPDIFF(SECOND, startt, endd)) FROM bastix WHERE $where AND startt IS NOT NULL AND endd IS NOT NULL AND success = 1");
}

// === tix data
foreach ($timeRanges as $label => $condition) {

    if ($type === 'ewh') {
        // 💡 استعلام خاص لحالة ewh
        $stats = getTixStatsEWH($conn, $condition);
        $aggregated = [
            'total' => 0,
            'ORDER_OK' => 0,
            'notOk' => 0,
            'orderTixOk' => 0,
            'brand_group' => 'EWH'
        ];
        foreach ($stats as $row) {
            $aggregated['total'] += (int)($row['total'] ?? 0);
            $aggregated['ORDER_OK'] += (int)($row['ORDER_OK'] ?? 0);
            $aggregated['notOk'] += (int)($row['notOk'] ?? 0);
            $aggregated['orderTixOk'] += (int)($row['orderTixOk'] ?? 0);
        }
        $data['tix'][$label][$key] = $aggregated;

    } else {
        // ✅ الاستعلام العادي
        $filterParts = [$condition];
        if ($retailOrgFilter) $filterParts[] = $retailOrgFilter;

        if ($type === 'hlm') {
            $filterParts[] = "traveltype LIKE 'HLM%'";
        } else {
            $filterParts[] = "(traveltype IS NULL OR traveltype NOT LIKE 'HLM%')";
        }

        $where = implode(' AND ', $filterParts);
        $stats = getTixStats($conn, $where);

        $matchedRow = null;
        foreach ($stats as $row) {
            if (($row['brand_group'] ?? '') === $key) {
                $matchedRow = $row;
                break;
            }
        }

        $data['tix'][$label][$key] = $matchedRow ?: [
            'total' => 0,
            'ORDER_OK' => 0,
            'notOk' => 0,
            'orderTixOk' => 0,
            'brand_group' => $key
        ];
    }
}

// === last sync
$data['lastSync'] = $conn->query("SELECT MAX(datum) FROM bastix")->fetchColumn();

// ✅ Output
return $data;
