<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\OpenDaySchedule;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class OpenDayScheduleTest extends TestCase
{
    public function testToArray(): void
    {
        $schedule = new OpenDaySchedule(
            'center-1',
            'Center Name',
            '2023-06-13',
            '10:00-11:45',
            'Gym',
            'Badminton',
            '2023-06-01 10:00:00',
            'https://example.com'
        );

        $expected = [
            'centerId' => 'center-1',
            'centerName' => 'Center Name',
            'date' => '2023-06-13',
            'timeSlot' => '10:00-11:45',
            'location' => 'Gym',
            'activities' => 'Badminton',
            'updatedAt' => '2023-06-01 10:00:00',
            'centerUrl' => 'https://example.com',
        ];

        $this->assertEquals($expected, $schedule->toArray());
    }

    public function testGetDayOfWeek(): void
    {
        // 2026-11-01 は日曜日
        $scheduleSun = new OpenDaySchedule('c1', 'Center', '2026-11-01', '09:00～12:00', '場', '卓球', '');
        $this->assertSame('日', $scheduleSun->getDayOfWeek());

        // 2026-11-02 は月曜日
        $scheduleMon = new OpenDaySchedule('c1', 'Center', '2026-11-02', '09:00～12:00', '場', '卓球', '');
        $this->assertSame('月', $scheduleMon->getDayOfWeek());
    }

    public function testIsPastAndIsGray(): void
    {
        $reference = Carbon::parse('2026-11-05 12:00:00');

        $pastTableTennis = new OpenDaySchedule('c1', 'Center', '2026-11-04', '09:00～12:00', '場', '卓球', '');
        $this->assertTrue($pastTableTennis->isPast($reference));
        $this->assertTrue($pastTableTennis->isGray($reference));

        $todayTableTennis = new OpenDaySchedule('c1', 'Center', '2026-11-05', '09:00～12:00', '場', '卓球', '');
        $this->assertFalse($todayTableTennis->isPast($reference));
        $this->assertFalse($todayTableTennis->isGray($reference));

        $futureTableTennis = new OpenDaySchedule('c1', 'Center', '2026-11-06', '09:00～12:00', '場', '卓球、バドミントン', '');
        $this->assertFalse($futureTableTennis->isPast($reference));
        $this->assertTrue($futureTableTennis->hasTableTennis());
        $this->assertFalse($futureTableTennis->isGray($reference));

        $futureBadmintonOnly = new OpenDaySchedule('c1', 'Center', '2026-11-06', '09:00～12:00', '場', 'バドミントンのみ', '');
        $this->assertFalse($futureBadmintonOnly->isPast($reference));
        $this->assertFalse($futureBadmintonOnly->hasTableTennis());
        $this->assertTrue($futureBadmintonOnly->isGray($reference));
    }

    public function testGetGoogleCalendarUrl(): void
    {
        $schedule = new OpenDaySchedule(
            'c1',
            '中央スポーツセンター',
            '2026-11-10',
            '09:00～12:30',
            '第1体育室',
            '卓球',
            '',
            'https://example.com/sports'
        );

        $calendarUrl = $schedule->getGoogleCalendarUrl();
        $this->assertNotNull($calendarUrl);
        $this->assertStringContainsString('action=TEMPLATE', $calendarUrl);
        $this->assertStringContainsString('dates=20261110T090000/20261110T123000', $calendarUrl);
        $this->assertStringContainsString(urlencode('【開放デー】中央スポーツセンター'), $calendarUrl);

        // 不正な timeSlot フォーマット時は null を返す
        $invalidSchedule = new OpenDaySchedule('c1', 'Center', '2026-11-10', '終日', '', '', '');
        $this->assertNull($invalidSchedule->getGoogleCalendarUrl());
    }

    public function testGetSafeCenterUrl(): void
    {
        $validHttp = new OpenDaySchedule('c1', 'C', '2026-11-10', '', '', '', '', 'http://example.com');
        $this->assertSame('http://example.com', $validHttp->getSafeCenterUrl());

        $validHttps = new OpenDaySchedule('c1', 'C', '2026-11-10', '', '', '', '', 'https://example.com/page');
        $this->assertSame('https://example.com/page', $validHttps->getSafeCenterUrl());

        $invalidScheme = new OpenDaySchedule('c1', 'C', '2026-11-10', '', '', '', '', 'javascript:alert(1)');
        $this->assertSame('', $invalidScheme->getSafeCenterUrl());

        $malformedUrl = new OpenDaySchedule('c1', 'C', '2026-11-10', '', '', '', '', 'not-a-valid-url');
        $this->assertSame('', $malformedUrl->getSafeCenterUrl());
    }

    public function testCompare(): void
    {
        $s1 = new OpenDaySchedule('c1', 'C', '2026-11-10', '09:00～12:00', '', '', '');
        $s2 = new OpenDaySchedule('c1', 'C', '2026-11-10', '13:00～17:00', '', '', '');
        $s3 = new OpenDaySchedule('c1', 'C', '2026-11-11', '09:00～12:00', '', '', '');

        $this->assertLessThan(0, OpenDaySchedule::compare($s1, $s2));
        $this->assertGreaterThan(0, OpenDaySchedule::compare($s2, $s1));
        $this->assertLessThan(0, OpenDaySchedule::compare($s1, $s3));
        $this->assertSame(0, OpenDaySchedule::compare($s1, $s1));
    }
}
