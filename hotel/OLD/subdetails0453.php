<?php
require_once 'config/sqlServer2.php';

if(isset($_GET['brand'], $_GET['errorNumber'], $_GET['dateRange'], $_GET['startTime'], $_GET['endTime'])) {

    $brand = $_GET['brand'];
    $errorNumber = (int)$_GET['errorNumber'];
    $dateRange = $_GET['dateRange'];
    $startTime = $_GET['startTime'];
    $endTime = $_GET['endTime'];
    $code = 'SS:ERR:0453';

    $connect = SQLServer2::connect();
    if(!$connect) die("Connection failed");

    $query = "
    SELECT a.[User] AS UserName, 
           a.Agency AS Agency,
           c.HotelGiataCode AS HotelCode,
           c.CreationDate
    FROM dbo.OperationInformationObjects a
    JOIN dbo.OperationInformationObject_ResponseMessages b 
        ON a.id = b.OperationInformationObject_id
    JOIN dbo.PackageInformationObjects c 
        ON a.PackageId = c.PackageId
    WHERE CONVERT(date, c.CreationDate) = ?
      AND CONVERT(time, c.CreationDate) BETWEEN ? AND ?
      AND a.PlayerBrand = ?
      AND b.Code = ?
      AND a.success = 'false'
      AND CAST(
            SUBSTRING(
                b.Message,
                PATINDEX('%[0-9]%', b.Message),
                PATINDEX('%[^0-9]%', SUBSTRING(b.Message, PATINDEX('%[0-9]%', b.Message), 1000)) - 1
            ) AS INT
          ) = ?
";

    $params = [$dateRange, $startTime, $endTime, $brand, $code, $errorNumber];
    $stmt = sqlsrv_prepare($connect,$query,$params);
    if(!$stmt || !sqlsrv_execute($stmt)){ die(print_r(sqlsrv_errors(),true)); }

    echo '<table class="table table-bordered table-striped">';
    echo '<thead><tr><th>User</th><th>Agency</th><th>HotelCode</th><th>CreationDate</th></tr></thead><tbody>';
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        echo '<tr>';
        echo '<td>'.htmlspecialchars($row['UserName']).'</td>';
        echo '<td>'.htmlspecialchars($row['Agency']).'</td>';
        echo '<td>'.htmlspecialchars($row['HotelCode']).'</td>';
        echo '<td>'.date('d.m.Y H:i:s', strtotime($row['CreationDate'])).'</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($connect);
}
?>
