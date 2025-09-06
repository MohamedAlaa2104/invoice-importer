<?php

namespace Mohamedaladdin\InvoiceImporter\Utility;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Mohamedaladdin\InvoiceImporter\Exception\ImportException;

/**
 * Excel Reader Utility
 * Handles reading Excel files using PhpSpreadsheet
 */
class ExcelReader
{
    private Spreadsheet $spreadsheet;
    private Worksheet $worksheet;

    public function __construct(string $filePath)
    {
        $this->loadFile($filePath);
    }

    /**
     * Load Excel file
     * 
     * @param string $filePath
     * @throws ImportException
     */
    private function loadFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw ImportException::withFile("Excel file not found: {$filePath}", $filePath);
        }

        try {
            $this->spreadsheet = IOFactory::load($filePath);
            $this->worksheet = $this->spreadsheet->getActiveSheet();
        } catch (\Exception $e) {
            throw ImportException::withFile(
                "Failed to load Excel file: " . $e->getMessage(),
                $filePath,
                $e
            );
        }
    }

    /**
     * Get all data from the worksheet
     * 
     * @return array
     */
    public function getAllData(): array
    {
        return $this->worksheet->toArray();
    }

    /**
     * Get data from specific range
     * 
     * @param string $range
     * @return array
     */
    public function getDataFromRange(string $range): array
    {
        return $this->worksheet->rangeToArray($range);
    }

    /**
     * Get data row by row
     * 
     * @param int $startRow
     * @param int $endRow
     * @return \Generator
     */
    public function getRows(int $startRow = 1, int $endRow = null): \Generator
    {
        $endRow = $endRow ?? $this->worksheet->getHighestRow();
        
        for ($row = $startRow; $row <= $endRow; $row++) {
            yield $this->worksheet->rangeToArray("A{$row}:Z{$row}")[0];
        }
    }

    /**
     * Get cell value
     * 
     * @param string $cell
     * @return mixed
     */
    public function getCellValue(string $cell): mixed
    {
        return $this->worksheet->getCell($cell)->getValue();
    }

    /**
     * Get highest row number
     * 
     * @return int
     */
    public function getHighestRow(): int
    {
        return $this->worksheet->getHighestRow();
    }

    /**
     * Get highest column letter
     * 
     * @return string
     */
    public function getHighestColumn(): string
    {
        return $this->worksheet->getHighestColumn();
    }

    /**
     * Convert Excel date to PHP DateTime
     * 
     * @param mixed $excelDate
     * @return \DateTime|null
     */
    public function convertExcelDate(mixed $excelDate): ?\DateTime
    {
        if (empty($excelDate)) {
            return null;
        }

        try {
            // If it's already a DateTime object
            if ($excelDate instanceof \DateTime) {
                return $excelDate;
            }

            // If it's a numeric value (Excel serial date)
            if (is_numeric($excelDate)) {
                $unixTimestamp = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($excelDate);
                return new \DateTime('@' . $unixTimestamp);
            }

            // If it's a string, try to parse it
            if (is_string($excelDate)) {
                return new \DateTime($excelDate);
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get worksheet name
     * 
     * @return string
     */
    public function getWorksheetName(): string
    {
        return $this->worksheet->getTitle();
    }

    /**
     * Get all worksheet names
     * 
     * @return array
     */
    public function getWorksheetNames(): array
    {
        return $this->spreadsheet->getSheetNames();
    }

    /**
     * Switch to a different worksheet
     * 
     * @param string $worksheetName
     * @throws ImportException
     */
    public function setActiveWorksheet(string $worksheetName): void
    {
        try {
            $this->worksheet = $this->spreadsheet->getSheetByName($worksheetName);
            if (!$this->worksheet) {
                throw new ImportException("Worksheet '{$worksheetName}' not found");
            }
        } catch (\Exception $e) {
            throw new ImportException("Failed to set active worksheet: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Check if file is a valid Excel file
     * 
     * @param string $filePath
     * @return bool
     */
    public static function isValidExcelFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $validExtensions = ['xlsx', 'xls', 'csv'];

        return in_array($extension, $validExtensions);
    }

    /**
     * Get file information
     * 
     * @return array
     */
    public function getFileInfo(): array
    {
        return [
            'worksheet_name' => $this->getWorksheetName(),
            'worksheet_names' => $this->getWorksheetNames(),
            'highest_row' => $this->getHighestRow(),
            'highest_column' => $this->getHighestColumn(),
            'total_cells' => $this->getHighestRow() * $this->getHighestColumn()
        ];
    }
}
