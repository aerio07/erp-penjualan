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

if (!function_exists('terbilang')) {
    /**
     * Konversi nominal angka ke kalimat terbilang Bahasa Indonesia
     *
     * @param float|int|string $number
     * @param string $suffix
     * @return string
     */
    function terbilang($number, string $suffix = 'Rupiah'): string
    {
        $number = (float) abs((float) $number);
        if ($number == 0) {
            return trim("Nol " . $suffix);
        }

        $words = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

        $spell = function ($n) use (&$spell, $words) {
            $n = (float) $n;
            if ($n < 12) {
                return $words[(int)$n];
            } elseif ($n < 20) {
                return $spell($n - 10) . ' Belas';
            } elseif ($n < 100) {
                return $spell((int)($n / 10)) . ' Puluh ' . $spell(fmod($n, 10));
            } elseif ($n < 200) {
                return 'Seratus ' . $spell($n - 100);
            } elseif ($n < 1000) {
                return $spell((int)($n / 100)) . ' Ratus ' . $spell(fmod($n, 100));
            } elseif ($n < 2000) {
                return 'Seribu ' . $spell($n - 1000);
            } elseif ($n < 1000000) {
                return $spell((int)($n / 1000)) . ' Ribu ' . $spell(fmod($n, 1000));
            } elseif ($n < 1000000000) {
                return $spell((int)($n / 1000000)) . ' Juta ' . $spell(fmod($n, 1000000));
            } elseif ($n < 1000000000000) {
                return $spell((int)($n / 1000000000)) . ' Miliar ' . $spell(fmod($n, 1000000000));
            } else {
                return $spell((int)($n / 1000000000000)) . ' Triliun ' . $spell(fmod($n, 1000000000000));
            }
        };

        $result = trim(preg_replace('/\s+/', ' ', $spell($number)));
        if (!empty($suffix)) {
            $result .= ' ' . $suffix;
        }

        return ucwords(strtolower($result));
    }
}

