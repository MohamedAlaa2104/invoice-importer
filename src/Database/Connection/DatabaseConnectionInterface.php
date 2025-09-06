<?php

namespace Mohamedaladdin\InvoiceImporter\Database\Connection;

/**
 * Database Connection Interface
 * Defines contract for all database connections
 */
interface DatabaseConnectionInterface
{
    /**
     * Establish database connection
     * 
     * @return bool True if connection successful
     * @throws \Exception If connection fails
     */
    public function connect(): bool;

    /**
     * Close database connection
     * 
     * @return bool True if disconnection successful
     */
    public function disconnect(): bool;

    /**
     * Check if connection is active
     * 
     * @return bool True if connected
     */
    public function isConnected(): bool;

    /**
     * Execute SQL query
     * 
     * @param string $sql SQL query to execute
     * @param array $params Query parameters
     * @return bool True if query executed successfully
     * @throws \Exception If query execution fails
     */
    public function execute(string $sql, array $params = []): bool;

    /**
     * Fetch all rows from query result
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array Array of rows
     * @throws \Exception If query execution fails
     */
    public function fetchAll(string $sql, array $params = []): array;

    /**
     * Fetch single row from query result
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array|null Single row or null if no results
     * @throws \Exception If query execution fails
     */
    public function fetchOne(string $sql, array $params = []): ?array;

    /**
     * Get last inserted ID
     * 
     * @return int|string Last inserted ID
     */
    public function getLastInsertId(): int|string;

    /**
     * Begin database transaction
     * 
     * @return bool True if transaction started successfully
     */
    public function beginTransaction(): bool;

    /**
     * Commit database transaction
     * 
     * @return bool True if transaction committed successfully
     */
    public function commit(): bool;

    /**
     * Rollback database transaction
     * 
     * @return bool True if transaction rolled back successfully
     */
    public function rollback(): bool;

    /**
     * Check if currently in transaction
     * 
     * @return bool True if in transaction
     */
    public function inTransaction(): bool;

    /**
     * Get database driver name
     * 
     * @return string Driver name
     */
    public function getDriver(): string;
}
