<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Details</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

<style>

.header-info{margin-bottom:20px;}

#loading{display:none;text-align:center;}

#subdetails{margin-top:25px;}

#detailsTable th,
#detailsTable td{white-space:nowrap;}

.details-row{cursor:pointer;}

.details-row:hover{background:#f2f2f2;}

</style>

</head>

<body>

<div class="container mt-4">

<?php

require_once 'config/sqlServer2.php';

$dateRange = $_GET['dateRange'] ?? null;
$startTime = $_GET['startTime'] ?? null;
$endTime   = $_GET['endTime'] ?? null;
$provider  = $_GET['provider'] ?? null;
$table     = $_GET['table'] ?? null;
$code      = $_GET['code'] ?? null;

if(!$dateRange || !$startTime || !$endTime || !$provider || !$table){

echo "<div class='alert alert-danger text-center'>Missing parameters</div>";
exit;

}

$connect = SQLServer2::connect();

if(!$connect){

echo "<div class='alert alert-danger'>Connection failed</div>";
exit;

}

$params = [$dateRange,$startTime,$endTime,$provider];

$isCode18 = ($code === 'SS:ERR:18');

if($isCode18){

$query="

SELECT TOP (5)

PlayerProvider,
c.PlayerBrand,
a.[User] AS agency,
c.OutboundOriginTlc AS Origin,
c.OutboundArrivalTlc AS destination,

CONCAT(
FORMAT(c.OutboundDate,'dd.MM.yy'),
' ',
CONVERT(varchar(5),c.OutboundDate,108),
' → ',
FORMAT(c.InboundDate,'dd.MM.yy'),
' ',
CONVERT(varchar(5),c.InboundDate,108)
) AS Reise,

c.OutboundFlightNumber,
c.InboundFlightNumber,

c.AdultCounts,
c.ChildrenCounts,
c.InfantCounts,

COUNT(a.id) AS ct

FROM dbo.OperationInformationObjects a
JOIN dbo.OperationInformationObject_ResponseMessages b
ON a.id=b.OperationInformationObject_id

JOIN dbo.PackageInformationObjects c
ON a.PackageId=c.PackageId

WHERE CONVERT(date,c.CreationDate)=?
AND CONVERT(time,c.CreationDate) BETWEEN ? AND ?
AND PlayerProvider LIKE ?
AND b.Code='SS:ERR:18'
AND success='false'

GROUP BY

PlayerProvider,
c.PlayerBrand,
a.[User],
c.OutboundOriginTlc,
c.OutboundArrivalTlc,
c.OutboundDate,
c.InboundDate,
c.OutboundFlightNumber,
c.InboundFlightNumber,
c.AdultCounts,
c.ChildrenCounts,
c.InfantCounts

ORDER BY COUNT(a.id) DESC

";

$columns=[

"Provider",
"Brand",
"Agency",
"Origin",
"Dest",
"Reise",
"OutboundFlight",
"InboundFlight",
"Adults",
"Children",
"Infants",
"Count"

];

}else{

$query="

SELECT TOP (5)
-- Reis, outbound, inbound, adults, children, infants, count
PlayerProvider,
b.Code,
c.HotelAccomodation,

c.AdultCounts,
c.ChildrenCounts,
c.InfantCounts,

a.[User] AS agency,

CONCAT(
FORMAT(c.OutboundDate,'dd.MM.yy'),
' ',
CONVERT(varchar(5),c.OutboundDate,108),
' → ',
FORMAT(c.InboundDate,'dd.MM.yy'),
' ',
CONVERT(varchar(5),c.InboundDate,108)
) AS Reise,

c.OutboundOriginTlc AS Origin,
c.OutboundArrivalTlc AS destination,

COUNT(a.id) AS ct

FROM dbo.OperationInformationObjects a
JOIN dbo.OperationInformationObject_ResponseMessages b
ON a.id=b.OperationInformationObject_id

JOIN dbo.PackageInformationObjects c
ON a.PackageId=c.PackageId

WHERE CONVERT(date,c.CreationDate)=?
AND CONVERT(time,c.CreationDate) BETWEEN ? AND ?
AND PlayerProvider LIKE ?
AND success='false'

GROUP BY

PlayerProvider,
b.Code,
c.HotelAccomodation,
c.AdultCounts,
c.ChildrenCounts,
c.InfantCounts,
a.[User],
c.OutboundDate,
c.InboundDate,
c.OutboundOriginTlc,
c.OutboundArrivalTlc

ORDER BY COUNT(a.id) DESC

";

$params[]=$code;

$columns=[

"Provider",
"Code",
"Accomodation",
"Adults",
"Children",
"Infants",
"Agency",
"Reise",
"Origin",
"Destination",
"Count"

];

}

$stmt = sqlsrv_query($connect,$query,$params);

?>

<div class="header-info">

<h4>Provider: <?=htmlspecialchars($provider)?></h4>
<h4>Date: <?=date('d.m.Y',strtotime($dateRange))?></h4>
<h4>Time: <?=$startTime?> - <?=$endTime?></h4>

</div>

<div class="table-responsive">

<table id="detailsTable" class="table table-striped table-bordered">

<thead>

<tr>

<?php
foreach($columns as $col){
echo "<th>$col</th>";
}
?>

</tr>

</thead>

<tbody>

<?php

while($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)){

echo "<tr class='details-row'

data-provider='".htmlspecialchars($row['PlayerProvider'])."'
data-code='".($isCode18 ? "SS:ERR:18" : htmlspecialchars($row['Code'] ?? ''))."'
data-table='".htmlspecialchars($table)."'
>";

if($isCode18){

echo "<td>".$row['PlayerProvider']."</td>";
echo "<td>".$row['PlayerBrand']."</td>";
echo "<td>".$row['agency']."</td>";
echo "<td>".$row['Origin']."</td>";
echo "<td>".$row['destination']."</td>";
echo "<td>".$row['Reise']."</td>";
echo "<td>".$row['OutboundFlightNumber']."</td>";
echo "<td>".$row['InboundFlightNumber']."</td>";
echo "<td>".$row['AdultCounts']."</td>";
echo "<td>".$row['ChildrenCounts']."</td>";
echo "<td>".$row['InfantCounts']."</td>";
echo "<td>".$row['ct']."</td>";

}else{

echo "<td>".$row['PlayerProvider']."</td>";
echo "<td>".$row['Code']."</td>";
echo "<td>".$row['HotelAccomodation']."</td>";
echo "<td>".$row['AdultCounts']."</td>";
echo "<td>".$row['ChildrenCounts']."</td>";
echo "<td>".$row['InfantCounts']."</td>";
echo "<td>".$row['agency']."</td>";
echo "<td>".$row['Reise']."</td>";
echo "<td>".$row['Origin']."</td>";
echo "<td>".$row['destination']."</td>";
echo "<td>".$row['ct']."</td>";

}

echo "</tr>";

}

?>

</tbody>

</table>

</div>

<div id="loading">

<div class="spinner-border"></div>
<p>Loading details...</p>

</div>

<div id="subdetails"></div>

</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>

$(document).ready(function(){

var lastColumn = $('#detailsTable thead th').length - 1;

var table = $('#detailsTable').DataTable({

order:[[lastColumn,'desc']],
pageLength:50,
autoWidth:true,
scrollX:true

});

$('#detailsTable tbody').on('click','tr',function(){

var provider=$(this).data('provider');
var code=$(this).data('code');
var tableName=$(this).data('table');

if(!provider) return;

$('#loading').show();
$('#subdetails').html('');

$.ajax({

url:'subdetails.php',

method:'GET',

data:{

dateRange:'<?=addslashes($dateRange)?>',
startTime:'<?=addslashes($startTime)?>',
endTime:'<?=addslashes($endTime)?>',
provider:provider,
code:code,
table:tableName

},

success:function(response){

$('#loading').hide();
$('#subdetails').html(response);

},

error:function(){

$('#loading').hide();
$('#subdetails').html('<div class="alert alert-danger">Error loading details</div>');

}

});

});

});

</script>

</body>
</html>