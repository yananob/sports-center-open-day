<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\AppConfig;
use App\Controllers\DashboardController;
use App\Repositories\FirestoreRepository;
use App\Services\AiParserService;
use App\Services\CrawlerService;
use App\UseCases\CollectionUseCase;
use CloudEvents\V1\CloudEventInterface;
use Google\CloudFunctions\FunctionsFramework;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

FunctionsFramework::http('main_http', 'main_http');

/**
 * HTTPリクエストを受け取るエントリポイント関数。
 */
function main_http(ServerRequestInterface $request): ResponseInterface
{
    $path = $request->getUri()->getPath();
    $basePath = AppConfig::getBasePath();

    // ベースパスのプレフィックスを除去してルーティング
    $route = str_replace($basePath, '', $path);
    $route = rtrim($route, '/');
    if ($route === '') {
        $route = '/';
    }

    $method = strtoupper($request->getMethod());
    $controller = new DashboardController();

    return match ("{$method} {$route}") {
        'GET /' => $controller->index($request),
        'POST /update' => $controller->update($request),
        'GET /targets' => $controller->targets($request),
        'POST /targets' => $controller->saveTargets($request),
        default => new Response(404, [], "Not Found"),
    };
}

FunctionsFramework::cloudEvent('main_event', 'main_event');

/**
 * Cloud Events (Pub/Sub等の定期実行) を受け取るエントリポイント関数。
 */
function main_event(CloudEventInterface $event): void
{
    $logger = new Logger('batch');
    $logger->pushHandler(new StreamHandler('php://stdout', Level::Info));
    $logger->info("Starting batch collection process from Cloud Event.");

    $repository = new FirestoreRepository();
    $useCase = new CollectionUseCase(
        $repository,
        new CrawlerService(),
        new AiParserService($logger),
        $logger
    );

    try {
        $useCase->execute();
        $logger->info("Batch collection process completed successfully.");
    } catch (\Throwable $e) {
        $logger->error("Batch collection process failed: " . $e->getMessage());
    }
}
