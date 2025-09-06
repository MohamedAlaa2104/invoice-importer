<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Mohamedaladdin\InvoiceImporter\Config\ConfigManager;
use Mohamedaladdin\InvoiceImporter\Database\Connection\DatabaseConnectionFactory;
use Mohamedaladdin\InvoiceImporter\Database\Migration\DatabaseMigration;
use Mohamedaladdin\InvoiceImporter\Repository\CustomerRepository;
use Mohamedaladdin\InvoiceImporter\Repository\InvoiceRepository;
use Mohamedaladdin\InvoiceImporter\Repository\InvoiceItemRepository;
use Mohamedaladdin\InvoiceImporter\Service\DataExportService;
use Mohamedaladdin\InvoiceImporter\Exception\DatabaseException;
use Mohamedaladdin\InvoiceImporter\Exception\ExportException;

/**
 * RESTful API Endpoint for Data Export
 * 
 * GET /export-json.php?format=json&type=invoices&customer_id=1
 * GET /export-json.php?format=xml&type=customers
 * GET /export-json.php?format=json&type=invoices&start_date=2023-01-01&end_date=2023-12-31
 */

class ExportAPI
{
    private ConfigManager $configManager;
    private $connection;
    private DataExportService $exportService;

    public function __construct()
    {
        $this->configManager = ConfigManager::getInstance();
        $this->loadConfiguration();
        $this->setupDatabase();
        $this->setupServices();
    }

    private function loadConfiguration(): void
    {
        $configPath = __DIR__ . '/../config/database.php';
        if (file_exists($configPath)) {
            $this->configManager->loadFromFile($configPath);
        }
    }

    private function setupDatabase(): void
    {
        try {
            $this->connection = DatabaseConnectionFactory::create();
            $this->connection->connect();

            $migration = new DatabaseMigration($this->connection);
            if (!$migration->schemaExists()) {
                $migration->createSchema();
            }
        } catch (DatabaseException $e) {
            $this->sendErrorResponse("Database error: " . $e->getMessage(), 500);
            exit;
        }
    }

    private function setupServices(): void
    {
        $customerRepository = new CustomerRepository($this->connection);
        $invoiceRepository = new InvoiceRepository(
            $this->connection,
            $customerRepository,
            new InvoiceItemRepository($this->connection)
        );

        $this->exportService = new DataExportService(
            $invoiceRepository,
            $customerRepository
        );
    }

    public function handleRequest(): void
    {
        // Only allow GET requests
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->sendErrorResponse('Method not allowed', 405);
            return;
        }

        try {
            $format = $this->getParameter('format', 'json');
            $type = $this->getParameter('type', 'invoices');
            $customerId = $this->getParameter('customer_id');
            $startDate = $this->getParameter('start_date');
            $endDate = $this->getParameter('end_date');
            $invoiceId = $this->getParameter('invoice_id');

            // Validate format
            if (!$this->exportService->isValidFormat($format)) {
                $this->sendErrorResponse("Invalid format. Supported formats: " . implode(', ', $this->exportService->getSupportedFormats()), 400);
                return;
            }

            // Validate type
            if (!in_array($type, ['invoices', 'customers'])) {
                $this->sendErrorResponse("Invalid type. Supported types: invoices, customers", 400);
                return;
            }

            $data = $this->getExportData($type, $format, $customerId, $startDate, $endDate, $invoiceId);
            $this->sendSuccessResponse($data, $format, $type);

        } catch (ExportException $e) {
            $this->sendErrorResponse("Export failed: " . $e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->sendErrorResponse("Internal server error: " . $e->getMessage(), 500);
        }
    }

    private function getExportData(string $type, string $format, ?string $customerId, ?string $startDate, ?string $endDate, ?string $invoiceId): string
    {
        switch ($type) {
            case 'invoices':
                if ($invoiceId) {
                    return $this->exportService->exportInvoice((int) $invoiceId, $format);
                } elseif ($customerId) {
                    return $this->exportService->exportInvoicesByCustomer((int) $customerId, $format);
                } elseif ($startDate && $endDate) {
                    return $this->exportService->exportInvoicesByDateRange(
                        new \DateTime($startDate),
                        new \DateTime($endDate),
                        $format
                    );
                } else {
                    return $this->exportService->exportAllInvoices($format);
                }

            case 'customers':
                return $this->exportService->exportCustomers($format);

            default:
                throw new ExportException("Invalid export type: {$type}");
        }
    }

    private function sendSuccessResponse(string $data, string $format, string $type): void
    {
        $contentType = $format === 'json' ? 'application/json' : 'application/xml';
        $filename = "export_{$type}_" . date('Y-m-d_H-i-s') . ".{$format}";

        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($data));
        header('Cache-Control: no-cache, must-revalidate');

        echo $data;
    }

    private function sendErrorResponse(string $message, int $statusCode): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'error' => true,
            'message' => $message,
            'status_code' => $statusCode,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    private function getParameter(string $name, ?string $default = null): ?string
    {
        return $_GET[$name] ?? $default;
    }
}

// Handle CORS if needed
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    exit(0);
}

// Start the API
$api = new ExportAPI();
$api->handleRequest();
