<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Google\CloudFunctions\FunctionsFramework;
use Psr\Http\Message\ServerRequestInterface;
use CloudEvents\V1\CloudEventInterface;

FunctionsFramework::http('main_http', 'main_http');
function main_http(ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
{
    $path = $request->getUri()->getPath();
    $basePath = \App\AppConfig::getBasePath();

    // プレフィックスを除去してルーティング
    $route = str_replace($basePath, '', $path);
    $route = rtrim($route, '/');
    if ($route === '') {
        $route = '/';
    }

    $controller = new \App\Controllers\DashboardController();

    if ($route === '/' && $request->getMethod() === 'GET') {
        return $controller->index($request);
    }

    if ($route === '/update' && $request->getMethod() === 'POST') {
        return $controller->update($request);
    }

    if ($route === '/targets' && $request->getMethod() === 'GET') {
        return $controller->targets($request);
    }

    if ($route === '/targets' && $request->getMethod() === 'POST') {
        return $controller->saveTargets($request);
    }

    return new \GuzzleHttp\Psr7\Response(404, [], "Not Found");
}

FunctionsFramework::cloudEvent('main_event', 'main_event');
function main_event(CloudEventInterface $event): void
{
    $logger = new \Monolog\Logger('batch');
    $logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Level::Info));
    $logger->info("Starting batch collection process from Cloud Event.");

    $repository = new \App\Repositories\FirestoreRepository();
    $useCase = new \App\UseCases\CollectionUseCase(
        $repository,
        new \App\Services\CrawlerService(),
        new \App\Services\AiParserService($logger),
        $logger
    );

    try {
        $useCase->execute();
        $logger->info("Batch collection process completed successfully.");
    } catch (\Exception $e) {
        $logger->error("Batch collection process failed: " . $e->getMessage());
    }
}
