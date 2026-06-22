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

        // 日付と時間帯でソート
        usort($schedules, function ($a, $b) {
            if ($a->date !== $b->date) {
                return strcmp($a->date, $b->date);
            }
            return strcmp($a->timeSlot, $b->timeSlot);
        });

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
            new AiParserService($this->logger),
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

    public function targets(ServerRequestInterface $request): ResponseInterface
    {
        $targets = $this->repository->getTargets();
        $basePath = AppConfig::getBasePath();

        $html = $this->blade->run("targets", [
            'targets' => $targets,
            'basePath' => $basePath
        ]);

        return new Response(200, ['Content-Type' => 'text/html'], $html);
    }

    public function saveTargets(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $targetData = $body['targets'] ?? [];

        $targets = [];
        foreach ($targetData as $data) {
            if (empty($data['id']) || empty($data['name']) || empty($data['url'])) {
                continue;
            }
            $targets[] = \App\Models\TargetCenter::fromArray($data['id'], $data);
        }

        try {
            $this->repository->saveTargets($targets);
            $basePath = AppConfig::getBasePath();
            return new Response(302, ['Location' => $basePath . '/targets']);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return new Response(500, [], "Internal Server Error: " . $e->getMessage());
        }
    }
}
