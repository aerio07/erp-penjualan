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

if (!function_exists('format_date')) {
    /**
     * Format tanggal ke dd/mm/yyyy (standar aplikasi)
     *
     * @param mixed $date
     * @param string $format
     * @return string
     */
    function format_date($date, string $format = 'd/m/Y'): string
    {
        if (empty($date)) {
            return '-';
        }

        if (is_string($date)) {
            try {
                $date = \Carbon\Carbon::parse($date);
            } catch (\Throwable $e) {
                return $date;
            }
        }

        return ($date instanceof \DateTimeInterface) ? $date->format($format) : '-';
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Format tanggal & waktu ke dd/mm/yyyy HH:mm
     *
     * @param mixed $date
     * @param string $format
     * @return string
     */
    function format_datetime($date, string $format = 'd/m/Y H:i'): string
    {
        if (empty($date)) {
            return '-';
        }

        if (is_string($date)) {
            try {
                $date = \Carbon\Carbon::parse($date);
            } catch (\Throwable $e) {
                return $date;
            }
        }

        return ($date instanceof \DateTimeInterface) ? $date->format($format) : '-';
    }
}
