<?php

namespace Mohamedaladdin\InvoiceImporter\Database\Connection;

use Mohamedaladdin\InvoiceImporter\Config\ConfigManager;
use Mohamedaladdin\InvoiceImporter\Exception\DatabaseException;

/**
 * Database Connection Factory
 * Creates database connections based on configuration
 */
class DatabaseConnectionFactory
{
    private static ConfigManager $configManager;

    /**
     * Create database connection using default configuration
     * 
     * @return DatabaseConnectionInterface
     * @throws DatabaseException
     */
    public static function create(): DatabaseConnectionInterface
    {
        $configManager = self::getConfigManager();
        $defaultConnection = $configManager->get('default', 'sqlite');
        return self::createConnection($defaultConnection);
    }

    /**
     * Create database connection by name
     * 
     * @param string $connectionName Connection name from config
     * @return DatabaseConnectionInterface
     * @throws DatabaseException
     */
    public static function createConnection(string $connectionName): DatabaseConnectionInterface
    {
        $configManager = self::getConfigManager();
        $config = $configManager->get("connections.{$connectionName}");

        if (!$config) {
            throw new DatabaseException("Database connection '{$connectionName}' not found in configuration");
        }

        $driver = $config['driver'] ?? null;
        if (!$driver) {
            throw new DatabaseException("Database driver not specified for connection '{$connectionName}'");
        }

        switch ($driver) {
            case 'sqlite':
                return new SQLiteConnection(
                    $config['database'],
                    $config['options'] ?? []
                );

            case 'mysql':
            case 'pgsql':
                return new PDOConnection($driver, $config);

            default:
                throw new DatabaseException("Unsupported database driver: {$driver}");
        }
    }

    /**
     * Get configuration manager instance
     * 
     * @return ConfigManager
     */
    private static function getConfigManager(): ConfigManager
    {
        if (!isset(self::$configManager)) {
            self::$configManager = ConfigManager::getInstance();
            
            // Load database configuration if not already loaded
            $configPath = __DIR__ . '/../../../config/database.php';
            if (file_exists($configPath)) {
                self::$configManager->loadFromFile($configPath);
            }
        }

        return self::$configManager;
    }
}
