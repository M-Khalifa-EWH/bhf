<?php

class SQLServer2 {
    private static $serverName = '';
    private static $connectionInfo = [
        'Database' => '', 
        'UID' => '',
        'PWD' => '
        //'PWD' => 'B1CueirqruVCPu3Xs2Es!'
    ];

    public static function connect() {
        $conn = sqlsrv_connect(self::$serverName, self::$connectionInfo);

        if (!$conn) {
            die("SQL Server connection failed: " . print_r(sqlsrv_errors(), true));
        }

        return $conn;
    }
    }