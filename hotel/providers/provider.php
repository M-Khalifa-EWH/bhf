<?php
// ───────────────── AJAX DETAILS ─────────────────
if (isset($_POST['ajaxDetails'])) {

    include_once $_SERVER['DOCUMENT_ROOT'].'/hotel/config/sqlServer2.php';

    $providerClicked = $_POST['providerClicked'];
    $code = $_POST['code'] ?? 'all';

    $startHour = (int)$_POST['start'];
    $endHour   = (int)$_POST['end'];

    $startTime = str_pad($startHour,2,'0',STR_PAD_LEFT).":00:00";
    $endTime   = str_pad($endHour,2,'0',STR_PAD_LEFT).":59:59";
    $dayDate   = (new DateTime())->format('Y-m-d');

    $connect = SQLServer2::connect();

    $codes = ($code === 'all')
        ? "'SS:ERR:11191','SS:INFO:00443','SS:ERR:11200','SS:ERR:1137'"
        : "'" . addslashes($code) . "'";

    // دعم UNKNOWN
    if ($providerClicked === 'UNKNOWN') {
        $providerCondition = "(PlayerProvider IS NULL OR PlayerProvider='')";
        $paramsProvider = [];
    } else {
        $providerCondition = "PlayerProvider = ?";
        $paramsProvider = [$providerClicked];
    }

    $sql = "
    SELECT
        ISNULL(NULLIF(PlayerProvider,''),'UNKNOWN') AS PlayerProvider,
        b.Code,
        c.PlayerBrand AS PlayerBrand,
        c.HotelGiataCode,
        c.HotelAccomodation,
        c.AdultCounts,
        c.ChildrenCounts,
        c.InfantCounts,
        a.[User] AS agency,
        CONCAT(FORMAT(c.OutboundDate,'dd.MM.yy'),' - ', FORMAT(c.InboundDate,'dd.MM.yy')) AS Reise,
        c.OutboundOriginTlc  AS Origin,
        c.OutboundArrivalTlc AS destination,
        COUNT(a.id) AS ct
    FROM dbo.OperationInformationObjects a
    JOIN dbo.OperationInformationObject_ResponseMessages b
        ON a.id = b.OperationInformationObject_id
    JOIN dbo.PackageInformationObjects c
        ON a.PackageId = c.PackageId
    WHERE CONVERT(date, c.CreationDate) = ?
      AND CONVERT(time, c.CreationDate) BETWEEN ? AND ?
      AND $providerCondition
      AND b.Code IN ($codes)
      AND OperationScope LIKE 'CreatePackage'
      AND (
            b.message LIKE
            CASE b.Code
                WHEN 'SS:ERR:11191'  THEN '%All such room are booked out in the hotel%'
                WHEN 'SS:INFO:00443' THEN '%The connection to the service provider is disturbed.%'
                WHEN 'SS:ERR:11200' THEN '%hotel is not activated for usage%'
                ELSE '%'
            END
          )
    GROUP BY
        ISNULL(NULLIF(PlayerProvider,''),'UNKNOWN'), c.PlayerBrand,
        b.Code, c.HotelGiataCode, a.[User],c.HotelAccomodation,
        c.AdultCounts, c.ChildrenCounts, c.InfantCounts,
        
        CONCAT(FORMAT(c.OutboundDate,'dd.MM.yy'),' - ', FORMAT(c.InboundDate,'dd.MM.yy')),
        c.OutboundOriginTlc, c.OutboundArrivalTlc
    ORDER BY ct DESC
    ";

    $params = array_merge([
        $dayDate,
        $startTime,
        $endTime
    ], $paramsProvider);

    $stmt = sqlsrv_prepare($connect, $sql, $params);
    sqlsrv_execute($stmt);

    echo "<div class='table-responsive'>
      <table id='detailsTable' class='table table-bordered table-sm mt-3'>
          <thead class='table-light'>
          <tr>
            <th>Provider</th><th>Brand</th><th>Agency</th><th>Giata</th><th>Accomodation</th>
            <th>Adults</th><th>Children</th><th>Infants</th>
            <th>Reise</th><th>Origin</th><th>Destination</th><th>Count</th>
          </tr>
          </thead><tbody>";

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo "<tr>
            <td>{$row['PlayerProvider']}</td>
            <td>{$row['PlayerBrand']}</td>
            <td>{$row['agency']}</td>
            <td>{$row['HotelGiataCode']}</td>
            <td>{$row['HotelAccomodation']}</td>
            <td>{$row['AdultCounts']}</td>
            <td>{$row['ChildrenCounts']}</td>
            <td>{$row['InfantCounts']}</td>
            <td>{$row['Reise']}</td>
            <td>{$row['Origin']}</td>
            <td>{$row['destination']}</td>
            <td>{$row['ct']}</td>
        </tr>";
    }

    echo "</tbody></table></div>";
    exit;
}
?>

<?php
// ───────────────── PAGE ─────────────────
include_once $_SERVER['DOCUMENT_ROOT'].'/hotel/config/sqlServer2.php';
include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/header.php';
?>

<div class="container mt-4">

<h3 class="mb-4">Provider Filter (Today Only)</h3>

<form method="POST" class="row g-3 mb-4">

    <div class="col-md-3">
        <input type="text" name="provider" class="form-control"
               value="<?= $_POST['provider'] ?? '' ?>" placeholder="Provider">
    </div>

    <div class="col-md-3">
        <?php $selectedCode = $_POST['code'] ?? 'all'; ?>
        <select name="code" id="code" class="form-select">
            <option value="all" <?= $selectedCode=='all'?'selected':'' ?>>All</option>
            <option value="SS:ERR:11191" <?= $selectedCode=='SS:ERR:11191'?'selected':'' ?>>SS:ERR:11191</option>
            <option value="SS:ERR:1137" <?= $selectedCode=='SS:ERR:1137'?'selected':'' ?>>SS:ERR:1137</option>
            <option value="SS:INFO:00443" <?= $selectedCode=='SS:INFO:00443'?'selected':'' ?>>SS:INFO:00443</option>
            <option value="SS:ERR:11200" <?= $selectedCode=='SS:ERR:11200'?'selected':'' ?>>SS:ERR:11200</option>
        </select>
    </div>

   <div class="col-md-2">

    <?php $selectedStart = $_POST['start_hour'] ?? 0; ?>

    <select name="start_hour" class="form-select">
        <?php for($h=0;$h<24;$h++): ?>
            <option value="<?= $h ?>"
                <?= $selectedStart == $h ? 'selected' : '' ?>>

                <?= str_pad($h,2,'0',STR_PAD_LEFT) ?>:00

            </option>
        <?php endfor; ?>
    </select>

</div>

<div class="col-md-2">

    <?php $selectedEnd = $_POST['end_hour'] ?? 0; ?>

    <select name="end_hour" class="form-select">
        <?php for($h=0;$h<24;$h++): ?>
            <option value="<?= $h ?>"
                <?= $selectedEnd == $h ? 'selected' : '' ?>>

                <?= str_pad($h,2,'0',STR_PAD_LEFT) ?>:59

            </option>
        <?php endfor; ?>
    </select>

</div>

    <div class="col-md-2">
        <button class="btn btn-primary w-100">Search</button>
    </div>
<div class="col-12">
    <div id="code-message" class="mt-2"></div>
</div>

</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $providerInput = trim($_POST['provider'] ?? '');
    $code = $_POST['code'] ?? 'all';

    $startHour = (int)$_POST['start_hour'];
    $endHour   = (int)$_POST['end_hour'];

    $startTime = str_pad($startHour,2,'0',STR_PAD_LEFT).":00:00";
    $endTime   = str_pad($endHour,2,'0',STR_PAD_LEFT).":59:59";
    $dayDate   = (new DateTime())->format('Y-m-d');

    $connect = SQLServer2::connect();

    $codes = ($code === 'all')
        ? "'SS:ERR:11191','SS:INFO:00443','SS:ERR:11200','SS:ERR:1137'"
        : "'" . addslashes($code) . "'";

    $sql = "
    SELECT
        ISNULL(NULLIF(PlayerProvider,''),'UNKNOWN') AS PlayerProvider,
        COUNT(*) ct
    FROM dbo.OperationInformationObjects a
    JOIN dbo.OperationInformationObject_ResponseMessages b
        ON a.id = b.OperationInformationObject_id
    JOIN dbo.PackageInformationObjects c
        ON a.PackageId = c.PackageId
    WHERE CONVERT(date, c.CreationDate) = ?
      AND CONVERT(time, c.CreationDate) BETWEEN ? AND ?
      AND (
            (?='' AND (PlayerProvider IS NULL OR PlayerProvider=''))
            OR (?<>'' AND PlayerProvider LIKE ?)
          )
      AND Code IN ($codes)
    GROUP BY ISNULL(NULLIF(PlayerProvider,''),'UNKNOWN')
    ORDER BY ct DESC
    ";

    $stmt = sqlsrv_prepare($connect, $sql, [
        $dayDate,
        $startTime,
        $endTime,
        $providerInput,
        $providerInput,
        "%$providerInput%"
    ]);

    sqlsrv_execute($stmt);

    echo "<table class='table table-bordered table-striped'>
    <tr><th>Provider</th><th>Count</th></tr>";

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo "<tr class='provider-row' style='cursor:pointer'
                data-provider='{$row['PlayerProvider']}'>
            <td>{$row['PlayerProvider']}</td>
            <td>{$row['ct']}</td>
        </tr>";
    }

    echo "</table>";
    echo "<div id='details-container'></div>";
}
?>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).on('click', '.provider-row', function () {

    var providerClicked = $(this).data('provider');

    var code  = $('select[name=code]').val();
    var start = $('select[name=start_hour]').val();
    var end   = $('select[name=end_hour]').val();

    $('#details-container').html('<div class="spinner-border"></div>');

    $.post('', {
        ajaxDetails: 1,
        providerClicked: providerClicked,
        code: code,
        start: start,
        end: end
    }, function (html) {
        $('#details-container').html(html);
        // Destroy if exists
    if ($.fn.DataTable.isDataTable('#detailsTable')) {
        $('#detailsTable').DataTable().destroy();
    }

    // Init DataTable
    $('#detailsTable').DataTable({
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        order: [[11, 'desc']], // Count column
        responsive: true,
        searching: true,
        dom: 'Bfrtip',
        buttons: ['excel', 'csv']
    });

    });
});
</script>
<script>

$(document).ready(function () {

    function updateCodeMessage() {

        let code = $('#code').val();
        let html = '';

        switch(code) {

            case 'SS:ERR:11191':
                html =
                '<div class="alert alert-danger">' +
                'All rooms are booked out in the hotel' +
                '</div>';
                break;

            case 'SS:INFO:00443':
                html =
                '<div class="alert alert-warning">' +
                'The connection to the service provider is disturbed' +
                '</div>';
                break;

            case 'SS:ERR:11200':
                html =
                '<div class="alert alert-warning">' +
                'Hotel is not activated for usage' +
                '</div>';
                break;

            case 'SS:ERR:1137':
                html =
                '<div class="alert alert-secondary">' +
                'General error' +
                '</div>';
                break;

            default:
                html = '';
        }

        $('#code-message').html(html);
    }

    updateCodeMessage();

    $('#code').on('change', function () {
        updateCodeMessage();
    });

});

</script>

<?php include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/footer.php'; ?>