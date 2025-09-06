<?php

namespace Mohamedaladdin\InvoiceImporter\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Mohamedaladdin\InvoiceImporter\Database\Connection\DatabaseConnectionInterface;
use Mohamedaladdin\InvoiceImporter\Database\Connection\SQLiteConnection;
use Mohamedaladdin\InvoiceImporter\Database\Migration\DatabaseMigration;

/**
 * Base Test Case
 * Provides common setup for all tests
 */
abstract class TestCase extends BaseTestCase
{
    protected DatabaseConnectionInterface $connection;
    protected string $testDatabasePath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create in-memory SQLite database for testing
        $this->connection = new SQLiteConnection(':memory:');
        $this->connection->connect();
        
        // Create schema
        $migration = new DatabaseMigration($this->connection);
        $migration->createSchema();
    }

    protected function tearDown(): void
    {
        if ($this->connection) {
            $this->connection->disconnect();
        }
        
        parent::tearDown();
    }

    /**
     * Get test data for invoices in Excel row format
     * 
     * @return array
     */
    protected function getTestInvoiceData(): array
    {
        return [
            // Header row
            ['invoice', 'Invoice Date', 'Customer Name', 'Customer Address', 'Product Name', 'Quantity', 'Price', 'Total', 'Grand Total'],
            // Invoice 1 - Item 1
            [1, '2023年1月15日', 'John Doe', '123 Main St, City, State', 'Product A', 2, 25.50, 51.00, 66.00],
            // Invoice 1 - Item 2
            [1, '2023年1月15日', 'John Doe', '123 Main St, City, State', 'Product B', 1, 15.00, 15.00, 66.00],
            // Invoice 2 - Item 1
            [2, '2023年1月16日', 'Jane Smith', '456 Oak Ave, City, State', 'Product C', 3, 10.00, 30.00, 30.00]
        ];
    }

    /**
     * Get test customer data
     * 
     * @return array
     */
    protected function getTestCustomerData(): array
    {
        return [
            'customer_name' => 'Test Customer',
            'customer_address' => 'Test Address, Test City, Test State'
        ];
    }
}
