<?php
include 'navbar.php'; 

require_once 'config/sqlServer2.php';
    $connect = SQLServer2::connect();

    if (!$connect) {
        echo "<div class='alert alert-danger text-center'>Connection could not be established.<br>" . print_r(sqlsrv_errors(), true) . "</div>";
        die();
    }


// Get filter values from GET request or set default (last hour to now)
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d H:i');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d H:i');
$playerbrand = isset($_GET['playerbrand']) ? trim($_GET['playerbrand']) : '';

// Convert to DateTime object to ensure validity
try {
    $from_date_obj = new DateTime($from_date);
    $to_date_obj = new DateTime($to_date);
} catch (Exception $e) {
    die("Invalid date format. Please enter a valid date.");
}

// --- build dates as you already do above ---
$from_date_sql = $from_date_obj->format('Y-m-d H:i:s');
$to_date_sql   = $to_date_obj->format('Y-m-d H:i:s');

// New query: get distinct PackageIds in the date range, then take latest player price and latest hotelEK price per PackageId
$query = "
WITH DistinctPIO AS (
    SELECT DISTINCT PackageId
    FROM PackageInformationObjects
    WHERE CreationDate BETWEEN CONVERT(DATETIME, ?, 120) AND CONVERT(DATETIME, ?, 120)
)
SELECT
    d.PackageId AS PIO_ID,
    FORMAT(p_creation.CreationDate, 'HH:mm') AS BA_Timestamp,
    p_creation.PlayerBrand,
    p_creation.HotelGiataCode,
    p_creation.HotelCode,
    CONCAT(FORMAT(p_creation.OutboundDate, 'dd.MM.yy'), ' - ', FORMAT(p_creation.InboundDate, 'dd.MM.yy')) AS Rise,
    p_creation.OutboundOriginTlc AS Outbound_flight_route,
    p_creation.InboundOriginTlc AS Inbound_flight_route,
    ph.playerHotelPrice,
    he.HotelEKPriceInSalesCY,
    (COALESCE(he.HotelEKPriceInSalesCY,0) - COALESCE(ph.playerHotelPrice,0)) AS PriceDiff
FROM DistinctPIO d
OUTER APPLY (
    -- latest row that has playerHotelPrice for this PackageId
    SELECT TOP 1 playerHotelPrice, CreationDate
    FROM PackageInformationObjects t
    WHERE t.PackageId = d.PackageId AND t.playerHotelPrice IS NOT NULL
    ORDER BY t.CreationDate DESC
) ph
OUTER APPLY (
    -- latest row that has HotelEKPriceInSalesCY for this PackageId
    SELECT TOP 1 HotelEKPriceInSalesCY, CreationDate
    FROM PackageInformationObjects t
    WHERE t.PackageId = d.PackageId AND t.HotelEKPriceInSalesCY IS NOT NULL
    ORDER BY t.CreationDate DESC
) he
OUTER APPLY (
    -- latest general row to show metadata (brand, giata, rise, routes, time...)
    SELECT TOP 1 PlayerBrand, HotelGiataCode, HotelCode, OutboundDate, InboundDate, OutboundOriginTlc, InboundOriginTlc, CreationDate
    FROM PackageInformationObjects t
    WHERE t.PackageId = d.PackageId
    ORDER BY t.CreationDate DESC
) p_creation
WHERE ph.playerHotelPrice IS NOT NULL
  AND he.HotelEKPriceInSalesCY IS NOT NULL
";

// parameters: the two placeholders for the CTE date filter
$params = [$from_date_sql, $to_date_sql];

// optional playerbrand filter (applied to the metadata row p_creation)
if (!empty($playerbrand)) {
    $query .= " AND p_creation.PlayerBrand LIKE ? ";
    $params[] = "%$playerbrand%";
}

$query .= " ORDER BY PriceDiff DESC";

// execute
$stmt = sqlsrv_query($connect, $query, $params);
if ($stmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}



// HTML Output
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Package Data (Filtered)</title>
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid black; padding: 8px; text-align: center; }
        th { background-color: yellow; }
        .highlight { background-color: #FFFF99; font-weight: bold; }

        
        form { margin-bottom: 20px; }
        label { font-weight: bold; margin-right: 5px; }
        input, button { padding: 5px; margin-right: 10px; }
.filter-button {
        background-color: #e0e6e9;    
        border: none;
        padding: 10px 20px;
        border-radius: 8px;          
        font-size: 16px;
        font-family: sans-serif;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .filter-button:hover {
        background-color: #d0d8db;   
    }
    </style>
</head>
<body>

<div class='container'>
    <h2>-</h2>

    <!-- Filter Form -->
   <form method='GET'>
    <label for='from_date'>Von:</label>
    <input type='datetime-local' id='from_date' name='from_date' value='" . date('Y-m-d\TH:i', strtotime($from_date)) . "' required>

    <label for='to_date'>Bis:</label>
    <input type='datetime-local' id='to_date' name='to_date' value='" . date('Y-m-d\TH:i', strtotime($to_date)) . "' required>

    <!--<label for='playerbrand'>Player Brand:</label>
    <input type='text' id='playerbrand' name='playerbrand' value='" . htmlspecialchars($_GET['playerbrand'] ?? '', ENT_QUOTES) . "'> -->

    <button type='submit' class='filter-button'>Suchen</button>
</form>

<div style='display: flex; justify-content: center; align-items: center; margin: 20px 0;'>
    <label for='searchInput' style='margin-right: 10px; font-weight: bold;'>Search</label>
    <input type='text' id='searchInput' placeholder='Text' style='width: 300px; padding: 5px;'>
</div>


    <!-- Table -->
    <table>
    <thead>
        <tr>
            <th>PIO ID</th> 
            <th>BA Time</th>
            <th>PlayerBrand</th>
            <th>Giata</th>
            <th>HotelCode</th>
             
            <th class='highlight'>Reise Dauer</th>
            <th class='highlight'>von</th>
            <th class='highlight'>nach</th>                

            
            <th>PlyHPrice</th>
            <th>HotelEK</th>
            <th>Price Diff</th>
        </tr>
    </thead>
    <tbody>
";

// Fetch and display rows
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>
            <td>{$row['PIO_ID']}</td>
            <td>{$row['BA_Timestamp']}</td>
            <td>{$row['PlayerBrand']}</td>
            <td>{$row['HotelGiataCode']}</td>
            <td>{$row['HotelCode']}</td>
            <td class='highlight'>{$row['Rise']}</td>
            <td class='highlight'>{$row['Outbound_flight_route']}</td>
            <td class='highlight'>{$row['Inbound_flight_route']}</td>
            <td>{$row['playerHotelPrice']}</td>
            <td>{$row['HotelEKPriceInSalesCY']}</td>
            <td>{$row['PriceDiff']}</td>
        </tr>";
}


echo "</tbody></table></div>
";

// Close connection
sqlsrv_close($connect);
?>

<script>
    document.addEventListener("DOMContentLoaded", function () {
    const getCellValue = (row, index) => {
        const cell = row.children[index].innerText || row.children[index].textContent;
        return isNaN(cell) ? cell.trim() : parseFloat(cell.replace(/,/g, '')); // Handle numbers properly
    };

    const comparer = (index, asc) => (a, b) => {
        const v1 = getCellValue(asc ? a : b, index);
        const v2 = getCellValue(asc ? b : a, index);
        return (typeof v1 === "number" && typeof v2 === "number") ? v1 - v2 : v1.localeCompare(v2);
    };

    document.querySelectorAll("th").forEach(th =>
        th.addEventListener("click", function () {
            const table = th.closest("table");
            const tbody = table.querySelector("tbody");
            Array.from(tbody.querySelectorAll("tr"))
                .sort(comparer(Array.from(th.parentNode.children).indexOf(th), this.asc = !this.asc))
                .forEach(tr => tbody.appendChild(tr));
        })
    );
});
</script>




<script>
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchInput");
    const table = document.querySelector("table tbody");

    searchInput.addEventListener("input", function () {
        const filter = this.value.toLowerCase();
        const rows = table.getElementsByTagName("tr");

        for (let i = 0; i < rows.length; i++) {
            const cells = rows[i].getElementsByTagName("td");
            let match = false;

            for (let j = 0; j < cells.length; j++) {
                const cellText = cells[j].textContent.toLowerCase();
                if (cellText.includes(filter)) {
                    match = true;
                    break;
                }
            }

            rows[i].style.display = match ? "" : "none";
        }
    });
});
</script>




</body></html>