<?php

if (!function_exists('rupiah')) {
    /**
     * Format angka ke format mata uang Rupiah
     *
     * @param mixed $amount
     * @param bool $withPrefix
     * @return string
     */
    function rupiah($amount, bool $withPrefix = true): string
    {
        if ($amount === null || $amount === '') {
            return $withPrefix ? 'Rp 0' : '0';
        }

        $formatted = number_format(round((float)$amount), 0, ',', '.');
        return $withPrefix ? 'Rp ' . $formatted : $formatted;
    }
}

if (!function_exists('unrupiah')) {
    /**
     * Bersihkan string Rupiah menjadi integer murni
     *
     * @param mixed $formattedString
     * @return int
     */
    function unrupiah($formattedString): int
    {
        if (empty($formattedString)) {
            return 0;
        }

        $clean = preg_replace('/[^\d]/', '', (string)$formattedString);
        return (int)$clean;
    }
}
