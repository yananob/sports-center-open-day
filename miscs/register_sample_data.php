<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Repositories\FirestoreRepository;
use App\Models\TargetCenter;

$targets = [
    new TargetCenter('chuo', '中央スポーツセンター', 'https://www.example.com/chuo'),
    new TargetCenter('minato', '港スポーツセンター', 'https://www.example.com/minato'),
    new TargetCenter('naniwa', '浪速スポーツセンター', 'https://www.example.com/naniwa'),
];

$repo = new FirestoreRepository();
try {
    $repo->saveTargets($targets);
    echo "Sample data registered successfully.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
