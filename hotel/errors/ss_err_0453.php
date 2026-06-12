<?php

include_once $_SERVER['DOCUMENT_ROOT'].'/hotel/config/sqlServer2.php';

/*
=====================================
AJAX LEVEL 1  (Details)
=====================================
*/

if(isset($_GET['action']) && $_GET['action']=='details'){

    $brand = $_GET['brand'];
    $errorNumber = (int)$_GET['errorNumber'];
    $dateRange = $_GET['dateRange'];
    $startTime = $_GET['startTime'];
    $endTime = $_GET['endTime'];
    $code='SS:ERR:0453';

    $connect = SQLServer2::connect();

    $query = "
    SELECT a.[User] AS UserName,
           a.Agency,
           c.HotelGiataCode,
           c.CreationDate
    FROM dbo.OperationInformationObjects a
    JOIN dbo.OperationInformationObject_ResponseMessages b
        ON a.id=b.OperationInformationObject_id
    JOIN dbo.PackageInformationObjects c
        ON a.PackageId=c.PackageId
    WHERE CONVERT(date,c.CreationDate)=?
      AND CONVERT(time,c.CreationDate) BETWEEN ? AND ?
      AND a.PlayerBrand=?
      AND b.Code=?
      AND a.success='false'
      AND CAST(
            SUBSTRING(
                b.Message,
                PATINDEX('%[0-9]%',b.Message),
                PATINDEX('%[^0-9]%',
                    SUBSTRING(b.Message,PATINDEX('%[0-9]%',b.Message),1000)
                )-1
            ) AS INT
          )=?
    ";

    $params=[$dateRange,$startTime,$endTime,$brand,$code,$errorNumber];
    $stmt=sqlsrv_query($connect,$query,$params);

    echo "<h4>Details for PlayerBrand: $brand</h4>";

    echo "<table id='detailsTable' class='table table-striped table-bordered'>
    <thead>
    <tr>
    <th>User</th>
    <th>Agency</th>
    <th>HotelCode</th>
    <th>CreationDate</th>
    </tr>
    </thead><tbody>";

    while($row=sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)){

        echo "<tr class='details-row'
        data-user='{$row['UserName']}'
        data-hotel='{$row['HotelGiataCode']}'>";

        echo "<td>".$row['UserName']."</td>";
        echo "<td>".$row['Agency']."</td>";
        echo "<td>".$row['HotelGiataCode']."</td>";
        echo "<td>".$row['CreationDate']->format('d.m.Y H:i:s')."</td>";

        echo "</tr>";
    }

    echo "</tbody></table>";

    exit;
}


/*
=====================================
AJAX LEVEL 2  (SubDetails)
=====================================
*/

if(isset($_GET['action']) && $_GET['action']=='subdetails'){

    $hotel=$_GET['hotel'];

    $connect=SQLServer2::connect();

    $query="
    SELECT *
    FROM dbo.PackageInformationObjects
    WHERE HotelGiataCode=?
    ";

    $stmt=sqlsrv_query($connect,$query,[$hotel]);

    echo "<h5>SubDetails</h5>";
    echo "<table class='table table-bordered'><tbody>";

    while($row=sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)){
        echo "<tr>";
        echo "<td>".$row['PackageId']."</td>";
        echo "<td>".$row['HotelGiataCode']."</td>";
        echo "</tr>";
    }

    echo "</tbody></table>";

    exit;
}

?>
<!DOCTYPE html>
<html>
<head>

<link rel="stylesheet"
href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

</head>

<body>

<div class="container">

<h3>SS:ERR:0453 Pivot Table</h3>

<?php

$connect=SQLServer2::connect();

$query="

WITH src AS(

SELECT
CAST(
SUBSTRING(
Message,
PATINDEX('%[0-9]%',Message),
PATINDEX('%[^0-9]%',
SUBSTRING(Message,PATINDEX('%[0-9]%',Message),1000)
)-1
) AS INT
) AS ErrorNumber,

PlayerBrand,
COUNT(a.PackageId) ct

FROM dbo.OperationInformationObjects a
JOIN dbo.OperationInformationObject_ResponseMessages b
ON a.id=b.OperationInformationObject_id
JOIN dbo.PackageInformationObjects c
ON a.PackageId=c.PackageId

WHERE Code='SS:ERR:0453'
AND success='false'

GROUP BY
CAST(
SUBSTRING(
Message,
PATINDEX('%[0-9]%',Message),
PATINDEX('%[^0-9]%',
SUBSTRING(Message,PATINDEX('%[0-9]%',Message),1000)
)-1
) AS INT
),
PlayerBrand

)

SELECT
PlayerBrand,
ISNULL([135],0) Error135,
ISNULL([136],0) Error136,
ISNULL([137],0) Error137

FROM src
PIVOT(
SUM(ct) FOR ErrorNumber IN ([135],[136],[137])
)p

";

$stmt=sqlsrv_query($connect,$query);

?>

<table id="pivotTable" class="display">

<thead>
<tr>
<th>Brand</th>
<th>135</th>
<th>136</th>
<th>137</th>
</tr>
</thead>

<tbody>

<?php

while($row=sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)){

$brand=$row['PlayerBrand'];

echo "<tr data-brand='$brand'>";

echo "<td>$brand</td>";

echo "<td class='error-click' data-error='135'>{$row['Error135']}</td>";
echo "<td class='error-click' data-error='136'>{$row['Error136']}</td>";
echo "<td class='error-click' data-error='137'>{$row['Error137']}</td>";

echo "</tr>";
}

?>

</tbody>
</table>

<br>

<div id="detailsContainer"></div>
<div id="subdetailsContainer"></div>

</div>


<script>

$('#pivotTable').DataTable();


/*
=====================================
CLICK PIVOT
=====================================
*/

$('#pivotTable').on('click','.error-click',function(){

let brand=$(this).closest('tr').data('brand');
let errorNumber=$(this).data('error');

$('#detailsContainer').html("Loading...");

$.get('',{

action:'details',
brand:brand,
errorNumber:errorNumber,
dateRange:'2026-04-01',
startTime:'00:00:00',
endTime:'23:59:59'

},function(data){

$('#detailsContainer').html(data);

});

});


/*
=====================================
CLICK DETAILS
=====================================
*/

$(document).on('click','.details-row',function(){

let hotel=$(this).data('hotel');

$('#subdetailsContainer').html("Loading...");

$.get('',{

action:'subdetails',
hotel:hotel

},function(data){

$('#subdetailsContainer').html(data);

});

});

</script>

</body>
</html>