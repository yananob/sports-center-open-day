<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;

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
     * 日付の Carbon インスタンスを取得します。パース失敗時は現在時刻を返します。
     */
    public function getCarbonDate(): CarbonInterface
    {
        try {
            return Carbon::parse($this->date);
        } catch (\Throwable) {
            return Carbon::now();
        }
    }

    /**
     * 日本語の曜日（例: "日", "月", "火" ...）を取得します。
     */
    public function getDayOfWeek(): string
    {
        $days = ['日', '月', '火', '水', '木', '金', '土'];
        $carbon = $this->getCarbonDate();
        return $days[$carbon->dayOfWeek] ?? '';
    }

    /**
     * 指定された日時（省略時は現在）と比較して、過去の日付かどうかを判定します（当日は過去とみなさない）。
     */
    public function isPast(?CarbonInterface $reference = null): bool
    {
        $target = $this->getCarbonDate();
        $ref = $reference ?? Carbon::now();

        return $target->isBefore($ref->copy()->startOfDay());
    }

    /**
     * 内容に卓球が含まれているかどうかを判定します。
     */
    public function hasTableTennis(): bool
    {
        return mb_strpos($this->activities, '卓球') !== false;
    }

    /**
     * ダッシュボード上でグレーアウト対象かどうかを判定します（過去日、または卓球を含まない場合）。
     */
    public function isGray(?CarbonInterface $reference = null): bool
    {
        return $this->isPast($reference) || !$this->hasTableTennis();
    }

    /**
     * Google カレンダー登録用 URL を生成します。時間帯から開始・終了時刻を抽出できない場合は null を返します。
     */
    public function getGoogleCalendarUrl(): ?string
    {
        // 全角チルダ・ハイフン・長音記号・波ダッシュなどに幅広くマッチ
        if (!preg_match('/(\d{2}):(\d{2})[\x{ff5e}~\x{ff0d}\-\x{30fc}\s]+(\d{2}):(\d{2})/u', $this->timeSlot, $matches)) {
            return null;
        }

        $dateStr = str_replace('-', '', $this->date);
        $startTime = $dateStr . 'T' . $matches[1] . $matches[2] . '00';
        $endTime = $dateStr . 'T' . $matches[3] . $matches[4] . '00';

        $details = "内容: " . $this->activities;
        if ($this->centerUrl !== '') {
            $details .= "\nURL: " . $this->centerUrl;
        }

        $location = trim($this->centerName . " " . $this->location);

        return "https://www.google.com/calendar/render?action=TEMPLATE"
            . "&text=" . urlencode("【開放デー】" . $this->centerName)
            . "&dates=" . $startTime . "/" . $endTime
            . "&details=" . urlencode($details)
            . "&location=" . urlencode($location);
    }

    /**
     * 安全なセンターWebサイトURLを取得します（http/httpsのみ許可）。
     */
    public function getSafeCenterUrl(): string
    {
        if (filter_var($this->centerUrl, FILTER_VALIDATE_URL) === false) {
            return '';
        }

        $scheme = strtolower((string) parse_url($this->centerUrl, PHP_URL_SCHEME));
        if ($scheme !== 'http' && $scheme !== 'https') {
            return '';
        }

        return $this->centerUrl;
    }

    /**
     * ソート用の比較メソッド（日付昇順、続いて時間帯昇順）。
     */
    public static function compare(self $a, self $b): int
    {
        return ($a->date <=> $b->date) ?: ($a->timeSlot <=> $b->timeSlot);
    }

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
