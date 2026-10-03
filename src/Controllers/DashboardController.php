<?php

declare(strict_types=1);

namespace App\Controllers;

use App\AppConfig;
use App\Models\OpenDaySchedule;
use App\Models\TargetCenter;
use App\Repositories\FirestoreRepository;
use App\Services\AiParserService;
use App\Services\CrawlerService;
use App\UseCases\CollectionUseCase;
use eftec\bladeone\BladeOne;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * ダッシュボードおよび管理画面のHTTPリクエストを処理するコントローラー。
 */
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

    /**
     * 開放デーのスケジュール一覧画面を表示します。
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $schedules = $this->repository->getAllSchedules();

        // 日付および時間帯で昇順ソート
        usort(
            $schedules,
            fn(OpenDaySchedule $a, OpenDaySchedule $b): int => $a->date <=> $b->date ?: $a->timeSlot <=> $b->timeSlot
        );

        $basePath = AppConfig::getBasePath();

        $html = $this->blade->run("dashboard", [
            'schedules' => $schedules,
            'basePath' => $basePath,
        ]);

        return new Response(200, ['Content-Type' => 'text/html'], $html);
    }

    /**
     * スケジュール情報の更新処理を実行します。
     */
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
        } catch (\Throwable $e) {
            $this->logger->error("Update error: " . $e->getMessage());
            return new Response(500, [], "Internal Server Error: " . $e->getMessage());
        }
    }

    /**
     * 収集対象スポーツセンターの一覧・編集画面を表示します。
     */
    public function targets(ServerRequestInterface $request): ResponseInterface
    {
        $targets = $this->repository->getTargets();
        $basePath = AppConfig::getBasePath();

        $html = $this->blade->run("targets", [
            'targets' => $targets,
            'basePath' => $basePath,
        ]);

        return new Response(200, ['Content-Type' => 'text/html'], $html);
    }

    /**
     * 収集対象スポーツセンターの情報を保存します。
     */
    public function saveTargets(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $targetData = is_array($body) && isset($body['targets']) && is_array($body['targets'])
            ? $body['targets']
            : [];

        $targets = [];
        foreach ($targetData as $data) {
            if (!is_array($data)) {
                continue;
            }
            $id = (string) ($data['id'] ?? '');
            $name = (string) ($data['name'] ?? '');
            $url = (string) ($data['url'] ?? '');

            if ($id === '' || $name === '' || $url === '') {
                continue;
            }
            $targets[] = TargetCenter::fromArray($id, $data);
        }

        try {
            $this->repository->saveTargets($targets);
            $basePath = AppConfig::getBasePath();
            return new Response(302, ['Location' => $basePath . '/targets']);
        } catch (\Throwable $e) {
            $this->logger->error("Save targets error: " . $e->getMessage());
            return new Response(500, [], "Internal Server Error: " . $e->getMessage());
        }
    }
}
