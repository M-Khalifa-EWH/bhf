<?php

function gp($key){ return isset($_GET[$key]) ? trim($_GET[$key]) : null; }

$dateRange      = gp('dateRange');
$startTime      = gp('startTime');
$endTime        = gp('endTime');
$provider       = gp('provider');
$hotelgiatacode = gp('hotelgiatacode');
$table          = gp('table');
$code           = gp('code');

if (!$dateRange || !$startTime || !$endTime || !$provider || !$table) {
    echo "<div class='alert alert-danger text-center'>Missing parameters.</div>"; exit;
}

$connect = SQLServer2::connect();
if (!$connect) { echo "<div class='alert alert-danger'>Cannot connect</div>"; exit; }

$providerParam = $provider;
$hotelParam    = $hotelgiatacode ? "%$hotelgiatacode%" : '%';

if ($table === 'table1') {
    if (!$code) { echo "<div class='alert alert-danger'>code is required for table1</div>"; exit; }

    $query = "
        SELECT PlayerProvider, b.Code, c.HotelAccomodation,
               c.AdultCounts, c.ChildrenCounts, c.InfantCounts,
               c.HotelGiataCode, a.[User] as agency,
               -- added Reise column
               CONCAT(FORMAT(c.OutboundDate, 'dd.MM.yy'), ' - ', FORMAT(c.InboundDate, 'dd.MM.yy')) AS Reise,
               c.OutboundArrivalTlc as destination, COUNT(a.id) as ct
        FROM dbo.OperationInformationObjects a
        JOIN dbo.OperationInformationObject_ResponseMessages b ON a.id = b.OperationInformationObject_id
        JOIN dbo.PackageInformationObjects c WITH (READUNCOMMITTED) ON a.PackageId = c.PackageId
        WHERE CONVERT(date, c.CreationDate)=? 
          AND CONVERT(time, c.CreationDate) BETWEEN ? AND ?
          AND PlayerProvider LIKE ? 
          AND c.HotelGiataCode LIKE ? 
          AND a.OperationScope LIKE 'CreatePackage'
          AND b.Code=? 
          AND a.success='false'
        GROUP BY PlayerProvider, b.Code, c.HotelAccomodation,
                 c.AdultCounts, c.ChildrenCounts, c.InfantCounts, 
                 c.HotelGiataCode, a.[User],
                 -- include same expression in GROUP BY
                 CONCAT(FORMAT(c.OutboundDate, 'dd.MM.yy'), ' - ', FORMAT(c.InboundDate, 'dd.MM.yy')),
                 c.OutboundArrivalTlc
        ORDER BY ct DESC
    ";
    $params = [$dateRange, $startTime, $endTime, $providerParam, $hotelParam, $code];

} 

$stmt = sqlsrv_prepare($connect, $query, $params);
if (!$stmt || !sqlsrv_execute($stmt)) { echo "<div class='alert alert-danger'>Query error</div>"; exit; }

?>
<div class="container mt-4">
<table id="subdetailsTable" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Provider</th>

            <?php if($table==='table1') echo '<th>GiataCode</th>'; ?>
            
            <th>Accomodation</th>   
            <th>AdultCounts</th>
            <th>ChildrenCounts</th>
            <th>InfantCounts</th>
            <th>Agency</th>
            <th>Reise</th> <!-- new column header -->
            <th>Destination</th>
            <th>Count</th>
        </tr>
    </thead>
    <tbody>
<?php
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo '<tr>';
    echo '<td>'.htmlspecialchars($row['PlayerProvider']).'</td>';  
    if ($table==='table1') echo '<td>'.htmlspecialchars($row['HotelGiataCode']).'</td>';
    echo '<td>'.htmlspecialchars($row['HotelAccomodation']).'</td>';  
    echo '<td>'.htmlspecialchars($row['AdultCounts']).'</td>';
    echo '<td>'.htmlspecialchars($row['ChildrenCounts']).'</td>';
    echo '<td>'.htmlspecialchars($row['InfantCounts']).'</td>';
    echo '<td>'.htmlspecialchars($row['agency']).'</td>';
    echo '<td>'.htmlspecialchars($row['Reise']).'</td>'; // output Reise
    echo '<td>'.htmlspecialchars($row['destination']).'</td>';
    echo '<td>'.(int)$row['ct'].'</td>';
    echo '</tr>';
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($connect);
?>
    </tbody>
</table>
</div>


$(document).ready(function(){
    $('#subdetailsTable').DataTable({
    dom:'Brtip',
    buttons:['excel','csv'],

    <?php if ($code === 'SS:ERR:18'): ?>
    columnDefs: [
        {
            targets: [
                <?php
                echo ($table === 'table1') ? '1, 2' : '1';
                ?>
            ],
            visible: false
        }
    ],
    <?php endif; ?>

    order:[[ <?php echo ($table==='table1')?'8':'7'; ?>,'desc']]
});

});
</script>
