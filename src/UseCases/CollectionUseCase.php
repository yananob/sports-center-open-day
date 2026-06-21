<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Repositories\FirestoreRepository;
use App\Services\AiParserService;
use App\Services\CrawlerService;
use Psr\Log\LoggerInterface;

class CollectionUseCase
{
    public function __construct(
        private FirestoreRepository $repository,
        private CrawlerService $crawler,
        private AiParserService $aiParser,
        private LoggerInterface $logger
    ) {}

    public function execute(): void
    {
        $this->logger->info("Starting schedule collection process.");

        $targets = $this->repository->getTargets();
        $this->logger->info(sprintf("Found %d targets.", count($targets)));

        foreach ($targets as $target) {
            try {
                $this->logger->info(sprintf("Processing target: %s (%s)", $target->name, $target->url));

                $markdown = $this->crawler->fetchAsMarkdown($target->url);
                $schedules = $this->aiParser->parseSchedules($target->id, $target->name, $markdown, $target->url);
                $this->logger->info(sprintf("Extracted %d schedules for %s.", count($schedules), $target->name));

                $this->repository->saveSchedules($target->id, $schedules);

                $this->logger->info(sprintf("Successfully saved %d schedules for %s.", count($schedules), $target->name));
            } catch (\Exception $e) {
                $this->logger->error(sprintf("Error processing target %s: %s", $target->name, $e->getMessage()));
            }
        }

        $this->logger->info("Schedule collection process completed.");
    }
}
