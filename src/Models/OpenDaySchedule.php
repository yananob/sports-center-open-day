<?php

declare(strict_types=1);

namespace App\Models;

class OpenDaySchedule
{
    public function __construct(
        public readonly string $centerId,
        public readonly string $centerName,
        public readonly string $date,
        public readonly string $timeSlot,
        public readonly string $location,
        public readonly string $activities,
        public readonly string $updatedAt,
        public readonly string $centerUrl = ''
    ) {}

    public function toArray(): array
    {
        return [
            'centerId' => $this->centerId,
            'centerName' => $this->centerName,
            'date' => $this->date,
            'timeSlot' => $this->timeSlot,
            'location' => $this->location,
            'activities' => $this->activities,
            'updatedAt' => $this->updatedAt,
            'centerUrl' => $this->centerUrl,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['centerId'] ?? '',
            $data['centerName'] ?? '',
            $data['date'] ?? '',
            $data['timeSlot'] ?? '',
            $data['location'] ?? '',
            $data['activities'] ?? '',
            $data['updatedAt'] ?? '',
            $data['centerUrl'] ?? ''
        );
    }
}
