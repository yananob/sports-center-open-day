<?php

declare(strict_types=1);

namespace App\Controllers;

use App\AppConfig;
use App\Repositories\FirestoreRepository;
use App\Services\AiParserService;
use App\Services\CrawlerService;
use App\UseCases\CollectionUseCase;
use eftec\bladeone\BladeOne;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class DashboardController
{
    private BladeOne $blade;
    private FirestoreRepository $repository;
    private Logger $logger;

    public function __construct()
    {
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        if (!is_dir($cache)) {
            mkdir($cache, 0777, true);
        }
        $this->blade = new BladeOne($views, $cache, BladeOne::MODE_DEBUG);
        $this->repository = new FirestoreRepository();
        $this->logger = new Logger('dashboard');
        $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $schedules = $this->repository->getAllSchedules();
        $basePath = AppConfig::getBasePath();

        $html = $this->blade->run("dashboard", [
            'schedules' => $schedules,
            'basePath' => $basePath
        ]);

        return new Response(200, ['Content-Type' => 'text/html'], $html);
    }

    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $useCase = new CollectionUseCase(
            $this->repository,
            new CrawlerService(),
            new AiParserService(),
            $this->logger
        );

        try {
            $useCase->execute();
            $basePath = AppConfig::getBasePath();
            return new Response(302, ['Location' => $basePath . '/']);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return new Response(500, [], "Internal Server Error: " . $e->getMessage());
        }
    }
}
