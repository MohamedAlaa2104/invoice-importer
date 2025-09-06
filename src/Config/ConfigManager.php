<?php

namespace Mohamedaladdin\InvoiceImporter\Config;

/**
 * Configuration Manager using Singleton pattern
 * Provides centralized access to configuration values
 */
class ConfigManager
{
    private static ?ConfigManager $instance = null;
    private array $config = [];
    private array $loadedFiles = [];

    private function __construct()
    {
        // Private constructor for singleton pattern
    }

    public static function getInstance(): ConfigManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get configuration value using dot notation
     * 
     * @param string $key Configuration key (e.g., 'database.default')
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set configuration value using dot notation
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     */
    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    /**
     * Load configuration from file
     * 
     * @param string $filePath Path to configuration file
     * @param string $prefix Optional prefix for configuration keys
     */
    public function loadFromFile(string $filePath, string $prefix = ''): void
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Configuration file not found: {$filePath}");
        }

        if (in_array($filePath, $this->loadedFiles)) {
            return; // Already loaded
        }

        $config = require $filePath;
        
        if (!is_array($config)) {
            throw new \InvalidArgumentException("Configuration file must return an array: {$filePath}");
        }

        if ($prefix) {
            $this->set($prefix, $config);
        } else {
            $this->config = array_merge($this->config, $config);
        }

        $this->loadedFiles[] = $filePath;
    }

    /**
     * Get all configuration
     * 
     * @return array
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Check if configuration key exists
     * 
     * @param string $key Configuration key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Clear all configuration
     */
    public function clear(): void
    {
        $this->config = [];
        $this->loadedFiles = [];
    }
}

/**
 * Helper function to get environment variable with default value
 * 
 * @param string $key Environment variable name
 * @param mixed $default Default value
 * @return mixed
 */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    
    if ($value === false) {
        return $default;
    }

    // Convert string values to appropriate types
    if (is_string($value)) {
        $lowerValue = strtolower($value);
        if (in_array($lowerValue, ['true', 'false'])) {
            return $lowerValue === 'true';
        }
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }
    }

    return $value;
}
