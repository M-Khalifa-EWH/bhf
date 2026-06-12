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
        .btn-toggle {
            border-radius: 20px;
            font-weight: 500;
            padding: 6px 18px;
            transition: all 0.3s ease;
        }
        .btn-toggle.active {
            background-color: #3b82f6; 
            color: #fff;
            border: 1px solid #3b82f6;
        }
        .btn-toggle {
            background-color: #fff;
            color: #3b82f6;
            border: 1px solid #3b82f6;
        }
        /* Hover effect */
        .btn-toggle:hover {
            background-color: #3b82f6;
            color: #fff;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; 
          include_once 'config/sqlServer2.php'; 
    ?>
    
    <div class="container mt-4">
        <form method="POST" action="" class="mb-4">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="code" class="form-label">Code</label>
                   <select name="code" class="form-select" id="code">
    <option value="SS:ERR:1137"
        <?php echo (isset($_POST['code']) && $_POST['code']=='SS:ERR:1137') ? 'selected' : ''; ?>>
        SS:ERR:1137
    </option>
</select>

                </div>

                <div class="col-md-4 mb-3">
                    <label for="zeit" class="form-label">Zeit, gestern</label>
                    <select name="zeit" class="form-select" id="zeit">
  <?php
$label = "00:00 - 23:59";
$selected = isset($_POST["zeit"]) && $_POST["zeit"] == 0 ? 'selected' : '';
echo "<option value='0' $selected>$label</option>";
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
    $code = 'SS:ERR:1137'; 

    $currentDate = new DateTime('yesterday');
    $dayDate = $currentDate->format('Y-m-d');

    $startTime = str_pad($zeit, 2, "0", STR_PAD_LEFT) . ":00:00";
    $endHour = $zeit + 3;
    if ($endHour > 23) $endHour = 23;
    $endTime = str_pad($endHour, 2, "0", STR_PAD_LEFT) . ":59:59";

    $connect = SQLServer2::connect();
    if (!$connect) {
        echo "<div class='alert alert-danger text-center'>Connection could not be established.<br>" . print_r(sqlsrv_errors(), true) . "</div>";
        die();
    }

    $query1 = "
    SELECT PlayerProvider, COUNT(a.PackageId) as ct
    FROM dbo.OperationInformationObjects a
    JOIN dbo.OperationInformationObject_ResponseMessages b 
        ON a.id = b.OperationInformationObject_id
    JOIN dbo.PackageInformationObjects c 
        ON a.PackageId = c.PackageId
    WHERE c.CreationDate >= DATEADD(day, DATEDIFF(day, 1, GETDATE()), 0)
      AND c.CreationDate <  DATEADD(day, DATEDIFF(day, 0, GETDATE()), 0)
      AND Code = 'SS:ERR:1137'
      AND PlayerProvider = 'DERN'
      AND success = 'false'
    GROUP BY PlayerProvider
";


    $params1 = [];

  
    function renderTable($query, $params, $connect, $id) {
        $stmt = sqlsrv_prepare($connect, $query, $params);
        if (!$stmt) {
            die("Error in query preparation: " . print_r(sqlsrv_errors(), true));
        }
        if (!sqlsrv_execute($stmt)) {
            die("Error in query execution: " . print_r(sqlsrv_errors(), true));
        }

        echo "<div class='col'><div class='container'><table id='$id' class='table table-striped table-bordered'>
                <thead>
                    <tr><th>PlayerProvider</th><th>Count</th></tr>
                </thead><tbody>";

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            echo "<tr class='clickable-row' data-provider='" . htmlspecialchars($row['PlayerProvider'], ENT_QUOTES) . "'><td>" . htmlspecialchars($row['PlayerProvider']) . "</td><td>{$row['ct']}</td></tr>";
        }

        echo "</tbody></table></div></div>";
        sqlsrv_free_stmt($stmt);
    }

    renderTable($query1, $params1, $connect, 'table1');
    sqlsrv_close($connect);
}
 ?>
    </div>

    <!-- jQuery and Bootstrap Bundle (includes Popper) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#table1').DataTable({
                dom: 'Brtip',
                buttons: ['excel', 'csv'],
                order: [[1, 'desc']]
            });

            $('#table1').on('click', '.clickable-row', function () {
                var provider = $(this).data('provider');
                var codeVal = $('#code').val();
                var dateRange = '<?php echo $dayDate; ?>';
                var startTime = '<?php echo $startTime; ?>';
                var endTime = '<?php echo $endTime; ?>';

                var url = 'codeDetails.php?dateRange=' + encodeURIComponent(dateRange)
                        + '&startTime=' + encodeURIComponent(startTime)
                        + '&endTime=' + encodeURIComponent(endTime)
                        + '&provider=' + encodeURIComponent(provider)
                        + '&code=' + encodeURIComponent(codeVal)
                        + '&table=table1';

                window.open(url, '_blank');
            });
        });
    </script>
</body>
</html>
