<?php
// عنوان الصفحة يظهر في <title> في الهيدر
$pageTitle = "Price Deviation Summary";

// استدعاء الهيدر (يحتوي على <html>, <head>, Navbar)
include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/header.php';

// استدعاء ملف الاتصال بقاعدة البيانات
include_once $_SERVER['DOCUMENT_ROOT'].'/hotel/config/sqlServer2.php';

$connect = SQLServer2::connect();
if (!$connect) {
    echo "<div class='alert alert-danger text-center'>Connection could not be established.<br>" 
         . print_r(sqlsrv_errors(), true) . "</div>";
    die();
}

// قيم الفلترة من GET أو القيم الافتراضية
$from_date = $_GET['from_date'] ?? date('Y-m-d H:i', strtotime('-60 minutes'));
$to_date   = $_GET['to_date']   ?? date('Y-m-d H:i');
$playerbrand = trim($_GET['playerbrand'] ?? '');
$search = trim($_GET['search'] ?? '');

// التحقق من صحة التواريخ
try {
    $from_date_obj = new DateTime($from_date);
    $to_date_obj = new DateTime($to_date);
} catch (Exception $e) {
    die("Invalid date format. Please enter a valid date.");
}

$from_date_sql = $from_date_obj->format('Y-m-d H:i:s');
$to_date_sql   = $to_date_obj->format('Y-m-d H:i:s');

// استعلام SQL
$query = "
SELECT TOP(20)
    HotelAccomodation, HotelCode, PlayerBrand, HotelGiataCode, HotelName, OutboundOriginTLC, OutboundArrivalTlc,
    COUNT(PackageId) AS TotalPackages,
    SUM(CASE WHEN PriceDeviation > 0 THEN 1 ELSE 0 END) AS PC_bookable,
    SUM(CASE WHEN PriceDeviation > 0 AND PriceDeviation <= 1 THEN 1 ELSE 0 END) AS pc_0_1,
    SUM(CASE WHEN PriceDeviation > 1 AND PriceDeviation <= 5 THEN 1 ELSE 0 END) AS pc_1_5,
    SUM(CASE WHEN PriceDeviation > 5 AND PriceDeviation <= 10 THEN 1 ELSE 0 END) AS pc_5_10,
    SUM(CASE WHEN PriceDeviation > 10 AND PriceDeviation <= 15 THEN 1 ELSE 0 END) AS pc_10_15,
    SUM(CASE WHEN PriceDeviation > 15 AND PriceDeviation <= 25 THEN 1 ELSE 0 END) AS pc_15_25,
    SUM(CASE WHEN PriceDeviation > 25 AND PriceDeviation <= 50 THEN 1 ELSE 0 END) AS pc_25_50,
    SUM(CASE WHEN PriceDeviation > 50 THEN 1 ELSE 0 END) AS pc_over_50
FROM dbo.PackageInformationObjects
WHERE CreationDate BETWEEN CONVERT(DATETIME, ?, 120) AND CONVERT(DATETIME, ?, 120)
";

$params = [$from_date_sql, $to_date_sql];

if ($search !== '') {
    $like = '%' . $search . '%';
    $query .= " AND (PlayerBrand LIKE ? OR HotelAccomodation LIKE ? OR HotelCode LIKE ? OR HotelGiataCode LIKE ?) ";
    $params = array_merge($params, [$like, $like, $like, $like]);
}

$query .= "
GROUP BY HotelAccomodation, HotelCode, PlayerBrand, HotelGiataCode, HotelName, OutboundOriginTLC, OutboundArrivalTlc
ORDER BY pc_over_50 DESC, COUNT(PackageId) DESC, HotelGiataCode
";

$stmt = sqlsrv_query($connect, $query, $params);
if ($stmt === false) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}

// تخزين النتائج
$rows = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $rows[] = $row;
}
sqlsrv_close($connect);

// للحماية في نموذج البحث
$search_html = htmlspecialchars($search, ENT_QUOTES);
?>

<div class="container mt-4">
    <h4>Price Deviation Summary</h4>

    <!-- نموذج البحث -->
    <form method="GET" class="mb-3 row g-3">
        <div class="col-md-3">
            <label for="from_date" class="form-label">Von:</label>
            <input type="datetime-local" class="form-control" id="from_date" name="from_date" value="<?php echo date('Y-m-d\TH:i', strtotime($from_date)); ?>" required>
        </div>
        <div class="col-md-3">
            <label for="to_date" class="form-label">Bis:</label>
            <input type="datetime-local" class="form-control" id="to_date" name="to_date" value="<?php echo date('Y-m-d\TH:i', strtotime($to_date)); ?>" required>
        </div>
        <div class="col-md-3">
            <label for="search" class="form-label">Search:</label>
            <input type="text" class="form-control" id="search" name="search" value="<?php echo $search_html; ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-custom">Suchen</button>
        </div>
    </form>

    <!-- جدول البيانات -->
    <table id="dataTable" class="table table-striped table-bordered table-sm w-100" style="font-size:13px;">
        <thead>
            <tr>
                <th>Hotel Code</th>
                <th>Hotel Giata Code</th>
                <th>Hotel Accomodation</th>
                <th>PlayerBrand</th>
                <th>Hotel Name</th>
                <th>von</th>
                <th>nach</th>
                <th>Total Packages</th>
                <th>PC bookable</th>
                <th>0 &lt; PCQ &le; 1</th>
                <th>1 &lt; PCQ &le; 5</th>
                <th>5 &lt; PCQ &le; 10</th>
                <th>10 &lt; PCQ &le; 15</th>
                <th>15 &lt; PCQ &le; 25</th>
                <th>25 &lt; PCQ &le; 50</th>
                <th>PCQ &gt; 50</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['HotelCode']); ?></td>
                <td><?php echo htmlspecialchars($row['HotelGiataCode']); ?></td>
                <td><?php echo htmlspecialchars($row['HotelAccomodation']); ?></td>
                <td><?php echo htmlspecialchars($row['PlayerBrand']); ?></td>
                <td><?php echo htmlspecialchars($row['HotelName']); ?></td>
                <td><?php echo htmlspecialchars($row['OutboundOriginTLC']); ?></td>
                <td><?php echo htmlspecialchars($row['OutboundArrivalTlc']); ?></td>
                <td><?php echo number_format($row['TotalPackages']); ?></td>
                <td><?php echo number_format($row['PC_bookable']); ?></td>
                <td><?php echo number_format($row['pc_0_1']); ?></td>
                <td><?php echo number_format($row['pc_1_5']); ?></td>
                <td><?php echo number_format($row['pc_5_10']); ?></td>
                <td><?php echo number_format($row['pc_10_15']); ?></td>
                <td><?php echo number_format($row['pc_15_25']); ?></td>
                <td><?php echo number_format($row['pc_25_50']); ?></td>
                <td><?php echo number_format($row['pc_over_50']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
// استدعاء الفوتر (يحتوي على Bootstrap JS وإغلاق </body> و </html>)
include $_SERVER['DOCUMENT_ROOT'].'/hotel/includes/footer.php';
?>