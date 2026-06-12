<?php
// hourly_stats.php
// Display provider stats using SQLServer2 (sqlsrv connection)

 include 'navbar.php'; 
          include_once 'config/sqlServer2.php'; 
   
$connect = SQLServer2::connect();

if (!$connect) {
    die("<div class='alert alert-danger text-center'>Connection could not be established.<br>" . print_r(sqlsrv_errors(), true) . "</div>");
}

// ---------------------------
// Define queries for previous and current hour
// ---------------------------
$sql_prev = "
SELECT
    b.PlayerProvider AS provider,
    COUNT(DISTINCT b.PackageInformationObject_Id) AS total_requests,
    ROUND(
        100.0 * COUNT(DISTINCT CASE WHEN a.success <> 'true' THEN c.PackageId END)
        / NULLIF(COUNT(DISTINCT c.PackageId), 0)
    , 2) AS failure_rate_percent,
    ROUND(AVG(c.TotalPrice - c.TotalPriceFromCalculationService), 2) AS avg_price_deviation,
    ROUND(
        100.0 * COUNT(DISTINCT CASE WHEN (c.TotalPrice - c.TotalPriceFromCalculationService) > 10 AND a.success = 'true' THEN c.PackageId END)
        / NULLIF(COUNT(DISTINCT CASE WHEN a.success = 'true' THEN c.PackageId END),0)
    , 2) AS percent_over_10eur,
    ROUND(
        100.0 * COUNT(DISTINCT CASE WHEN (c.TotalPrice - c.TotalPriceFromCalculationService) > 20 AND a.success = 'true' THEN c.PackageId END)
        / NULLIF(COUNT(DISTINCT CASE WHEN a.success = 'true' THEN c.PackageId END),0)
    , 2) AS percent_over_20eur,
    ROUND(
        100.0 * COUNT(DISTINCT CASE WHEN (c.TotalPrice - c.TotalPriceFromCalculationService) > 50 AND a.success = 'true' THEN c.PackageId END)
        / NULLIF(COUNT(DISTINCT CASE WHEN a.success = 'true' THEN c.PackageId END),0)
    , 2) AS percent_over_50eur
FROM dbo.OperationInformationObjects a WITH (READUNCOMMITTED)
JOIN dbo.ServiceInformationObjects b WITH (READUNCOMMITTED)
    ON a.PackageInformationObject_Id = b.PackageInformationObject_Id
JOIN dbo.PackageInformationObjects c WITH (READUNCOMMITTED)
    ON c.id = a.PackageInformationObject_Id
WHERE c.creationDate >= DATEADD(HOUR, -2, GETDATE())
  AND c.creationDate < DATEADD(HOUR, -1, GETDATE())
  AND a.OperationScope = 'CreatePackage'
  AND b.PlayerProvider IS NOT NULL
GROUP BY b.PlayerProvider
ORDER BY total_requests DESC;
";

$sql_curr = "
SELECT
    b.PlayerProvider AS provider,
    COUNT(DISTINCT b.PackageInformationObject_Id) AS total_requests,
    ROUND(
        100.0 * COUNT(DISTINCT CASE WHEN a.success <> 'true' THEN c.PackageId END)
        / NULLIF(COUNT(DISTINCT c.PackageId), 0)
    , 2) AS failure_rate_percent,
    ROUND(AVG(c.TotalPrice - c.TotalPriceFromCalculationService), 2) AS avg_price_deviation,
    ROUND(
        100.0 * COUNT(DISTINCT CASE WHEN (c.TotalPrice - c.TotalPriceFromCalculationService) > 10 AND a.success = 'true' THEN c.PackageId END)
        / NULLIF(COUNT(DISTINCT CASE WHEN a.success = 'true' THEN c.PackageId END),0)
    , 2) AS percent_over_10eur,
    ROUND(
        100.0 * COUNT(DISTINCT CASE WHEN (c.TotalPrice - c.TotalPriceFromCalculationService) > 20 AND a.success = 'true' THEN c.PackageId END)
        / NULLIF(COUNT(DISTINCT CASE WHEN a.success = 'true' THEN c.PackageId END),0)
    , 2) AS percent_over_20eur,
    ROUND(
        100.0 * COUNT(DISTINCT CASE WHEN (c.TotalPrice - c.TotalPriceFromCalculationService) > 50 AND a.success = 'true' THEN c.PackageId END)
        / NULLIF(COUNT(DISTINCT CASE WHEN a.success = 'true' THEN c.PackageId END),0)
    , 2) AS percent_over_50eur
FROM dbo.OperationInformationObjects a WITH (READUNCOMMITTED)
JOIN dbo.ServiceInformationObjects b WITH (READUNCOMMITTED)
    ON a.PackageInformationObject_Id = b.PackageInformationObject_Id
JOIN dbo.PackageInformationObjects c WITH (READUNCOMMITTED)
    ON c.id = a.PackageInformationObject_Id
WHERE c.creationDate >= DATEADD(HOUR, -1, GETDATE())
  AND a.OperationScope = 'CreatePackage'
  AND b.PlayerProvider IS NOT NULL
GROUP BY b.PlayerProvider
ORDER BY total_requests DESC;
";

// ---------------------------
// Execute queries and fetch results
// ---------------------------
$stats_prev = [];
$stmt_prev = sqlsrv_query($connect, $sql_prev);
if ($stmt_prev === false) {
    die("Previous hour query failed: " . print_r(sqlsrv_errors(), true));
}
while ($row = sqlsrv_fetch_array($stmt_prev, SQLSRV_FETCH_ASSOC)) {
    $stats_prev[] = $row;
}
sqlsrv_free_stmt($stmt_prev);

$stats_curr = [];
$stmt_curr = sqlsrv_query($connect, $sql_curr);
if ($stmt_curr === false) {
    die("Current hour query failed: " . print_r(sqlsrv_errors(), true));
}
while ($row = sqlsrv_fetch_array($stmt_curr, SQLSRV_FETCH_ASSOC)) {
    $stats_curr[] = $row;
}
sqlsrv_free_stmt($stmt_curr);

// Close connection
sqlsrv_close($connect);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Provider Stats (Previous vs Current Hour)</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="p-4">

<h3 class="text-center mb-4">Provider Stats — Previous Hour vs Current Hour</h3>

<div class="container-fluid">
  <div class="row">

    <!-- Previous Hour Table & Chart -->
    <div class="col-md-6">
      <h5 class="text-center text-primary">Previous Hour (−2h → −1h)</h5>
      <div class="table-responsive mb-3">
        <table class="table table-bordered table-hover table-sm text-center">
          <thead class="thead-dark">
            <tr>
              <th>Provider</th>
              <th>Total</th>
              <th>Fail %</th>
              <th>Avg €</th>
              <th>>10 €</th>
              <th>>20 €</th>
              <th>>50 €</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($stats_prev as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['provider']) ?></td>
                <td><?= number_format($row['total_requests']) ?></td>
                <td><?= number_format($row['failure_rate_percent'], 2) ?>%</td>
<td><?= number_format($row['avg_price_deviation'], 2) ?>€</td>
<td><?= number_format($row['percent_over_10eur'], 2) ?>%</td>
<td><?= number_format($row['percent_over_20eur'], 2) ?>%</td>
<td><?= number_format($row['percent_over_50eur'], 2) ?>%</td>

              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <canvas id="chartPrev" height="150"></canvas>
    </div>

    <!-- Current Hour Table & Chart -->
    <div class="col-md-6">
      <h5 class="text-center text-success">Current Hour (−1h → now)</h5>
      <div class="table-responsive mb-3">
        <table class="table table-bordered table-hover table-sm text-center">
          <thead class="thead-dark">
            <tr>
              <th>Provider</th>
              <th>Total</th>
              <th>Fail %</th>
              <th>Avg €</th>
              <th>>10 €</th>
              <th>>20 €</th>
              <th>>50 €</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($stats_curr as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['provider']) ?></td>
                <td><?= number_format($row['total_requests']) ?></td>
               <td><?= number_format($row['failure_rate_percent'], 2) ?>%</td>
<td><?= number_format($row['avg_price_deviation'], 2) ?>€</td>
<td><?= number_format($row['percent_over_10eur'], 2) ?>%</td>
<td><?= number_format($row['percent_over_20eur'], 2) ?>%</td>
<td><?= number_format($row['percent_over_50eur'], 2) ?>%</td>

              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <canvas id="chartCurr" height="150"></canvas>
    </div>

  </div>
</div>

<script>
function createChart(canvasId, stats, color) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: stats.map(s => s.provider),
            datasets: [
                { label: 'Failure %', data: stats.map(s => s.failure_rate_percent), backgroundColor: color[0], yAxisID: 'y1' },
                { label: 'Avg €', data: stats.map(s => s.avg_price_deviation), backgroundColor: color[1], yAxisID: 'y2' },
                { label: 'Total', data: stats.map(s => s.total_requests), borderColor: color[2], type: 'line', fill: false, yAxisID: 'y3' }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            stacked: false,
            scales: {
                y1: { type: 'linear', position: 'left', beginAtZero: true, title: { display: true, text: '%' } },
                y2: { type: 'linear', position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, title: { display: true, text: '€' } },
                y3: { type: 'linear', position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, title: { display: true, text: 'Requests' } }
            }
        }
    });
}

const statsPrev = <?= json_encode($stats_prev) ?>;
const statsCurr = <?= json_encode($stats_curr) ?>;

createChart('chartPrev', statsPrev, ['rgba(255,99,132,0.6)', 'rgba(54,162,235,0.6)', 'rgba(153,102,255,1)']);
createChart('chartCurr', statsCurr, ['rgba(75,192,192,0.6)', 'rgba(255,206,86,0.6)', 'rgba(255,159,64,1)']);
</script>

</body>
</html>
