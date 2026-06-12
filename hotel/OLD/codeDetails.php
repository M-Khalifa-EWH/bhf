<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ErrCodeDetails</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<style>
.header-info { margin-bottom: 20px; }
#loading { display: none; text-align: center; }
#subdetails { margin-top: 20px; }
</style>
</head>
<body>
<div class="container mt-4">

<?php
require_once 'config/sqlServer2.php';

if (isset($_GET['dateRange'], $_GET['startTime'], $_GET['endTime'], $_GET['provider'], $_GET['table'])) {

    $dateRange = trim($_GET['dateRange']);
    $startTime = trim($_GET['startTime']);
    $endTime   = trim($_GET['endTime']);
    $provider  = trim($_GET['provider']);
    $table     = trim($_GET['table']);
    $code      = isset($_GET['code']) ? trim($_GET['code']) : null;


    if ($table === 'table1' && !$code) {
        echo "<div class='alert alert-danger text-center'>Missing parameter: code is required for table1</div>";
        exit;
    }

    $connect = SQLServer2::connect();
    if (!$connect) {
        echo "<div class='alert alert-danger'>Connection could not be established.<br>" . htmlspecialchars(print_r(sqlsrv_errors(), true)) . "</div>";
        exit;
    }


    if ($table === 'table1') {
        $query = "
            SELECT PlayerProvider, b.Code AS Code,
                   c.HotelGiataCode AS HotelGiataCode,
                   c.HotelAccomodation AS HotelAccomodation,
                   c.AdultCounts AS AdultCounts,
                   c.ChildrenCounts AS ChildrenCounts,
                   c.InfantCounts AS InfantCounts,
                   a.[User] AS agency,
                   CONCAT(FORMAT(c.OutboundDate, 'dd.MM.yy'), ' - ', FORMAT(c.InboundDate, 'dd.MM.yy')) AS Reise,
                   c.OutboundArrivalTlc AS destination,
                   COUNT(a.id) AS ct
            FROM dbo.OperationInformationObjects a
            JOIN dbo.OperationInformationObject_ResponseMessages b ON a.id = b.OperationInformationObject_id
            JOIN dbo.PackageInformationObjects c WITH (READUNCOMMITTED) ON a.PackageId = c.PackageId
            WHERE CONVERT(date, c.CreationDate) = ?
              AND CONVERT(time, c.CreationDate) BETWEEN ? AND ?
              AND PlayerProvider LIKE ?
              AND b.Code = ?
              AND OperationScope LIKE 'CreatePackage'
              AND success = 'false'
            GROUP BY PlayerProvider, b.Code, c.HotelGiataCode,
                     c.HotelAccomodation, c.AdultCounts, c.ChildrenCounts, c.InfantCounts,
                     a.[User],
                     CONCAT(FORMAT(c.OutboundDate, 'dd.MM.yy'), ' - ', FORMAT(c.InboundDate, 'dd.MM.yy')),
                     c.OutboundArrivalTlc
        ";
        $params = [$dateRange, $startTime, $endTime, $provider, $code];
    } else {
        $query = "
            SELECT PlayerProvider, b.Code AS Code,
                   c.HotelAccomodation AS HotelAccomodation,
                   c.AdultCounts AS AdultCounts,
                   c.ChildrenCounts AS ChildrenCounts,
                   c.InfantCounts AS InfantCounts,
                   a.[User] AS agency,
                   CONCAT(FORMAT(c.OutboundDate, 'dd.MM.yy'), ' - ', FORMAT(c.InboundDate, 'dd.MM.yy')) AS Reise,
                   c.OutboundArrivalTlc AS destination,
                   COUNT(a.id) AS ct
            FROM dbo.OperationInformationObjects a
            JOIN dbo.OperationInformationObject_ResponseMessages b ON a.id = b.OperationInformationObject_id
            JOIN dbo.PackageInformationObjects c ON a.PackageId = c.PackageId
            WHERE CONVERT(date, c.CreationDate) = ?
              AND CONVERT(time, c.CreationDate) BETWEEN ? AND ?
              AND PlayerProvider LIKE ?
              AND OperationScope LIKE 'CreatePackage'
              AND PlayerProvider IN ('EWP','AITF','Condor','DEP','airtuerk','XQP','AITS','Travelport','U2','VFLY','VY','Eurowings','XQ','TFLY','TUIFly','FHY','LHG','NORWEGIAN')
              AND success = 'false'
            GROUP BY PlayerProvider, b.Code,
                     c.HotelAccomodation, c.AdultCounts, c.ChildrenCounts, c.InfantCounts,
                     a.[User],
                     CONCAT(FORMAT(c.OutboundDate, 'dd.MM.yy'), ' - ', FORMAT(c.InboundDate, 'dd.MM.yy')),
                     c.OutboundArrivalTlc
        ";
        $params = [$dateRange, $startTime, $endTime, $provider];
    }

    $stmt = sqlsrv_prepare($connect, $query, $params);
    if (!$stmt || !sqlsrv_execute($stmt)) {
        echo "<div class='alert alert-danger'>Query Error: " . htmlspecialchars(print_r(sqlsrv_errors(), true)) . "</div>";
        exit;
    }

    ?>

    <div class="header-info">
        <h4>Provider: <?php echo htmlspecialchars($provider); ?></h4>
        <?php if ($code == 'SS:ERR:1137'): ?>
    <h5>DERN hotel hat nur "OnRequest" zur Verfügung und muss abgelehnt werden.</h5>
<?php endif; ?>
<?php if ($code == 'SS:ERR:11191'): ?>
<h5>All such room are booked out in the hotel.</h5> 
<?php endif; ?>
        <h4>Datum: <?php echo date('d.m.Y', strtotime($dateRange)); ?></h4>
        <h4>Zeit: <?php echo htmlspecialchars($startTime); ?> - <?php echo htmlspecialchars($endTime); ?></h4>
    </div>

    <table id="detailsTable" class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>PlayerProvider</th>
                <th>Code</th>
                <?php if ($table==='table1') echo '<th>GiataCode</th>'; ?>
                <th>Accomodation</th>
                <th>AdultCounts</th>
                <th>ChildrenCounts</th>
                <th>InfantCounts</th>
                <th>Reise</th>
                <th>Agency</th>
                <th>Destination</th>
                <th>Count</th>
            </tr>
        </thead>
        <tbody>
        <?php
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $playerProvider = htmlspecialchars($row['PlayerProvider']);
            $codeCell = htmlspecialchars($row['Code']);
            $hotelGiata = isset($row['HotelGiataCode']) ? htmlspecialchars($row['HotelGiataCode']) : '';
            $hotelAcc = isset($row['HotelAccomodation']) ? htmlspecialchars($row['HotelAccomodation']) : '';
            $adultCounts = isset($row['AdultCounts']) ? (int)$row['AdultCounts'] : '';
            $childrenCounts = isset($row['ChildrenCounts']) ? (int)$row['ChildrenCounts'] : '';
            $infantCounts = isset($row['InfantCounts']) ? (int)$row['InfantCounts'] : '';
            $reise = isset($row['Reise']) ? htmlspecialchars($row['Reise']) : '';
            $agency = htmlspecialchars($row['agency']);
            $destination = htmlspecialchars($row['destination']);
            $ct = (int)$row['ct'];

            // put useful values in data- attributes for possible client-side use
            $dataAttrs = "data-provider='{$playerProvider}' data-code='{$codeCell}' data-hotelgiatacode='{$hotelGiata}'"
                       . " data-hotelacc='{$hotelAcc}' data-adults='{$adultCounts}' data-children='{$childrenCounts}' data-infants='{$infantCounts}' data-reise='{$reise}' data-table='{$table}'";

            echo "<tr class='details-row' {$dataAttrs}>";
            echo "<td>{$playerProvider}</td>";
            echo "<td>{$codeCell}</td>";
            if ($table==='table1') echo "<td>{$hotelGiata}</td>";
            echo "<td>{$hotelAcc}</td>";
            echo "<td>{$adultCounts}</td>";
            echo "<td>{$childrenCounts}</td>";
            echo "<td>{$infantCounts}</td>";
            echo "<td>{$reise}</td>";
            echo "<td>{$agency}</td>";
            echo "<td>{$destination}</td>";
            echo "<td>{$ct}</td>";
            echo "</tr>";
        }
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($connect);
        ?>
        </tbody>
    </table>

    <div id="loading">
        <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
        <p>Loading, please wait...</p>
    </div>
    <div id="subdetails"></div>

<?php } else { ?>
    <div class="alert alert-danger text-center">Missing parameters.</div>
<?php } ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script>
$(document).ready(function() {
    $('#detailsTable').DataTable({
        dom:'Brtip',
        buttons: [
          { extend: 'excel', exportOptions: { columns: ':visible' } },
          { extend: 'csv', exportOptions: { columns: ':visible' } }
        ],
        // Count is now at index 10 for table1, and 9 for other table
        order:[[<?php echo ($table==='table1')?'10':'9'; ?>,'desc']],
        columnDefs: [
            { targets: 1, visible: false } // hide Code column if you want (same as before)
        ]
    });


    $('#detailsTable').on('click', '.details-row', function(){
        // you can use data attributes we've added if needed; for now we pass same params as before
        var provider = $(this).data('provider');
        var code     = $(this).data('code');
        var hotel    = $(this).data('hotelgiatacode');
        var table    = $(this).data('table');

        $('#loading').show();
        $('#subdetails').empty();

        $.ajax({
            url:'subdetails.php',
            method:'GET',
            data:{
                dateRange:'<?php echo addslashes($dateRange); ?>',
                startTime:'<?php echo addslashes($startTime); ?>',
                endTime:'<?php echo addslashes($endTime); ?>',
                provider:provider,
                code:code,
                hotelgiatacode:hotel,
                table:table
            },
            success:function(data){
                $('#loading').hide();
                $('#subdetails').html(data);
            },
            error:function(){
                $('#loading').hide();
                $('#subdetails').html('<div class="alert alert-danger">Error loading data.</div>');
            }
        });
    });
});
</script>

</div>
</body>
</html>
