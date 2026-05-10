<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(\App\Services\AIChatService::class);
$response = $service->chat("Liệt kê các hợp đồng thuê của tôi");
print_r($response);
