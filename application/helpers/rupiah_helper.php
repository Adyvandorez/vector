<?php
defined('BASEPATH') or exit('No direct script access allowed');

function rupiah($num)
{
    return 'Rp ' . number_format((int)$num, 0, ',', '.');
}

/**
 * Ubah input uang dari UI menjadi integer aman.
 * Contoh: "1.600.000" / "Rp 1.600.000" / "1600000" -> 1600000.
 */
function rupiah_number($value)
{
    if (is_array($value)) return 0;
    $value = (string)$value;
    $digits = preg_replace('/[^0-9]/', '', $value);
    return $digits === '' ? 0 : (int)$digits;
}
