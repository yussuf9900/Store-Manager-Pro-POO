<?php

namespace App\Core;

use PDO;
use PDOException;
use Exception;

class Database
{
    private static ?Database $instance = null;
    private static ?PDO $connection = null;
    private static string $driver = 'unknown';
    private static ?string $connectionMessage = null;

    private function __construct()
    {
        self::initConnection();
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
        throw new Exception("Impossible de désérialiser une instance Singleton de " . __CLASS__);
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function getPDO(): PDO
    {
        if (self::$connection === null) {
            self::initConnection();
        }

        return self::$connection;
    }

    public static function getConnection(): PDO
    {
        return self::getPDO();
    }

    public static function getDriver(): string
    {
        if (self::$connection === null) {
            self::initConnection();
        }
        return self::$driver;
    }

    public static function isSqlite(): bool
    {
        return self::getDriver() === 'sqlite';
    }

    public static function isPgsql(): bool
    {
        return self::getDriver() === 'pgsql';
    }

    public static function getConnectionMessage(): ?string
    {
        if (self::$connection === null) {
            self::initConnection();
        }
        return self::$connectionMessage;
    }

    private static function initConnection(): void
    {
        if (self::$connection !== null) {
            return;
        }

        $pdoOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pgHost = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '127.0.0.1');
            $pgPort = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '5433');
            $pgDb   = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'storemanager_db');
            $pgUser = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'ichigo');
            $pgPass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? 'password');

            $dsnPgsql = "pgsql:host={$pgHost};port={$pgPort};dbname={$pgDb};";

            self::$connection = new PDO($dsnPgsql, $pgUser, $pgPass, $pdoOptions);
            self::$driver = 'pgsql';
            self::$connectionMessage = "Connexion PostgreSQL établie ({$pgHost}:{$pgPort}/{$pgDb}).";
            return;
        } catch (PDOException $pgException) {
            $pgError = $pgException->getMessage();
        }

        try {
            $baseDir = dirname(__DIR__, 2);
            $sqliteFile = getenv('DB_SQLITE_PATH') ?: ($_ENV['DB_SQLITE_PATH'] ?? $baseDir . '/database/erp.db');

            $sqliteDir = dirname($sqliteFile);
            if (!is_dir($sqliteDir)) {
                mkdir($sqliteDir, 0755, true);
            }

            $isNewDatabase = !file_exists($sqliteFile) || filesize($sqliteFile) === 0;

            $dsnSqlite = "sqlite:" . $sqliteFile;
            self::$connection = new PDO($dsnSqlite, null, null, $pdoOptions);
            self::$driver = 'sqlite';

            self::$connection->exec("PRAGMA foreign_keys = ON;");

            if ($isNewDatabase) {
                $schemaFile = $baseDir . '/database/schema_sqlite.sql';
                if (!file_exists($schemaFile)) {
                    $schemaFile = $baseDir . '/schema_sqlite.sql';
                }

                if (file_exists($schemaFile)) {
                    $schemaSql = file_get_contents($schemaFile);
                    self::$connection->exec($schemaSql);
                }
            }

            self::$connectionMessage = "Bascule sur SQLite réussie ({$sqliteFile}).";
        } catch (PDOException $sqliteException) {
            throw new Exception(
                "Erreur connexion BDD : Échec PostgreSQL ({$pgError}) et échec SQLite ({$sqliteException->getMessage()})"
            );
        }
    }

    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    public static function rollBack(): bool
    {
        return self::getConnection()->rollBack();
    }

    public static function inTransaction(): bool
    {
        return self::getConnection()->inTransaction();
    }

    public static function lastInsertId(?string $name = null): string|false
    {
        return self::getConnection()->lastInsertId($name);
    }
}
