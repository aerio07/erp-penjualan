<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasListFilters
{
    /**
     * Terapkan pencarian global berbasis keyword (q) pada kolom yang ditentukan.
     * Mendukung pencarian kolom langsung (misal 'po_number') dan relasi (misal 'supplier.name').
     *
     * @param Builder $query
     * @param Request $request
     * @param array $searchableColumns
     * @return Builder
     */
    protected function applySearch(Builder $query, Request $request, array $searchableColumns): Builder
    {
        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $query->where(function (Builder $q) use ($keyword, $searchableColumns) {
                foreach ($searchableColumns as $index => $column) {
                    if (str_contains($column, '.')) {
                        $parts = explode('.', $column);
                        $relColumn = array_pop($parts);
                        $relation = implode('.', $parts);
                        $whereMethod = $index === 0 ? 'whereHas' : 'orWhereHas';
                        $q->$whereMethod($relation, function (Builder $relQuery) use ($relColumn, $keyword) {
                            $relQuery->where($relColumn, 'like', '%' . $keyword . '%');
                        });
                    } else {
                        $whereMethod = $index === 0 ? 'where' : 'orWhere';
                        $q->$whereMethod($column, 'like', '%' . $keyword . '%');
                    }
                }
            });
        }

        return $query;
    }

    /**
     * Terapkan filter exact-match sederhana pada kolom tertentu jika request parameter terisi.
     *
     * @param Builder $query
     * @param Request $request
     * @param string $column
     * @param string|null $requestParam
     * @return Builder
     */
    protected function applyFilter(Builder $query, Request $request, string $column, ?string $requestParam = null): Builder
    {
        $param = $requestParam ?? $column;
        if ($request->filled($param)) {
            $query->where($column, $request->$param);
        }

        return $query;
    }

    /**
     * Terapkan filter rentang tanggal.
     *
     * @param Builder $query
     * @param Request $request
     * @param string $column
     * @param string $fromParam
     * @param string $toParam
     * @return Builder
     */
    protected function applyDateRange(Builder $query, Request $request, string $column = 'created_at', string $fromParam = 'date_from', string $toParam = 'date_to'): Builder
    {
        if ($request->filled($fromParam)) {
            $query->whereDate($column, '>=', $request->$fromParam);
        }
        if ($request->filled($toParam)) {
            $query->whereDate($column, '<=', $request->$toParam);
        }

        return $query;
    }

    /**
     * Terapkan pengurutan kolom dengan pengamanan whitelisting dan penanganan tie-breaker
     * agar data/dokumen selalu terurut konsisten (dokumen terbaru selalu di atas).
     *
     * @param Builder $query
     * @param Request $request
     * @param array $allowedColumns
     * @param string $defaultSort
     * @param string $defaultDir
     * @return Builder
     */
    protected function applySort(Builder $query, Request $request, array $allowedColumns, string $defaultSort = 'created_at', string $defaultDir = 'desc'): Builder
    {
        $sortBy = in_array($request->sort_by, $allowedColumns, true) ? $request->sort_by : $defaultSort;
        $sortDir = strtolower($request->sort_dir) === 'asc' ? 'asc' : (strtolower($request->sort_dir) === 'desc' ? 'desc' : $defaultDir);

        $model = $query->getModel();
        $table = $model->getTable();
        $primaryKey = $model->getKeyName() ?: 'id';

        $primaryCol = (str_contains($sortBy, '.') || $sortBy === 'products_count')
            ? $sortBy
            : $table . '.' . $sortBy;
        $query->orderBy($primaryCol, $sortDir);

        // Kolom-kolom temporal/tanggal
        $temporalColumns = [
            'order_date', 'return_date', 'received_date', 'invoice_date',
            'delivery_date', 'payment_date', 'transfer_date', 'opname_date',
            'movement_date', 'entry_date', 'disposed_at', 'expected_date',
            'due_date', 'created_at', $primaryKey
        ];

        // Jika sort utama adalah kolom temporal dan user memilih ASC, secondary mengikuti ASC.
        // Jika sort utama adalah kolom non-tanggal (misal status/supplier/customer), secondary SELALU 'desc'
        // agar dokumen di dalam grup/filter tersebut selalu terurut dari yang terbaru ke terlama.
        $secondaryDir = in_array($sortBy, $temporalColumns, true) ? $sortDir : 'desc';

        if ($model->usesTimestamps() && !in_array($sortBy, ['created_at', $primaryKey], true)) {
            $query->orderBy($table . '.created_at', $secondaryDir);
        }

        if ($sortBy !== $primaryKey) {
            $query->orderBy($table . '.' . $primaryKey, $secondaryDir);
        }

        return $query;
    }
}
