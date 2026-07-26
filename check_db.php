<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$posts = App\Models\Baiviet::latest('id')->take(3)->get();
foreach ($posts as $b) {
    echo "====================================\n";
    echo "ID: " . $b->id . "\n";
    echo "TITLE: " . $b->tieude . "\n";
    echo "IMAGE: " . $b->image_url . "\n";
    echo "CONTENT: " . substr(strip_tags($b->noidung), 0, 100) . "...\n";
}
