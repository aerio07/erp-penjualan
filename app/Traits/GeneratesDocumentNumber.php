<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait GeneratesDocumentNumber
{
    /**
     * Generate nomor dokumen dengan format: PREFIX-DD/MM/YYYY-XXXX
     * Contoh: LPB-05/09/2026-0001, PO-05/09/2026-0001
     * Nomor urut akan otomatis berlanjut pada hari yang sama dan direset setiap hari baru.
     *
     * @param class-string<Model> $modelClass
     * @param string $column
     * @param string $prefix
     * @param int $padding
     * @return string
     */
    protected function generateDocumentNumber(string $modelClass, string $column, string $prefix, int $padding = 4): string
    {
        $datePrefix = strtoupper($prefix) . '-' . date('d/m/Y') . '-';

        $last = $modelClass::where($column, 'like', $datePrefix . '%')
            ->orderByDesc('id')
            ->value($column);

        $seq = $last ? ((int) substr($last, strlen($datePrefix)) + 1) : 1;

        return $datePrefix . str_pad($seq, $padding, '0', STR_PAD_LEFT);
    }

    /**
     * Generate kode master data unik dengan format: PREFIX-XXXX (contoh: CUST-0001, SUP-0001, PRD-0001)
     *
     * @param class-string<Model> $modelClass
     * @param string $column
     * @param string $prefix
     * @param int $padding
     * @return string
     */
    protected function generateMasterCode(string $modelClass, string $column, string $prefix, int $padding = 4): string
    {
        $cleanPrefix = rtrim(strtoupper(trim($prefix)), '-');
        $pattern = $cleanPrefix . '-%';

        $last = $modelClass::where($column, 'like', $pattern)
            ->orderByDesc('id')
            ->value($column);

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $lastNum = end($parts);
            if (is_numeric($lastNum)) {
                $seq = (int) $lastNum + 1;
            } else {
                $seq = $modelClass::count() + 1;
            }
        } else {
            $seq = $modelClass::count() + 1;
        }

        $code = $cleanPrefix . '-' . str_pad($seq, $padding, '0', STR_PAD_LEFT);

        while ($modelClass::where($column, $code)->exists()) {
            $seq++;
            $code = $cleanPrefix . '-' . str_pad($seq, $padding, '0', STR_PAD_LEFT);
        }

        return $code;
    }
}
