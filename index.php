<?php

use raphaelramosds\PdfToTxt\PdfToTxt;

require 'vendor/autoload.php';

include_once ("env.php");

// ---------- PDF to TXT conversion

$pdfh = new PdfToTxt('NPP', './assets/pdf/NPP.pdf', TXT_PATH);
$pdfh->convert();


// ---------- XLSX PARSING

$excel = EXCEL_PATH . '/102-modelo-nd.xlsx';

$sections = [];
try {
    if (!file_exists($excel)) {
        return new InvalidArgumentException("Path {$excel} does not exit");
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

// Scan sheets of this report
$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$reader->setReadDataOnly(true);
$sheet = $reader->load($excel);

$sheets = $sheet->getSheetNames();

// Get fields of each section
foreach ($sheets as $s) {
    $worksheet = $sheet->setActiveSheetIndexByName($s);
    $dataArray = $worksheet->toArray();
    $sections[$s] = $dataArray[0];
}


// ---------- TXT PARSING

$txt = TXT_PATH . '/'  . $pdfh->getFilename() . '.txt';

$sanitizeStr = function ($str) {
    $find = ['.'];
    $replace = array_map(function ($el) {
        return '';
    }, $find);

    $str = str_replace($find, $replace, $str);
    $str = strip_accents($str);

    return $str;
};

try {
    if (!file_exists($txt)) {
        return new InvalidArgumentException("Path {$txt} does not exit");
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

$content = file_get_contents($txt);
$chunks = preg_split('/^Título:\s(.*)/m', $content);

// Ignore introduction
array_shift($chunks);

// Parse chunks and re-index result by the field column
$fields = [];
foreach ($chunks as $c) {
    $str = trim($c);

    if (preg_match('/^Nome\sXML\:(.+)/m', $str, $matches)) {
        $s['field'] = $this->sanitizeStr(trim($matches[1]));
    }

    if (preg_match('/^Natureza\:(.+)/m', $str, $matches)) {
        $s['type'] = trim($matches[1]);
    }

    if (preg_match('/^Tamanho\:(.+)/m', $str, $matches)) {
        $s['length'] = trim($matches[1]);
    }

    if (preg_match('/^Obrigatoriedade\:(.+)/m', $str, $matches)) {
        $s['required'] = trim($matches[1]);
    }

    $fields[] = $s ?? [];
}
$txt_fields = array_combine(array_column($fields, 'field'), $fields);

// ---------- TXT and XLSX mapping

$mapping = [];
foreach ($xls_sections as $key => $fields) {
    array_walk($fields, function ($field) use (&$mapping, $txt_fields) {
        $f = trim(str_replace('_', ' ', $field));
        if (!array_key_exists($f, $txt_fields)) {
            echo "Field $f does not match any fields on TXT" . PHP_EOL;
        } else {
            $mapping[$field] = $txt_fields[$f];
        }
    });
}

// ---------- DATA PARSING

$rules = function ($str) {

    if (preg_match('/\d+/', $str, $matches)) {
        return intval($str);
    }

    if (preg_match('/(Sim)|(Não)/', $str, $matches)) {
        return $matches[1] === 'Sim' ? true : false;
    }

    if (preg_match('/(\d+).+(\d+).+/', $str, $matches)) {
        array_shift($matches);
        if ($matches) {
            $matches = array_map('intval', $matches);
            return [
                'digits' => $matches[0] + $matches[1],
                'dplaces' => $matches[1]
            ];
        }
        return null;
    }

    return $str;
};