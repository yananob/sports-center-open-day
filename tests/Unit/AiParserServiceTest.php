<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AiParserService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class AiParserServiceTest extends TestCase
{
    private AiParserService $service;

    protected function setUp(): void
    {
        $this->service = new AiParserService(new NullLogger());
    }

    public function testParseResponseContentWithCleanJson(): void
    {
        $json = json_encode([
            'schedules' => [
                [
                    'date' => '2026-11-15',
                    'timeSlot' => '9:00～12:00',
                    'location' => '体育館',
                    'activities' => '卓球',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $results = $this->service->parseResponseContent($json, 'center1', 'Center One', 'https://example.com');

        $this->assertCount(1, $results);
        $this->assertSame('2026-11-15', $results[0]->date);
        $this->assertSame('09:00～12:00', $results[0]->timeSlot);
        $this->assertSame('体育館', $results[0]->location);
        $this->assertSame('卓球', $results[0]->activities);
        $this->assertSame('https://example.com', $results[0]->centerUrl);
    }

    public function testParseResponseContentWithMarkdownCodeBlock(): void
    {
        $content = "```json\n" . json_encode([
            'schedules' => [
                [
                    'date' => '2026-11-20',
                    'timeSlot' => '13:00～17:00',
                    'location' => '小体育室',
                    'activities' => 'バドミントン',
                ],
            ],
        ]) . "\n```";

        $results = $this->service->parseResponseContent($content, 'center2', 'Center Two', 'https://example.com');

        $this->assertCount(1, $results);
        $this->assertSame('2026-11-20', $results[0]->date);
        $this->assertSame('13:00～17:00', $results[0]->timeSlot);
    }

    public function testParseResponseContentWithSingleObject(): void
    {
        $content = json_encode([
            'date' => '2026-11-22',
            'timeSlot' => '17:30～21:00',
            'location' => '本館',
            'activities' => '卓球',
        ], JSON_THROW_ON_ERROR);

        $results = $this->service->parseResponseContent($content, 'center3', 'Center Three', 'https://example.com');

        $this->assertCount(1, $results);
        $this->assertSame('2026-11-22', $results[0]->date);
    }

    public function testParseResponseContentWithInvalidJsonReturnsEmpty(): void
    {
        $content = "Not a json string";
        $results = $this->service->parseResponseContent($content, 'c', 'C', '');
        $this->assertSame([], $results);
    }

    public function testNormalizeTimeSlot(): void
    {
        $this->assertSame('09:00～13:00', $this->service->normalizeTimeSlot('9:00～13:00'));
        $this->assertSame('09:30～11:45', $this->service->normalizeTimeSlot('9:30～11:45'));
        $this->assertSame('17:00～21:00', $this->service->normalizeTimeSlot('17:00～21:00'));
    }
}
