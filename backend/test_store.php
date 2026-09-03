<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$req = new \Illuminate\Http\Request();
$req->merge([
    'no' => 'INV/TRS/2026/0011',
    'date' => '2026-08-29',
    'status' => 'Belum Lunas',
    'group' => 'Tanpa Nama Grup',
    'pax' => 0,
    'items' => [['cat' => 'Lainnya', 'qty' => 0, 'cost' => 0, 'price' => 0]],
    'discount' => '',
    'discountType' => 'Rp',
    'serviceFee' => '',
    'serviceFeeType' => 'Rp',
    'taxPercent' => 11,
    'dpPercent' => '',
    'dpDueDate' => '',
    'tenggatDate' => '',
    'notes' => '',
    'payment_info' => ''
]);
$req->setUserResolver(function() { return \App\Models\User::first(); });

try {
    $controller = app()->make(\App\Http\Controllers\Api\DashboardController::class);
    $res = $controller->store($req);
    echo $res->getContent();
} catch (\Exception $e) {
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
    if ($e instanceof \Illuminate\Validation\ValidationException) {
        print_r($e->errors());
    }
}
