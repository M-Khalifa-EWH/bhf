<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Suchen Tool</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <style>
        .btn-toggle { border-radius: 20px; font-weight: 500; padding: 6px 18px; transition: all 0.3s ease; }
        .btn-toggle.active { background-color: #3b82f6; color: #fff; border: 1px solid #3b82f6; }
        .btn-toggle { background-color: #fff; color: #3b82f6; border: 1px solid #3b82f6; }
        .btn-toggle:hover { background-color: #3b82f6; color: #fff; }
    </style>
</head>
<body>
<?php 
    include 'navbar.php'; 
    include_once 'config/sqlServer2.php'; 
?>

<div class="container mt-4">
    <form method="POST" action="" class="mb-4">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="code" class="form-label">Code</label>
                <select name="code" class="form-select" id="code">
                    <option value="all" <?= (isset($_POST['code']) && $_POST['code']=='all') ? 'selected' : '' ?>>All</option>
                    <option value="SS:ERR:11191" <?= (isset($_POST['code']) && $_POST['code']=='SS:ERR:11191') ? 'selected' : '' ?>>SS:ERR:11191</option>
                    <option value="SS:ERR:1121" <?= (isset($_POST['code']) && $_POST['code']=='SS:ERR:1121') ? 'selected' : '' ?>>SS:ERR:1121</option>
                    <option value="SS:ERR:00443" <?= (isset($_POST['code']) && $_POST['code']=='SS:ERR:00443') ? 'selected' : '' ?>>SS:ERR:00443</option>
                    <option value="SS:ERR:00441" <?= (isset($_POST['code']) && $_POST['code']=='SS:ERR:00441') ? 'selected' : '' ?>>SS:ERR:00441</option>
                    <option value="SS:ERR:00442" <?= (isset($_POST['code']) && $_POST['code']=='SS:ERR:00442') ? 'selected' : '' ?>>SS:ERR:00442</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="zeit" class="form-label">Zeit</label>
                <select name="zeit" class="form-select" id="zeit">
                    <?php 
                        for ($h = 0; $h < 24; $h++) {
                            $label = str_pad($h, 2, "0", STR_PAD_LEFT) . ":00 - " . str_pad($h, 2, "0", STR_PAD_LEFT) . ":59";
                            $selected = isset($_POST["zeit"]) && $_POST["zeit"] == $h ? 'selected' : '';
                            echo "<option value='$h' $selected>$label</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="col-md-3 mb-3 d-flex align-items-end">
                <button type="submit" name="submit" class="btn btn-custom">Suchen</button>
            </div>
        </div>
    </form>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $zeit = (int) $_POST["zeit"]; 
    $code = $_POST['code'] ?? 'all';

    $currentDate = new DateTime();
    $dayDate = $currentDate->format('Y-m-d');

    $timeRanges = [];
    for ($h = 0; $h < 24; $h++) {
        $timeRanges[$h] = [
            str_pad($h, 2, "0", STR_PAD_LEFT) . ":00:00",
            str_pad($h, 2, "0", STR_PAD_LEFT) . ":59:59"
        ];
    }

    $startTime = $timeRanges[$zeit][0];
    $endTime   = $timeRanges[$zeit][1];

    $connect = SQLServer2::connect();
    if (!$connect) { echo "<div class='alert alert-danger text-center'>Connection could not be established.<br>" . print_r(sqlsrv_errors(), true) . "</div>"; die(); }

    if ($code == 'all') {
        $query1 = "
            SELECT TOP (5) PlayerProvider, COUNT(a.id) as ct
            FROM dbo.OperationInformationObjects a
            JOIN dbo.OperationInformationObject_ResponseMessages b ON a.id = b.OperationInformationObject_id
            JOIN dbo.PackageInformationObjects c ON a.PackageId = c.PackageId
            WHERE CONVERT(date, c.CreationDate) = ?
              AND (CONVERT(time, c.CreationDate) >= ? AND CONVERT(time, c.CreationDate) <= ?)
              AND OperationScope LIKE 'CreatePackage'
              AND PlayerProvider != ''
              AND success = 'false'
            GROUP BY PlayerProvider
            ORDER BY COUNT(DISTINCT a.id) DESC
        ";
        $params1 = [$dayDate, $startTime, $endTime];
    } else {
        $query1 = "
            SELECT TOP (5) PlayerProvider, COUNT(a.id) as ct
            FROM dbo.OperationInformationObjects a
            JOIN dbo.OperationInformationObject_ResponseMessages b ON a.id = b.OperationInformationObject_id
            JOIN dbo.PackageInformationObjects c ON a.PackageId = c.PackageId
            WHERE CONVERT(date, c.CreationDate) = ?
              AND (CONVERT(time, c.CreationDate) >= ? AND CONVERT(time, c.CreationDate) <= ?)
              AND OperationScope LIKE 'CreatePackage'
              AND Code = ?
              AND PlayerProvider != ''
              AND success = 'false'
            GROUP BY PlayerProvider
            ORDER BY COUNT(DISTINCT a.id) DESC
        ";
        $params1 = [$dayDate, $startTime, $endTime, $code];
    }

    $query2 = "
        SELECT TOP (5) PlayerProvider, COUNT(a.id) as ct
        FROM dbo.OperationInformationObjects a
        JOIN dbo.OperationInformationObject_ResponseMessages b ON a.id = b.OperationInformationObject_id
        JOIN dbo.PackageInformationObjects c ON a.PackageId = c.PackageId
        WHERE CONVERT(date, c.CreationDate) = ?
            AND (CONVERT(time, c.CreationDate) >= ? AND CONVERT(time, c.CreationDate) <= ?)
            AND PlayerProvider IN ('EWP','AITF','Condor','DEP','airtuerk','XQP','AITS','Travelport','U2','VFLY','VY','Eurowings','XQ','TFLY','TUIFly','FHY','LHG','NORWEGIAN')
            AND OperationScope LIKE 'CreatePackage'
            AND success = 'false'
        GROUP BY PlayerProvider
        ORDER BY COUNT(a.id) DESC
    ";
    $params2 = [$dayDate, $startTime, $endTime];

    function renderTable($query, $params, $connect, $id) {
        $stmt = sqlsrv_prepare($connect, $query, $params);
        if (!$stmt) die("Error in query preparation: " . print_r(sqlsrv_errors(), true));
        if (!sqlsrv_execute($stmt)) die("Error in query execution: " . print_r(sqlsrv_errors(), true));

        echo "<div class='col'><div class='container'><table id='$id' class='table table-striped table-bordered'>
                <thead><tr><th>PlayerProvider</th><th>Count</th></tr></thead><tbody>";

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            echo "<tr class='clickable-row' data-provider='{$row['PlayerProvider']}'><td>{$row['PlayerProvider']}</td><td>{$row['ct']}</td></tr>";
        }

        echo "</tbody></table></div></div>";
        sqlsrv_free_stmt($stmt);
    }
?>
<div class="row">
    <?php renderTable($query1, $params1, $connect, 'table1'); ?>
    <?php renderTable($query2, $params2, $connect, 'table2'); ?>
</div>
<?php
    sqlsrv_close($connect);
}
?>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script>
$(document).ready(function () {
    $('#table1, #table2').DataTable({
        dom: 'Brtip',
        buttons: ['excel', 'csv'],
        order: [[1, 'desc']]
    });

    $('#table1').on('click', '.clickable-row', function () {
        var provider = $(this).data('provider');
        var dateRange = '<?= $dayDate ?? '' ?>';
        var startTime = '<?= $startTime ?? '' ?>';
        var endTime = '<?= $endTime ?? '' ?>';
        window.open('details.php?dateRange=' + dateRange + '&startTime=' + startTime + '&endTime=' + endTime + '&provider=' + provider + '&table=table1', '_blank');
    });

    $('#table2').on('click', '.clickable-row', function () {
        var provider = $(this).data('provider');
        var dateRange = '<?= $dayDate ?? '' ?>';
        var startTime = '<?= $startTime ?? '' ?>';
        var endTime = '<?= $endTime ?? '' ?>';
        window.open('details.php?dateRange=' + dateRange + '&startTime=' + startTime + '&endTime=' + endTime + '&provider=' + provider + '&table=table2', '_blank');
    });
});
</script>
</body>
</html>
