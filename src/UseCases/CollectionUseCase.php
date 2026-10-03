<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Repositories\FirestoreRepository;
use App\Services\AiParserService;
use App\Services\CrawlerService;
use Psr\Log\LoggerInterface;

/**
 * スポーツセンターの開放デー情報をWebクローリングし、AIで解析・抽出して保存するユースケース。
 */
class CollectionUseCase
{
    public function __construct(
        private FirestoreRepository $repository,
        private CrawlerService $crawler,
        private AiParserService $aiParser,
        private LoggerInterface $logger
    ) {}

    /**
     * スケジュール情報の収集・解析・保存プロセスを実行します。
     *
     * @throws \Throwable エラー発生時（呼び出し元へ例外を伝搬）
     */
    public function execute(): void
    {
        $this->logger->info("Starting schedule collection process.");

        $targets = $this->repository->getTargets();
        $this->logger->info(sprintf("Found %d targets.", count($targets)));

        foreach ($targets as $target) {
            $this->logger->info(sprintf("Processing target: %s (%s)", $target->name, $target->url));

            $markdown = $this->crawler->fetchAsMarkdown($target->url);
            $schedules = $this->aiParser->parseSchedules($target->id, $target->name, $markdown, $target->url);
            $this->logger->info(sprintf("Extracted %d schedules for %s.", count($schedules), $target->name));

            $this->repository->saveSchedules($target->id, $schedules);

            $this->logger->info(sprintf("Successfully saved %d schedules for %s.", count($schedules), $target->name));
        }

        $this->logger->info("Schedule collection process completed.");
    }
}
