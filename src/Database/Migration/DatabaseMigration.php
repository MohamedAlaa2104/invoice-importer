<?php

namespace Mohamedaladdin\InvoiceImporter\Database\Migration;

use Mohamedaladdin\InvoiceImporter\Database\Connection\DatabaseConnectionInterface;
use Mohamedaladdin\InvoiceImporter\Exception\DatabaseException;

/**
 * Database Migration System
 * Creates and manages database schema
 */
class DatabaseMigration
{
    private DatabaseConnectionInterface $connection;

    public function __construct(DatabaseConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Check if database schema exists
     * 
     * @return bool
     */
    public function schemaExists(): bool
    {
        try {
            $driver = $this->connection->getDriver();
            
            switch ($driver) {
                case 'sqlite':
                    return $this->checkSQLiteSchema();
                case 'mysql':
                    return $this->checkMySQLSchema();
                case 'pgsql':
                    return $this->checkPostgreSQLSchema();
                default:
                    throw new DatabaseException("Unsupported database driver: {$driver}");
            }
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to check schema existence: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create database schema
     * 
     * @return bool
     */
    public function createSchema(): bool
    {
        try {
            $driver = $this->connection->getDriver();
            
            switch ($driver) {
                case 'sqlite':
                    return $this->createSQLiteSchema();
                case 'mysql':
                    return $this->createMySQLSchema();
                case 'pgsql':
                    return $this->createPostgreSQLSchema();
                default:
                    throw new DatabaseException("Unsupported database driver: {$driver}");
            }
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to create schema: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Drop database schema
     * 
     * @return bool
     */
    public function dropSchema(): bool
    {
        try {
            $driver = $this->connection->getDriver();
            
            switch ($driver) {
                case 'sqlite':
                    return $this->dropSQLiteSchema();
                case 'mysql':
                    return $this->dropMySQLSchema();
                case 'pgsql':
                    return $this->dropPostgreSQLSchema();
                default:
                    throw new DatabaseException("Unsupported database driver: {$driver}");
            }
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to drop schema: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Check SQLite schema existence
     * 
     * @return bool
     */
    private function checkSQLiteSchema(): bool
    {
        $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name IN ('customers', 'invoices', 'invoice_items')";
        $tables = $this->connection->fetchAll($sql);
        return count($tables) === 3;
    }

    /**
     * Check MySQL schema existence
     * 
     * @return bool
     */
    private function checkMySQLSchema(): bool
    {
        $sql = "SELECT TABLE_NAME FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME IN ('customers', 'invoices', 'invoice_items')";
        $tables = $this->connection->fetchAll($sql);
        return count($tables) === 3;
    }

    /**
     * Check PostgreSQL schema existence
     * 
     * @return bool
     */
    private function checkPostgreSQLSchema(): bool
    {
        $sql = "SELECT table_name FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name IN ('customers', 'invoices', 'invoice_items')";
        $tables = $this->connection->fetchAll($sql);
        return count($tables) === 3;
    }

    /**
     * Create SQLite schema
     * 
     * @return bool
     */
    private function createSQLiteSchema(): bool
    {
        $statements = [
            // Customers table
            "CREATE TABLE IF NOT EXISTS customers (
                customer_id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_name VARCHAR(255) NOT NULL,
                customer_address TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )",

            // Invoices table
            "CREATE TABLE IF NOT EXISTS invoices (
                invoice_id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_number INTEGER NOT NULL UNIQUE,
                invoice_date DATE NOT NULL,
                customer_id INTEGER NOT NULL,
                grand_total DECIMAL(10,2) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
            )",

            // Invoice items table
            "CREATE TABLE IF NOT EXISTS invoice_items (
                item_id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_id INTEGER NOT NULL,
                product_name VARCHAR(255) NOT NULL,
                quantity INTEGER NOT NULL,
                unit_price DECIMAL(10,2) NOT NULL,
                total_price DECIMAL(10,2) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id)
            )",

            // Indexes
            "CREATE INDEX IF NOT EXISTS idx_invoices_customer_id ON invoices(customer_id)",
            "CREATE INDEX IF NOT EXISTS idx_invoices_invoice_number ON invoices(invoice_number)",
            "CREATE INDEX IF NOT EXISTS idx_invoices_date ON invoices(invoice_date)",
            "CREATE INDEX IF NOT EXISTS idx_invoice_items_invoice_id ON invoice_items(invoice_id)"
        ];

        return $this->executeStatements($statements);
    }

    /**
     * Create MySQL schema
     * 
     * @return bool
     */
    private function createMySQLSchema(): bool
    {
        $statements = [
            // Customers table
            "CREATE TABLE IF NOT EXISTS customers (
                customer_id INT AUTO_INCREMENT PRIMARY KEY,
                customer_name VARCHAR(255) NOT NULL,
                customer_address TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Invoices table
            "CREATE TABLE IF NOT EXISTS invoices (
                invoice_id INT AUTO_INCREMENT PRIMARY KEY,
                invoice_number INT NOT NULL UNIQUE,
                invoice_date DATE NOT NULL,
                customer_id INT NOT NULL,
                grand_total DECIMAL(10,2) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Invoice items table
            "CREATE TABLE IF NOT EXISTS invoice_items (
                item_id INT AUTO_INCREMENT PRIMARY KEY,
                invoice_id INT NOT NULL,
                product_name VARCHAR(255) NOT NULL,
                quantity INT NOT NULL,
                unit_price DECIMAL(10,2) NOT NULL,
                total_price DECIMAL(10,2) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Indexes
            "CREATE INDEX IF NOT EXISTS idx_invoices_customer_id ON invoices(customer_id)",
            "CREATE INDEX IF NOT EXISTS idx_invoices_invoice_number ON invoices(invoice_number)",
            "CREATE INDEX IF NOT EXISTS idx_invoices_date ON invoices(invoice_date)",
            "CREATE INDEX IF NOT EXISTS idx_invoice_items_invoice_id ON invoice_items(invoice_id)"
        ];

        return $this->executeStatements($statements);
    }

    /**
     * Create PostgreSQL schema
     * 
     * @return bool
     */
    private function createPostgreSQLSchema(): bool
    {
        $statements = [
            // Customers table
            "CREATE TABLE IF NOT EXISTS customers (
                customer_id SERIAL PRIMARY KEY,
                customer_name VARCHAR(255) NOT NULL,
                customer_address TEXT NOT NULL,
                created_at TIMESTAMP NOT NULL,
                updated_at TIMESTAMP NOT NULL
            )",

            // Invoices table
            "CREATE TABLE IF NOT EXISTS invoices (
                invoice_id SERIAL PRIMARY KEY,
                invoice_number INTEGER NOT NULL UNIQUE,
                invoice_date DATE NOT NULL,
                customer_id INTEGER NOT NULL,
                grand_total DECIMAL(10,2) NOT NULL,
                created_at TIMESTAMP NOT NULL,
                updated_at TIMESTAMP NOT NULL,
                FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE
            )",

            // Invoice items table
            "CREATE TABLE IF NOT EXISTS invoice_items (
                item_id SERIAL PRIMARY KEY,
                invoice_id INTEGER NOT NULL,
                product_name VARCHAR(255) NOT NULL,
                quantity INTEGER NOT NULL,
                unit_price DECIMAL(10,2) NOT NULL,
                total_price DECIMAL(10,2) NOT NULL,
                created_at TIMESTAMP NOT NULL,
                updated_at TIMESTAMP NOT NULL,
                FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id) ON DELETE CASCADE
            )",

            // Indexes
            "CREATE INDEX IF NOT EXISTS idx_invoices_customer_id ON invoices(customer_id)",
            "CREATE INDEX IF NOT EXISTS idx_invoices_invoice_number ON invoices(invoice_number)",
            "CREATE INDEX IF NOT EXISTS idx_invoices_date ON invoices(invoice_date)",
            "CREATE INDEX IF NOT EXISTS idx_invoice_items_invoice_id ON invoice_items(invoice_id)"
        ];

        return $this->executeStatements($statements);
    }

    /**
     * Drop SQLite schema
     * 
     * @return bool
     */
    private function dropSQLiteSchema(): bool
    {
        $statements = [
            "DROP TABLE IF EXISTS invoice_items",
            "DROP TABLE IF EXISTS invoices",
            "DROP TABLE IF EXISTS customers"
        ];

        return $this->executeStatements($statements);
    }

    /**
     * Drop MySQL schema
     * 
     * @return bool
     */
    private function dropMySQLSchema(): bool
    {
        $statements = [
            "DROP TABLE IF EXISTS invoice_items",
            "DROP TABLE IF EXISTS invoices",
            "DROP TABLE IF EXISTS customers"
        ];

        return $this->executeStatements($statements);
    }

    /**
     * Drop PostgreSQL schema
     * 
     * @return bool
     */
    private function dropPostgreSQLSchema(): bool
    {
        $statements = [
            "DROP TABLE IF EXISTS invoice_items CASCADE",
            "DROP TABLE IF EXISTS invoices CASCADE",
            "DROP TABLE IF EXISTS customers CASCADE"
        ];

        return $this->executeStatements($statements);
    }

    /**
     * Execute array of SQL statements
     * 
     * @param array $statements
     * @return bool
     */
    private function executeStatements(array $statements): bool
    {
        try {
            $this->connection->beginTransaction();

            foreach ($statements as $statement) {
                $this->connection->execute($statement);
            }

            $this->connection->commit();
            return true;
        } catch (\Exception $e) {
            $this->connection->rollback();
            throw new DatabaseException("Failed to execute schema statements: " . $e->getMessage(), 0, $e);
        }
    }
}
