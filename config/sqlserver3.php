<?php
class SQLServer3 {
    private static $serverName = '';
    private static $database   = '';
    private static $username   = '';
    private static $password   = '';
    private static $pdo        = null;

    /**
     * Returns a PDO connection to SQL Server
     * Singleton: same instance reused
     */
    public static function connect() {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $dsn = "sqlsrv:Server=" . self::$serverName . ";Database=" . self::$database;

        try {
            self::$pdo = new PDO($dsn, self::$username, self::$password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die("SQL Server PDO connection failed: " . $e->getMessage());
        }

        return self::$pdo;
    }
}
