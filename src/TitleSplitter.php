<?php

namespace DppManualParser;

class TitleSplitter implements SplitterInterface
{
    public function handle(string $content) : array
    {
        $chunks = preg_split('/^(Título:\s(.*))/m', $content);

        // Ignore introduction
        array_shift($chunks);

        // Parse chunks and re-index result by the field column
        $fields = [];
        foreach ($chunks as $c) {
            $str = trim($c);

            $sec['str'] = $str;

            if (preg_match('/^Nome\sXML\:(.+)/m', $str, $matches)) {
                $sec['field'] = str_replace(' ', '_', $this->format($matches[1]));
            }

            if (preg_match('/^Natureza\:(.+)/m', $str, $matches)) {
                $sec['type'] = $this->transType($this->format($matches[1]));
            }

            if (preg_match('/^Tamanho\:(.+)/m', $str, $matches)) {
                $sec['length'] = $this->format($matches[1]);
            }

            if (preg_match('/^Obrigatoriedade\:(.+)/m', $str, $matches)) {
                $sec['required'] = $this->format($matches[1]);
            }

            if (preg_match('/Preenchimento:\s*(.+)/s', $str, $matches)) {
                $sec['filling_rule'] = $this->format($matches[1]);
            }

            $fields[] = $sec ?? [];
        }

        $output = array_combine(array_column($fields, 'field'), $fields);

        return $output;
    }

    private function transType(string $type)
    {
        $trans = [
            'TEXTO' => 'TEXT',
            'INTEIRO' => 'INTEGER',
            'DATA' => 'DATE'
        ];
        return $trans[$type] ?? $type;
    }

    private function format($str)
    {
        $str = trim($str);
        $find = ['.'];
        $replace = array_map(function ($el) {
            return '';
        }, $find);

        $str = str_replace($find, $replace, $str);
        $str = strip_accents($str);

        preg_replace('/\w+:\/\/[^\s]+|.*?Visualizar\sManual\sda\sCarga.*/', '', $str);

        if (preg_match('/^\d+$/', $str, $matches)) {
            return intval($str);
        }

        if (preg_match('/Sim|N(a|\ã)o|Condicional/i', $str, $matches)) {
            if (strcasecmp($matches[0], 'Sim') === 0) {
                return "1";
            }

            if (preg_match('/N(a|\ã)o/i', $matches[0], $matches2) === 1) {
                return "0";
            }

            if (strcasecmp($matches[0], 'Condicional') === 0) {
                return "-1";
            }

            return 'UNKNOWN';
        }

        if (preg_match('/^(\d+).+(\d+).+/', $str, $matches)) {
            array_shift($matches);
            if ($matches) {
                $matches = array_map('intval', $matches);
                return $matches[0] + $matches[1] . ',' . $matches[1];
            }
            return null;
        }

        return $str;
    }
}
