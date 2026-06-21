<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Repositories\FirestoreRepository;
use App\Models\TargetCenter;

$targets = [
    new TargetCenter('ikuno', '生野スポーツセンター', 'http://osakashi.opas.jp/chikubetu/facility_e/city_ward_ikuno/%E7%94%9F%E9%87%8E%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84%E3%82%BB%E3%83%B3%E3%82%BF%E3%83%BC/1427.html'),
    new TargetCenter('abeno', '阿倍野スポーツセンター', 'http://osakashi.opas.jp/chikubetu/facility_s/city_ward_abeno/%E9%98%BF%E5%80%8D%E9%87%8E%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84%E3%82%BB%E3%83%B3%E3%82%BF%E3%83%BC/1758.html'),
];

$repo = new FirestoreRepository();
try {
    $repo->saveTargets($targets);
    echo "Sample data registered successfully.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
