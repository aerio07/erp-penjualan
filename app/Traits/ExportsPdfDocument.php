<?php

namespace App\Traits;

use Barryvdh\DomPDF\Facade\Pdf;

trait ExportsPdfDocument
{
    /**
     * Render and stream a DomPDF document in A4 portrait format.
     *
     * @param string $view Blade view name (e.g. 'pdf.sales-invoice')
     * @param array $data Data to pass to the view
     * @param string $filename Download/display filename
     * @param string $paper Paper size ('a4', 'letter', etc.)
     * @param string $orientation 'portrait' or 'landscape'
     * @return \Illuminate\Http\Response
     */
    protected function streamPdf(
        string $view,
        array $data,
        string $filename,
        string $paper = 'a4',
        string $orientation = 'portrait'
    ) {
        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper($paper, $orientation);

        $safeNumber = str_replace(['/', '\\'], '-', $filename);
        if (!str_ends_with(strtolower($safeNumber), '.pdf')) {
            $safeNumber .= '.pdf';
        }

        return $pdf->stream($safeNumber, ['Attachment' => false]);
    }
}
