<?php
/**
 * Shared helper functions for data fetching and formatting.
 */

// Fetch results and return as [key => value] pairs
function fetchGrouped(PDO $conn, string $sql, string $key = null, string $value = 'value'): array {
    $result = [];
    foreach ($conn->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $groupKey = $key ?? $row['agency'];
        $result[$groupKey] = $row[$value];
    }
    return $result;
}


// Build SQL query string for grouped fetch
function buildQuery(string $select, string $table, string $where): string {
    return "SELECT agency, $select AS value FROM $table WHERE $where GROUP BY agency";
}

// Fetch ticket statistics (with optional extra WHERE filter)
function getTixStats(PDO $conn, string $where): array {
    $sql = "
        SELECT
            COUNT(*) AS total,
            SUM(sts = 'ORDER_OK') AS ORDER_OK,
            SUM(CASE WHEN sts IN ('CREDITCARD_FAIL', 'BOOKING_FAIL', 'POOR_CREDITWORTHINESS') THEN 1 ELSE 0 END) AS notOk,
            SUM(CASE WHEN sts = 'ORDER_OK' THEN adult + child ELSE 0 END) AS orderTixOk,
            'hlm' AS brand_group
        FROM tix
        WHERE traveltype LIKE 'HLM%' AND $where

        UNION ALL

        SELECT
            COUNT(*) AS total,
            SUM(sts = 'ORDER_OK') AS ORDER_OK,
            SUM(CASE WHEN sts IN ('CREDITCARD_FAIL', 'BOOKING_FAIL', 'POOR_CREDITWORTHINESS') THEN 1 ELSE 0 END) AS notOk,
            SUM(CASE WHEN sts = 'ORDER_OK' THEN adult + child ELSE 0 END) AS orderTixOk,
            'fv' AS brand_group
        FROM tix
        WHERE (traveltype NOT LIKE 'HLM%' OR traveltype IS NULL) AND retailOrg = '2' AND $where

        UNION ALL

        SELECT
            COUNT(*) AS total,
            SUM(sts = 'ORDER_OK') AS ORDER_OK,
            SUM(CASE WHEN sts IN ('CREDITCARD_FAIL', 'BOOKING_FAIL', 'POOR_CREDITWORTHINESS') THEN 1 ELSE 0 END) AS notOk,
            SUM(CASE WHEN sts = 'ORDER_OK' THEN adult + child ELSE 0 END) AS orderTixOk,
            'ev' AS brand_group
        FROM tix
        WHERE (traveltype NOT LIKE 'HLM%' OR traveltype IS NULL) AND retailOrg != '2' AND $where

        UNION ALL

        SELECT
            COUNT(*) AS total,
            SUM(sts = 'ORDER_OK') AS ORDER_OK,
            SUM(CASE WHEN sts IN ('CREDITCARD_FAIL', 'BOOKING_FAIL', 'POOR_CREDITWORTHINESS') THEN 1 ELSE 0 END) AS notOk,
            SUM(CASE WHEN sts = 'ORDER_OK' THEN adult + child ELSE 0 END) AS orderTixOk,
            'ewh' AS brand_group
        FROM tix
        WHERE (traveltype NOT LIKE 'HLM%' OR traveltype IS NULL) AND $where
    ";

    return $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}


// Save structured data to specified cache table
function saveToCache(PDO $conn, string $table, array $data) {
    $stmt = $conn->prepare("INSERT INTO $table (data_json, updated_at) VALUES (?, NOW())");
    $stmt->execute([json_encode($data)]);
    echo "✅ Cache saved to DB table `$table` at " . date('Y-m-d H:i:s') . "\n";
}

// Fetch a single value (scalar)
function fetchValue(PDO $conn, string $sql): float {
    return (float) $conn->query($sql)->fetchColumn();
}

function getTixStatsEWH(PDO $conn, string $whereCondition): array {
    $sql = "
        SELECT 
            COUNT(*) as total,
            SUM(sts = 'ORDER_OK') as ORDER_OK,
            SUM(CASE WHEN sts IN ('CREDITCARD_FAIL', 'BOOKING_FAIL', 'POOR_CREDITWORTHINESS') THEN 1 ELSE 0 END) as notOk,
            SUM(CASE WHEN sts = 'ORDER_OK' THEN adult + child ELSE 0 END) as orderTixOk
        FROM tix
        WHERE 
            $whereCondition
           
    ";
    return [$conn->query($sql)->fetch(PDO::FETCH_ASSOC)];
}
