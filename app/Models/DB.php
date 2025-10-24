<?php
declare(strict_types=1);

final class DB {
    private static ?PDO $pdo = null;

    public static function conn(): PDO {
        if (self::$pdo) return self::$pdo;

        $config = require __DIR__ . '/../../config/config.php';
        $dbFile = $config['db_path'];

        $pdo = new PDO('sqlite:' . $dbFile, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        return self::$pdo = $pdo;
    }
}
