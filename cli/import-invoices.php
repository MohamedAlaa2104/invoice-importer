<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Mohamedaladdin\InvoiceImporter\Config\ConfigManager;
use Mohamedaladdin\InvoiceImporter\Database\Connection\DatabaseConnectionFactory;
use Mohamedaladdin\InvoiceImporter\Database\Migration\DatabaseMigration;
use Mohamedaladdin\InvoiceImporter\Repository\CustomerRepository;
use Mohamedaladdin\InvoiceImporter\Repository\InvoiceRepository;
use Mohamedaladdin\InvoiceImporter\Repository\InvoiceItemRepository;
use Mohamedaladdin\InvoiceImporter\Service\ExcelImportService;
use Mohamedaladdin\InvoiceImporter\Exception\ImportException;
use Mohamedaladdin\InvoiceImporter\Exception\DatabaseException;

/**
 * CLI Script for Importing Invoices from Excel Files
 * 
 * Usage: php cli/import-invoices.php <excel_file_path>
 * 
 * Example: php cli/import-invoices.php data/invoices.xlsx
 */

class InvoiceImporterCLI
{
    private ConfigManager $configManager;
    private $connection;
    private ExcelImportService $importService;

    public function __construct()
    {
        $this->configManager = ConfigManager::getInstance();
        $this->loadConfiguration();
        $this->setupDatabase();
        $this->setupServices();
    }

    private function loadConfiguration(): void
    {
        // Configuration will be loaded automatically by DatabaseConnectionFactory
        // No need to load it here
    }

    private function setupDatabase(): void
    {
        try {
            $this->connection = DatabaseConnectionFactory::create();
            $this->connection->connect();

            // Run database migration
            $migration = new DatabaseMigration($this->connection);
            if (!$migration->schemaExists()) {
                $this->info("Creating database schema...");
                $migration->createSchema();
                $this->info("Database schema created successfully.");
            } else {
                $this->info("Database schema already exists.");
            }
        } catch (DatabaseException $e) {
            $this->error("Database error: " . $e->getMessage());
            exit(1);
        }
    }

    private function setupServices(): void
    {
        $customerRepository = new CustomerRepository($this->connection);
        $invoiceRepository = new InvoiceRepository($this->connection, $customerRepository, new InvoiceItemRepository($this->connection));
        $itemRepository = new InvoiceItemRepository($this->connection);

        $this->importService = new ExcelImportService(
            $customerRepository,
            $invoiceRepository,
            $itemRepository
        );
    }

    public function import(string $filePath): void
    {
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            exit(1);
        }

        $this->info("Starting import process...");
        $this->info("File: {$filePath}");

        try {
            // Validate file first
            if (!$this->importService->validateFile($filePath)) {
                $this->error("Invalid Excel file or unsupported format.");
                exit(1);
            }

            $this->info("File validation passed. Proceeding with import...");

            // Import data
            $result = $this->importService->importFromFile($filePath);

            // Display results
            $this->displayResults($result);

        } catch (ImportException $e) {
            $this->error("Import failed: " . $e->getMessage());
            if ($e->getFilePath()) {
                $this->error("File: " . $e->getFilePath());
            }
            if ($e->getRowNumber()) {
                $this->error("Row: " . $e->getRowNumber());
            }
            exit(1);
        } catch (\Exception $e) {
            $this->error("Unexpected error: " . $e->getMessage());
            exit(1);
        }
    }

    private function displayResults($result): void
    {
        $this->info("\n" . str_repeat("=", 50));
        $this->info("IMPORT RESULTS");
        $this->info(str_repeat("=", 50));

        $this->info("Total processed: " . $result->getTotalProcessed());
        $this->info("Successful imports: " . $result->getSuccessCount());
        $this->info("Failed imports: " . $result->getErrorCount());

        if ($result->getErrorCount() > 0) {
            $this->warn("\nErrors encountered:");
            foreach ($result->getErrors() as $error) {
                $this->warn("  - " . $error);
            }
        }

        // Display statistics
        $stats = $this->importService->getImportStatistics();
        if (!empty($stats)) {
            $this->info("\nDetailed Statistics:");
            $this->info("  - Total rows processed: " . ($stats['total_rows'] ?? 0));
            $this->info("  - Customers created: " . ($stats['customers_created'] ?? 0));
            $this->info("  - Invoices created: " . ($stats['invoices_created'] ?? 0));
        }

        if ($result->isSuccess()) {
            $this->success("\nImport completed successfully!");
        } else {
            $this->warn("\nImport completed with errors.");
        }
    }

    private function info(string $message): void
    {
        echo "\033[0;36m[INFO]\033[0m {$message}\n";
    }

    private function warn(string $message): void
    {
        echo "\033[0;33m[WARN]\033[0m {$message}\n";
    }

    private function error(string $message): void
    {
        echo "\033[0;31m[ERROR]\033[0m {$message}\n";
    }

    private function success(string $message): void
    {
        echo "\033[0;32m[SUCCESS]\033[0m {$message}\n";
    }

    public function showHelp(): void
    {
        echo "Invoice Importer CLI\n";
        echo "===================\n\n";
        echo "Usage: php cli/import-invoices.php <excel_file_path>\n\n";
        echo "Arguments:\n";
        echo "  excel_file_path    Path to Excel file (.xlsx, .xls, .csv)\n\n";
        echo "Examples:\n";
        echo "  php cli/import-invoices.php data/invoices.xlsx\n";
        echo "  php cli/import-invoices.php /path/to/invoices.xls\n\n";
        echo "Supported formats:\n";
        echo "  - Excel 2007+ (.xlsx)\n";
        echo "  - Excel 97-2003 (.xls)\n";
        echo "  - CSV files (.csv)\n\n";
        echo "Excel file structure expected:\n";
        echo "  Column 1: Invoice Number\n";
        echo "  Column 2: Invoice Date\n";
        echo "  Column 3: Customer Name\n";
        echo "  Column 4: Customer Address\n";
        echo "  Column 5: Product Name\n";
        echo "  Column 6: Quantity\n";
        echo "  Column 7: Unit Price\n";
        echo "  Column 8: Line Total\n";
        echo "  Column 9: Grand Total\n\n";
        echo "Note: Each row represents an invoice item. Multiple rows with the same\n";
        echo "invoice number belong to the same invoice.\n\n";
    }
}

// Main execution
if ($argc < 2) {
    $cli = new InvoiceImporterCLI();
    $cli->showHelp();
    exit(1);
}

$filePath = $argv[1];

// Handle help flag
if (in_array($filePath, ['-h', '--help', 'help'])) {
    $cli = new InvoiceImporterCLI();
    $cli->showHelp();
    exit(0);
}

try {
    $cli = new InvoiceImporterCLI();
    $cli->import($filePath);
} catch (\Exception $e) {
    echo "\033[0;31m[FATAL ERROR]\033[0m " . $e->getMessage() . "\n";
    exit(1);
}
