<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\OpenDaySchedule;
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
            '2023-06-01 10:00:00'
        );

        $expected = [
            'centerId' => 'center-1',
            'centerName' => 'Center Name',
            'date' => '2023-06-13',
            'timeSlot' => '10:00-11:45',
            'location' => 'Gym',
            'activities' => 'Badminton',
            'updatedAt' => '2023-06-01 10:00:00',
        ];

        $this->assertEquals($expected, $schedule->toArray());
    }
}
