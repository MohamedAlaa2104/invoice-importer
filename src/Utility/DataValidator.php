<?php

namespace Mohamedaladdin\InvoiceImporter\Utility;

use Mohamedaladdin\InvoiceImporter\Exception\ImportException;

/**
 * Data Validator Utility
 * Validates data integrity and structure
 */
class DataValidator
{
    /**
     * Validate invoice data structure
     * 
     * @param array $data
     * @return bool
     * @throws ImportException
     */
    public static function validateInvoiceData(array $data): bool
    {
        $requiredFields = ['invoice_number', 'customer_name', 'customer_address', 'invoice_date', 'items'];
        
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                throw new ImportException("Missing required field: {$field}");
            }
        }

        // Validate invoice number
        if (!is_numeric($data['invoice_number']) || $data['invoice_number'] <= 0) {
            throw new ImportException("Invalid invoice number: {$data['invoice_number']}");
        }

        // Validate customer data
        if (empty(trim($data['customer_name']))) {
            throw new ImportException("Customer name cannot be empty");
        }

        if (empty(trim($data['customer_address']))) {
            throw new ImportException("Customer address cannot be empty");
        }

        // Validate invoice date
        if (!self::isValidDate($data['invoice_date'])) {
            throw new ImportException("Invalid invoice date: {$data['invoice_date']}");
        }

        // Validate items
        if (!is_array($data['items']) || empty($data['items'])) {
            throw new ImportException("Invoice must have at least one item");
        }

        foreach ($data['items'] as $index => $item) {
            self::validateInvoiceItem($item, $index);
        }

        return true;
    }

    /**
     * Validate invoice item data
     * 
     * @param array $item
     * @param int $index
     * @return bool
     * @throws ImportException
     */
    public static function validateInvoiceItem(array $item, int $index = 0): bool
    {
        $requiredFields = ['product_name', 'quantity', 'unit_price'];
        
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $item)) {
                throw new ImportException("Missing required field in item {$index}: {$field}");
            }
        }

        // Validate product name
        if (empty(trim($item['product_name']))) {
            throw new ImportException("Product name cannot be empty in item {$index}");
        }

        // Validate quantity
        if (!is_numeric($item['quantity']) || $item['quantity'] <= 0) {
            throw new ImportException("Invalid quantity in item {$index}: {$item['quantity']}");
        }

        // Validate unit price
        if (!is_numeric($item['unit_price']) || $item['unit_price'] < 0) {
            throw new ImportException("Invalid unit price in item {$index}: {$item['unit_price']}");
        }

        return true;
    }

    /**
     * Validate customer data
     * 
     * @param array $data
     * @return bool
     * @throws ImportException
     */
    public static function validateCustomerData(array $data): bool
    {
        $requiredFields = ['customer_name', 'customer_address'];
        
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                throw new ImportException("Missing required field: {$field}");
            }
        }

        if (empty(trim($data['customer_name']))) {
            throw new ImportException("Customer name cannot be empty");
        }

        if (empty(trim($data['customer_address']))) {
            throw new ImportException("Customer address cannot be empty");
        }

        if (strlen($data['customer_name']) > 255) {
            throw new ImportException("Customer name cannot exceed 255 characters");
        }

        return true;
    }

    /**
     * Check if date string is valid
     * 
     * @param mixed $date
     * @return bool
     */
    public static function isValidDate(mixed $date): bool
    {
        if (empty($date)) {
            return false;
        }

        try {
            if ($date instanceof \DateTime) {
                return true;
            }

            if (is_string($date)) {
                $dateTime = new \DateTime($date);
                return $dateTime !== false;
            }

            if (is_numeric($date)) {
                // Excel serial date
                $dateTime = new \DateTime('@' . \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($date));
                return $dateTime !== false;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sanitize string data
     * 
     * @param string $data
     * @return string
     */
    public static function sanitizeString(string $data): string
    {
        return trim($data);
    }

    /**
     * Sanitize numeric data
     * 
     * @param mixed $data
     * @return float
     * @throws ImportException
     */
    public static function sanitizeNumeric(mixed $data): float
    {
        if (is_numeric($data)) {
            return (float) $data;
        }

        // Try to extract numeric value from string
        if (is_string($data)) {
            $cleaned = preg_replace('/[^0-9.-]/', '', $data);
            if (is_numeric($cleaned)) {
                return (float) $cleaned;
            }
        }

        throw new ImportException("Invalid numeric value: {$data}");
    }

    /**
     * Sanitize integer data
     * 
     * @param mixed $data
     * @return int
     * @throws ImportException
     */
    public static function sanitizeInteger(mixed $data): int
    {
        if (is_int($data)) {
            return $data;
        }

        if (is_numeric($data)) {
            return (int) $data;
        }

        // Try to extract integer value from string
        if (is_string($data)) {
            $cleaned = preg_replace('/[^0-9-]/', '', $data);
            if (is_numeric($cleaned)) {
                return (int) $cleaned;
            }
        }

        throw new ImportException("Invalid integer value: {$data}");
    }

    /**
     * Validate email address
     * 
     * @param string $email
     * @return bool
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate phone number
     * 
     * @param string $phone
     * @return bool
     */
    public static function isValidPhone(string $phone): bool
    {
        $cleaned = preg_replace('/[^0-9+()-]/', '', $phone);
        return strlen($cleaned) >= 10;
    }

    /**
     * Check if array has required keys
     * 
     * @param array $data
     * @param array $requiredKeys
     * @return bool
     * @throws ImportException
     */
    public static function hasRequiredKeys(array $data, array $requiredKeys): bool
    {
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new ImportException("Missing required key: {$key}");
            }
        }

        return true;
    }

    /**
     * Validate data types
     * 
     * @param array $data
     * @param array $typeMap
     * @return bool
     * @throws ImportException
     */
    public static function validateDataTypes(array $data, array $typeMap): bool
    {
        foreach ($typeMap as $key => $expectedType) {
            if (!array_key_exists($key, $data)) {
                continue; // Skip if key doesn't exist
            }

            $actualType = gettype($data[$key]);
            if ($actualType !== $expectedType) {
                throw new ImportException("Invalid data type for '{$key}': expected {$expectedType}, got {$actualType}");
            }
        }

        return true;
    }
}
