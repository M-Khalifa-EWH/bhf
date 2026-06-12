<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Suchen Tool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <style>
        .header-buttons {
            margin-bottom: 20px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5em 1em;
            margin: 0 0.1em;
        }
        .navbar {
            background-color: #f3f3f3;
        }
        .navbar-brand,
        .navbar-nav .nav-link {
            color: #080808;
            margin-right: 15px;
            transition: color 0.3s;
        }
        .navbar-nav .nav-link:hover {
            color: #d1d1d1;
        }
        .container {
            max-width: 1200px;
        }
        table.dataTable th,
        table.dataTable td {
            white-space: nowrap;
        }
    </style>
</head>
<body>

<?php
$pageTitle = "ErrCode";
include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/header.php';
//include_once $_SERVER['DOCUMENT_ROOT'].'/hotel/config/sqlServer2.php';
?>
<div class="container mt-4">
    <form method="POST" action="" class="mb-4">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="dateRange" class="form-label">Tag</label>
                <select name="dateRange" class="form-select" id="dateRange">
                    <option value="yesterday" <?php echo isset($_POST["dateRange"]) && $_POST["dateRange"] == "yesterday" ? 'selected' : ''; ?>>gestern</option>
                    <option value="today" <?php echo isset($_POST["dateRange"]) && $_POST["dateRange"] == "today" ? 'selected' : ''; ?>>heute</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="ErrCode" class="form-label">Error Code</label>
                <input type="text" id="ErrCode" name="ErrCode" class="form-control" value="<?php echo isset($_POST['ErrCode']) ? htmlspecialchars($_POST['ErrCode']) : ''; ?>" required>
            </div>
            <div class="col-md-3 mb-3 d-flex align-items-end">
                <button type="submit" name="submit" class="btn btn-primary">Suchen</button>
            </div>
        </div>
    </form>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dateRange = $_POST["dateRange"] ?? '';
    $errCode = $_POST["ErrCode"] ?? '';

    ini_set('max_execution_time', 300);
    $currentDate = new DateTime();
    if ($dateRange == 'yesterday') {
        $currentDate->modify('-1 day');
    }
    $dayDate = $currentDate->format('Y-m-d');

  
    $connect = SQLServer2::connect();

    if (!$connect) {
        echo "<div class='alert alert-danger text-center'>Connection could not be established.<br>" . print_r(sqlsrv_errors(), true) . "</div>";
        die();
    }

    $sql = "SELECT 
            SUBSTRING(CONVERT(VARCHAR, c.CreationDate, 120), 0, 11) as datum, 
            PlayerProvider, MIN(c.PackageId) as PackageId, MIN(FORMAT(c.CreationDate, 'HH:mm')) AS BA_Timestamp,
            Code, 
            Message, 
            HotelCode, 
            c.HotelGiataCode as HotelGiataCode,
            a.[User] as agency, 
            c.OutboundArrivalTlc as destination, 
            COUNT(a.id) as ct
        FROM dbo.OperationInformationObjects a
        JOIN dbo.OperationInformationObject_ResponseMessages b 
            ON a.id = b.OperationInformationObject_id
        JOIN dbo.PackageInformationObjects c 
            ON a.PackageId = c.PackageId
        WHERE CONVERT(DATE, c.CreationDate) = ? 
          AND Code like ? AND HotelCode NOT like '[0-9]%' AND HotelGiataCode IS NOT NULL
        GROUP BY 
            SUBSTRING(CONVERT(VARCHAR, c.CreationDate, 120), 0, 11), 
            PlayerProvider, 
            Code, 
            Message, 
            HotelCode, 
            c.HotelGiataCode, 
            a.[User], 
            c.OutboundArrivalTlc
        ORDER BY COUNT(a.id) DESC";

$errCode = $errCode . '%'; 
$params = array($dayDate, $errCode);
$stmt = sqlsrv_query($connect, $sql, $params);



    if (!$stmt) {
        echo "<div class='alert alert-danger text-center'>Error in query: " . print_r(sqlsrv_errors(), true) . "</div>";
        die();
    }

    $firstMessage = null;
    $rows = [];

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if ($firstMessage === null && !empty($row['Message'])) {
            $firstMessage = $row['Message'];
        }
        $rows[] = $row;
    }

    // ✅ عرض الرسالة هنا، بعد الفورم مباشرةً
    if (!empty($firstMessage)) {
        echo '<div class="alert alert-info"><strong>Message:</strong><br>' .
             nl2br(htmlspecialchars($firstMessage)) .
             '</div>';
    }

    // ✅ طباعة الجدول
    if (!empty($rows)) {
        echo '<div class="table-responsive mt-3">
            <table id="dataTable" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>BA Time</th> 
                        <th>PackageId</th>
                        <th>P.Provider</th>
                        <th>H.Code</th>
                        <th>Giata Code</th>
                        <th>Agency</th>
                        <th>Dest</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($rows as $row) {
            echo "<tr>
                    <td>" . htmlspecialchars($row['datum']) . "</td>
                    <td>" . htmlspecialchars($row['BA_Timestamp']) . "</td>
                    <td>" . htmlspecialchars($row['PackageId']) . "</td> 
                    <td>" . htmlspecialchars($row['PlayerProvider']) . "</td>
                    <td>" . htmlspecialchars($row['HotelCode']) . "</td>
                    <td>" . $row['HotelGiataCode'] . "</td>
                    <td>" . htmlspecialchars($row['agency']) . "</td>
                    <td>" . htmlspecialchars($row['destination']) . "</td>
                    <td>" . htmlspecialchars($row['ct']) . "</td>
                </tr>";
        }
        echo '</tbody>
                <tfoot>
                    <tr>
                        <th>Datum</th>
                        <th>BA Time</th> 
                        <th>PackageId</th>
                        <th>P.Provider</th>
                        <th>H.Code</th>
                        <th>Giata Code</th>
                        <th>Agency</th>
                        <th>Dest</th>
                        <th>Count</th>
                    </tr>
                </tfoot>
            </table>
        </div>';
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($connect);
}
?>
</div>

<!-- JavaScript dependencies -->
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
    new DataTable('#dataTable', {
        info: false,
        autoWidth: true,
        dom: 'Brtp',
        buttons: ['csv', 'excel'],
        initComplete: function () {
            this.api().columns().every(function () {
                let column = this;
                let title = column.footer().textContent;
                let input = document.createElement('input');
                input.placeholder = title;
                input.style.width = '100%';
                column.footer().replaceChildren(input);
                input.addEventListener('keyup', () => {
                    if (column.search() !== input.value) {
                        column.search(input.value).draw();
                    }
                });
            });
        }
    });

    $('#dataTable tfoot tr').appendTo('#dataTable thead');
</script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/footer.php'; ?>
</body>
</html>
