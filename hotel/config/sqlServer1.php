<?php
//config/sqlServer1.php
class SQLServer1 {
    private static $serverName = ';
    private static $connectionInfo = [
        'Database' => '',
        'UID' => '',
        'PWD' => '',
        "CharacterSet" => "UTF-8",
        "Encrypt" => 1,
        "TrustServerCertificate" => 1 
    ];

    public static function connect() {
        $conn = sqlsrv_connect(self::$serverName, self::$connectionInfo);

        if (!$conn) {
            die("SQL Server connection failed: " . print_r(sqlsrv_errors(), true));
        }

        return $conn;
    }
}
