<?php

declare(strict_types=1);

namespace App\Models;

/**
 * 開放デーのスケジュール情報を表すドメインモデル。
 */
class OpenDaySchedule
{
    /**
     * @param string $centerId スポーツセンターID
     * @param string $centerName スポーツセンター名称
     * @param string $date 開催日付 (YYYY-MM-DD)
     * @param string $timeSlot 時間帯 (例: 09:00～13:00)
     * @param string $location 開催場所
     * @param string $activities 内容・競技 (例: バドミントン、卓球)
     * @param string $updatedAt 最終更新日時
     * @param string $centerUrl スポーツセンターのWebサイトURL
     */
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

    /**
     * 配列形式へ変換します。
     *
     * @return array<string, string>
     */
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

    /**
     * 配列データからモデルインスタンスを生成します。
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['centerId'] ?? ''),
            (string) ($data['centerName'] ?? ''),
            (string) ($data['date'] ?? ''),
            (string) ($data['timeSlot'] ?? ''),
            (string) ($data['location'] ?? ''),
            (string) ($data['activities'] ?? ''),
            (string) ($data['updatedAt'] ?? ''),
            (string) ($data['centerUrl'] ?? '')
        );
    }
}
