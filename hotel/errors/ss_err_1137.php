
<?php
$pageTitle = "ErrCode 0456";
include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/header.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/hotel/config/sqlServer2.php';

$zeit = $_GET['zeit'] ?? 0;
$currentDate = new DateTime('yesterday');
$dayDate = $currentDate->format('Y-m-d');
$startTime = str_pad($zeit, 2, "0", STR_PAD_LEFT) . ":00:00";
$endHour = min($zeit + 3, 23);
$endTime = str_pad($endHour, 2, "0", STR_PAD_LEFT) . ":59:59";

$connect = SQLServer2::connect();
if (!$connect) { die("Connection failed: " . print_r(sqlsrv_errors(), true)); }

$query = "
    SELECT PlayerProvider, COUNT(a.PackageId) as ct
    FROM dbo.OperationInformationObjects a
    JOIN dbo.OperationInformationObject_ResponseMessages b ON a.id = b.OperationInformationObject_id
    JOIN dbo.PackageInformationObjects c ON a.PackageId = c.PackageId
    WHERE c.CreationDate >= DATEADD(day, DATEDIFF(day, 1, GETDATE()), 0)
      AND c.CreationDate <  DATEADD(day, DATEDIFF(day, 0, GETDATE()), 0)
      AND Code = 'SS:ERR:1137'
      AND success = 'false'
    GROUP BY PlayerProvider
";

function renderTable($query, $connect, $id) {
    $stmt = sqlsrv_query($connect, $query);
    if (!$stmt) die("Query failed: " . print_r(sqlsrv_errors(), true));

    echo "<div class='container'><table id='$id' class='table table-striped table-bordered'><thead><tr>";
    $firstRow = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($firstRow) {
        foreach ($firstRow as $col => $val) { echo "<th>$col</th>"; }
        echo "</tr></thead><tbody>";

        // أول صف
        echo "<tr>";
        foreach ($firstRow as $val) { echo "<td>$val</td>"; }
        echo "</tr>";

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            echo "<tr>";
            foreach ($row as $val) { echo "<td>$val</td>"; }
            echo "</tr>";
        }
    } else { echo "<tr><td colspan='2'>No data found</td></tr>"; }
    echo "</tbody></table></div>";
    sqlsrv_free_stmt($stmt);
}

renderTable($query, $connect, 'table1137');
sqlsrv_close($connect);
?>

<script>
$(document).ready(function(){
    $('#table1137').DataTable({ dom: 'Brtip', buttons:['excel','csv'], order:[[1,'desc']] });
});
</script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/footer.php'; ?>