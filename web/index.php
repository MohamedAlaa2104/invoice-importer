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

/**
 * Web Interface for Invoice Importer
 * Provides a simple web interface for viewing and exporting data
 */

class InvoiceImporterWeb
{
    private ConfigManager $configManager;
    private $connection;
    private DataExportService $exportService;
    private InvoiceRepository $invoiceRepository;
    private CustomerRepository $customerRepository;

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
            $this->showError("Database error: " . $e->getMessage());
            exit;
        }
    }

    private function setupServices(): void
    {
        $this->customerRepository = new CustomerRepository($this->connection);
        $this->invoiceRepository = new InvoiceRepository(
            $this->connection,
            $this->customerRepository,
            new InvoiceItemRepository($this->connection)
        );

        $this->exportService = new DataExportService(
            $this->invoiceRepository,
            $this->customerRepository
        );
    }

    public function handleRequest(): void
    {
        $action = $_GET['action'] ?? 'dashboard';
        $format = $_GET['format'] ?? 'json';

        try {
            switch ($action) {
                case 'dashboard':
                    $this->showDashboard();
                    break;
                case 'invoices':
                    $this->showInvoices();
                    break;
                case 'customers':
                    $this->showCustomers();
                    break;
                case 'export':
                    $this->handleExport($format);
                    break;
                default:
                    $this->showDashboard();
            }
        } catch (\Exception $e) {
            $this->showError("Error: " . $e->getMessage());
        }
    }

    private function showDashboard(): void
    {
        $stats = $this->getStatistics();
        $this->renderPage('dashboard', $stats);
    }

    private function showInvoices(): void
    {
        $invoices = $this->invoiceRepository->findAll();
        $this->renderPage('invoices', ['invoices' => $invoices]);
    }

    private function showCustomers(): void
    {
        $customers = $this->customerRepository->findAll();
        $this->renderPage('customers', ['customers' => $customers]);
    }

    private function handleExport(string $format): void
    {
        $type = $_GET['type'] ?? 'invoices';
        $customerId = $_GET['customer_id'] ?? null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        try {
            if ($type === 'invoices') {
                if ($customerId) {
                    $data = $this->exportService->exportInvoicesByCustomer((int) $customerId, $format);
                } elseif ($startDate && $endDate) {
                    $data = $this->exportService->exportInvoicesByDateRange(
                        new \DateTime($startDate),
                        new \DateTime($endDate),
                        $format
                    );
                } else {
                    $data = $this->exportService->exportAllInvoices($format);
                }
            } else {
                $data = $this->exportService->exportCustomers($format);
            }

            $this->sendExportResponse($data, $format, $type);
        } catch (\Exception $e) {
            $this->showError("Export failed: " . $e->getMessage());
        }
    }

    private function sendExportResponse(string $data, string $format, string $type): void
    {
        $filename = "export_{$type}_" . date('Y-m-d_H-i-s') . ".{$format}";
        
        header('Content-Type: ' . ($format === 'json' ? 'application/json' : 'application/xml'));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($data));
        
        echo $data;
        exit;
    }

    private function getStatistics(): array
    {
        return [
            'total_customers' => $this->customerRepository->count(),
            'total_invoices' => $this->invoiceRepository->count(),
            'total_revenue' => $this->invoiceRepository->getTotalRevenue(),
            'recent_invoices' => array_slice($this->invoiceRepository->findAll(), 0, 5)
        ];
    }

    private function renderPage(string $template, array $data = []): void
    {
        $data['title'] = ucfirst($template);
        $data['current_action'] = $_GET['action'] ?? 'dashboard';
        
        // Extract variables for template
        extract($data);
        
        include __DIR__ . "/templates/{$template}.php";
    }

    private function showError(string $message): void
    {
        $this->renderPage('error', ['message' => $message]);
    }
}

// Start the application
$app = new InvoiceImporterWeb();
$app->handleRequest();
