<?php
if (isset($_POST['providerAjax'])) {

    include_once $_SERVER['DOCUMENT_ROOT'].'/hotel/config/sqlServer2.php';

    $isSub = strpos($_POST['providerAjax'], '_subdetails') !== false;

    $connect = SQLServer2::connect();
    if (!$connect) {
        die("Connection error: " . print_r(sqlsrv_errors(), true));
    }

    $dayDate   = (new DateTime())->format('Y-m-d');
    $zeit      = (int)($_POST['zeit'] ?? 0);
    $startTime = str_pad($zeit, 2, '0', STR_PAD_LEFT).":00:00";
    $endTime   = str_pad($zeit, 2, '0', STR_PAD_LEFT).":59:59";

    // =========================
    // SUB DETAILS
    // =========================
    if ($isSub) {

        $provider = $_POST['provider'] ?? '';
        $code     = $_POST['parentCode'] ?? '';

        $sql = "
            SELECT
                PlayerProvider,
                b.Code,
                c.AdultCounts,
                c.ChildrenCounts,
                c.InfantCounts,
                a.[User] AS agency,
                CONCAT(FORMAT(c.OutboundDate,'dd.MM.yy'),' - ', FORMAT(c.InboundDate,'dd.MM.yy')) AS Reise,
                c.OutboundOriginTlc AS Origin,
                c.OutboundArrivalTlc AS destination,
                COUNT(DISTINCT a.id) AS ct
            FROM dbo.OperationInformationObjects a
            JOIN dbo.OperationInformationObject_ResponseMessages b
                ON a.id = b.OperationInformationObject_id
            JOIN dbo.PackageInformationObjects c
                ON a.PackageId = c.PackageId
            WHERE CONVERT(date, c.CreationDate)=?
              AND CONVERT(time, c.CreationDate) BETWEEN ? AND ?
              AND PlayerProvider = ?
              AND b.Code = ?
              AND OperationScope = 'CreatePackage'
            GROUP BY
                PlayerProvider,
                b.Code,
                c.AdultCounts,
                c.ChildrenCounts,
                c.InfantCounts,
                a.[User],
                c.OutboundDate,
                c.InboundDate,
                c.OutboundOriginTlc,
                c.OutboundArrivalTlc
        ";

        $stmt = sqlsrv_prepare($connect, $sql, [
            $dayDate,
            $startTime,
            $endTime,
            $provider,
            $code
        ]);

        sqlsrv_execute($stmt);

        echo "<table id='subdetailsTable' class='table table-striped table-bordered mt-2'>
                <thead>
                    <tr>
                        <th>Provider</th>
                        <th>Code</th>
                        <th>Adults</th>
                        <th>Children</th>
                        <th>Agency</th>
                        <th>Reise</th>
                        <th>Origin</th>
                        <th>Destination</th>
                        <th>Infants</th>
                        <th>Count</th>
                    </tr>
                </thead><tbody>";

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            echo "<tr>
                <td>{$row['PlayerProvider']}</td>
                <td>{$row['Code']}</td>
                <td>{$row['AdultCounts']}</td>
                <td>{$row['ChildrenCounts']}</td>
                <td>{$row['agency']}</td>
                <td>{$row['Reise']}</td>
                <td>{$row['Origin']}</td>
                <td>{$row['destination']}</td>
                <td>{$row['InfantCounts']}</td>
                <td>{$row['ct']}</td>
            </tr>";
        }

        echo "</tbody></table>";

        sqlsrv_close($connect);
        exit;
    }

    // =========================
    // DETAILS
    // =========================
    $provider = $_POST['providerAjax'];
    $code     = $_POST['code'] ?? 'all';

    $sql = "
        SELECT
            PlayerProvider,
            b.Code,
            c.AdultCounts,
            c.ChildrenCounts,
            c.InfantCounts,
            a.[User] AS agency,
            CONCAT(FORMAT(c.OutboundDate,'dd.MM.yy'),' - ', FORMAT(c.InboundDate,'dd.MM.yy')) AS Reise,
            c.OutboundOriginTlc AS Origin,
            c.OutboundArrivalTlc AS destination,
            COUNT(DISTINCT a.id) AS ct
        FROM dbo.OperationInformationObjects a
        JOIN dbo.OperationInformationObject_ResponseMessages b
            ON a.id = b.OperationInformationObject_id
        JOIN dbo.PackageInformationObjects c
            ON a.PackageId = c.PackageId
        WHERE CONVERT(date, c.CreationDate)=?
          AND CONVERT(time, c.CreationDate) BETWEEN ? AND ?
          AND PlayerProvider = ?
          AND OperationScope = 'CreatePackage'
    ";

    $params = [$dayDate, $startTime, $endTime, $provider];

    if ($code !== 'all') {
        $sql .= " AND b.Code = ?";
        $params[] = $code;
    }

    $sql .= "
        GROUP BY
            PlayerProvider,
            b.Code,
            c.AdultCounts,
            c.ChildrenCounts,
            c.InfantCounts,
            a.[User],
            c.OutboundDate,
            c.InboundDate,
            c.OutboundOriginTlc,
            c.OutboundArrivalTlc
    ";

    $stmt = sqlsrv_prepare($connect, $sql, $params);
    sqlsrv_execute($stmt);

    echo "<div id='ajax-details'>";
    echo "<table id='table2' class='table table-bordered table-sm'>
          <thead>
          <tr>
            <th>Provider</th>
            <th>Code</th>
            <th>Agency</th>
            <th>Reise</th>
            <th>Origin</th>
            <th>Destination</th>
            <th>Adults</th>
            <th>Children</th>
            <th>Infants</th>
            <th>Count</th>
          </tr>
          </thead><tbody>";

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {

        echo "<tr class='details-row'
                data-provider='".htmlspecialchars($row['PlayerProvider'],ENT_QUOTES)."'
                data-code='".htmlspecialchars($row['Code'],ENT_QUOTES)."'
                data-zeit='$zeit'>
            <td>{$row['PlayerProvider']}</td>
            <td>{$row['Code']}</td>
            <td>{$row['agency']}</td>
            <td>{$row['Reise']}</td>
            <td>{$row['Origin']}</td>
            <td>{$row['destination']}</td>
            <td>{$row['AdultCounts']}</td>
            <td>{$row['ChildrenCounts']}</td>
            <td>{$row['InfantCounts']}</td>
            <td>{$row['ct']}</td>
        </tr>";
    }

    echo "</tbody></table></div>";
    echo "<div id='subdetails-container'></div>";

    sqlsrv_close($connect);
    exit;
}


// ─── Full page content ───────────────────────────────────────────────
$pageTitle = "Top 10 Flights";
include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/header.php';
?>

<div class="container mt-4">

  <form method="POST" action="" class="mb-4">


  <div class="row">
    <div class="col-md-6 mb-3">
      <label for="code" class="form-label">Code</label>
      <select name="code" class="form-select" id="code">
        <option value="all" <?= (($_POST['code'] ?? '') === 'all') ? 'selected' : '' ?>>All</option>
        <option value="SS:ERR:18" <?= (($_POST['code'] ?? '') === 'SS:ERR:18') ? 'selected' : '' ?>>SS:ERR:18</option>
        <option value="IFS:BHUB:INF:0" <?= (($_POST['code'] ?? '') === 'IFS:BHUB:INF:0') ? 'selected' : '' ?>>IFS:BHUB:INF:0</option>
        <option value="IFS:EMIND:ERR:1160" <?= (($_POST['code'] ?? '') === 'IFS:EMIND:ERR:1160') ? 'selected' : '' ?>>IFS:EMIND:ERR:1160</option>
      </select>
    </div>
  </div>

  
  <div class="row">
    <div class="col-md-6 mb-3">
      <label for="zeit" class="form-label">Zeit</label>
      <select name="zeit" class="form-select" id="zeit">
        <?php for ($h = 0; $h < 24; $h++):
            $hh  = str_pad($h, 2, "0", STR_PAD_LEFT);
            $sel = (isset($_POST['zeit']) && (int)$_POST['zeit'] === $h) ? 'selected' : '';
        ?>
          <option value="<?= $h ?>" <?= $sel ?>><?= "$hh:00 – $hh:59" ?></option>
        <?php endfor; ?>
      </select>
    </div>

    <div class="col-md-3 mb-3 d-flex align-items-end">
      <button type="submit" name="submit" class="btn btn-primary w-100">
        Suchen
      </button>
    </div>
     <div id="code-message" class="mt-3"></div>
  </div>

</form>
<?php
// ─── Display Top 10 Providers ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['providerAjax'])) {

    include_once $_SERVER['DOCUMENT_ROOT'].'/hotel/config/sqlServer2.php';

    $zeit = (int) ($_POST['zeit'] ?? 0);
    $code = $_POST['code'] ?? 'all';
    $dayDate = (new DateTime())->format('Y-m-d');
    $startTime = str_pad($zeit, 2, "0", STR_PAD_LEFT) . ":00:00";
    $endTime   = str_pad($zeit, 2, "0", STR_PAD_LEFT) . ":59:59";

    $connect = SQLServer2::connect();
    if (!$connect) { die("Connection error: " . print_r(sqlsrv_errors(), true)); }

    $codes = ($code === 'all') ? "'SS:ERR:18','IFS:BHUB:INF:0'" : "'" . addslashes($code) . "'";

    $query = "
WITH b_filtered AS (
    SELECT *
    FROM dbo.OperationInformationObject_ResponseMessages
    WHERE Code IN ('SS:ERR:18','IFS:BHUB:INF:0','IFS:EMIND:ERR:1160')
      AND (
            (Code = 'SS:ERR:18' AND message LIKE '%ist nicht verfügbar%')
         OR (Code = 'IFS:BHUB:INF:0' AND message LIKE '%connection%')
         OR (Code = 'IFS:EMIND:ERR:1160' AND message LIKE '%Flugleistung%nicht mehr vorhanden%')
      )
),
base AS (
    SELECT
        a.id,
        b.PlayerProvider,
        b.Code
    FROM dbo.OperationInformationObjects a
    JOIN b_filtered b
        ON a.id = b.OperationInformationObject_id
    JOIN dbo.PackageInformationObjects c
        ON a.PackageId = c.PackageId
    WHERE CONVERT(date, c.CreationDate) = ?
      AND CONVERT(time, c.CreationDate) BETWEEN ? AND ?
      AND a.OperationScope = 'CreatePackage'
)

SELECT TOP(10)
    PlayerProvider,
    Code,
    COUNT(DISTINCT id) AS ct
FROM base
GROUP BY PlayerProvider, Code
ORDER BY ct DESC;
    ";

    $stmt = sqlsrv_prepare($connect, $query, [$dayDate, $startTime, $endTime]);
    if (!$stmt || !sqlsrv_execute($stmt)) {
        die("SQL Error: " . print_r(sqlsrv_errors(), true));
    }

    echo "<div class='row'><div class='col'>
          <table id='table1' class='table table-striped table-bordered'>
          <thead><tr><th>Provider</th><th>Count</th></tr></thead>
          <tbody>";

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $p = htmlspecialchars($row['PlayerProvider'], ENT_QUOTES);
        echo "<tr style='cursor:pointer' data-provider='$p'>
                <td>$p</td>
                <td>{$row['ct']}</td>
              </tr>";
    }

    echo "</tbody></table></div></div>";
    sqlsrv_close($connect);

    echo "<div id='details-container' class='mt-4'></div>";
}
?>

</div>

<script>
$(document).ready(function () {

    // =========================
    // DataTable for table1
    // =========================
    if ($('#table1').length) {
        $('#table1').DataTable({
            dom: 'Bfrtip',
            buttons: ['excel', 'csv'],
            order: [[1, 'desc']],
            paging: false
        });
    }

    // =========================
    // CLICK: Provider → Details
    // =========================
    $(document).on('click', '#table1 tbody tr', function () {

        $('#table1 tbody tr').removeClass('table-active');
        $(this).addClass('table-active');

        let provider = $(this).data('provider');
        let code     = $('#code').val();
        let zeit     = $('#zeit').val();

        if (!provider) return;

        $('#details-container').html(`
            <div class="text-center p-3">
                <div class="spinner-border"></div>
            </div>
        `);

        $.ajax({
            url: '',
            type: 'POST',
            data: {
                providerAjax: provider,
                provider: provider,
                code: code,
                zeit: zeit
            },
            success: function (html) {

                $('#details-container').html(html);

                // destroy old table2 if exists
                if ($.fn.DataTable.isDataTable('#table2')) {
                    $('#table2').DataTable().destroy();
                }

                if ($('#table2').length) {
                    $('#table2').DataTable({
                        dom: 'Bfrtip',
                        buttons: ['excel', 'csv'],
                        pageLength: 10,
                        lengthMenu: [5, 10, 25, 50, 100],
                        order: [[8, 'desc']], // Count column
                        responsive: true,
                        searching: true
                    });
                }
            },
            error: function () {
                $('#details-container').html(`
                    <div class="alert alert-danger">
                        Fehler beim Laden der Daten
                    </div>
                `);
            }
        });
    });


    // =========================
    // CLICK: Details → SubDetails
    // =========================
    $(document).on('click', '#table2 tbody tr', function () {

        $('#table2 tbody tr').removeClass('table-active');
        $(this).addClass('table-active');

        let provider = $(this).data('provider');
        let zeit     = $(this).data('zeit');
        let code     = $('#code').val();

        if (!provider) return;

        $('#subdetails-container').html(`
            <div class="text-center p-3">
                <div class="spinner-border"></div>
            </div>
        `);

        $.ajax({
            url: '',
            type: 'POST',
            data: {
                providerAjax: provider + '_subdetails',
                provider: provider,
                zeit: zeit,
                parentCode: code,
                hotelgiatacode: ''
            },
            success: function (html) {

                $('#subdetails-container').html(html);

                // destroy old sub table if exists
                if ($.fn.DataTable.isDataTable('#subdetailsTable')) {
                    $('#subdetailsTable').DataTable().destroy();
                }

                if ($('#subdetailsTable').length) {
                    $('#subdetailsTable').DataTable({
                        dom: 'Bfrtip',
                        buttons: ['excel', 'csv'],
                        pageLength: 10,
                        lengthMenu: [5, 10, 25, 50, 100],
                        order: [[8, 'desc']],
                        responsive: true,
                        searching: true
                    });
                }
            },
            error: function () {
                $('#subdetails-container').html(`
                    <div class="alert alert-danger">
                        Fehler beim Laden der SubDetails
                    </div>
                `);
            }
        });
    });

});
</script>

<script>
function updateCodeMessage() {
    var code = $('#code').val();
    var html = '';

    if (code === 'SS:ERR:18') {
        html = '<div class="alert alert-danger">Flight(s) not available</div>';
    } 
    else if (code === 'IFS:BHUB:INF:0') {
        html = '<div class="alert alert-warning">The connection to the service provider is disturbed</div>';
    } 
    else if (code === 'IFS:EMIND:ERR:1160') {
        html = '<div class="alert alert-warning">Flugleistung nicht mehr vorhanden</div>';
    } 
     
    else {
        html = '';
    }

    $('#code-message').html(html);
}


$(document).on('change', '#code', function () {
    updateCodeMessage();
});


$(document).ready(function () {
    updateCodeMessage();
});
</script>


<?php include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/footer.php'; ?>