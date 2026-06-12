<?php
$pageTitle = "Top 10 Hotels";
include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/header.php';
?>

<div class="container mt-4">

<form method="POST" action="" class="mb-4">
    <div class="row">

        <div class="col-md-4 mb-3">
            <label for="code" class="form-label">Code</label>

            <select name="code" class="form-select" id="code">

    <option value="all"
        <?= (($_POST['code'] ?? '') === 'all') ? 'selected' : '' ?>>
        All
    </option>

    <option value="SS:ERR:11191"
        <?= (($_POST['code'] ?? '') === 'SS:ERR:11191') ? 'selected' : '' ?>>
        SS:ERR:11191
    </option>

    <option value="SS:ERR:1137"
        <?= (($_POST['code'] ?? '') === 'SS:ERR:1137') ? 'selected' : '' ?>>
        SS:ERR:1137
    </option>

    <option value="SS:INFO:00443"
        <?= (($_POST['code'] ?? '') === 'SS:INFO:00443') ? 'selected' : '' ?>>
        SS:INFO:00443
    </option>

    <option value="SS:ERR:11200"
        <?= (($_POST['code'] ?? '') === 'SS:ERR:11200') ? 'selected' : '' ?>>
        SS:ERR:11200
    </option>

</select>
        </div>

        <div class="col-md-4 mb-3">
            <label for="zeit" class="form-label">Zeit-gestern</label>

            <select name="zeit" class="form-select" id="zeit">

    <option value="all"
        <?= (($_POST['zeit'] ?? '') === 'all') ? 'selected' : '' ?>>
        Ganzer Tag (Gestern)
    </option>

<?php
for ($h = 0; $h < 24; $h++):

    $hh = str_pad($h, 2, "0", STR_PAD_LEFT);

    $selected =
        (isset($_POST['zeit']) && (string)$_POST['zeit'] === (string)$h)
        ? 'selected'
        : '';
?>

    <option value="<?= $h ?>" <?= $selected ?>>
        <?= "$hh:00 – $hh:59" ?>
    </option>

<?php endfor; ?>

</select>
        </div>

      <div class="col-md-3 mb-3 d-flex align-items-end">
    <button type="submit" name="submit" class="btn btn-primary">
        Suchen
    </button>
</div>

<div class="col-12">
    <div id="code-message" class="mt-2"></div>
</div>

    </div>
</form>

<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    include_once $_SERVER['DOCUMENT_ROOT'].'/hotel/config/sqlServer2.php';

   $zeit = $_POST['zeit'] ?? 'all';
$code = $_POST['code'] ?? 'all';

$dayDate = date('Y-m-d', strtotime('-1 day'));

if ($zeit === 'all') {

    $startTime = "00:00:00";
    $endTime   = "23:59:59";

} else {

    $zeit = (int)$zeit;

    $startTime =
        str_pad($zeit, 2, "0", STR_PAD_LEFT) . ":00:00";

    $endTime =
        str_pad($zeit, 2, "0", STR_PAD_LEFT) . ":59:59";
}

    $connect = SQLServer2::connect();

    if (!$connect) {
        die("Connection error: " . print_r(sqlsrv_errors(), true));
    }

    $codes = ($code === 'all')
    ? "'SS:ERR:11191','SS:ERR:1137','SS:INFO:00443','SS:ERR:11200'"
    : "'" . addslashes($code) . "'";

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

      AND (
            b.message LIKE
            CASE b.Code
                WHEN 'SS:ERR:11191'
                    THEN '%All such room are booked out in the hotel%'

                WHEN 'SS:INFO:00443'
                    THEN '%The connection to the service provider is disturbed.%'

                WHEN 'SS:ERR:11200'
                    THEN '%hotel is not activated for usage%'

                ELSE '%'
            END
          )

    GROUP BY PlayerProvider

    ORDER BY COUNT(DISTINCT a.id) DESC

    ";

    $stmt = sqlsrv_prepare(
        $connect,
        $query,
        [$dayDate, $startTime, $endTime]
    );

    if (!$stmt || !sqlsrv_execute($stmt)) {
        die("SQL Error: " . print_r(sqlsrv_errors(), true));
    }

    echo "
    <table id='table1'
           class='table table-striped table-bordered'>

        <thead>
            <tr>
                <th>Provider</th>
                <th>Count</th>
            </tr>
        </thead>

        <tbody>
    ";

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {

        echo "
        <tr>
            <td>" . htmlspecialchars($row['PlayerProvider']) . "</td>
            <td>" . (int)$row['ct'] . "</td>
        </tr>
        ";
    }

    echo "
        </tbody>
    </table>
    ";

    sqlsrv_close($connect);
}
?>

</div>

<script>
$(document).ready(function () {

    if ($('#table1').length) {

        $('#table1').DataTable({
            dom: 'Bfrtip',
            buttons: ['excel', 'csv'],
            order: [[1, 'desc']],
            paging: false
        });

    }

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
<?php
include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/footer.php';
?>