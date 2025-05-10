<?php

use raphaelramosds\PdfToTxt\PdfToTxt;

require 'vendor/autoload.php';

require_once 'functions.php';

include_once ("env.php");

// ---------- INPUTS

$excel = './assets/excel/081_NOTIFICACAO_PERFURACAO_POCO.xlsx';

$pdf = './assets/pdf/NPP.pdf';
$txt_output_filename = preg_replace('/[\.\/\w\-]+\/(.+)\.(.+)/', '\1', $pdf);
$txt_output = TXT_OUTPUT_DIR . "/$txt_output_filename.txt";

// ---------- PDF to TXT conversion

$pdfh = new PdfToTxt($txt_output_filename, $pdf, TXT_OUTPUT_DIR);
$pdfh->setReloadPdf(false);
$pdfh->convert();

// ---------- XLSX PARSING

$xlsx_sections = [];
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
$sheetNames = $sheet->getSheetNames();

// Get fields of each section
foreach ($sheetNames as $sn) {
    $worksheet = $sheet->setActiveSheetIndexByName($sn);
    $dataArray = $worksheet->toArray();
    $xlsx_sections[$sn] = $dataArray[0];
}


// ---------- TXT PARSING

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
    if (!file_exists($txt_output)) {
        return new InvalidArgumentException("Path {$txt_output} does not exit");
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

$content = file_get_contents($txt_output);
$chunks = preg_split('/^(Título:\s(.*))|(VALIDAÇÕES\sAPLICADAS\sAUTOMATICAMENTE\sAO\sARQUIVO\s)/m', $content);

// Ignore introduction
array_shift($chunks);

// Parse chunks and re-index result by the field column
$fields = [];
foreach ($chunks as $c)
{
    $str = trim($c);

    $sec['str'] = $str;

    if (preg_match('/^Nome\sXML\:(.+)/m', $str, $matches)) {
        $sec['field'] = $sanitizeStr(trim($matches[1]));
    }

    if (preg_match('/^Natureza\:(.+)/m', $str, $matches)) {
        $sec['type'] = trim($matches[1]);
    }

    if (preg_match('/^Tamanho\:(.+)/m', $str, $matches)) {
        $sec['length'] = trim($matches[1]);
    }

    if (preg_match('/^Obrigatoriedade\:(.+)/m', $str, $matches)) {
        $sec['required'] = trim($matches[1]);
    }

    
    if (preg_match('/Preenchimento:\s*(.+)/s', $str, $matches)) {
        $sec['filling_rule'] = trim($matches[1]);
    }

    $fields[] = $sec ?? [];
}
$txt_fields = array_combine(array_column($fields, 'field'), $fields);

// ---------- TXT and XLSX mapping

$mapping = [];
foreach ($xlsx_sections as $key => $fields) {
    array_walk($fields, function ($field) use (&$mapping, $txt_fields) {
        $f = trim(str_replace('_', ' ', $field));
        if (!array_key_exists($f, $txt_fields)) {
            echo "Field $f does not match any fields on TXT" . PHP_EOL;
        } else {
            $mapping[$field] = $txt_fields[$f];
        }
    });
}

// ---------- APPLY RULES ON MAPPED DATA

$applyRules = function ($str) {

    if (preg_match('/^\d+$/', $str, $matches)) {
        return intval($str);
    }

    if (preg_match('/Sim|Não|Condicional/', $str, $matches)) {
        switch ($matches[0]) {
            case 'Sim':
                return 'REQUIRED';
            case 'Não':
                return 'NULLABLE';
            case 'Condicional':
                return 'CONDITIONAL';
            default:
                return 'UNKNOWN';
        }
    }

    if (preg_match('/^(\d+).+(\d+).+/', $str, $matches)) {
        array_shift($matches);
        if ($matches) {
            $matches = array_map('intval', $matches);
            return $matches[0] + $matches[1] . ',' . $matches[1];
        }
        return null;
    }

    preg_replace('/(https\:\/\/[\w\|\.\/]+)|(.+Visualizar\sManual\sda\sCarga)/', '', $str);

    return $str;
};

foreach ($mapping as $section => $fields) 
{
    array_walk($fields, function ($field) use ($applyRules, &$mapping, $section, $fields) {
        $mapping[$section]['required'] = $applyRules($fields['required']);
        $mapping[$section]['type'] = $applyRules($fields['type']);
        $mapping[$section]['length'] = $applyRules($fields['length']);
        $mapping[$section]['filling_rule'] = $applyRules($fields['filling_rule']);
    });
}

// ---------- CREATE TXT WITH FIELDS AND THEIR RULES

$output = fopen("./assets/txt/$txt_output_filename-FIELDS.txt", 'w');

foreach ($mapping as $field => $rules)
{
    $content = <<<EOF
    FIELD "$field" IS {$rules['type']}({$rules['length']}) {$rules['required']} 
    RULES ARE {$rules['filling_rule']}


    EOF;
    fwrite($output, $content);
}

fclose($output);