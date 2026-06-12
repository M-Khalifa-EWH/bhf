<?php
// ─── AJAX Handler ───────────────────────────────────────────────
if (isset($_POST['providerAjax'])) {

    include_once $_SERVER['DOCUMENT_ROOT'].'/hotel/config/sqlServer2.php';

    $isSub = strpos($_POST['providerAjax'], '_subdetails') !== false;

    $dayDate = (new DateTime())->format('Y-m-d');
    $zeit    = (int)($_POST['zeit'] ?? 0);
    $startTime = str_pad($zeit, 2, '0', STR_PAD_LEFT) . ":00:00";
    $endTime   = str_pad($zeit, 2, '0', STR_PAD_LEFT) . ":59:59";

    $message = $_POST['message'] ?? 'all';

    $messageCondition = '';
    $messageParams = [];

    if ($message !== 'all') {
        $messageCondition = " AND b.Message = ? ";
        $messageParams[] = $message;
    }

    $connect = SQLServer2::connect();
    if (!$connect) {
        die("Connection error: " . print_r(sqlsrv_errors(), true));
    }

    if ($isSub) {
        // ─── SubDetails Request ───────────────────────────────
        $provider   = str_replace('_subdetails', '', $_POST['providerAjax']);
        $parentCode = $_POST['parentCode'] ?? '';
        $giata      = $_POST['hotelgiatacode'] ?? '%';

        if ($giata === '') {
            $giata = '%';
        }

        $providerCondition = ($provider === 'NULL')
            ? "(PlayerProvider IS NULL OR PlayerProvider LIKE ?)"
            : "PlayerProvider LIKE ?";

        $providerParam = ($provider === 'NULL') ? '%' : $provider;

        $sql = "
SELECT
    c.PlayerBrand AS PlayerBrand,
    c.HotelCode AS HotelCode,
    b.Code,
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
WHERE CONVERT(date, c.CreationDate) = ?
  AND CONVERT(time, c.CreationDate) BETWEEN ? AND ?
  AND $providerCondition
  AND b.Code = ?
  AND c.HotelCode LIKE ?
  $messageCondition
  AND OperationScope LIKE 'CreatePackage'
GROUP BY
    c.PlayerBrand,
    c.HotelCode,
    b.Code,
    c.HotelAccomodation,
    c.AdultCounts,
    c.ChildrenCounts,
    c.InfantCounts,
    a.[User],
    CONCAT(FORMAT(c.OutboundDate,'dd.MM.yy'),' - ',FORMAT(c.InboundDate,'dd.MM.yy')),
    c.OutboundOriginTlc,
    c.OutboundArrivalTlc
";

        $params = array_merge(
            [$dayDate, $startTime, $endTime, $providerParam, $parentCode, $giata],
            $messageParams
        );

        $stmt = sqlsrv_prepare($connect, $sql, $params);

        if (!$stmt || !sqlsrv_execute($stmt)) {
            die("SQL Error: " . print_r(sqlsrv_errors(), true));
        }

        echo "<table id='subdetailsTable' class='table table-striped table-bordered mt-2'>
                <thead>
                  <tr>
                    <th>Brand</th>
                    <th>HotelCode</th>
                    <th>Code</th>
                    <th>Accomodation</th>
                    <th>Adults</th>
                    <th>Children</th>
                    <th>Infants</th>
                    <th>Agency</th>
                    <th>Reise</th>
                    <th>Origin</th>
                    <th>Destination</th>
                    <th>Count</th>
                  </tr>
                </thead>
                <tbody>";

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            echo "<tr>
                <td>" . htmlspecialchars($row['PlayerBrand'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['HotelCode'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['Code'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['HotelAccomodation'] ?? '') . "</td>
                <td>" . (int)($row['AdultCounts'] ?? 0) . "</td>
                <td>" . (int)($row['ChildrenCounts'] ?? 0) . "</td>
                <td>" . (int)($row['InfantCounts'] ?? 0) . "</td>
                <td>" . htmlspecialchars($row['agency'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['Reise'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['Origin'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['destination'] ?? '') . "</td>
                <td>" . (int)($row['ct'] ?? 0) . "</td>
            </tr>";
        }

        echo "</tbody></table>";

        sqlsrv_close($connect);
        exit;

    } else {
        // ─── Details Request ───────────────────────────────
        $provider = $_POST['providerAjax'] ?? '';
        $code     = $_POST['code'] ?? 'all';

        $codes = ($code === 'all') ? "'SS:ERR:0453'" : "'" . addslashes($code) . "'";

        $providerCondition = ($provider === 'NULL')
            ? "(PlayerProvider IS NULL OR PlayerProvider LIKE ?)"
            : "PlayerProvider LIKE ?";

        $providerParam = ($provider === 'NULL') ? '%' : $provider;

        $sql = "
SELECT
    PlayerProvider AS PlayerProvider,
    c.PlayerBrand AS PlayerBrand,
    c.HotelCode AS HotelCode,
    c.PackageId,
    b.Code,
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
WHERE CONVERT(date, c.CreationDate) = ?
  AND CONVERT(time, c.CreationDate) BETWEEN ? AND ?
  AND $providerCondition
  AND b.Code IN ($codes)
  $messageCondition
  AND OperationScope LIKE 'CreatePackage'
GROUP BY
    PlayerProvider,
    c.PlayerBrand,
    c.HotelCode,
    c.PackageId,
    b.Code,
    c.HotelAccomodation,
    c.AdultCounts,
    c.ChildrenCounts,
    c.InfantCounts,
    a.[User],
    CONCAT(FORMAT(c.OutboundDate,'dd.MM.yy'),' - ',FORMAT(c.InboundDate,'dd.MM.yy')),
    c.OutboundOriginTlc,
    c.OutboundArrivalTlc
";

        $params = array_merge(
            [$dayDate, $startTime, $endTime, $providerParam],
            $messageParams
        );

        $stmt = sqlsrv_prepare($connect, $sql, $params);

        if (!$stmt || !sqlsrv_execute($stmt)) {
            die("SQL Error: " . print_r(sqlsrv_errors(), true));
        }

        echo '<div id="ajax-details">';
        echo "<h5 class='mb-3'>Details für: <strong>" . ($provider === 'NULL' ? '<em>null</em>' : htmlspecialchars($provider)) . "</strong></h5>";

        echo "<div class='table-responsive'>
              <table id='table2' class='table table-bordered table-sm'>
              <thead class='table-light'>
              <tr>
                <th>Brand</th>
                <th>Agency</th>
                <th>HotelCode</th>
                <th>PackageId</th>
                <th>Accomodation</th>
                <th>Adults</th>
                <th>Children</th>
                <th>Infants</th>
                <th>Reise</th>
                <th>Origin</th>
                <th>Destination</th>
                <th>Count</th>
              </tr>
              </thead>
              <tbody>";

        $hasRows = false;

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $hasRows = true;

            $rowProvider = $row['PlayerProvider'] ?? 'NULL';
            $rowCode     = $row['Code'] ?? '';
            $rowHotel    = $row['HotelCode'] ?? '';

            echo "<tr class='details-row'
                data-provider='" . htmlspecialchars($rowProvider, ENT_QUOTES) . "'
                data-code='" . htmlspecialchars($rowCode, ENT_QUOTES) . "'
                data-giata='" . htmlspecialchars($rowHotel, ENT_QUOTES) . "'
                data-zeit='" . htmlspecialchars((string)$zeit, ENT_QUOTES) . "'>

                <td>" . htmlspecialchars($row['PlayerBrand'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['agency'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['HotelCode'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['PackageId'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['HotelAccomodation'] ?? '') . "</td>
                <td>" . (int)($row['AdultCounts'] ?? 0) . "</td>
                <td>" . (int)($row['ChildrenCounts'] ?? 0) . "</td>
                <td>" . (int)($row['InfantCounts'] ?? 0) . "</td>
                <td>" . htmlspecialchars($row['Reise'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['Origin'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['destination'] ?? '') . "</td>
                <td>" . (int)($row['ct'] ?? 0) . "</td>
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
$pageTitle = "Top 10 Flights";
include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/header.php';
?>

<div class="container mt-4">

  <form method="POST" action="" class="mb-4">
    <div class="row">
      <div class="col-md-6 mb-3">
        <label for="code" class="form-label">Code</label>
        <select name="code" class="form-select" id="code">
          <option value="all" <?= (($_POST['code'] ?? 'all') === 'all') ? 'selected' : '' ?>>All</option>
          <option value="SS:ERR:0453" <?= (($_POST['code'] ?? '') === 'SS:ERR:0453') ? 'selected' : '' ?>>SS:ERR:0453</option>
        </select>
      </div>

      <div class="col-md-6 mb-3">
        <label for="message" class="form-label">Message</label>
        <select name="message" class="form-select" id="message">
            <option value="all" <?= (($_POST['message'] ?? 'all') === 'all') ? 'selected' : '' ?>>All</option>

            <option value="Bitte Unterbringung in Flugzeile löschen und erneut abfragen."
                <?= (($_POST['message'] ?? '') === 'Bitte Unterbringung in Flugzeile löschen und erneut abfragen.') ? 'selected' : '' ?>>
                Bitte Unterbringung in Flugzeile löschen und erneut abfragen.
            </option>

            <option value="138 CBSMISMATCH: No computeblock available for the given filters."
                <?= (($_POST['message'] ?? '') === '138 CBSMISMATCH: No computeblock available for the given filters.') ? 'selected' : '' ?>>
                138 CBSMISMATCH
            </option>

            <option value="136 NOROOMMATCHING: No room matched the search parameters."
                <?= (($_POST['message'] ?? '') === '136 NOROOMMATCHING: No room matched the search parameters.') ? 'selected' : '' ?>>
                136 NOROOMMATCHING
            </option>

            <option value="Error not present"
                <?= (($_POST['message'] ?? '') === 'Error not present') ? 'selected' : '' ?>>
                Error not present
            </option>

            <option value="137 OCCUPANCYMISMATCH: No rooms matched the given occupancy."
                <?= (($_POST['message'] ?? '') === '137 OCCUPANCYMISMATCH: No rooms matched the given occupancy.') ? 'selected' : '' ?>>
                137 OCCUPANCYMISMATCH
            </option>

            <option value="135 NOHOTELMATCHING: No hotel matched the search parameters."
                <?= (($_POST['message'] ?? '') === '135 NOHOTELMATCHING: No hotel matched the search parameters.') ? 'selected' : '' ?>>
                135 NOHOTELMATCHING
            </option>

            <option value="106 DATEOUTOFRANGE: The date period specified is out of the range this proxy supports."
                <?= (($_POST['message'] ?? '') === '106 DATEOUTOFRANGE: The date period specified is out of the range this proxy supports.') ? 'selected' : '' ?>>
                106 DATEOUTOFRANGE
            </option>

            <option value="152 NOOUTBOUNDFLIGHTIDMATCHING: No outbound flightid matched the search parameters."
                <?= (($_POST['message'] ?? '') === '152 NOOUTBOUNDFLIGHTIDMATCHING: No outbound flightid matched the search parameters.') ? 'selected' : '' ?>>
                152 NOOUTBOUNDFLIGHTIDMATCHING
            </option>
        </select>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label for="zeit" class="form-label">Zeit</label>
        <select name="zeit" class="form-select" id="zeit">
          <?php
          for ($h = 0; $h < 24; $h++):
              $hh  = str_pad($h, 2, "0", STR_PAD_LEFT);
              $sel = (isset($_POST['zeit']) && (int)$_POST['zeit'] === $h) ? 'selected' : '';
          ?>
            <option value="<?= $h ?>" <?= $sel ?>><?= "$hh:00 – $hh:59" ?></option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="col-md-3 mb-3 d-flex align-items-end">
        <button type="submit" name="submit" class="btn btn-primary w-100">Suchen</button>
      </div>
    </div>
  </form>

<?php
// ─── Display Top 10 Providers ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['providerAjax'])) {

    include_once $_SERVER['DOCUMENT_ROOT'].'/hotel/config/sqlServer2.php';

    $zeit    = (int)($_POST['zeit'] ?? 0);
    $code    = $_POST['code'] ?? 'all';
    $message = $_POST['message'] ?? 'all';

    $dayDate = (new DateTime())->format('Y-m-d');
    $startTime = str_pad($zeit, 2, "0", STR_PAD_LEFT) . ":00:00";
    $endTime   = str_pad($zeit, 2, "0", STR_PAD_LEFT) . ":59:59";

    $messageCondition = '';
    $messageParams = [];

    if ($message !== 'all') {
        $messageCondition = " AND b.Message = ? ";
        $messageParams[] = $message;
    }

    $connect = SQLServer2::connect();
    if (!$connect) {
        die("Connection error: " . print_r(sqlsrv_errors(), true));
    }

    $codes = ($code === 'all') ? "'SS:ERR:0453'" : "'" . addslashes($code) . "'";

    $query = "
SELECT TOP(10)
    PlayerProvider AS PlayerProvider,
    c.PlayerBrand AS Brand,
    COUNT(DISTINCT a.id) AS ct
FROM dbo.OperationInformationObjects a
JOIN dbo.OperationInformationObject_ResponseMessages b
    ON a.id = b.OperationInformationObject_id
JOIN dbo.PackageInformationObjects c
    ON a.PackageId = c.PackageId
WHERE CONVERT(date, c.CreationDate) = ?
  AND CONVERT(time, c.CreationDate) BETWEEN ? AND ?
  AND OperationScope LIKE 'CreatePackage'
  AND b.Code IN ($codes)
  $messageCondition
GROUP BY
    PlayerProvider,
    c.PlayerBrand
ORDER BY COUNT(DISTINCT a.id) DESC
";

    $params = array_merge(
        [$dayDate, $startTime, $endTime],
        $messageParams
    );

    $stmt = sqlsrv_prepare($connect, $query, $params);

    if (!$stmt || !sqlsrv_execute($stmt)) {
        die("SQL Error: " . print_r(sqlsrv_errors(), true));
    }

    echo "<div class='row'><div class='col'>
          <table id='table1' class='table table-striped table-bordered'>
          <thead>
            <tr>
                <th>Provider</th>
                <th>Brand</th>
                <th>Count</th>
            </tr>
          </thead>
          <tbody>";

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {

        $p = $row['PlayerProvider'] ?? 'NULL';
        $display = $row['PlayerProvider'] ?? '<em>null</em>';

        echo "<tr style='cursor:pointer' data-provider='" . htmlspecialchars($p, ENT_QUOTES) . "'>
                <td>" . ($row['PlayerProvider'] === null ? '<em>null</em>' : htmlspecialchars($display)) . "</td>
                <td>" . htmlspecialchars($row['Brand'] ?? '') . "</td>
                <td>" . (int)($row['ct'] ?? 0) . "</td>
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
            order: [[2, 'desc']],
            paging: false
        });
    }

    // Click Provider → Details
    $(document).on('click', '#table1 tbody tr', function () {
        $('#table1 tbody tr').removeClass('table-active');
        $(this).addClass('table-active');

        var provider = $(this).data('provider');
        var code     = $('#code').val();
        var zeit     = $('#zeit').val();
        var message  = $('#message').val();

        if (!provider) return;

        $('#details-container').html('<div class="text-center p-3"><div class="spinner-border"></div></div>');

        $.ajax({
            url: '',
            type: 'POST',
            data: {
                providerAjax: provider,
                code: code,
                zeit: zeit,
                message: message
            },
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
                        lengthMenu: [5, 10, 25, 50, 100],
                        order: [[11, 'desc']],
                        responsive: true,
                        searching: true
                    });
                }
            },
            error: function () {
                $('#details-container').html('<div class="alert alert-danger">Fehler beim Laden der Daten.</div>');
            }
        });
    });

    // Click Details → SubDetails
    $(document).on('click', '#table2 tbody tr', function () {
        $('#table2 tbody tr').removeClass('table-active');
        $(this).addClass('table-active');

        var provider = $(this).data('provider');
        var giata    = $(this).data('giata');
        var code     = $(this).data('code');
        var zeit     = $(this).data('zeit');
        var message  = $('#message').val();

        if (!provider || !code) return;

        $('#subdetails-container').html('<div class="text-center p-3"><div class="spinner-border"></div></div>');

        $.ajax({
            url: '',
            type: 'POST',
            data: {
                providerAjax: provider + '_subdetails',
                parentCode: code,
                hotelgiatacode: giata,
                zeit: zeit,
                message: message
            },
            success: function (html) {
                $('#subdetails-container').html(html);

                if ($.fn.DataTable.isDataTable('#subdetailsTable')) {
                    $('#subdetailsTable').DataTable().destroy();
                }

                if ($('#subdetailsTable').length) {
                    $('#subdetailsTable').DataTable({
                        dom: 'Brtip',
                        buttons: ['excel', 'csv'],
                        pageLength: 10,
                        lengthMenu: [5, 10, 25, 50, 100],
                        order: [[11, 'desc']],
                        responsive: true,
                        searching: true
                    });
                }
            },
            error: function () {
                $('#subdetails-container').html('<div class="alert alert-danger">Fehler beim Laden der SubDetails.</div>');
            }
        });
    });
});
</script>

<?php include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/footer.php'; ?>