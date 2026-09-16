<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (App\Models\Product::all() as $p) {
    echo "ID: {$p->id} | SKU: {$p->sku} | Name: {$p->name} | purchase_price: {$p->purchase_price}\n";
}
