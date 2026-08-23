<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\TargetCenter;
use App\Repositories\FirestoreRepository;
use App\Services\AiParserService;
use App\Services\CrawlerService;
use App\UseCases\CollectionUseCase;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class CollectionUseCaseTest extends TestCase
{
    public function testExecuteThrowsExceptionWhenCrawlerFails(): void
    {
        $repository = $this->createMock(FirestoreRepository::class);
        $target = TargetCenter::fromArray('target1', [
            'id' => 'target1',
            'name' => 'Test Center',
            'url' => 'https://example.com/target1'
        ]);
        $repository->expects($this->once())
            ->method('getTargets')
            ->willReturn([$target]);

        $crawler = $this->createMock(CrawlerService::class);
        $crawler->expects($this->once())
            ->method('fetchAsMarkdown')
            ->with('https://example.com/target1')
            ->willThrowException(new \RuntimeException('403 Forbidden'));

        $aiParser = $this->createMock(AiParserService::class);
        $logger = new NullLogger();

        $useCase = new CollectionUseCase($repository, $crawler, $aiParser, $logger);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('403 Forbidden');

        $useCase->execute();
    }
}
