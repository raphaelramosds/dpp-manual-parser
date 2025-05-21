<?php

class SpreadsheetContext
{
    private $fields;

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
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    private function extractFields($sheet)
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

    public function getFields()
    {
        return $this->fields;
    }
}
