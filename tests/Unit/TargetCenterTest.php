<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\TargetCenter;
use PHPUnit\Framework\TestCase;

class TargetCenterTest extends TestCase
{
    public function testFromArray(): void
    {
        $id = 'test-id';
        $data = [
            'name' => 'Test Center',
            'url' => 'https://example.com'
        ];

        $target = TargetCenter::fromArray($id, $data);

        $this->assertEquals($id, $target->id);
        $this->assertEquals($data['name'], $target->name);
        $this->assertEquals($data['url'], $target->url);
    }
}
