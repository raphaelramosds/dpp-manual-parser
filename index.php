<?php

use DppManualParser\TitleSplitter;
use raphaelramosds\PdfToTxt\PdfToTxt;

require 'vendor/autoload.php';

/**
 * $reports
 * 
 * An associative array defining reports for processing.
 * Each key represents a unique report identifier (e.g., 'crp', 'ncrp').
 * The value associated with each key is an array with three elements:
 * 
 * [0] string   => Spreadsheet filename in XLSX format (required).
 * [1] ?string  => Related PDF filename (optional; can be null).
 * [2] object   => An instance of a class that defines how the report content should be split.
 *                Example: TitleSplitter or FieldFormatSplitter.
 * 
 * Notes:
 *  1. Spreadsheets must be in XLSX format.
 *  2. The PDF filename is optional, which is useful when you already have a TXT version of this PDF
 *  3. Each key defines the TXT filename that will be processed (e.g., 'crp' maps to 'crp.txt').
 */
$reports = [
    // 'crp' => ['108_crp.xlsx', 'crp_ncrp_manual.pdf', new TitleSplitter()],
    //    'ncrp' => ['108_ncrp.xlsx', 'crp_ncrp_manual.pdf', new TitleSplitter()],
    //    'nd' => ['102-modelo-nd.xlsx', '102-modelo-nd.pdf', new TitleSplitter()],
    //    'sop' => ['007-sop.xlsx', '007-sop.pdf', new TitleSplitter()],
    //    'fp' => ['082-fp.xlsx', '082-fp.pdf', new TitleSplitter()],
    //    'cipp' => ['095_CIPP.xlsx', '095-dpp-cipp.pdf', new TitleSplitter()],
    //    'rfp' => ['098-rfp-exp.xlsx', 'manual-rfp-exp.pdf', new TitleSplitter()],
    //    'rfcp' => ['RFCP_NOME_POÇO_V00.xlsx', null, new FieldFormatSplitter()],
    //    'la' => ['018-la.xlsx', null, new TitleSplitter()]
    // 'cp' => ['109_CP.xlsx', 'cp.pdf', new TitleSplitter()]
    'rap' => [JSON_PATH . '/rap.json', null, new TitleSplitter()]
];

foreach ($reports as $report => $files) {
    list($excel, $pdf, $splitter) = $files;
    process(
        $report,
        $excel,
        $pdf ? PDF_PATH . '/' . $pdf : '',
        $splitter,
    );
}

function process($report, $excel, $pdf, $splitter)
{
    echo "Generating XML for $report..." . PHP_EOL;

    // ---------- PDF to TXT conversion

    if ($pdf) {
        $pdfh = new PdfToTxt($pdf, TXT_OUTPUT_DIR, $report);
        $pdfh->setReloadPdf(true);
        $pdfh->convert();
    }

    // ---------- GET FIELDS AND SECTIONS FROM XLSX

    $sc = create_context($excel);
    $spreadsheet_fields = $sc->getFields();
    // $spreadsheet_dimensions = $sc->getDimensions();
    $spreadsheet_labels = $sc->getLabels();

    log_info($spreadsheet_fields);

    // ---------- GET FIELDS FROM TXT

    $textContext = create_context(TXT_OUTPUT_DIR . "/$report.txt");
    $textContext->setSplitter($splitter);
    $txtFields = $textContext->parse();

    log_info($txtFields);

    // ---------- TXT and XLSX mapping
    $mapping = [];
    foreach ($spreadsheet_fields as $section => $fields) {
        $n = sizeof($fields);
        $cols = generate_excel_column_index_pattern($n);
        $fields = array_values($fields);

        foreach ($fields as $i => $field) {
            if (!array_key_exists($field, $txtFields)) {
                // echo "Spreadsheet field $field does not match any fields on TXT" . PHP_EOL;
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

            // if (array_key_exists($cols[$i], $spreadsheet_dimensions[$section])) {
            //     $mapping[$field]['colWidth'] = $spreadsheet_dimensions[$section][$cols[$i]]->getWidth();
            // }
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

    $output = fopen(XML_OUTPUT_DIR . "/$report.xml", 'w');

    $header = <<<XML
    <?xml version='1.0' encoding='UTF-8'?>
    <report name="{$report}">

    XML;
    fwrite($output, $header);

    foreach ($spreadsheet_fields as $section => $fields) {
        $content = <<<XML
        \t<section name="{$section}">

        XML;
        fwrite($output, $content);


        foreach ($fields as $field) {
            $mf = $mapping[$field];
            $type = $mf['type'];
            $label = $spreadsheet_labels[$field] ?? '';
            // if ($mf['length']) $type .= '(' . $mf['length'] . ')';
            $content = <<<XML
            \t\t<field type="$type" required="{$mf['required']}" ptBr="$label">{$mf['field']}</field>

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
}
