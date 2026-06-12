<?php
// config/SQLServer.php
class SQLServer {
    private static $server;
    private static $connections;
    private static $options;

    public static function init() {
        $config = include __DIR__ . '/db_config.php';
        self::$server = $config['server'];
        self::$connections = $config['connections'];
        self::$options = $config['options'];
    }

    public static function connect($dbKey = 'DB1') {
        if (!isset(self::$server)) {
            self::init();
        }

        if (!isset(self::$connections[$dbKey])) {
            die("Database key '$dbKey' not found in configuration.");
        }

        $connectionInfo = array_merge(self::$connections[$dbKey], self::$options);

        $conn = sqlsrv_connect(self::$server, $connectionInfo);

        if (!$conn) {
            die("❌ Connection to $dbKey failed: " . print_r(sqlsrv_errors(), true));
        }

        return $conn;
    }
}
