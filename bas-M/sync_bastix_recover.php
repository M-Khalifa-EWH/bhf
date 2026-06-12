<?php
// cron_import_sqlsrv_to_mysql.php
// Bulk-insert from MSSQL to MySQL WITHOUT ON DUPLICATE KEY UPDATE.
// Uses error_log() instead of writing to local log files (no folder write required).

date_default_timezone_set('Europe/Berlin');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('max_execution_time', 0);
set_time_limit(0);

// Simple logger wrapper using PHP error_log (goes to PHP error log / syslog)
function logmsg($msg) {
    $prefix = '[' . date('Y-m-d H:i:s') . '] cron_import_sqlsrv_to_mysql: ';
    error_log($prefix . $msg);
}

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err) {
        logmsg("SHUTDOWN ERROR: " . print_r($err, true));
    } else {
        logmsg("SHUTDOWN: normal exit");
    }
});

try {
    logmsg("START");

    // Adjust these require paths if necessary
    require_once __DIR__ . '/../config/sqlserver2.php'; // SQLServer2::connect()
    require_once __DIR__ . '/../config/mysql.php';      // MySQLDatabase::connect()

    // Connect to MySQL (PDO)
    $mysql = MySQLDatabase::connect();
    $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $mysql->exec("SET NAMES utf8mb4");
    logmsg("Connected to MySQL");

    // Connect to SQL Server (sqlsrv resource)
    $sqlsrv = SQLServer2::connect();
    if (!$sqlsrv) {
        throw new Exception("Could not connect to SQL Server.");
    }
    logmsg("Connected to MSSQL");

    // Fetch rows from MSSQL
    $sqlsrvQuery = <<<SQL
SELECT 
    a.CreationDate AS Datum,
    a.PackageId AS BAs,
    b.[User] AS usr,
    b.Agency AS agency,
    b.Success,
    a.PriceDeviation,
    b.Start AS startt,
    b.[End] AS endd
FROM dbo.OperationInformationObjects b WITH (READUNCOMMITTED)
INNER JOIN dbo.PackageInformationObjects a WITH (READUNCOMMITTED)
    ON a.Id = b.PackageInformationObject_Id
WHERE 
    a.CreationDate BETWEEN '2025-10-28T16:46:00' AND '2025-10-28T16:47:00'
    AND b.PackageInformationObject_Id IS NOT NULL
    AND b.IsPBP = 0
    AND b.OperationScope = 'createpackage';
SQL;

    $stmt = sqlsrv_query($sqlsrv, $sqlsrvQuery);
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        throw new Exception('SQL Server query failed: ' . print_r($errors, true));
    }

    $rows = [];
    $count = 0;
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $rows[] = $row;
        $count++;
    }
    sqlsrv_free_stmt($stmt);
    logmsg("Fetched rows from MSSQL: {$count}");

    if ($count === 0) {
        logmsg("No rows to insert. Exiting.");
        exit(0);
    }

    // Bulk insert settings
    $batchSize = 600; // you said ~500-600 rows per run
    $columns = ['Datum','BAs','usr','agency','Success','PriceDeviation','startt','endd','marke'];
    $insertBase = "INSERT IGNORE INTO bastix (" . implode(',', $columns) . ") VALUES ";

    $chunks = array_chunk($rows, $batchSize);
    $totalInserted = 0;

    foreach ($chunks as $chunkIndex => $chunk) {
        $placeholders = [];
        $values = [];

        foreach ($chunk as $r) {
            // Normalize dates (sqlsrv may return DateTime objects)
            $Datum = null;
            if (isset($r['Datum']) && $r['Datum'] instanceof DateTime) {
                $Datum = $r['Datum']->format('Y-m-d H:i:s');
            } elseif (!empty($r['Datum'])) {
                $Datum = date('Y-m-d H:i:s', strtotime($r['Datum']));
            }

            $startt = null;
            if (isset($r['startt']) && $r['startt'] instanceof DateTime) {
                $startt = $r['startt']->format('Y-m-d H:i:s');
            } elseif (!empty($r['startt'])) {
                $startt = date('Y-m-d H:i:s', strtotime($r['startt']));
            }

            $endd = null;
            if (isset($r['endd']) && $r['endd'] instanceof DateTime) {
                $endd = $r['endd']->format('Y-m-d H:i:s');
            } elseif (!empty($r['endd'])) {
                $endd = date('Y-m-d H:i:s', strtotime($r['endd']));
            }

  $priceDeviation = $r['PriceDeviation'];
if ($priceDeviation !== null && $priceDeviation !== '') {
    $priceDeviation = (float)$priceDeviation;
} else {
    $priceDeviation = 0;  // بدل null
}

// Clamp PriceDeviation to match DECIMAL(6,2) range
$maxVal = 9999.99;
$minVal = -9999.99;

if ($priceDeviation > $maxVal) $priceDeviation = $maxVal;
if ($priceDeviation < $minVal) $priceDeviation = $minVal;



            $Success = ($r['Success'] === null) ? null : (((int)$r['Success']) ? 1 : 0);

            // placeholders: one set per row
            $placeholders[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $values[] = $Datum;
            $values[] = isset($r['BAs']) ? (string)$r['BAs'] : null;
            $values[] = isset($r['usr']) ? (string)$r['usr'] : null;
            $values[] = isset($r['agency']) ? (string)$r['agency'] : null;
            $values[] = $Success;
            $values[] = $priceDeviation;
            $values[] = $startt;
            $values[] = $endd;
            $values[] = 'EWH'; // marke
        }

        $sql = $insertBase . implode(', ', $placeholders);

        try {
            $mysql->beginTransaction();
            $stmtInsert = $mysql->prepare($sql);
            $stmtInsert->execute($values);
            $mysql->commit();

            $insertedCount = count($chunk);
            $totalInserted += $insertedCount;
            logmsg("Batch " . ($chunkIndex + 1) . " inserted {$insertedCount} rows. Total: {$totalInserted}");
        } catch (Exception $eBatch) {
            try { $mysql->rollBack(); } catch (Exception $e) {}
            logmsg("Batch " . ($chunkIndex + 1) . " FAILED: " . $eBatch->getMessage());
            // Abort to avoid partial inconsistent state; change to 'continue;' if you prefer skipping failed batches
            throw $eBatch;
        }
    }

    logmsg("Finished inserting. Total rows inserted: {$totalInserted}");

} catch (Exception $ex) {
    logmsg("ERROR: " . $ex->getMessage() . " in " . $ex->getFile() . ":" . $ex->getLine());
    exit(1);
} finally {
    if (isset($sqlsrv) && is_resource($sqlsrv)) {
        @sqlsrv_close($sqlsrv);
    }
    logmsg("END");
}
