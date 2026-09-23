<?php

namespace App\Console\Commands;

use Database\Seeders\CutoffDataSeeder;
use Illuminate\Console\Command;

class ImportCutoffDataCommand extends Command
{
    protected $signature = 'app:import-cutoff-data';
    protected $description = 'Import data cut-off per 31 Agustus 2026 (Stok Barang, Kartu Hutang, dan Kartu Piutang dengan item detail)';

    public function handle(): int
    {
        $this->info("=== MEMULAI IMPORT DATA CUT-OFF PERIODE AGUSTUS 2026 (DETAILED ITEMS) ===");
        $this->call('db:seed', [
            '--class' => CutoffDataSeeder::class,
            '--force' => true,
        ]);
        $this->info("=== IMPORT DATA CUT-OFF BERHASIL ===");
        return Command::SUCCESS;
    }
}
