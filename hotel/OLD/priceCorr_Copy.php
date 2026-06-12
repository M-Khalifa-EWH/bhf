
<?php
include 'navbar.php'; 

require_once 'config/sqlServer2.php';
$connect = SQLServer2::connect();

if (!$connect) {
    echo "<div class='alert alert-danger text-center'>Connection could not be established.<br>" . print_r(sqlsrv_errors(), true) . "</div>";
    die();
}

// Get filter values from GET request or set default (last hour to now)
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d H:i', strtotime('-60 minutes'));
$to_date   = isset($_GET['to_date'])   ? $_GET['to_date']   : date('Y-m-d H:i');
$playerbrand = isset($_GET['playerbrand']) ? trim($_GET['playerbrand']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Validate dates
try {
    $from_date_obj = new DateTime($from_date);
    $to_date_obj = new DateTime($to_date);
} catch (Exception $e) {
    die("Invalid date format. Please enter a valid date.");
}

$from_date_sql = $from_date_obj->format('Y-m-d H:i:s');
$to_date_sql   = $to_date_obj->format('Y-m-d H:i:s');

// Base query (date range required)
$query = "
SELECT
    HotelAccomodation,
    HotelCode,
    PlayerBrand,
    HotelGiataCode, HotelName, OutboundOriginTLC, OutboundArrivalTlc,
    COUNT(PackageId) AS TotalPackages,
    SUM(CASE WHEN PriceDeviation > 0 THEN 1 ELSE 0 END) AS PC_bookable,
    SUM(CASE WHEN PriceDeviation > 0 AND PriceDeviation <= 1 THEN 1 ELSE 0 END) AS pc_0_1,
    SUM(CASE WHEN PriceDeviation > 1 AND PriceDeviation <= 5 THEN 1 ELSE 0 END) AS pc_1_5,
    SUM(CASE WHEN PriceDeviation > 5 AND PriceDeviation <= 10 THEN 1 ELSE 0 END) AS pc_5_10,
    SUM(CASE WHEN PriceDeviation > 10 AND PriceDeviation <= 15 THEN 1 ELSE 0 END) AS pc_10_15,
    SUM(CASE WHEN PriceDeviation > 15 AND PriceDeviation <= 25 THEN 1 ELSE 0 END) AS pc_15_25,
    SUM(CASE WHEN PriceDeviation > 25 AND PriceDeviation <= 50 THEN 1 ELSE 0 END) AS pc_25_50,
    SUM(CASE WHEN PriceDeviation > 50 THEN 1 ELSE 0 END) AS pc_over_50
FROM
    dbo.PackageInformationObjects
WHERE
    CreationDate BETWEEN CONVERT(DATETIME, ?, 120) AND CONVERT(DATETIME, ?, 120)
";

$params = [$from_date_sql, $to_date_sql];

// Add global search if provided
if ($search !== '') {
    $like = '%' . $search . '%';
    $query .= " AND (PlayerBrand LIKE ? OR HotelAccomodation LIKE ? OR HotelCode LIKE ? OR HotelGiataCode LIKE ?) ";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$query .= "
GROUP BY
    HotelAccomodation,
    HotelCode,
    PlayerBrand,
    HotelGiataCode,HotelName, OutboundOriginTLC, OutboundArrivalTlc
ORDER BY
    pc_over_50 DESC,
    COUNT(PackageId) DESC,
    HotelGiataCode
";

$stmt = sqlsrv_query($connect, $query, $params);
if ($stmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}

// Fetch all rows into array so we can both display and optionally export
$rows = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $rows[] = $row;
}

// If export requested, send Excel headers and output table, then exit
if (isset($_GET['export_excel']) && $_GET['export_excel'] == '1') {
    // filename with timestamp
    $filename = "price_deviation_summary_" . date('Ymd_His') . ".xls";

    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Start outputting a simple HTML table which Excel will open
    echo "<table border='1'>";
    echo "<tr>
        <th>Hotel Code</th>
        <th>Hotel Giata Code</th>
        <th>Hotel Accomodation</th>
        <th>PlayerBrand</th>
        <th>Hotel Name</th>
        <th>von</th>
        <th>nach</th>
        <th>Total Packages</th>
        <th>PC bookable</th>
        <th>0 < PCQ ≤ 1</th>
        <th>1 < PCQ ≤ 5</th>
        <th>5 < PCQ ≤ 10</th>
        <th>10 < PCQ ≤ 15</th>
        <th>15 < PCQ ≤ 25</th>
        <th>25 < PCQ ≤ 50</th>
        <th>PCQ > 50</th>
    </tr>";

    foreach ($rows as $r) {
        // Use tab/HTML-safe values
        $hc = isset($r['HotelCode']) ? $r['HotelCode'] : '';
        $hg = isset($r['HotelGiataCode']) ? $r['HotelGiataCode'] : '';
        $ha = isset($r['HotelAccomodation']) ? $r['HotelAccomodation'] : '';
        $pb = isset($r['PlayerBrand']) ? $r['PlayerBrand'] : '';
        $hn = isset($r['HotelName']) ? $r['HotelName'] : '';
        $oo = isset($r['OutboundOriginTLC']) ? $r['OutboundOriginTLC'] : '';
        $oa = isset($r['OutboundArrivalTlc']) ? $r['OutboundArrivalTlc'] : '';
        $tp = isset($r['TotalPackages']) ? $r['TotalPackages'] : 0;
        $pcb = isset($r['PC_bookable']) ? $r['PC_bookable'] : 0;
        $p0_1 = isset($r['pc_0_1']) ? $r['pc_0_1'] : 0;
        $p1_5 = isset($r['pc_1_5']) ? $r['pc_1_5'] : 0;
        $p5_10 = isset($r['pc_5_10']) ? $r['pc_5_10'] : 0;
        $p10_15 = isset($r['pc_10_15']) ? $r['pc_10_15'] : 0;
        $p15_25 = isset($r['pc_15_25']) ? $r['pc_15_25'] : 0;
        $p25_50 = isset($r['pc_25_50']) ? $r['pc_25_50'] : 0;
        $po50 = isset($r['pc_over_50']) ? $r['pc_over_50'] : 0;

        // echo a row (no htmlspecialchars needed for excel .xls HTML, but we still ensure strings safe)
        echo "<tr>
            <td>{$hc}</td>
            <td>{$hg}</td>
            <td>{$ha}</td>
            <td>{$pb}</td>
            <td>{$hn}</td>
            <td>{$oo}</td>
            <td>{$oa}</td>
            <td>{$tp}</td>
            <td>{$pcb}</td>
            <td>{$p0_1}</td>
            <td>{$p1_5}</td>
            <td>{$p5_10}</td>
            <td>{$p10_15}</td>
            <td>{$p15_25}</td>
            <td>{$p25_50}</td>
            <td>{$po50}</td>
        </tr>";
    }

    echo "</table>";
    // close connection and exit
    sqlsrv_close($connect);
    exit;
}

// Prepare safe HTML values for the page
$search_html = htmlspecialchars($search, ENT_QUOTES);

?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Price Deviation Summary</title>
<style>
    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-family: Arial, sans-serif; font-size: 14px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    th { background-color: #f7f7a6; }
    form { margin-bottom: 20px; }
    label { font-weight: bold; margin-right: 5px; }
    input, button { padding: 5px; margin-right: 10px; }
    .filter-button { background-color: #e0e6e9; border: none; padding: 8px 14px; border-radius: 8px; cursor: pointer; }
</style>
</head>
<body>
<div class='container'>
<h2>Price Deviation Summary</h2>

<form method='GET'>
    <label for='from_date'>Von:</label>
    <input type='datetime-local' id='from_date' name='from_date' value='<?php echo date('Y-m-d\TH:i', strtotime($from_date)); ?>' required>

    <label for='to_date'>Bis:</label>
    <input type='datetime-local' id='to_date' name='to_date' value='<?php echo date('Y-m-d\TH:i', strtotime($to_date)); ?>' required>

    <label for='search'>Search:</label>
    <input type='text' id='search' name='search' value='<?php echo $search_html; ?>' />

    <button type='submit' class='filter-button'>Suchen</button>

    <!-- Export_excel=1 -->
    <button type='submit' name='export_excel' value='1' class='filter-button'>Excel</button>
</form>

<table id="priceDeviationTable">
<thead>
<tr>
    <th>Hotel Code</th>
    <th>Hotel Giata Code</th>
    <th>Hotel Accomodation</th>
    <th>PlayerBrand</th>
    <th>Hotel Name</th>
    <th>von</th>
    <th>nach</th>
    <th>Total Packages</th>
    <th>PC bookable</th>
    <th>0 &lt; PCQ &le; 1</th>
    <th>1 &lt; PCQ &le; 5</th>
    <th>5 &lt; PCQ &le; 10</th>
    <th>10 &lt; PCQ &le; 15</th>
    <th>15 &lt; PCQ &le; 25</th>
    <th>25 &lt; PCQ &le; 50</th>
    <th>PCQ &gt; 50 </th>
</tr>
</thead>
<tbody>
<?php
foreach ($rows as $row) {
    echo "<tr>
        <td>" . htmlspecialchars($row['HotelCode']) . "</td>
        <td>" . htmlspecialchars($row['HotelGiataCode']) . "</td>
        <td>" . htmlspecialchars($row['HotelAccomodation']) . "</td>
        <td>" . htmlspecialchars($row['PlayerBrand']) . "</td>
        <td>" . htmlspecialchars($row['HotelName']) . "</td>
        <td>" . htmlspecialchars($row['OutboundOriginTLC']) . "</td>
        <td>" . htmlspecialchars($row['OutboundArrivalTlc']) . "</td>
        <td>" . number_format($row['TotalPackages']) . "</td>
        <td>" . number_format($row['PC_bookable']) . "</td>
        <td>" . number_format($row['pc_0_1']) . "</td>
        <td>" . number_format($row['pc_1_5']) . "</td>
        <td>" . number_format($row['pc_5_10']) . "</td>
        <td>" . number_format($row['pc_10_15']) . "</td>
        <td>" . number_format($row['pc_15_25']) . "</td>
        <td>" . number_format($row['pc_25_50']) . "</td>
        <td>" . number_format($row['pc_over_50']) . "</td>
    </tr>";
}
?>
</tbody>
</table>
</div>

</body>
</html>

<?php
// close connection
sqlsrv_close($connect);
?>
