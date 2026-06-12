<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Hotel Suchen Tool</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
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
        .navbar-brand,
        .navbar-nav .nav-link {
            color: #080808; /* White text color */
        }
        .navbar-nav .nav-link {
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
        .btn-custom {
            background-color: #dce2e3;
            border-color: #dce2e3;
            color: #000; /* Optional: Set the text color */
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; 
          include_once 'config/sqlServer1.php'; 
    ?>

    <!-- Search Form -->
    <div class="container mt-4">
        <form method="POST" action="" class="mb-4">
           <div class="row">
    <div class="col-md-4 mb-3">
        <label for="Statusderbuchung" class="form-label">Status der Buchung</label>
        <select name="Statusderbuchung" class="form-select" id="Statusderbuchung">
    <option value="ORDER_OK" <?php echo (isset($_POST['Statusderbuchung']) && $_POST['Statusderbuchung'] == 'ORDER_OK') ? 'selected' : ''; ?>>Buchung.OK</option>

    <option value="S_ORDER_OK" <?php echo (isset($_POST['Statusderbuchung']) && $_POST['Statusderbuchung'] == 'S_ORDER_OK') ? 'selected' : ''; ?>>S_ORDER_OK</option>

    <option value="BOOKING_FAIL,CREDITCARD_FAIL,NEW,POOR_CREDITWORTHINESS,REQ_OK,S_ORDER_OK" <?php echo (isset($_POST['Statusderbuchung']) && $_POST['Statusderbuchung'] == 'BOOKING_FAIL,CREDITCARD_FAIL,NEW,POOR_CREDITWORTHINESS,REQ_OK,S_ORDER_OK') ? 'selected' : ''; ?>>Buchung.NOT.OK</option>
</select>
    </div>
   <div class="col-md-4 mb-3">
    <label for="monthPicker" class="form-label">Hinflug Monat</label>
    <input type="month" name="selectedMonth" class="form-control" id="monthPicker" 
        value="<?php 
            echo isset($_POST['selectedMonth']) 
                ? $_POST['selectedMonth'] 
                : date('Y-m'); 
        ?>">
</div>



            <div class="col-md-3 mb-3 d-flex align-items-end">
                <button type="submit" name="submit" class="btn btn-custom">Suchen</button>
            </div>
            </div>
        </form>
    </div>

    <!-- PHP and Table -->
    <?php
ini_set('memory_limit', '1024M');

if (isset($_POST['submit'])) {
    $Statusderbuchung = $_POST['Statusderbuchung'];
    $selectedMonth = $_POST['selectedMonth'];  // Format will be 'YYYY-MM'
    $start_time = microtime(true);
    
    // Extract year and month
    list($year, $month) = explode('-', $selectedMonth);
    
    $connect = SQLServer1::connect();


    if(!$connect) {
        echo "Connection could not be established.<br />";
        die(print_r(sqlsrv_errors(), true));
    }

    // Exploding and cleaning up the Statusderbuchung input
    $statusArray = explode(',', $Statusderbuchung);
    
    // Adjusted SQL Query
    $sql = "SELECT 
                Buchungsdatum, 
                Agenturnummer, 
                Buchungsuhrzeit as Uhrzeit, 
                Statusderbuchung, 
                PlayerBrand, 
                Transfertyp,
                Betrag,
                Zahlungsart, 
                Hinflugdatum, 
                TrademarkCode, 
                FlugnummerHin,
                AbflugzeitHin, 
                AnkunftszeitHin, 
                AbflugzeitRück, 
                AnkunftszeitRück, 
                AirlineHin, 
                Buchungsnummer, 
                Von, 
                Nach, 
                Nachname, 
                ErwachseneAktiv, 
                ErwachseneInaktiv, 
                KinderAktiv,
                KinderInaktiv, 
                BabiesAktiv, 
                HotelCode, 
                Hotelname, 
                GiataCode, 
                Zimmerart, 
                ZimmerartCode, 
                Verpflegung, 
                AnkunftsdatumHin, 
                AnkunftsdatumRück, 
                Transfer 
            FROM 
                dbo.csv_export_copy 
            WHERE 
                CAST(CONVERT(datetime, Buchungsdatum, 104) AS date) >= DATEADD(day, -4, GETDATE())
                AND Statusderbuchung IN (" . implode(',', array_fill(0, count($statusArray), '?')) . ")
                
--                 AND DATEPART(YEAR, Hinflugdatum) = ? 
-- AND DATEPART(MONTH, Hinflugdatum) = ?
            ORDER BY 
                CAST(CONVERT(datetime, Buchungsdatum, 104) AS date) DESC;";
    
    // Merge statusArray with year and month parameters
    $params = array_merge($statusArray, [$year, $month]);
    
    $stmt = sqlsrv_query($connect, $sql, $params);

    if (!$stmt) {
        throw new Exception("Error in query: " . print_r(sqlsrv_errors(), true));
    }

    $data = array();
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $data[] = $row;
    }

    sqlsrv_close($connect);

    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time);
    echo "<p>Execution time: " . number_format($execution_time, 2) . " seconds</p>";


    // Display the data in the table
    echo '
    <div class="box box-primary table-responsive">
    <table id="dataTable" class="table table-striped table-bordered">
    <thead>
    <tr>
    <th nowrap>B.Datum</th>
    <th nowrap>Uhrzeit</th>
    <th nowrap>V. Marke</th>
    <th nowrap>Transfer</th>
    <th nowrap>Transfertyp</th>
    
    <th nowrap>PlayerBrand</th>
    <th nowrap>Buchungs.Nr.</th>
    <th nowrap>Betrag   </th>
    <th nowrap>Agency</th>
    <th nowrap>Von</th>
    <th nowrap>Nach</th>
    <th nowrap>H.F. Datum</th>
    <th nowrap>G. Code</th>
    <th nowrap>Htl. Code</th>
    <th nowrap>Htl. Name</th>
    
    <th nowrap>Zmr. Art</th>
    <th nowrap>Zmr. Code</th>
    <th nowrap>Verpflegung</th>
    <th nowrap>Ankft.D. H.</th>
    <th nowrap>Ankft.D. R.</th>
    <th nowrap>Airline H</th>
    <th nowrap>FlNr. H</th>
    <th nowrap>Ab.Fl. H.</th>
    <th nowrap>Ankft.Z. H.</th>
    <th nowrap>Ab.Fl. R.</th>
    <th nowrap>Ankft.Z. R.</th>
    <th nowrap>Nachname</th>
    <th nowrap>Erw. Aktiv</th>
    <th nowrap>CHD. Aktiv</th>
    <th nowrap>INF. Aktiv</th>        
    <th nowrap>Zahlungsart</th>
    </tr>
    </thead>
    <tbody>';

    foreach ($data as $row) {
        echo "<tr>
        <td nowrap>".$row['Buchungsdatum']."</td>
        <td nowrap>".$row['Uhrzeit']."</td>
        <td nowrap>".$row['TrademarkCode']."</td>
        <td nowrap>".$row['Transfer']."</td>
        <td nowrap>".$row['Transfertyp']."</td>

  

        <td nowrap>".$row['PlayerBrand']."</td>
        <td nowrap>".$row['Buchungsnummer']."</td>
        <td nowrap>".$row['Betrag']."</td>
        <td nowrap>".$row['Agenturnummer']."</td>
        <td nowrap>".$row['Von']."</td>
        <td nowrap>".$row['Nach']."</td>
        <td nowrap>".$row['Hinflugdatum']."</td>
        <td nowrap>".$row['GiataCode']."</td>
        <td nowrap>".$row['HotelCode']."</td>
        <td nowrap>".$row['Hotelname']."</td>
        
        <td nowrap>".$row['Zimmerart']."</td>
        <td nowrap>".$row['ZimmerartCode']."</td>
        <td nowrap>".$row['Verpflegung']."</td>
        <td nowrap>".$row['AnkunftsdatumHin']."</td>
        <td nowrap>".$row['AnkunftsdatumRück']."</td>
        <td nowrap>".$row['AirlineHin']."</td>
        <td nowrap>".$row['FlugnummerHin']."</td>
        <td nowrap>".$row['AbflugzeitHin']."</td>
        <td nowrap>".$row['AnkunftszeitHin']."</td>
        <td nowrap>".$row['AbflugzeitRück']."</td>
        <td nowrap>".$row['AnkunftszeitRück']."</td>
        <td nowrap>".$row['Nachname']."</td>
        <td nowrap>".$row['ErwachseneAktiv']."</td>
        <td nowrap>".$row['KinderAktiv']."</td>
        <td nowrap>".$row['BabiesAktiv']."</td>            
        <td nowrap>".$row['Zahlungsart']."</td>
        </tr>";
    }
    echo '
    </tbody>
    <tfoot>
    <tr>
    <th nowrap>B.Datum</th>
    <th nowrap>Uhrzeit</th>
    <th nowrap>V. Marke</th>
    <th nowrap>Transfer</th>
    <th nowrap>Transfertyp</th>
    
    <th nowrap>PlayerBrand</th>
    <th nowrap>Buchungs.Nr.</th>
    <th nowrap>Betrag   </th>
    <th nowrap>Agency</th>
    <th nowrap>Von</th>
    <th nowrap>Nach</th>
    <th nowrap>H.F. Datum</th>
    <th nowrap>G. Code</th>
    <th nowrap>Htl. Code</th>
    <th nowrap>Htl. Name</th>
    
    <th nowrap>Zmr. Art</th>
    <th nowrap>Zmr. Code</th>
    <th nowrap>Verpflegung</th>
    <th nowrap>Ankft.D. H.</th>
    <th nowrap>Ankft.D. R.</th>
    <th nowrap>Airline H</th>
    <th nowrap>FlNr. H</th>
    <th nowrap>Ab.Fl. H.</th>
    <th nowrap>Ankft.Z. H.</th>
    <th nowrap>Ab.Fl. R.</th>
    <th nowrap>Ankft.Z. R.</th>
    <th nowrap>Nachname</th>
    <th nowrap>Erw. Aktiv</th>
    <th nowrap>CHD. Aktiv</th>
    <th nowrap>INF. Aktiv</th>        
    <th nowrap>Zahlungsart</th>
    </tr>
    </tfoot>
    </table>
    </div>';
}
?>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script>
        $(document).ready(function() {
            var table = $('#dataTable').DataTable({
                "info": false,
                dom: 'Brtp',
                buttons: [
                    'csv', 'excel'
                ],
                initComplete: function() {
                    this.api().columns().every(function() {
                        var column = this;
                        var input = $('<input placeholder="' + column.footer().textContent + '" style="width: 100%;" />');
                        input.appendTo($(column.footer()).empty()).on('keyup change clear', function() {
                            if (column.search() !== this.value) {
                                column.search(this.value).draw();
                            }
                        });
                    });
                }
            });
            $('#dataTable tfoot tr').appendTo('#dataTable thead');
        });
    </script>
</body>
</html>
