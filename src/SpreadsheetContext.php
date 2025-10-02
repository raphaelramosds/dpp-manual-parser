<?php

namespace DppManualParser;

use Exception;
use InvalidArgumentException;

class SpreadsheetContext
{
    private $fields;
    private $dimensions;
    private $labels;

    public function __construct(string $file)
    {
        try {
            if (!file_exists($file)) {
                throw new InvalidArgumentException("File not found: $file");
            }

            $parts = pathinfo($file);

            switch ($parts['extension']) {
                case 'xls':
                    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
                    break;
                case 'xlsx':
                    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                    break;
                default:
                    throw new InvalidArgumentException('Invalid extension ' . $parts['extension']);
            }

            $reader->setReadDataOnly(true);
            $sheet = $reader->load($file);

            $this->fields = $this->extractFields($sheet);
            $this->dimensions = $this->extractDimensions($sheet);
            $this->labels = $this->extractLabels($sheet);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    private function extractFields(\PhpOffice\PhpSpreadsheet\Spreadsheet $sheet)
    {
        $sections = [];
        $sheetNames = $sheet->getSheetNames();

        // Get fields of each section
        foreach ($sheetNames as $sn) {
            $worksheet = $sheet->setActiveSheetIndexByName($sn);
            $dataArray = $worksheet->toArray();
            $sections[$sn] = $dataArray[0];
        }

        return $sections;
    }


    private function extractDimensions (\PhpOffice\PhpSpreadsheet\Spreadsheet $sheet)
    {
        $field_dimensions = [];
        $sheetNames = $sheet->getSheetNames();
        foreach ($sheetNames as $sn) {
            $worksheet = $sheet->setActiveSheetIndexByName($sn);
            $field_dimensions[$sn] = $worksheet->getColumnDimensions();
        }
        return $field_dimensions;
    }

    private function extractLabels(\PhpOffice\PhpSpreadsheet\Spreadsheet $sheet)
    {   
        $field_label = [];
        $sheetNames = $sheet->getSheetNames();
        foreach ($sheetNames as $sn) {
            $worksheet = $sheet->setActiveSheetIndexByName($sn);
            $dataArray = $worksheet->toArray(returnCellRef:true);
            $fields = array_filter($dataArray['1']);

            foreach ($fields as $col => $name) {
                // With a greedy strategy, find index of header's last row and extract the field translated name
                $guess = 5; // Guessing of the last row index
                while (true) {
                    if ($value = $worksheet->getCell($col . $guess)->getValue()) {
                        $field_label[$name] = $value;
                        break;
                    }
                    $guess--;
                }
            }
        }
        return $field_label;
    }

    public function getFields ()
    {
        return $this->fields;
    }

    public function getDimensions ()
    {
        return $this->dimensions;
    }

    public function getLabels() {
        return $this->labels;
    }
}
