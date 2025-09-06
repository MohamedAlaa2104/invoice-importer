<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Mohamedaladdin\InvoiceImporter\Config\ConfigManager;
use Mohamedaladdin\InvoiceImporter\Database\Connection\DatabaseConnectionFactory;
use Mohamedaladdin\InvoiceImporter\Database\Migration\DatabaseMigration;
use Mohamedaladdin\InvoiceImporter\Repository\CustomerRepository;
use Mohamedaladdin\InvoiceImporter\Repository\InvoiceRepository;
use Mohamedaladdin\InvoiceImporter\Repository\InvoiceItemRepository;
use Mohamedaladdin\InvoiceImporter\Service\DataExportService;
use Mohamedaladdin\InvoiceImporter\Exception\ExportException;
use Mohamedaladdin\InvoiceImporter\Exception\DatabaseException;

/**
 * CLI Script for Exporting Data
 * 
 * Usage: php cli/export-data.php [options]
 * 
 * Options:
 *   --format=json|xml|excel Export format (default: json)
 *   --output=file         Output file path (default: stdout)
 *   --type=invoices|customers  Data type to export (default: invoices)
 *   --customer-id=ID      Export invoices for specific customer
 *   --start-date=DATE     Start date for date range filter (YYYY-MM-DD)
 *   --end-date=DATE       End date for date range filter (YYYY-MM-DD)
 *   --min-amount=AMOUNT   Minimum invoice amount filter
 *   --max-amount=AMOUNT   Maximum invoice amount filter
 *   --help                Show help message
 */

class DataExporterCLI
{
    private ConfigManager $configManager;
    private $connection;
    private DataExportService $exportService;
    private array $options = [];

    public function __construct()
    {
        $this->configManager = ConfigManager::getInstance();
        $this->loadConfiguration();
        $this->setupDatabase();
        $this->setupServices();
    }

    private function loadConfiguration(): void
    {
        // Load database configuration
        $configPath = __DIR__ . '/../config/database.php';
        if (file_exists($configPath)) {
            $this->configManager->loadFromFile($configPath);
        } else {
            $this->error("Configuration file not found: {$configPath}");
            exit(1);
        }
    }

    private function setupDatabase(): void
    {
        try {
            $this->connection = DatabaseConnectionFactory::create();
            $this->connection->connect();

            // Check if schema exists
            $migration = new DatabaseMigration($this->connection);
            if (!$migration->schemaExists()) {
                $this->error("Database schema not found. Please run import first.");
                exit(1);
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

        $this->exportService = new DataExportService(
            $invoiceRepository,
            $customerRepository
        );
    }

    public function export(array $options): void
    {
        $this->options = $this->parseOptions($options);

        try {
            $data = $this->getExportData();
            $formattedData = $this->formatData($data);

            if (isset($this->options['output'])) {
                $this->writeToFile($formattedData, $this->options['output']);
                $this->success("Data exported to: " . $this->options['output']);
            } else {
                echo $formattedData;
            }

        } catch (ExportException $e) {
            $this->error("Export failed: " . $e->getMessage());
            exit(1);
        } catch (\Exception $e) {
            $this->error("Unexpected error: " . $e->getMessage());
            exit(1);
        }
    }

    private function parseOptions(array $options): array
    {
        $parsed = [
            'format' => 'json',
            'type' => 'invoices',
            'output' => null
        ];

        foreach ($options as $option) {
            if (strpos($option, '--') === 0) {
                $parts = explode('=', $option, 2);
                $key = substr($parts[0], 2);
                $value = $parts[1] ?? true;

                switch ($key) {
                    case 'format':
                        if (!in_array($value, ['json', 'xml', 'excel'])) {
                            $this->error("Invalid format. Supported formats: json, xml, excel");
                            exit(1);
                        }
                        $parsed['format'] = $value;
                        break;

                    case 'output':
                        $parsed['output'] = $value;
                        break;

                    case 'type':
                        if (!in_array($value, ['invoices', 'customers'])) {
                            $this->error("Invalid type. Supported types: invoices, customers");
                            exit(1);
                        }
                        $parsed['type'] = $value;
                        break;

                    case 'customer-id':
                        $parsed['customer_id'] = (int) $value;
                        break;

                    case 'start-date':
                        $parsed['start_date'] = $value;
                        break;

                    case 'end-date':
                        $parsed['end_date'] = $value;
                        break;

                    case 'min-amount':
                        $parsed['min_amount'] = (float) $value;
                        break;

                    case 'max-amount':
                        $parsed['max_amount'] = (float) $value;
                        break;

                    case 'help':
                        $this->showHelp();
                        exit(0);
                        break;
                }
            }
        }

        return $parsed;
    }

    private function getExportData(): string
    {
        $filters = $this->buildFilters();

        switch ($this->options['type']) {
            case 'invoices':
                if (isset($this->options['customer_id'])) {
                    return $this->exportService->exportInvoicesByCustomer(
                        $this->options['customer_id'],
                        $this->options['format']
                    );
                } elseif (isset($this->options['start_date']) && isset($this->options['end_date'])) {
                    $startDate = new \DateTime($this->options['start_date']);
                    $endDate = new \DateTime($this->options['end_date']);
                    return $this->exportService->exportInvoicesByDateRange(
                        $startDate,
                        $endDate,
                        $this->options['format']
                    );
                } else {
                    return $this->exportService->exportAllInvoices(
                        $this->options['format'],
                        $filters
                    );
                }

            case 'customers':
                return $this->exportService->exportCustomers($this->options['format']);

            default:
                throw new ExportException("Invalid export type: " . $this->options['type']);
        }
    }

    private function buildFilters(): array
    {
        $filters = [];

        if (isset($this->options['customer_id'])) {
            $filters['customer_id'] = $this->options['customer_id'];
        }

        if (isset($this->options['start_date'])) {
            $filters['start_date'] = $this->options['start_date'];
        }

        if (isset($this->options['end_date'])) {
            $filters['end_date'] = $this->options['end_date'];
        }

        if (isset($this->options['min_amount'])) {
            $filters['min_amount'] = $this->options['min_amount'];
        }

        if (isset($this->options['max_amount'])) {
            $filters['max_amount'] = $this->options['max_amount'];
        }

        return $filters;
    }

    private function formatData(string $data): string
    {
        if ($this->options['format'] === 'json') {
            // Pretty print JSON if not writing to file
            if (!isset($this->options['output'])) {
                $decoded = json_decode($data, true);
                if ($decoded) {
                    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }
            }
        }

        return $data;
    }

    private function writeToFile(string $data, string $filePath): void
    {
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new ExportException("Failed to create directory: {$directory}");
            }
        }

        if (file_put_contents($filePath, $data) === false) {
            throw new ExportException("Failed to write to file: {$filePath}");
        }
    }

    public function showHelp(): void
    {
        echo "Data Exporter CLI\n";
        echo "================\n\n";
        echo "Usage: php cli/export-data.php [options]\n\n";
        echo "Options:\n";
        echo "  --format=json|xml|excel  Export format (default: json)\n";
        echo "  --output=file            Output file path (default: stdout)\n";
        echo "  --type=invoices|customers Data type to export (default: invoices)\n";
        echo "  --customer-id=ID         Export invoices for specific customer\n";
        echo "  --start-date=DATE        Start date for date range filter (YYYY-MM-DD)\n";
        echo "  --end-date=DATE          End date for date range filter (YYYY-MM-DD)\n";
        echo "  --min-amount=AMOUNT      Minimum invoice amount filter\n";
        echo "  --max-amount=AMOUNT      Maximum invoice amount filter\n";
        echo "  --help                   Show this help message\n\n";
        echo "Examples:\n";
        echo "  php cli/export-data.php --format=json --type=invoices\n";
        echo "  php cli/export-data.php --format=xml --output=export.xml\n";
        echo "  php cli/export-data.php --format=excel --output=export.xlsx\n";
        echo "  php cli/export-data.php --type=invoices --customer-id=1\n";
        echo "  php cli/export-data.php --start-date=2023-01-01 --end-date=2023-12-31\n";
        echo "  php cli/export-data.php --min-amount=100 --max-amount=1000\n\n";
    }

    private function info(string $message): void
    {
        echo "\033[0;36m[INFO]\033[0m {$message}\n";
    }

    private function error(string $message): void
    {
        echo "\033[0;31m[ERROR]\033[0m {$message}\n";
    }

    private function success(string $message): void
    {
        echo "\033[0;32m[SUCCESS]\033[0m {$message}\n";
    }
}

// Main execution
$options = array_slice($argv, 1);

// Handle help flag
if (empty($options) || in_array('--help', $options) || in_array('-h', $options)) {
    $cli = new DataExporterCLI();
    $cli->showHelp();
    exit(0);
}

try {
    $cli = new DataExporterCLI();
    $cli->export($options);
} catch (\Exception $e) {
    echo "\033[0;31m[FATAL ERROR]\033[0m " . $e->getMessage() . "\n";
    exit(1);
}
