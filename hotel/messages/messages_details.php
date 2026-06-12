<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/hotel/config/sqlServer2.php';
$connect = SQLServer2::connect();

$minute = $_POST['minute'] ?? '';
$serviceKey = $_POST['serviceKey'] ?? '';
$code = $_POST['code'] ?? '';
$message = $_POST['message'] ?? '';

if (!$connect || !$minute) exit;

$sql = "
SELECT 
    FORMAT(c.CreationDate, 'yyyy-MM-dd HH:mm:ss') AS CreationDate,
    CONCAT(b.Code, ': ', b.message) AS CodeMessage,
    c.HotelGiataCode,
    c.HotelAccomodation,
    c.AdultCounts,
    c.ChildrenCounts,
    c.InfantCounts,
    a.[User] AS agency,
    CONVERT(varchar(10), c.OutboundDate, 104) + ' - ' + CONVERT(varchar(10), c.InboundDate, 104) AS Reise,
    c.OutboundOriginTlc,
    c.OutboundArrivalTlc
FROM dbo.OperationInformationObjects a
JOIN dbo.OperationInformationObject_ResponseMessages b
    ON a.id = b.OperationInformationObject_id
JOIN dbo.PackageInformationObjects c
    ON a.PackageId = c.PackageId
WHERE b.ServiceKey = ?
  AND b.Code = ?
  AND b.message LIKE ?
  AND FORMAT(c.CreationDate, 'yyyy-MM-dd HH:mm') + ' ' + FORMAT(c.CreationDate, 'HH:mm') = ?
ORDER BY CreationDate ASC
";

$params = [$serviceKey, $code, "%$message%", $minute];
$stmt = sqlsrv_query($connect, $sql, $params);

if ($stmt) {
    echo "<div class='table-responsive'>";
    echo "<table class='table table-bordered table-sm table-smaller'>";
    echo "<thead>
<tr>
<th>CreationDate</th>
<th>Code + Message</th>
<th>Giata</th>
<th>Hotel</th>
<th>Ad</th>
<th>Ch</th>
<th>In</th>
<th>Agency</th>
<th>Travel</th>
<th>Orig</th>
<th>Dest</th>
</tr>
</thead>
<tbody>";

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo "<tr>
            <td>".$row['CreationDate']."</td>
            <td>".htmlspecialchars($row['CodeMessage'])."</td>
            <td>".$row['HotelGiataCode']."</td>
            <td>".htmlspecialchars($row['HotelAccomodation'])."</td>
            <td>".$row['AdultCounts']."</td>
            <td>".$row['ChildrenCounts']."</td>
            <td>".$row['InfantCounts']."</td>
            <td>".htmlspecialchars($row['agency'])."</td>
            <td>".$row['Reise']."</td>
            <td>".$row['OutboundOriginTlc']."</td>
            <td>".$row['OutboundArrivalTlc']."</td>
        </tr>";
    }

    echo "</tbody></table></div>";
}

sqlsrv_close($connect);
?>