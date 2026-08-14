<?php

namespace App\Core;

use PDO;
use PDOException;
use Exception;

class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private string $driver = 'unknown';
    private ?string $connectionMessage = null;

    private function __construct()
    {
        $this->initConnection();
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
        return self::getInstance()->getConnection();
    }

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->initConnection();
        }

        return $this->connection;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function isSqlite(): bool
    {
        return $this->driver === 'sqlite';
    }

    public function isPgsql(): bool
    {
        return $this->driver === 'pgsql';
    }

    public function getConnectionMessage(): ?string
    {
        return $this->connectionMessage;
    }

    private function initConnection(): void
    {
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

            $this->connection = new PDO($dsnPgsql, $pgUser, $pgPass, $pdoOptions);
            $this->driver = 'pgsql';
            $this->connectionMessage = "Connexion PostgreSQL établie ({$pgHost}:{$pgPort}/{$pgDb}).";
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
            $this->connection = new PDO($dsnSqlite, null, null, $pdoOptions);
            $this->driver = 'sqlite';

            $this->connection->exec("PRAGMA foreign_keys = ON;");

            if ($isNewDatabase) {
                $schemaFile = $baseDir . '/database/schema_sqlite.sql';
                if (!file_exists($schemaFile)) {
                    $schemaFile = $baseDir . '/schema_sqlite.sql';
                }

                if (file_exists($schemaFile)) {
                    $schemaSql = file_get_contents($schemaFile);
                    $this->connection->exec($schemaSql);
                }
            }

            $this->connectionMessage = "Bascule sur SQLite réussie ({$sqliteFile}).";
        } catch (PDOException $sqliteException) {
            throw new Exception(
                "Erreur connexion BDD : Échec PostgreSQL ({$pgError}) et échec SQLite ({$sqliteException->getMessage()})"
            );
        }
    }

    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }

    public function rollBack(): bool
    {
        return $this->getConnection()->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->getConnection()->inTransaction();
    }

    public function lastInsertId(?string $name = null): string|false
    {
        return $this->getConnection()->lastInsertId($name);
    }
}
