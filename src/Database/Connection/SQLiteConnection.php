<?php

namespace Mohamedaladdin\InvoiceImporter\Database\Connection;

use Mohamedaladdin\InvoiceImporter\Exception\DatabaseException;

/**
 * SQLite Database Connection Implementation
 */
class SQLiteConnection implements DatabaseConnectionInterface
{
    private ?\SQLite3 $connection = null;
    private string $databasePath;
    private array $options;
    private bool $inTransaction = false;

    public function __construct(string $databasePath, array $options = [])
    {
        $this->databasePath = $databasePath;
        $this->options = array_merge([
            'enable_exceptions' => true,
            'timeout' => 30
        ], $options);
    }

    public function connect(): bool
    {
        try {
            // Ensure directory exists
            $directory = dirname($this->databasePath);
            if (!is_dir($directory)) {
                if (!mkdir($directory, 0755, true)) {
                    throw new DatabaseException("Failed to create database directory: {$directory}");
                }
            }

            $this->connection = new \SQLite3($this->databasePath);
            
            if (!$this->connection) {
                throw new DatabaseException("Failed to connect to SQLite database: {$this->databasePath}");
            }

            // Set options
            if ($this->options['enable_exceptions']) {
                $this->connection->enableExceptions(true);
            }

            // Enable foreign key constraints
            $this->connection->exec('PRAGMA foreign_keys = ON');
            
            return true;
        } catch (\Exception $e) {
            throw new DatabaseException("SQLite connection failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function disconnect(): bool
    {
        if ($this->connection) {
            $this->connection->close();
            $this->connection = null;
            $this->inTransaction = false;
        }
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
            
            if (!$stmt) {
                throw new DatabaseException("Failed to prepare statement: " . $this->connection->lastErrorMsg());
            }

            // Bind parameters
            foreach ($params as $index => $value) {
                $stmt->bindValue($index + 1, $value);
            }

            $result = $stmt->execute();
            
            if (!$result) {
                throw new DatabaseException("Query execution failed: " . $this->connection->lastErrorMsg());
            }

            return true;
        } catch (\Exception $e) {
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
            
            if (!$stmt) {
                throw new DatabaseException("Failed to prepare statement: " . $this->connection->lastErrorMsg());
            }

            // Bind parameters
            foreach ($params as $index => $value) {
                $stmt->bindValue($index + 1, $value);
            }

            $result = $stmt->execute();
            
            if (!$result) {
                throw new DatabaseException("Query execution failed: " . $this->connection->lastErrorMsg());
            }

            $rows = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $rows[] = $row;
            }

            return $rows;
        } catch (\Exception $e) {
            throw new DatabaseException("SQL fetch failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $rows = $this->fetchAll($sql, $params);
        return $rows[0] ?? null;
    }

    public function getLastInsertId(): int|string
    {
        if (!$this->isConnected()) {
            throw new DatabaseException("Database not connected");
        }

        return $this->connection->lastInsertRowID();
    }

    public function beginTransaction(): bool
    {
        if (!$this->isConnected()) {
            throw new DatabaseException("Database not connected");
        }

        if ($this->inTransaction) {
            return true; // Already in transaction
        }

        $result = $this->connection->exec('BEGIN TRANSACTION');
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

        $result = $this->connection->exec('COMMIT');
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

        $result = $this->connection->exec('ROLLBACK');
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
        return 'sqlite';
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
