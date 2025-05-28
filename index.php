<?php

use raphaelramosds\PdfToTxt\PdfToTxt;

require 'vendor/autoload.php';
require 'src/TxtContext.php';
require 'src/SpreadsheetContext.php';
require 'src/Splitter/FieldFormatSplitter.php';
require 'src/Splitter/TitleSplitter.php';

include_once 'env.php';

// ---------- INPUTS

$report = 'npr';
$excel = './assets/excel/006_CNPJ_AAAAMMDDHHMMSS_VER.xlsx';
$pdf = './assets/pdf/Manual_NPR.pdf';

// ---------- PDF to TXT conversion

$pdfh = new PdfToTxt($pdf, TXT_OUTPUT_DIR, $report);
$pdfh->setReloadPdf(false);
$pdfh->convert();

// ---------- GET FIELDS AND SECTIONS FROM XLSX

$sc = new SpreadsheetContext($excel);
$spreadsheet_fields = $sc->getFields();
$spreadsheet_dimensions = $sc->getDimensions();
$spreadsheet_labels = $sc->getLabels();

// ---------- GET FIELDS FROM TXT

$textContext = new TxtContext(TXT_OUTPUT_DIR . "/$report.txt");
$textContext->setSplitter(new TitleSplitter());
$txtFields = $textContext->parse();

// ---------- TXT and XLSX mapping
$mapping = [];
foreach ($spreadsheet_fields as $section => $fields) {
    $n = sizeof($fields);
    $cols = generate_excel_column_index_pattern($n);
    $fields = array_values($fields);

    foreach ($fields as $i => $field)
    {
        if (!array_key_exists($field, $txtFields)) {
            echo "Spreadsheet field $field does not match any fields on TXT" . PHP_EOL;
            $mapping[$field] = [
                'field' => $field,
                'type' => 'UNKNOWN',
                'length' => 'UNKNOWN',
                'required' => 'UNKNOWN',
                'filling_rule' => 'UNKNOWN'
            ];
        } else {
            $mapping[$field] = $txtFields[$field];
        }
        $mapping[$field]['colWidth'] = $spreadsheet_dimensions[$section][$cols[$i]]->getWidth();
    }
}

foreach ($mapping as $section => $fields) {
    array_walk($fields, function ($field) use (&$mapping, $section, $fields) {
        $mapping[$section]['required'] = $fields['required'];
        $mapping[$section]['type'] = strtoupper($fields['type']);
        $mapping[$section]['length'] = $fields['length'];
        $mapping[$section]['filling_rule'] = $fields['filling_rule'];
    });
}

// ---------- CREATE XML WITH FIELDS AND THEIR RULES

$output = fopen(XML_OUTPUT_DIR . "/$report-generated.xml", 'w');

$header = <<<XML
<?xml version='1.0' encoding='UTF-8'?>
<report name="{$report}">

XML;
fwrite($output, $header);

foreach ($spreadsheet_fields as $section => $fields) {
    $fieldsList = implode(', ', array_filter($fields));
    $content = <<<XML
    \t<section name="{$section}">

    XML;
    fwrite($output, $content);


    foreach ($fields as $field) {
        $mf = $mapping[$field];
        $type = $mf['type'];
        $label = $spreadsheet_labels[$field];
        if ($mf['length']) $type .= '(' . $mf['length'] . ')';
        $content = <<<XML
        \t\t<field type="$type" required="{$mf['required']}" colWidth="{$mf['colWidth']}" ptBr="$label">{$mf['field']}</field>

        XML;
        fwrite($output, $content);
    }

    $content = <<<XML
    \t</section>

    XML;

    fwrite($output, $content);
}

$content = <<<XML
</report>
XML;
fwrite($output, $content);

fclose($output);

// ---------- PARSE XML

// $xml = simplexml_load_file(XML_OUTPUT_DIR . "/$report.xml");

// if (!$xml) {
//   echo "Failed loading XML: ";
//   foreach(libxml_get_errors() as $error) {
//     echo "<br>", $error->message;
//   }
// } else {
    // Size of sections
    // echo var_dump(sizeof($xml));

    // Get attribute of a section
    // echo var_dump($xml->section[0]['name']);

    // List fields of a section
    // echo var_dump($xml->section[0]->fields);
// }