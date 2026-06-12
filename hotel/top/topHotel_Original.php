<?php
// ─── AJAX Handler ───────────────────────────────────────────────
if (isset($_POST['providerAjax'])) {

    include_once $_SERVER['DOCUMENT_ROOT'].'/hotel/config/sqlServer2.php';

    $isSub = strpos($_POST['providerAjax'], '_subdetails') !== false;

    if ($isSub) {
        // ─── SubDetails Request ───────────────────────────────
        $provider   = str_replace('_subdetails','', $_POST['providerAjax']);
        $parentCode = $_POST['parentCode'] ?? '';
        $giata      = $_POST['hotelgiatacode'] ?? '%';
        $zeit       = (int)$_POST['zeit'];
        $dayDate    = (new DateTime())->format('Y-m-d');
        $startTime  = str_pad($zeit,2,'0',STR_PAD_LEFT).":00:00";
        $endTime    = str_pad($zeit,2,'0',STR_PAD_LEFT).":59:59";

        $connect = SQLServer2::connect();
        if (!$connect) { die("Connection error: " . print_r(sqlsrv_errors(), true)); }

        $sql = "
            SELECT
                PlayerProvider,
                b.Code,
                c.HotelGiataCode,
                c.HotelAccomodation,
                c.AdultCounts,
                c.ChildrenCounts,
                c.InfantCounts,
                a.[User] AS agency,
                CONCAT(FORMAT(c.OutboundDate,'dd.MM.yy'),' - ', FORMAT(c.InboundDate,'dd.MM.yy')) AS Reise,
                c.OutboundOriginTlc AS Origin,
                c.OutboundArrivalTlc AS destination,
                COUNT(a.id) AS ct
            FROM dbo.OperationInformationObjects a
            JOIN dbo.OperationInformationObject_ResponseMessages b
                ON a.id = b.OperationInformationObject_id
            JOIN dbo.PackageInformationObjects c
                ON a.PackageId = c.PackageId
            WHERE CONVERT(date, c.CreationDate)=?
              AND CONVERT(time, c.CreationDate) BETWEEN ? AND ?
              AND PlayerProvider LIKE ?
              AND b.Code = ?
              AND c.HotelGiataCode LIKE ?
              AND OperationScope LIKE 'CreatePackage'
            GROUP BY
                PlayerProvider, b.Code, c.HotelGiataCode, c.HotelAccomodation,
                c.AdultCounts, c.ChildrenCounts, c.InfantCounts, a.[User],
                CONCAT(FORMAT(c.OutboundDate,'dd.MM.yy'),' - ',FORMAT(c.InboundDate,'dd.MM.yy')),
                c.OutboundOriginTlc, c.OutboundArrivalTlc
        ";

        $stmt = sqlsrv_prepare($connect, $sql, [$dayDate, $startTime, $endTime, $provider, $parentCode, $giata]);
        sqlsrv_execute($stmt);

        echo "<table id='subdetailsTable' class='table table-striped table-bordered mt-2'>
                <thead>
                    <tr>
                        <th>Provider</th><th>Code</th><th>GiataCode</th><th>Accomodation</th>
                        <th>Adults</th><th>Children</th><th>Infants</th>
                        <th>Agency</th><th>Reise</th><th>Origin</th><th>Destination</th><th>Count</th>
                    </tr>
                </thead>
                <tbody>";

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            echo "<tr>
                <td>".htmlspecialchars($row['PlayerProvider'])."</td>
                <td>".htmlspecialchars($row['Code'])."</td>
                <td>".htmlspecialchars($row['HotelGiataCode'])."</td>
                <td>".htmlspecialchars($row['HotelAccomodation'])."</td>
                <td>".(int)$row['AdultCounts']."</td>
                <td>".(int)$row['ChildrenCounts']."</td>
                <td>".(int)$row['InfantCounts']."</td>
                <td>".htmlspecialchars($row['agency'])."</td>
                <td>".htmlspecialchars($row['Reise'])."</td>
                <td>".htmlspecialchars($row['Origin'])."</td>
                <td>".htmlspecialchars($row['destination'])."</td>
                <td>".(int)$row['ct']."</td>
            </tr>";
        }
        echo "</tbody></table>";
        sqlsrv_close($connect);
        exit;

    } else {
        // ─── Details Request ───────────────────────────────
        $provider  = $_POST['providerAjax'];
        $code      = $_POST['code'] ?? 'all';
        $zeit      = (int)$_POST['zeit'];
        $dayDate   = (new DateTime())->format('Y-m-d');
        $startTime = str_pad($zeit, 2, "0", STR_PAD_LEFT) . ":00:00";
        $endTime   = str_pad($zeit, 2, "0", STR_PAD_LEFT) . ":59:59";

        $connect = SQLServer2::connect();
        if (!$connect) { die("Connection error: " . print_r(sqlsrv_errors(), true)); }

        $codes = ($code === 'all') ? "'SS:ERR:11191','SS:ERR:1137'" : "'" . addslashes($code) . "'";

        $sql = "
            SELECT
                PlayerProvider,
                b.Code,
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
              AND PlayerProvider LIKE ?
              AND b.Code IN ($codes)
              AND OperationScope LIKE 'CreatePackage'
            GROUP BY
                PlayerProvider, b.Code, c.HotelGiataCode, c.HotelAccomodation,
                c.AdultCounts, c.ChildrenCounts, c.InfantCounts, a.[User],
                CONCAT(FORMAT(c.OutboundDate,'dd.MM.yy'),' - ',FORMAT(c.InboundDate,'dd.MM.yy')),
                c.OutboundOriginTlc, c.OutboundArrivalTlc
        ";

        $stmt = sqlsrv_prepare($connect, $sql, [$dayDate, $startTime, $endTime, $provider]);
        sqlsrv_execute($stmt);

        echo '<div id="ajax-details">';
        echo "<h5 class='mb-3'>Details für: <strong>" . htmlspecialchars($provider) . "</strong></h5>";
        echo "<div class='table-responsive'>
              <table id='table2' class='table table-bordered table-sm'>
              <thead class='table-light'>
              <tr>
                <th>Provider</th><th>GiataCode</th><th>Accomodation</th>
                <th>Adults</th><th>Children</th><th>Infants</th>
                <th>Reise</th><th>Agency</th><th>Origin</th><th>Destination</th><th>Count</th>
              </tr>
              </thead><tbody>";

        $hasRows = false;
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $hasRows = true;
            echo "<tr class='details-row'
                    data-provider='".htmlspecialchars($row['PlayerProvider'],ENT_QUOTES)."'
                    data-giata='".htmlspecialchars($row['HotelGiataCode'],ENT_QUOTES)."'
                    data-code='".htmlspecialchars($row['Code'],ENT_QUOTES)."'
                    data-zeit='$zeit'>
                <td>" . htmlspecialchars($row['PlayerProvider'])    . "</td>
                <td>" . htmlspecialchars($row['HotelGiataCode'])    . "</td>
                <td>" . htmlspecialchars($row['HotelAccomodation']) . "</td>
                <td>" . (int)$row['AdultCounts']                    . "</td>
                <td>" . (int)$row['ChildrenCounts']                 . "</td>
                <td>" . (int)$row['InfantCounts']                   . "</td>
                <td>" . htmlspecialchars($row['Reise'])             . "</td>
                <td>" . htmlspecialchars($row['agency'])            . "</td>
                <td>" . htmlspecialchars($row['Origin'])            . "</td>
                <td>" . htmlspecialchars($row['destination'])       . "</td>
                <td>" . (int)$row['ct']                             . "</td>
              </tr>";
        }

        if (!$hasRows) {
            echo "<tr><td colspan='12' class='text-center text-muted'>Keine Daten gefunden.</td></tr>";
        }

        echo "</tbody></table></div>";
        echo '<div id="subdetails-container" class="mt-3"></div>';
        echo '</div>';

        sqlsrv_close($connect);
        exit;
    }
}

// ─── Full page content ───────────────────────────────────────────────
$pageTitle = "Top 10 Hotels";
include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/header.php';
?>

<div class="container mt-4">

  <form method="POST" action="" class="mb-4">
    <div class="row">
      <div class="col-md-4 mb-3">
        <label for="code" class="form-label">Code</label>
        <select name="code" class="form-select" id="code">
          <option value="all" <?= (($_POST['code'] ?? '') === 'all') ? 'selected' : '' ?>>All</option>
          <option value="SS:ERR:11191" <?= (($_POST['code'] ?? '') === 'SS:ERR:11191') ? 'selected' : '' ?>>SS:ERR:11191</option>
          <option value="SS:ERR:1137" <?= (($_POST['code'] ?? '') === 'SS:ERR:1137') ? 'selected' : '' ?>>SS:ERR:1137</option>
          <option value="SS:INFO:00443" <?= (($_POST['code'] ?? '') === 'SS:INFO:00443') ? 'selected' : '' ?>>SS:INFO:00443</option>
        </select>
      </div>

      <div class="col-md-4 mb-3">
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
        <button type="submit" name="submit" class="btn btn-primary">Suchen</button>
      </div>
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

    $codes = ($code === 'all') ? "'SS:ERR:11191','SS:ERR:1137'" : "'" . addslashes($code) . "'";

    $query = "
        SELECT TOP(10)
            PlayerProvider,
            COUNT(DISTINCT a.id) AS ct
        FROM dbo.OperationInformationObjects a
        JOIN dbo.OperationInformationObject_ResponseMessages b
            ON a.id = b.OperationInformationObject_id
        JOIN dbo.PackageInformationObjects c
            ON a.PackageId = c.PackageId
        WHERE CONVERT(date, c.CreationDate) = ?
          AND CONVERT(time, c.CreationDate) BETWEEN ? AND ?
          AND OperationScope LIKE 'CreatePackage'
          AND Code IN ($codes)
        GROUP BY PlayerProvider
        ORDER BY COUNT(DISTINCT a.id) DESC
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

    if ($('#table1').length) {
        $('#table1').DataTable({
            dom: 'Brtip',
            buttons: ['excel', 'csv'],
            order: [[1, 'desc']],
            paging: false
        });
    }

    // ── Click Provider → Details ──
    $(document).on('click', '#table1 tbody tr', function () {

        $('#table1 tbody tr').removeClass('table-active');
        $(this).addClass('table-active');

        var provider = $(this).data('provider');
        var code     = $('#code').val();
        var zeit     = $('#zeit').val();

        if (!provider) return;

        $('#details-container').html('<div class="text-center p-3"><div class="spinner-border"></div></div>');

        $.ajax({
            url: '',
            type: 'POST',
            data: { providerAjax: provider, code: code, zeit: zeit },
            success: function (html) {
                $('#details-container').html(html);

                if ($.fn.DataTable.isDataTable('#table2')) {
                    $('#table2').DataTable().destroy();
                }

                if ($('#table2').length) {
                    $('#table2').DataTable({
                        dom: '<"d-none"B>frtip',
                        buttons: ['excel', 'csv'],
                        pageLength: 10,
                        lengthMenu: [5,10,25,50,100],
                        order: [[10,'desc']],
                        responsive:true,
                        searching:true
                    });
                }
            },
            error: function () {
                $('#details-container').html('<div class="alert alert-danger">Fehler beim Laden der Daten.</div>');
            }
        });
    });

    // ── Click Details → SubDetails ──
    $(document).on('click', '#table2 tbody tr', function() {

        $('#table2 tbody tr').removeClass('table-active');
        $(this).addClass('table-active');

        var provider = $(this).data('provider');
        var giata    = $(this).data('giata');
        var code     = $(this).data('code');
        var zeit     = $(this).data('zeit');

        $('#subdetails-container').html('<div class="text-center p-3"><div class="spinner-border"></div></div>');

        $.ajax({
            url: '',
            type: 'POST',
            data: {
                providerAjax: provider+'_subdetails',
                parentCode: code,
                hotelgiatacode: giata,
                zeit: zeit
            },
            success: function(html){
                $('#subdetails-container').html(html);

                if ($.fn.DataTable.isDataTable('#subdetailsTable')) {
                    $('#subdetailsTable').DataTable().destroy();
                }

                $('#subdetailsTable').DataTable({
                    dom:'Brtip',
                    buttons:['excel','csv'],
                    pageLength:10,
                    lengthMenu:[5,10,25,50,100],
                    order:[[11,'desc']],
                    responsive:true,
                    searching:true
                });
            },
            error: function() {
                $('#subdetails-container').html('<div class="alert alert-danger">Fehler beim Laden der SubDetails.</div>');
            }
        });
    });

});
</script>

<?php include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/footer.php'; ?>