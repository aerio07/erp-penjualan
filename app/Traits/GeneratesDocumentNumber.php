<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait GeneratesDocumentNumber
{
    /**
     * Generate nomor dokumen dengan format: PREFIX-DD/MM/YYYY-XXXX
     * Contoh: GRN-05/09/2026-0001, PO-05/09/2026-0001
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
}
