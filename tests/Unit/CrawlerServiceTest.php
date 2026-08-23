<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CrawlerService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class CrawlerServiceTest extends TestCase
{
    public function testFetchAsMarkdownWithoutApiKey(): void
    {
        putenv('JINA_API_KEY');

        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(200, [], '# Test Content'),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $crawler = new CrawlerService($client);
        $result = $crawler->fetchAsMarkdown('https://example.com');

        $this->assertSame('# Test Content', $result);
        $this->assertCount(1, $container);
        $request = $container[0]['request'];
        $this->assertSame('https://r.jina.ai/https://example.com', (string) $request->getUri());
        $this->assertFalse($request->hasHeader('Authorization'));
    }

    public function testConstructorSetsAuthorizationHeaderWhenApiKeyIsSet(): void
    {
        putenv('JINA_API_KEY=test_api_key_123');

        try {
            $crawler = new CrawlerService();
            $reflection = new \ReflectionClass($crawler);
            $property = $reflection->getProperty('client');
            $property->setAccessible(true);
            /** @var Client $client */
            $client = $property->getValue($crawler);

            $config = $client->getConfig();
            $this->assertArrayHasKey('headers', $config);
            $this->assertArrayHasKey('Authorization', $config['headers']);
            $this->assertSame('Bearer test_api_key_123', $config['headers']['Authorization']);
        } finally {
            putenv('JINA_API_KEY');
        }
    }
}
