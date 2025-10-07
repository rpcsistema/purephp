<?php

class Database
{
    public static function pdo(): \PDO
    {
        static $pdo = null;
        if ($pdo instanceof \PDO) {
            return $pdo;
        }

        $cfg = require __DIR__ . '/../config/config.php';
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $cfg['db']['host'], $cfg['db']['port'], $cfg['db']['name'], $cfg['db']['charset']);
        $pdo = new \PDO($dsn, $cfg['db']['user'], $cfg['db']['pass'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    }
}