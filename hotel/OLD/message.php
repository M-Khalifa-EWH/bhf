<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Suchen Tool</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <!-- DataTables CSS -->
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
            background-color: #f3f3f3; /* Dark background color */
        }
        .navbar-brand {
            color: #080808; /* White text color */
        }
        .navbar-nav .nav-link {
            color: #080808; /* White text color for links */
            margin-right: 15px; /* Spacing between links */
            transition: color 0.3s; /* Smooth transition for hover effect */
        }
        .navbar-nav .nav-link:hover {
            color: #d1d1d1; /* Light grey color on hover */
        }
        .navbar-toggler-icon {
            background-image: url('data:image/svg+xml;charset=utf8,<svg viewBox="0 0 30 30" xmlns="http://www.w3.org/2000/svg"><path stroke="currentColor" stroke-width="2" d="M4 7h22M4 15h22M4 23h22"/></svg>'); /* Custom hamburger icon */
        }
        .container {
            max-width: 1200px; /* Centering and setting max width */
        }
        .dataTable {
            width: 100% !important;
            table-layout: auto !important;
        }
        table.dataTable th, table.dataTable td {
            white-space: nowrap;
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
                <label for="dateRange" class="form-label">Tag</label>
                <select name="dateRange" class="form-select" id="dateRange">
                    <option value="yesterday" <?php echo isset($_POST["dateRange"]) && $_POST["dateRange"] == "yesterday" ? 'selected' : ''; ?>>gestern</option>
                    <option value="today" <?php echo isset($_POST["dateRange"]) && $_POST["dateRange"] == "today" ? 'selected' : ''; ?>>heute</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="zeit" class="form-label">Zeit</label>
                <select name="zeit" class="form-select" id="zeit">
                    <option value="null" <?php echo isset($_POST["zeit"]) && $_POST["zeit"] == "null" ? 'selected' : ''; ?>>00-03 Uhr</option>
                    <option value="drei" <?php echo isset($_POST["zeit"]) && $_POST["zeit"] == "drei" ? 'selected' : ''; ?>>03-06 Uhr</option>
                    <option value="sechs" <?php echo isset($_POST["zeit"]) && $_POST["zeit"] == "sechs" ? 'selected' : ''; ?>>06-10 Uhr</option>
                    <option value="zhen" <?php echo isset($_POST["zeit"]) && $_POST["zeit"] == "zhen" ? 'selected' : ''; ?>>10-14 Uhr</option>
                    <option value="vierzhen" <?php echo isset($_POST["zeit"]) && $_POST["zeit"] == "vierzhen" ? 'selected' : ''; ?>>14-18 Uhr</option>
                    <option value="achtzhen" <?php echo isset($_POST["zeit"]) && $_POST["zeit"] == "achtzhen" ? 'selected' : ''; ?>>18-22 Uhr</option>
                    <option value="zweizwansig" <?php echo isset($_POST["zeit"]) && $_POST["zeit"] == "zweizwansig" ? 'selected' : ''; ?>>22-00 Uhr</option>
                    <option value="tag" <?php echo isset($_POST["zeit"]) && $_POST["zeit"] == "tag" ? 'selected' : ''; ?>>00-24 Uhr</option>
                </select>
            </div>
        
         <div class="col-md-3 mb-3 d-flex align-items-end">
            <button type="submit" name="submit" class="btn btn-custom">Suchen</button>
        </div>
        </div>
    </form>
</div>
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $datenbank = $_POST["datenbank"] ?? '';
        $zeit = $_POST["zeit"] ?? '';
        $dateRange = $_POST["dateRange"] ?? '';

        // Set execution time to 5 minutes
        ini_set('max_execution_time', 300);

        // Determine the start and end date based on user selection
        $currentDate = new DateTime();
        if ($dateRange == 'yesterday') {
            $currentDate->modify('-1 day');
        }
        $dayDate = $currentDate->format('Y-m-d');

        // Determine time range based on user selection
        $timeRanges = array(
            'null' => array('00:00:00.0000000', '03:00:00.0000000'),
            'drei' => array('03:00:00.0000000', '06:00:00.0000000'),
            'sechs' => array('06:00:00.0000000', '10:00:00.0000000'),
            'zhen' => array('10:00:00.0000000', '14:00:00.0000000'),
            'vierzhen' => array('14:00:00.0000000', '18:00:00.0000000'),
            'achtzhen' => array('18:00:00.0000000', '22:00:00.0000000'),
            'zweizwansig' => array('22:00:00.0000000', '23:59:59.0000000'),
            'tag' => array('00:00:00.0000000', '23:59:59.0000000')
        );

        $startTime = $timeRanges[$zeit][0];
        $endTime = $timeRanges[$zeit][1];

        $connect = SQLServer2::connect();
        // Check connection
        if (!$connect) {
            echo "<div class='alert alert-danger text-center'>Connection could not be established.<br>" . print_r(sqlsrv_errors(), true) . "</div>";
            die();
        }

        // SQL query and table display
        $sql = "SELECT SUBSTRING(CONVERT(VARCHAR, c.CreationDate, 120), 0, 11) as datum, PlayerProvider, Code, Message as Message, HotelCode, c.HotelGiataCode as HotelGiataCode, MIN(c.PackageId) as PackageId,
                a.[User] as agency, c.OutboundArrivalTlc as destination, COUNT(a.id) as ct
                FROM dbo.OperationInformationObjects a
                JOIN dbo.OperationInformationObject_ResponseMessages b ON a.id = b.OperationInformationObject_id
                JOIN dbo.PackageInformationObjects c ON a.PackageId = c.PackageId
                WHERE CONVERT(date, c.CreationDate) = ?
                AND (CONVERT(time, c.CreationDate) >= ? AND CONVERT(time, c.CreationDate) <= ?)
                AND HotelCode NOT like '[0-9]%'
                AND HotelCode LIKE '%\_%' ESCAPE '\'
                GROUP BY SUBSTRING(CONVERT(VARCHAR, c.CreationDate, 120), 0, 11), PlayerProvider, Code, Message, HotelCode, c.HotelGiataCode, a.[User], c.OutboundArrivalTlc
                ORDER BY COUNT(a.id) DESC";

        $params = array($dayDate, $startTime, $endTime);
        $stmt = sqlsrv_query($connect, $sql, $params);

        if (!$stmt) {
            echo "<div class='alert alert-danger text-center'>Error in query: " . print_r(sqlsrv_errors(), true) . "</div>";
            die();
        }

        echo '<div class="table-responsive">
                <table id="dataTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>Datum</th>
                        <th>PackageId</th>
                            <th>P.Provider</th>
                            <th>Code</th>
                            <th>Message</th>
                            <th>H.Code</th>
                            <th>Giata Code</th>
                            <th>Agency</th>
                            <th>Dest</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>';
        // Fetching and displaying results
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            echo "<tr>
                    <td>" . htmlspecialchars($row['datum'] ?? '') . "</td>
                    <td>" . htmlspecialchars($row['PackageId']) . "</td> 
                    <td>" . htmlspecialchars($row['PlayerProvider'] ?? '') . "</td>
                    <td>" . htmlspecialchars($row['Code'] ?? '') . "</td>
                    <td>" . htmlspecialchars($row['Message'] ?? '') . "</td>
                    <td>" . htmlspecialchars($row['HotelCode'] ?? '') . "</td>
                    <td>" . htmlspecialchars($row['HotelGiataCode'] ?? '') . "</td>                        
                    <td>" . htmlspecialchars($row['agency'] ?? '') . "</td>
                    <td>" . htmlspecialchars($row['destination'] ?? '') . "</td>
                    <td>" . htmlspecialchars($row['ct'] ?? '') . "</td>
                </tr>";
        }
        echo '</tbody>
                <tfoot>
                    <tr>
                        <th>Datum</th>
                        <th>PackageId</th>
                        <th>P.Provider</th>
                        <th>Code</th>
                        <th>Message</th>
                        <th>H.Code</th>
                        <th>Giata Code</th>
                        <th>Agency</th>
                        <th>Dest</th>
                        <th>Count</th>
                    </tr>
                </tfoot>
            </table>
        ';

        // Free statement and connection resources
        sqlsrv_free_stmt($stmt);
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
    new DataTable('#dataTable', {
        "info": false,
        "autoWidth": true,
        dom: 'Brtp',
        buttons: [
            'csv', 'excel'
        ],
        initComplete: function () {
            this.api()
                .columns()
                .every(function () {
                    let column = this;
                    let title = column.footer().textContent;

                    // Create input element
                    let input = document.createElement('input');
                    input.placeholder = title;
                    input.style.width = '100%';
                    column.footer().replaceChildren(input);

                    // Event listener for user input
                    input.addEventListener('keyup', () => {
                        if (column.search() !== this.value) {
                            column.search(input.value).draw();
                        }
                    });
                });
        }
    });
</script>
<script>
    $('#dataTable tfoot tr').appendTo('#dataTable thead');
</script>
</body>
</html>
