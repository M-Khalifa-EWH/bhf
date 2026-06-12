<?php

class MySQLDatabase {
    private static $host = '';
    private static $dbName = '';
    private static $username = '';
    private static $password = '';

    public static function connect() {
        try {
            $pdo = new PDO(
                'mysql:host=' . self::$host . ';dbname=' . self::$dbName . ';charset=utf8',
                self::$username,
                self::$password
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'MySQL connection failed',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
}
