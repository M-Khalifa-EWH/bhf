<?php

include_once $_SERVER['DOCUMENT_ROOT'].'/hotel/config/sqlServer2.php';
$connect = SQLServer2::connect();

if (!$connect) {
    die("<div class='alert alert-danger'>Database connection failed</div>");
}


/* ===============================
   ServiceKey Resolver
================================ */

function resolveServiceKeys($serviceKey){

switch($serviceKey){

case 'flug':
return [
'outbound',
'inbound',
'outbound,inbound',
'outbound, inbound'
];

default:
return [$serviceKey];

}

}

/* ===============================
   Child rows query
================================ */

function getChildRows($connect,$provider,$serviceKeys,$code,$message,$hourRange){

$params = [];
$where = [];

if($provider){
$where[] = "p.Provider = ?";
$params[] = $provider;
}

if($code){
$where[] = "c.Code LIKE ?";
$params[] = "%$code%";
}

if($message){
$where[] = "c.Message LIKE ?";
$params[] = "%$message%";
}

if($hourRange){

$range = explode('-',$hourRange);

if(count($range)==2){

$start = trim($range[0]);
$end = trim($range[1]);

$where[] = "CAST(c.CreationDate AS TIME) BETWEEN ? AND ?";
$params[] = $start;
$params[] = $end;

}

}

if($serviceKeys){

$placeholders = implode(',',array_fill(0,count($serviceKeys),'?'));

$where[] = "c.ServiceKey IN ($placeholders)";

$params = array_merge($params,$serviceKeys);

}

$whereSql = $where ? "WHERE ".implode(" AND ",$where) : "";

$sql = "

SELECT
c.Code,
c.Message,
c.ServiceKey,
c.CreationDate

FROM CRSLogs c
JOIN PlayerProvider p ON p.Id = c.ProviderId

$whereSql
ORDER BY c.CreationDate DESC

";

$stmt = sqlsrv_query($connect,$sql,$params);

$data = [];

while($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)){
$data[] = $row;
}

return $data;

}

/* ===============================
   AJAX
================================ */

if(isset($_POST['ajaxProvider'])){

$provider = $_POST['ajaxProvider'];
$code = $_POST['code'] ?? '';
$message = $_POST['message'] ?? '';
$hourRange = $_POST['hourRange'] ?? '';
$serviceKey = $_POST['serviceKey'] ?? 'hotel';

$serviceKeys = resolveServiceKeys($serviceKey);

$data = getChildRows(
$connect,
$provider,
$serviceKeys,
$code,
$message,
$hourRange
);

foreach($data as $row){

echo "<tr>";
echo "<td>".$row['Code']."</td>";
echo "<td>".$row['Message']."</td>";
echo "<td>".$row['ServiceKey']."</td>";
echo "<td>".$row['CreationDate']->format('H:i:s')."</td>";
echo "</tr>";

}

exit;

}

/* ===============================
   Main Providers Query
================================ */

$sql = "

SELECT DISTINCT Provider
FROM PlayerProvider
ORDER BY Provider

";

$stmt = sqlsrv_query($connect,$sql);

?>

<!DOCTYPE html>
<html>
<head>

<title>CRS Logs</title>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

</head>

<body class="p-4">

<h3>CRS Logs</h3>

<div class="row mb-3">

<div class="col">
<input type="text" name="code" class="form-control" placeholder="Code">
</div>

<div class="col">
<input type="text" name="message" class="form-control" placeholder="Message">
</div>

<div class="col">
<select name="serviceKey" class="form-control">

<option value="hotel">Hotel</option>
<option value="inbound">Inbound</option>
<option value="outbound">Outbound</option>
<option value="flug">Flug</option>

</select>
</div>

<div class="col">
<select name="hourRange" class="form-control">

<option value="">All</option>
<option value="00:00-06:00">00-06</option>
<option value="06:00-12:00">06-12</option>
<option value="12:00-18:00">12-18</option>
<option value="18:00-23:59">18-24</option>

</select>
</div>

</div>

<table class="table table-bordered">

<thead>

<tr>
<th></th>
<th>Provider</th>
</tr>

</thead>

<tbody>

<?php while($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)){ ?>

<tr data-provider="<?= $row['Provider'] ?>">

<td class="details-control" style="cursor:pointer">+</td>
<td><?= $row['Provider'] ?></td>

</tr>

<?php } ?>

</tbody>

</table>

<script>

$('.details-control').on('click', function(){

var tr = $(this).closest('tr');
var provider = tr.data('provider');

$.post('',{

ajaxProvider: provider,
serviceKey: $('[name="serviceKey"]').val(),
code: $('[name="code"]').val(),
message: $('[name="message"]').val(),
hourRange: $('[name="hourRange"]').val()

}, function(data){

tr.next('.child-row').remove();

var html = `
<tr class="child-row">
<td colspan="2">

<table class="table table-sm">

<thead>
<tr>
<th>Code</th>
<th>Message</th>
<th>ServiceKey</th>
<th>Time</th>
</tr>
</thead>

<tbody>

${data}

</tbody>

</table>

</td>
</tr>
`;

tr.after(html);

});

});

</script>

<?php include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/footer.php'; ?>