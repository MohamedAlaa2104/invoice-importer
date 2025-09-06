<?php

namespace Mohamedaladdin\InvoiceImporter\Database\Connection;

use Mohamedaladdin\InvoiceImporter\Exception\DatabaseException;
use PDO;

/**
 * PDO Database Connection Implementation
 * Supports MySQL, PostgreSQL, and other PDO-compatible databases
 */
class PDOConnection implements DatabaseConnectionInterface
{
    private ?PDO $connection = null;
    private string $driver;
    private array $config;
    private bool $inTransaction = false;

    public function __construct(string $driver, array $config)
    {
        $this->driver = $driver;
        $this->config = $config;
    }

    public function connect(): bool
    {
        try {
            $dsn = $this->buildDsn();
            $username = $this->config['username'] ?? null;
            $password = $this->config['password'] ?? null;
            $options = $this->config['options'] ?? [];

            $this->connection = new PDO($dsn, $username, $password, $options);
            
            return true;
        } catch (\PDOException $e) {
            throw new DatabaseException("PDO connection failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function disconnect(): bool
    {
        $this->connection = null;
        $this->inTransaction = false;
        return true;
    }

    public function isConnected(): bool
    {
        return $this->connection !== null;
    }

    public function execute(string $sql, array $params = []): bool
    {
        if (!$this->isConnected()) {
            throw new DatabaseException("Database not connected");
        }

        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            throw new DatabaseException("SQL execution failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        if (!$this->isConnected()) {
            throw new DatabaseException("Database not connected");
        }

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException("SQL fetch failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        if (!$this->isConnected()) {
            throw new DatabaseException("Database not connected");
        }

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            throw new DatabaseException("SQL fetch failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function getLastInsertId(): int|string
    {
        if (!$this->isConnected()) {
            throw new DatabaseException("Database not connected");
        }

        return $this->connection->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        if (!$this->isConnected()) {
            throw new DatabaseException("Database not connected");
        }

        if ($this->inTransaction) {
            return true; // Already in transaction
        }

        $result = $this->connection->beginTransaction();
        if ($result) {
            $this->inTransaction = true;
        }
        return $result;
    }

    public function commit(): bool
    {
        if (!$this->isConnected()) {
            throw new DatabaseException("Database not connected");
        }

        if (!$this->inTransaction) {
            return true; // Not in transaction
        }

        $result = $this->connection->commit();
        if ($result) {
            $this->inTransaction = false;
        }
        return $result;
    }

    public function rollback(): bool
    {
        if (!$this->isConnected()) {
            throw new DatabaseException("Database not connected");
        }

        if (!$this->inTransaction) {
            return true; // Not in transaction
        }

        $result = $this->connection->rollBack();
        if ($result) {
            $this->inTransaction = false;
        }
        return $result;
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Build DSN string based on driver and configuration
     * 
     * @return string DSN string
     */
    private function buildDsn(): string
    {
        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? null;
        $database = $this->config['database'] ?? '';

        switch ($this->driver) {
            case 'mysql':
                $dsn = "mysql:host={$host}";
                if ($port) {
                    $dsn .= ";port={$port}";
                }
                $dsn .= ";dbname={$database}";
                $dsn .= ";charset=utf8mb4";
                break;

            case 'pgsql':
                $dsn = "pgsql:host={$host}";
                if ($port) {
                    $dsn .= ";port={$port}";
                }
                $dsn .= ";dbname={$database}";
                break;

            default:
                throw new DatabaseException("Unsupported database driver: {$this->driver}");
        }

        return $dsn;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
