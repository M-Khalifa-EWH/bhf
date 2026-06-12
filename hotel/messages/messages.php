<?php
// ───── Config & DB Connection ─────
include_once $_SERVER['DOCUMENT_ROOT'].'/hotel/config/sqlServer2.php';
$connect = SQLServer2::connect();
if (!$connect) {
    die("<div class='alert alert-danger'>Database connection failed</div>");
}

// ───── Utility Functions ─────

/**
 * Get PlayerProvider counts with optional time filtering
 */
function getPlayerProviderCounts($connect, $serviceKey, $code, $message, $hourRange = null) {

    $sql = "
        SELECT ISNULL(b.PlayerProvider,'<NULL>') AS PlayerProvider,
               COUNT(DISTINCT a.id) AS Count
        FROM dbo.OperationInformationObjects a
        JOIN dbo.OperationInformationObject_ResponseMessages b 
            ON a.id=b.OperationInformationObject_id
        JOIN dbo.PackageInformationObjects c 
            ON a.PackageId=c.PackageId
        WHERE c.CreationDate>=DATEADD(HOUR,-23,GETDATE())
    ";

    $params = [];

  
    if (!empty($serviceKey)) {
        $sql .= " AND b.ServiceKey = ?";
        $params[] = $serviceKey;
    }

   
    if (!empty($code)) {
        $sql .= " AND b.Code = ?";
        $params[] = $code;
    }


   if (!empty($message)) {
    $sql .= " AND LTRIM(RTRIM(
                    CASE 
                        WHEN CHARINDEX(':', b.message) > 0
                        THEN RIGHT(b.message, CHARINDEX(':', REVERSE(b.message)) - 1)
                        ELSE b.message 
                    END
                )) LIKE ?";
    $params[] = "%$message%";
}

   
    if ($hourRange) {
        list($startTime, $endTime) = explode('|', $hourRange);
        $sql .= " AND CAST(c.CreationDate AS TIME) BETWEEN ? AND ?";
        $params[] = $startTime;
        $params[] = $endTime;
    }

    $sql .= "
        GROUP BY ISNULL(b.PlayerProvider,'<NULL>')
        ORDER BY Count DESC
    ";

    $stmt = sqlsrv_query($connect, $sql, $params);
    if ($stmt === false) return [];

    $results = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $results[] = $row;
    }

    return $results;
}

/**
 * Get Child Rows for a given provider with optional time filtering
 */
function getChildRows($connect, $provider, $serviceKey, $code, $message, $hourRange = null) {

    $sql = "
        SELECT 
            CONVERT(varchar(16), c.CreationDate, 120) AS MinuteSlot,
            c.HotelGiataCode,
            c.HotelAccomodation,
            c.AdultCounts,
            c.ChildrenCounts,
            c.InfantCounts,
            a.[User] AS agency,
            CONVERT(varchar(10), c.OutboundDate, 104) + ' - ' + CONVERT(varchar(10), c.InboundDate, 104) AS Reise,
            c.OutboundOriginTlc,
            c.OutboundArrivalTlc,
            CONCAT(
                b.Code, ': ',
                CASE 
                    WHEN CHARINDEX(':', b.message) > 0
                    THEN RIGHT(b.message, CHARINDEX(':', REVERSE(b.message)) - 1)
                    ELSE b.message 
                END
            ) AS CodeMessage
        FROM dbo.OperationInformationObjects a
        JOIN dbo.OperationInformationObject_ResponseMessages b 
            ON a.id = b.OperationInformationObject_id
        JOIN dbo.PackageInformationObjects c 
            ON a.PackageId = c.PackageId
        WHERE c.CreationDate >= DATEADD(HOUR,-23,GETDATE())
        AND c.HotelCode LIKE '%[_]%'
    ";

    $params = [];

    // ───── Provider ─────
    if ($provider === '<NULL>') {
        $sql .= " AND b.PlayerProvider IS NULL";
    } elseif (!empty($provider)) {
        $sql .= " AND b.PlayerProvider = ?";
        $params[] = $provider;
    }

    // ───── Service Key (اختياري) ─────
    if (!empty($serviceKey)) {
        $sql .= " AND b.ServiceKey = ?";
        $params[] = $serviceKey;
    }

    // ───── Code (يتجاهل إذا فارغ) ─────
    if (!empty($code)) {
        $sql .= " AND b.Code = ?";
        $params[] = $code;
    }

    // ───── Message (يتجاهل إذا فارغ) ─────
   if (!empty($message)) {
    $sql .= " AND LTRIM(RTRIM(
                    CASE 
                        WHEN CHARINDEX(':', b.message) > 0
                        THEN RIGHT(b.message, CHARINDEX(':', REVERSE(b.message)) - 1)
                        ELSE b.message 
                    END
                )) LIKE ?";
    $params[] = "%$message%";
}

    // ───── Hour Range (محسن) ─────
    if (!empty($hourRange)) {
        list($startTime, $endTime) = explode('|', $hourRange);
        $sql .= " AND CAST(c.CreationDate AS TIME) BETWEEN ? AND ?";
        $params[] = $startTime;
        $params[] = $endTime;
    }

    $sql .= " ORDER BY c.CreationDate DESC";

    $stmt = sqlsrv_query($connect, $sql, $params);
    if ($stmt === false) return [];

    $results = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $results[] = $row;
    }

    return $results;
}

// ───── Handle AJAX Request ─────
if (isset($_POST['ajaxProvider'])) {
    $provider   = $_POST['ajaxProvider'];
    $serviceKey = $_POST['serviceKey'] ?? '';
    $code       = $_POST['code'] ?? '';
    $message    = $_POST['message'] ?? '';
    $hourRange  = $_POST['hourRange'] ?? null;

    $data = getChildRows($connect, $provider, $serviceKey, $code, $message, $hourRange);
    header('Content-Type: application/json');
    echo json_encode($data);
    sqlsrv_close($connect);
    exit;
}

// ───── Include Header ─────
$pageTitle = "Hotel Tool - PlayerProvider Count";
include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/header.php';
?>

<!-- ───── Container & Form ───── -->
<div class="container mt-4">
<h5>PlayerProvider Count</h5>

<form method="POST" class="mb-4">
<div class="row g-3">
    <div class="col-md-2">
        <label class="form-label">Hour Range</label>
        <select name="hourRange" class="form-control">
     


        <?php
$selectedRange = $_POST['hourRange'] ?? '';

for ($i = 0; $i < 24; $i++) {
    $start = str_pad($i, 2, '0', STR_PAD_LEFT) . ":00";
    $end   = str_pad($i, 2, '0', STR_PAD_LEFT) . ":59"; // تعديل هنا

    $value = "$start|$end";
    $sel = ($selectedRange == $value) ? "selected" : "";

    echo "<option value='$value' $sel>$start - $end</option>";
}
?>
        </select>
    </div>
    <div class="col-md-2">
    <label class="form-label">Service Key</label>
    <?php $selectedService = $_POST['serviceKey'] ?? ''; ?>

    <select name="serviceKey" class="form-control">
        <option value="" <?= ($selectedService == '') ? 'selected' : '' ?>>
            -- All Services --
        </option>

        <option value="Hotel"
            <?= ($selectedService == "Hotel") ? 'selected' : '' ?>>
            Hotel
        </option>

        <option value="outbound,inbound"
            <?= ($selectedService == "outbound,inbound") ? 'selected' : '' ?>>
            Flug
        </option>
    </select>
</div>
    <div class="col-md-2">
    <label class="form-label">Code</label>
    <?php $selectedCode = $_POST['code'] ?? ''; ?>

    <select name="code" class="form-control">
        <option value="" <?= ($selectedCode == '') ? 'selected' : '' ?>>
            -- All Codes --
        </option>

        <option value="IFS:BHUB:INF:0"
            <?= ($selectedCode == "IFS:BHUB:INF:0") ? 'selected' : '' ?>>
            IFS:BHUB:INF:0
        </option>

        <option value="SS:INFO:00443"
            <?= ($selectedCode == "SS:INFO:00443") ? 'selected' : '' ?>>
            SS:INFO:00443
        </option>

        <option value="SS:ERR:1137"
            <?= ($selectedCode == "SS:ERR:1137") ? 'selected' : '' ?>>
            SS:ERR:1137
        </option>
         <option value="SS:ERR:18"
            <?= ($selectedCode == "SS:ERR:18") ? 'selected' : '' ?>>
            SS:ERR:18
        </option>
        <option value="SS:ERR:11200"
            <?= ($selectedCode == "SS:ERR:11200") ? 'selected' : '' ?>>
            SS:ERR:11200
        </option>
        <option value="SS:ERR:11191"
            <?= ($selectedCode == "SS:ERR:11191") ? 'selected' : '' ?>>
            SS:ERR:11191
        </option>
        <option value="IFS:EMIND:ERR:1160"
            <?= ($selectedCode == "IFS:EMIND:ERR:1160") ? 'selected' : '' ?>>
            IFS:EMIND:ERR:1160
        </option>
    </select>
</div>
    <div class="col-md-4">
        <label class="form-label">Message Contains</label>
        <?php $selectedMessage = $_POST['message'] ?? ''; ?>

<select name="message" class="form-control">
    <option value="" <?= ($selectedMessage == '') ? 'selected' : '' ?>>
        -- All Messages --
    </option>

    <option value="All such room are booked out in the hotel"
        <?= ($selectedMessage == "All such room are booked out in the hotel") ? 'selected' : '' ?>>
        All such room are booked out in the hotel
    </option>

    <option value="The connection to the service provider is disturbed."
        <?= ($selectedMessage == "The connection to the service provider is disturbed.") ? 'selected' : '' ?>>
        The connection to the service provider is disturbed.
    </option>

    <option value="hotel is not activated for usage"
        <?= ($selectedMessage == "hotel is not activated for usage") ? 'selected' : '' ?>>
        Hotel is not activated for usage
    </option>
    <option value="Flugleistung/en nicht bzw. nicht mehr vorhanden/gültig"
        <?= ($selectedMessage == "Flugleistung/en nicht bzw. nicht mehr vorhanden/gültig") ? 'selected' : '' ?>>
        Flugleistung/en nicht bzw. nicht mehr vorhanden/gültig
    </option>
      
</select>
    </div>
    <div class="col-md-2 align-self-end">
        <button type="submit" class="btn btn-primary w-100">Filter</button>
    </div>
</div>
</form>

<?php
// ───── Main Table ─────
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $serviceKey = $_POST['serviceKey'] ?? '';
    $code       = $_POST['code'] ?? '';
    $message    = $_POST['message'] ?? '';
    $hourRange  = $_POST['hourRange'] ?? null;

    $data = getPlayerProviderCounts($connect, $serviceKey, $code, $message, $hourRange);

    echo "<div class='table-responsive'>
          <table id='resultTable' class='table table-bordered table-striped table-sm'>
          <thead><tr><th></th><th>PlayerProvider</th><th>Count</th></tr></thead><tbody>";
    foreach ($data as $row) {
        $providerEsc = htmlspecialchars($row['PlayerProvider']);
        echo "<tr data-provider='{$providerEsc}'>
                <td class='details-control'>+</td>
                <td>{$providerEsc}</td>
                <td>{$row['Count']}</td>
              </tr>";
    }
    echo "</tbody></table></div>";
    sqlsrv_close($connect);
}
?>
</div>

<!-- ───── Subtable CSS for smaller font ───── -->
<style>
.child-row-table {
    font-size: 0.8rem;
}
.child-row-table th, .child-row-table td {
    padding: 0.25rem 0.4rem;
}
</style>

<!-- ───── JavaScript ───── -->
<script>
$(document).ready(function(){
    var table = $('#resultTable').DataTable({ pageLength:20, order:[[2,'desc']] });

    // Handle child row toggle
    $('#resultTable tbody').on('click','td.details-control',function(){
        var tr = $(this).closest('tr'),
            row = table.row(tr),
            provider = tr.data('provider');

        if(row.child.isShown()){
            row.child.hide(); tr.removeClass('shown'); $(this).text('+');
            return;
        }

        // Show loading indicator
        $(this).text('...');
        row.child('<div class="text-center p-2">Loading...</div>').show();
        tr.addClass('shown');

       $.post('<?= $_SERVER['PHP_SELF']?>',{
    ajaxProvider: provider,
    serviceKey: $('select[name="serviceKey"]').val(),
    code: $('select[name="code"]').val(),
    message: $('select[name="message"]').val(),
    hourRange: $('select[name="hourRange"]').val()
}, function(data){

    let serviceKey = $('select[name="serviceKey"]').val();
    let isFlug = (serviceKey === "outbound,inbound");

    if(!data.length) {
        row.child('<div class="text-center p-2">No results found</div>').show();
        return;
    }

    let seen = new Set();
    let uniqueData = data.filter(r => {
        let key = r.MinuteSlot + '|' + r.HotelGiataCode + '|' + r.CodeMessage;
        if(seen.has(key)) return false;
        seen.add(key);
        return true;
    });

    // 👇 بناء الجدول ديناميكياً
    let html = `<table class="table table-bordered table-sm child-row-table">
                <thead><tr>
                <th>Minute</th>`;

    if(!isFlug){
        html += `<th>Giata</th><th>Hotel</th>`;
    }

    html += `<th>Adult</th><th>Children</th><th>Infant</th>
             <th>Agency</th><th>Travel</th><th>Origin</th><th>Destination</th><th>CodeMessage</th>
             </tr></thead><tbody>`;

    uniqueData.forEach(r => {
        html += `<tr>
                    <td>${r.MinuteSlot}</td>`;

        if(!isFlug){
            html += `<td>${r.HotelGiataCode}</td>
                     <td>${r.HotelAccomodation}</td>`;
        }

        html += `<td>${r.AdultCounts}</td>
                 <td>${r.ChildrenCounts}</td>
                 <td>${r.InfantCounts}</td>
                 <td>${r.agency}</td>
                 <td>${r.Reise}</td>
                 <td>${r.OutboundOriginTlc}</td>
                 <td>${r.OutboundArrivalTlc}</td>
                 <td>${r.CodeMessage}</td>
              </tr>`;
    });

    html += `</tbody></table>`;
    row.child(html).show();

    tr.find('td.details-control').text('-');

}, 'json');
    });
});
</script>

<?php include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/footer.php'; ?>