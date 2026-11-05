<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\DashboardController;
use App\Models\OpenDaySchedule;
use App\Models\TargetCenter;
use App\Repositories\FirestoreRepository;
use eftec\bladeone\BladeOne;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class DashboardControllerTest extends TestCase
{
    public function testIndexSortsSchedulesAndRendersHtml(): void
    {
        $repository = $this->createMock(FirestoreRepository::class);
        $schedules = [
            new OpenDaySchedule('c1', 'Center 1', '2026-11-20', '13:00～15:00', '場', '卓球', ''),
            new OpenDaySchedule('c2', 'Center 2', '2026-11-10', '09:00～12:00', '場', '卓球', ''),
        ];
        $repository->expects($this->once())
            ->method('getAllSchedules')
            ->willReturn($schedules);

        $blade = $this->createMock(BladeOne::class);
        $blade->expects($this->once())
            ->method('run')
            ->with(
                'dashboard',
                $this->callback(function (array $data): bool {
                    $sorted = $data['schedules'];
                    // 2026-11-10 が先に来るべき
                    return count($sorted) === 2 && $sorted[0]->date === '2026-11-10';
                })
            )
            ->willReturn('<html>Rendered Dashboard</html>');

        $controller = new DashboardController($repository, $blade, new NullLogger());
        $request = new ServerRequest('GET', '/');

        $response = $controller->index($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('<html>Rendered Dashboard</html>', (string) $response->getBody());
    }

    public function testSaveTargetsFiltersInvalidUrlsAndSaves(): void
    {
        $repository = $this->createMock(FirestoreRepository::class);
        $repository->expects($this->once())
            ->method('saveTargets')
            ->with($this->callback(function (array $targets): bool {
                // 有効なURLの1件のみ保存されているはず
                return count($targets) === 1
                    && $targets[0] instanceof TargetCenter
                    && $targets[0]->id === 'valid-target'
                    && $targets[0]->url === 'https://example.com/valid';
            }));

        $blade = $this->createMock(BladeOne::class);
        $controller = new DashboardController($repository, $blade, new NullLogger());

        $request = (new ServerRequest('POST', '/targets'))->withParsedBody([
            'targets' => [
                [
                    'id' => 'valid-target',
                    'name' => 'Valid Center',
                    'url' => 'https://example.com/valid',
                ],
                [
                    'id' => 'invalid-scheme',
                    'name' => 'Bad Scheme',
                    'url' => 'javascript:alert(1)',
                ],
                [
                    'id' => 'invalid-url',
                    'name' => 'Bad URL',
                    'url' => 'not a valid url',
                ],
                [
                    'id' => '',
                    'name' => 'Empty ID',
                    'url' => 'https://example.com',
                ],
            ],
        ]);

        $response = $controller->saveTargets($request);

        $this->assertSame(302, $response->getStatusCode());
    }
}
