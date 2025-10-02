<?php

/**
 * Generates excel column index pattern
 * A, B, C, ..., Z, AA, AB, ..., AZ, AAB, AAC, ...
 * @param integer $n number of elements
 * @param array 
 */
function generate_excel_column_index_pattern ($n) : array
{
    $alphabet = range('A', 'Z');
    
    $na = sizeof($alphabet);
    $n_alphabets = intdiv($n, $na);
    $n_remaining = $n % $na;

    $pattern = [];
    for ($i = 0; $i < $n_alphabets + 1; $i++)
    {
        $arr = array_map(function ($e) use ($i) {
            return str_pad($e, $i + 1, 'A', STR_PAD_LEFT);
        }, $alphabet);

        $pattern = array_merge($pattern, $arr);
    }

    return array_splice($pattern, 0, $n_alphabets * $na + $n_remaining);
}

/**
 * Strip accents
 * @param string $str
 * @param string
 */
function strip_accents($str) {
    return strtr(utf8_decode($str), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
}