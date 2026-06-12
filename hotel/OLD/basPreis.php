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
        /* Styling to remove whitespace on left for results section */
        .results-container {
            width: 100%;
            padding: 0;
            margin: 0;
        }

        .results-container .mb-4 {
            padding-left: 0;
            margin-left: 0;
        }

        /* Make table container scrollable horizontally */
        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }
         #resultsTable thead th {
        font-size: 80%;
    }

    .dataTables_filter {
        text-align: center !important;
    }

    .dataTables_filter label {
        width: 100%;
    }

    .dataTables_filter input {
        display: inline-block;
        width: 300px;
        margin-top: 10px;
    }

    .dt-button {
        background-color: #f3f3f3 !important;
        color: #000 !important;
        border: 1px solid #ccc !important;
        margin-right: 5px;
    }
    .dataTables_wrapper .dt-buttons {
    display: inline-block;
    vertical-align: middle;
    float: none !important;
}
.dataTables_wrapper .dataTables_filter {
    display: inline-block;
    float: none !important;
    text-align: center;
    margin-left: 100px; 
}
#resultsTable thead th {
    white-space: normal !important; 
    font-size: 70%; 
     font-weight: 500;
    vertical-align: middle;
    text-align: center;
    padding: 4px 6px;
    line-height: 1.2;
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
            <div class="col-md-3 mb-3">
                <label for="dateRange" class="form-label">Tag</label>
                <select name="dateRange" class="form-select" id="dateRange">
                    <?php 
                    $dateRanges = ["yesterday" => "gestern", "today" => "heute"];
                    foreach ($dateRanges as $value => $label) {
                        $selected = isset($_POST["dateRange"]) && $_POST["dateRange"] == $value ? 'selected' : '';
                        echo "<option value='$value' $selected>$label</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label for="zeit" class="form-label">Zeit</label>
                <select name="zeit" class="form-select" id="zeit">
                    <?php 
                    $times = [
                        "null" => "00-03 Uhr", "drei" => "03-06 Uhr", "sechs" => "06-10 Uhr",
                        "zhen" => "10-14 Uhr", "vierzhen" => "14-18 Uhr", "achtzhen" => "18-22 Uhr",
                        "zweizwansig" => "22-00 Uhr", "tag" => "00-24 Uhr"
                    ];
                    foreach ($times as $value => $label) {
                        $selected = isset($_POST["zeit"]) && $_POST["zeit"] == $value ? 'selected' : '';
                        echo "<option value='$value' $selected>$label</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label for="preissprung" class="form-label">Preissprüngen</label>
                <select name="preissprung" class="form-select" id="preissprung">
                    <?php 
                    $priceChanges = ["all" => "Alle", "with" => "Mit Preisabweichung", "without" => "Ohne Preisabweichung"];
                    foreach ($priceChanges as $value => $label) {
                        $selected = isset($_POST["preissprung"]) && $_POST["preissprung"] == $value ? 'selected' : '';
                        echo "<option value='$value' $selected>$label</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3 mb-3 d-flex align-items-end">
                <button type="submit" name="submit" class="btn btn-custom w-100">Suchen</button>
            </div>
        </div>
    </form>
</div>

<?php 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $zeit = $_POST["zeit"];
    $dateRange = $_POST["dateRange"];
    $preissprung = $_POST["preissprung"]; // Get preissprung selection

    ini_set('max_execution_time', 300);
    $currentDate = new DateTime();
    if ($dateRange == 'yesterday') {
        $currentDate->modify('-1 day');
    }
    $dayDate = $currentDate->format('Y-m-d');

    $timeRanges = [
        'null' => ['00:00:00', '03:00:00'],
        'drei' => ['03:00:00', '06:00:00'],
        'sechs' => ['06:00:00', '10:00:00'],
        'zhen' => ['10:00:00', '14:00:00'],
        'vierzhen' => ['14:00:00', '18:00:00'],
        'achtzhen' => ['18:00:00', '22:00:00'],
        'zweizwansig' => ['22:00:00', '23:59:59'],
        'tag' => ['00:00:00', '23:59:59']
    ];

    $startTime = $timeRanges[$zeit][0];
    $endTime = $timeRanges[$zeit][1];

    $connect = SQLServer2::connect();

    if (!$connect) {
        echo "<div class='alert alert-danger text-center'>Connection could not be established.<br>" . print_r(sqlsrv_errors(), true) . "</div>";
        die();
    }

    // Construct WHERE clause based on preissprung selection
    $preissprungCondition = "";
    if ($preissprung == "with") {
        $preissprungCondition = "AND PlayerTotalPriceDifferenceInSalesCY <> 0";
    } elseif ($preissprung == "without") {
        $preissprungCondition = "AND PlayerTotalPriceDifferenceInSalesCY = 0";
    }
    
    $query1 = "
        SELECT TOP(25) 
            PackageID,
            OutboundFlightPrice,
            InboundFlightPrice,
            HotelPrice,
            TransferPrice,
            ServiceFeePrice,
            PricePerAdult_DEL,
            PricePerChild_DEL,
            TotalPrice,
            TotalPriceFromCalculationService,
            InboundChildPrice,
            InboundInfantPrice,
            OutboundChildPrice,
            OutboundInfantPrice,
            PlayerHotelPrice,
            PlayerInboundPrice,
            PlayerOutboundPrice,
            HandlingBuyingPrice,
            TransferBuyingPrice,
            PlayerInboundPriceByPax,
            PlayerOutboundPriceByPax,
            PlayerOverallPrice,
            PlayerTotalPriceDifference,
            ExternalPackagePrice,
            PriceDeviation,
            OutboundFlightEKPriceInSalesCY,
            InboundFlightEKPriceInSalesCY,
            OriginalOutboundFlightEKPrice,
            OriginalInboundFlightEKPrice,
            OutboundFactorOriginalCYToSalesCY,
            InboundFactorOriginalCYToSalesCY,
            HotelEKPriceInSalesCY,
            HotelEKFactorOriginalCYToSalesCY,
            HandlingBuyingPriceInSalesCY,
            HandlingEKFactorOriginalCYToSalesCY,
            TransferBuyingPriceInSalesCY,
            TransferEKFactorOriginalCYToSalesCY,
            PresetPriceInSalesCY,
            BasePriceInclTransferInSalesCY,
            TomaCachePackagePrice,
            TomaCacheFlightPrice,
            TomaCacheHotelPrice,
            OriginalOutboundFlightPrice,
            OriginalInboundFlightPrice,
            OriginalInboundChildPrice,
            OriginalOutboundChildPrice,
            OriginalInboundInfantPrice,
            OriginalOutboundInfantPrice,
            PlayerInboundPriceInSalesCY,
            PlayerOutboundPriceInSalesCY,
            PlayerInboundPriceByPaxInSalesCY,
            PlayerOutboundPriceByPaxInSalesCY,
            PlayerOverallPriceInSalesCY,
            PlayerTotalPriceDifferenceInSalesCY,
            OriginalHotelEKPrice
        FROM PackageInformationObjects 
        WHERE CONVERT(date, CreationDate) = ?
            AND (CONVERT(time, CreationDate) >= ? AND CONVERT(time, CreationDate) <= ?)            
            $preissprungCondition
    ";
//AND PackageID like '%1585686384'

    $params = array($dayDate, $startTime, $endTime);
    $stmt = sqlsrv_query($connect, $query1, $params);

          if ($stmt !== false) {
            /*echo "<div class='results-container'>";
            // Column selection checkboxes
            echo "<div class='mb-4'><strong>Select Columns to Display:</strong><br>";
            $columns = sqlsrv_field_metadata($stmt);
            foreach ($columns as $index => $col) {
                $colName = htmlspecialchars($col["Name"]);
                echo "<input type='checkbox' class='column-toggle' data-column='$index' checked> $colName &nbsp;";
            }
            echo "</div>";*/

            // Wrap table in a scrollable container
            echo "<div class='table-responsive'>";
            echo "<table id='resultsTable' class='table table-striped table-bordered text-end'>";
            echo "    <thead>
        <tr>
            <th>Package ID</th>
            <th>Outbound Flight Price</th>
            <th>Inbound Flight Price</th>
            <th>Hotel Price</th>
            <th>Transfer Price</th>
            <th>Service Fee Price</th>
            <th>Price Per Adult (DEL)</th>
            <th>Price Per Child (DEL)</th>
            <th>Total Price</th>
            <th>Total Price (Calculation Service)</th>
            <th>Inbound Child Price</th>
            <th>Inbound Infant Price</th>
            <th>Outbound Child Price</th>
            <th>Outbound Infant Price</th>
            <th>Player Hotel Price</th>
            <th>Player Inbound Price</th>
            <th>Player Outbound Price</th>
            <th>Handling Buying Price</th>
            <th>Transfer Buying Price</th>
            <th>Player Inbound Price By Pax</th>
            <th>Player Outbound Price By Pax</th>
            <th>Player Overall Price</th>
            <th>Player Total Price Difference</th>
            <th>External Package Price</th>
            <th>Price Deviation</th>
            <th>Outbound Flight EK Price (Sales CY)</th>
            <th>Inbound Flight EK Price (Sales CY)</th>
            <th>Original Outbound Flight EK Price</th>
            <th>Original Inbound Flight EK Price</th>
            <th>Outbound Factor (Original CY to Sales CY)</th>
            <th>Inbound Factor (Original CY to Sales CY)</th>
            <th>Hotel EK Price (Sales CY)</th>
            <th>Hotel EK Factor (Original CY to Sales CY)</th>
            <th>Handling Buying Price (Sales CY)</th>
            <th>Handling EK Factor (Original CY to Sales CY)</th>
            <th>Transfer Buying Price (Sales CY)</th>
            <th>Transfer EK Factor (Original CY to Sales CY)</th>
            <th>Preset Price (Sales CY)</th>
            <th>Base Price Incl Transfer (Sales CY)</th>
            <th>Toma Cache Package Price</th>
            <th>Toma Cache Flight Price</th>
            <th>Toma Cache Hotel Price</th>
            <th>Original Outbound Flight Price</th>
            <th>Original Inbound Flight Price</th>
            <th>Original Inbound Child Price</th>
            <th>Original Outbound Child Price</th>
            <th>Original Inbound Infant Price</th>
            <th>Original Outbound Infant Price</th>
            <th>Player Inbound Price (Sales CY)</th>
            <th>Player Outbound Price (Sales CY)</th>
            <th>Player Inbound Price By Pax (Sales CY)</th>
            <th>Player Outbound Price By Pax (Sales CY)</th>
            <th>Player Overall Price (Sales CY)</th>
            <th>Player Total Price Difference (Sales CY)</th>
            <th>Original Hotel EK Price</th>
        </tr>
    </thead><tbody>";

            // Fetch and display data rows
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                echo "<tr>";
                foreach ($row as $colValue) {
                    if ($colValue instanceof DateTime) {
                        echo "<td>" . htmlspecialchars($colValue->format('Y-m-d H:i:s')) . "</td>";
                    } elseif ($colValue !== null) {
                        echo "<td>" . htmlspecialchars($colValue) . "</td>";
                    } else {
                        echo "<td></td>";
                    }
                }
                echo "</tr>";
            }
            echo "</tbody></table>";
            echo "</div>"; // Close table-responsive div
            echo "</div>";
        }
        sqlsrv_close($connect);
    }
    ?>

    <!-- jQuery and DataTables Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#resultsTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });

            // Toggle columns based on checkbox selection
            $('.column-toggle').on('change', function() {
                var column = table.column($(this).attr('data-column'));
                column.visible($(this).is(':checked'));
            });
        });
    </script>
</body>
</html>