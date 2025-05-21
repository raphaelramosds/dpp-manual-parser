<?php

use raphaelramosds\PdfToTxt\PdfToTxt;

require 'vendor/autoload.php';
require 'src/TxtContext.php';
require 'src/SpreadsheetContext.php';
require 'src/Splitter/FieldFormatSplitter.php';
require 'src/Splitter/TitleSplitter.php';

include_once 'env.php';

// ---------- INPUTS

$report = 'NPP';
$excel = './assets/excel/006_CNPJ_AAAAMMDDHHMMSS_VER.xls';
$pdf = './assets/pdf/NPP.pdf';

// ---------- PDF to TXT conversion

$pdfh = new PdfToTxt($pdf, TXT_OUTPUT_DIR, $report);
$pdfh->setReloadPdf(false);
$pdfh->convert();

// ---------- GET FIELDS AND SECTIONS FROM XLSX

$spreadsheet_fields = (new SpreadsheetContext($excel))->getFields();

// ---------- GET FIELDS FROM TXT

$textContext = new TxtContext(TXT_OUTPUT_DIR . "/$report.txt"); 
$textContext->setSplitter(new TitleSplitter());
$txtFields = $textContext->parse();

// ---------- TXT and XLSX mapping

$mapping = [];
foreach ($spreadsheet_fields as $key => $fields) {
    array_walk($fields, function ($field) use (&$mapping, $txtFields) {
        $f = trim(str_replace('_', ' ', $field));
        if (!array_key_exists($f, $txtFields)) {
            echo "Spreadsheet field $f does not match any fields on TXT" . PHP_EOL;
            $mapping[$field] = [
                'field' => $field,
                'type' => 'UNKNOWN',
                'length' => 'UNKNOWN',
                'required' => 'UNKNOWN',
                'filling_rule' => 'UNKNOWN'
            ];
        } else {
            $mapping[$field] = $txtFields[$f];
        }
    });
}

foreach ($mapping as $section => $fields) 
{
    array_walk($fields, function ($field) use (&$mapping, $section, $fields) {
        $mapping[$section]['required'] = $fields['required'];
        $mapping[$section]['type'] = strtoupper($fields['type']);
        $mapping[$section]['length'] = $fields['length'];
        $mapping[$section]['filling_rule'] = $fields['filling_rule'];
    });
}

// ---------- CREATE TXT WITH FIELDS AND THEIR RULES

$output = fopen(TXT_OUTPUT_DIR . "/$report-FIELDS.txt", 'w');

$header = <<<EOF
$report


EOF;
fwrite($output, $header);

foreach ($spreadsheet_fields as $section => $fields) 
{
    $fieldsList = implode(', ', array_filter($fields));
    $content = <<<EOF
    FIELDS OF SECTION "$section" ARE $fieldsList;
    
    EOF;
    fwrite($output, $content);
}

fwrite($output, PHP_EOL);

foreach ($mapping as $field => $rules)
{
    $content = <<<EOF
    FIELD "$field" IS {$rules['type']}({$rules['length']}) {$rules['required']}; 

    EOF;
    fwrite($output, $content);
}

fclose($output);