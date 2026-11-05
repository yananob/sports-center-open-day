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
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * ダッシュボードおよび管理画面のHTTPリクエストを処理するコントローラー。
 */
class DashboardController
{
    private BladeOne $blade;
    private FirestoreRepository $repository;
    private LoggerInterface $logger;

    public function __construct(
        ?FirestoreRepository $repository = null,
        ?BladeOne $blade = null,
        ?LoggerInterface $logger = null
    ) {
        $this->repository = $repository ?? new FirestoreRepository();

        if ($logger !== null) {
            $this->logger = $logger;
        } else {
            $log = new Logger('dashboard');
            $log->pushHandler(new StreamHandler('php://stdout', Logger::INFO));
            $this->logger = $log;
        }

        if ($blade !== null) {
            $this->blade = $blade;
        } else {
            $views = __DIR__ . '/../../views';
            $cache = __DIR__ . '/../../cache';
            if (!is_dir($cache)) {
                mkdir($cache, 0777, true);
            }
            // 本番・テスト環境ではMODE_AUTO（変更検知時のみ再コンパイル）にしてパフォーマンスを向上
            $mode = AppConfig::getEnvironment() === 'local'
                ? BladeOne::MODE_DEBUG
                : BladeOne::MODE_AUTO;
            $this->blade = new BladeOne($views, $cache, $mode);
        }
    }

    /**
     * 開放デーのスケジュール一覧画面を表示します。
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $schedules = $this->repository->getAllSchedules();

        // ドメインモデルの比較ロジックを用いて日付および時間帯で昇順ソート
        usort($schedules, [OpenDaySchedule::class, 'compare']);

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
            $id = trim((string) ($data['id'] ?? ''));
            $name = trim((string) ($data['name'] ?? ''));
            $url = trim((string) ($data['url'] ?? ''));

            if ($id === '' || $name === '' || $url === '') {
                continue;
            }

            // URLの形式検証および安全なスキーム (http/https) のチェック
            if (!$this->isValidHttpUrl($url)) {
                $this->logger->warning("Skipped invalid target URL: {$url} for ID: {$id}");
                continue;
            }

            $targets[] = TargetCenter::fromArray($id, [
                'name' => $name,
                'url' => $url,
            ]);
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

    /**
     * 有効なHTTPまたはHTTPSのURLかどうかを検証します。
     */
    private function isValidHttpUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return $scheme === 'http' || $scheme === 'https';
    }
}
