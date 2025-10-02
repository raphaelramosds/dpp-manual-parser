<?php

use DppManualParser\JsonContext;
use DppManualParser\SpreadsheetContext;
use DppManualParser\TxtContext;

class InvalidContextException extends Exception {}

function create_context(string $definition)
{
    try {
        if (!file_exists($definition)) {
            throw new InvalidArgumentException("File not found: $definition");
        }

        $pathinfo = pathinfo($definition);
        $extension = $pathinfo['extension'];

        switch ($extension) {
            case 'json':
                return new JsonContext($definition);
                break;
            case 'xls':
                return new SpreadsheetContext($definition);
                break;
            case 'xlsx':
                return new SpreadsheetContext($definition);
                break;
            case 'txt':
                return new TxtContext($definition);
                break;
            default:
                throw new InvalidContextException("Context not found for $extension");
                break;
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

function log_info(mixed $message)
{
    $log = fopen(LOGS . '/app.log', 'a+');
    $timestamp = date('Y-d-m H:i:s');
    $entry = "[$timestamp] INFO: ";

    if (is_string($message)) {
        $entry .= $message;
    } else {
        $message = (array) $message;
        $entry .= json_encode($message);
    } 

    $entry .= PHP_EOL;

    fwrite($log, $entry);
}
