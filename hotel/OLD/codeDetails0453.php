<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SS:ERR:0453 Details</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-4">

<?php
require_once 'config/sqlServer2.php';

$connect = SQLServer2::connect();
if (!$connect) die("Connection failed");

$brand = $_GET['brand'] ?? '';
$errorNumber = $_GET['errorNumber'] ?? '';

$errorMessages = [
    '137' => 'Playerbridge response error: 137 OCCUPANCYMISMATCH: No rooms matched the given occupancy.',
    '136' => 'Playerbridge response error: 136 NOROOMMATCHING: No room matched the search parameters.',
    '135' => 'Playerbridge response error: 135 NOHOTELMATCHING: No hotel matched the search parameters.'
];

$displayMessage = $errorMessages[$errorNumber] ?? 'Unknown error';
?>

<h3>Details for PlayerBrand: <?php echo htmlspecialchars($brand); ?></h3>

<div class="alert alert-info">
    <strong>Error:</strong> <?php echo htmlspecialchars($displayMessage); ?>
</div>

<?php
$likeError = '%' . $errorNumber . '%';

/* =========================
   1️⃣ Query User Count
========================= */
$countQuery = "
SELECT  
    a.[User] AS UserName,
    COUNT(*) AS UserCount
FROM dbo.OperationInformationObjects a
JOIN dbo.OperationInformationObject_ResponseMessages b
    ON a.id = b.OperationInformationObject_id
JOIN dbo.PackageInformationObjects c
    ON a.PackageId = c.PackageId
WHERE c.CreationDate >= DATEADD(day, DATEDIFF(day, 1, GETDATE()), 0)
  AND c.CreationDate <  DATEADD(day, DATEDIFF(day, 0, GETDATE()), 0)
  AND Code = 'SS:ERR:0453'
  AND success = 'false'
  AND c.PlayerBrand = ?
  AND b.Message LIKE ?
GROUP BY a.[User]
ORDER BY UserCount DESC
";

$params = [
    [$brand, SQLSRV_PARAM_IN],
    [$likeError, SQLSRV_PARAM_IN]
];

$countStmt = sqlsrv_prepare($connect, $countQuery, $params);
if (!$countStmt || !sqlsrv_execute($countStmt)) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!-- =========================
     User Count Table
========================= -->

<h5 class="mt-4">User Error Count</h5>

<table class="table table-bordered table-sm">
    <thead class="table-warning">
        <tr>
            <th>User</th>
            <th>Number of Errors</th>
        </tr>
    </thead>
    <tbody>
<?php
while ($row = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC)) {
    echo '<tr>';
    echo '<td>'.htmlspecialchars($row['UserName']).'</td>';
    echo '<td><strong>'.$row['UserCount'].'</strong></td>';
    echo '</tr>';
}
sqlsrv_free_stmt($countStmt);
?>
    </tbody>
</table>

<?php
/* =========================
   2️⃣ Query Details Table
========================= */
$query = "
SELECT  
    a.[User] AS UserName,
    a.Agency AS Agency,
    c.HotelGiataCode AS HotelCode,
    c.CreationDate, 
    c.OutboundOriginTlc as Von, 
    c.OutboundArrivalTlc as Nach,
    c.HotelCode, 
    c.HotelAccomodation, 
    c.HotelBoard
FROM dbo.OperationInformationObjects a
JOIN dbo.OperationInformationObject_ResponseMessages b
    ON a.id = b.OperationInformationObject_id
JOIN dbo.PackageInformationObjects c
    ON a.PackageId = c.PackageId
WHERE c.CreationDate >= DATEADD(day, DATEDIFF(day, 1, GETDATE()), 0)
  AND c.CreationDate <  DATEADD(day, DATEDIFF(day, 0, GETDATE()), 0)
  AND Code = 'SS:ERR:0453'
  AND success = 'false'
  AND c.PlayerBrand = ?
  AND b.Message LIKE ?
ORDER BY c.CreationDate DESC
";

$stmt = sqlsrv_prepare($connect, $query, $params);
if (!$stmt || !sqlsrv_execute($stmt)) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!-- =========================
     Details Table
========================= -->

<table id="detailsTable" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>User</th>
            <th>Agency</th>
            <th>HotelCode</th>
            <th>HotelAccomodation</th>
            <th>HotelBoard</th>
            <th>CreationDate</th>
            <th>Von</th>
            <th>Nach</th>
        </tr>
    </thead>
    <tbody>
<?php
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo '<tr>';
    echo '<td>'.htmlspecialchars($row['UserName']).'</td>';
    echo '<td>'.htmlspecialchars($row['Agency']).'</td>';
    echo '<td>'.htmlspecialchars($row['HotelCode']).'</td>';
    echo '<td>'.htmlspecialchars($row['HotelAccomodation']).'</td>';
    echo '<td>'.htmlspecialchars($row['HotelBoard']).'</td>';
    echo '<td>'.$row['CreationDate']->format('d.m.Y H:i:s').'</td>';
    echo '<td>'.htmlspecialchars($row['Von']).'</td>';
    echo '<td>'.htmlspecialchars($row['Nach']).'</td>';
    echo '</tr>';
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($connect);
?>
    </tbody>
</table>

</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function(){
    $('#detailsTable').DataTable({
        dom:'Brtip',
        buttons:['excel','csv'],
        order:[[5,'desc']]
    });
});
</script>

</body>
</html>