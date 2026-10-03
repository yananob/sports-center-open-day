<?php

declare(strict_types=1);

namespace App\Models;

/**
 * 収集対象のスポーツセンター情報を表すドメインモデル。
 */
class TargetCenter
{
    /**
     * @param string $id スポーツセンターID
     * @param string $name スポーツセンター名称
     * @param string $url スケジュールが掲載されているWebサイトURL
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $url
    ) {}

    /**
     * IDおよび連想配列データからモデルインスタンスを生成します。
     *
     * @param string $id
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(string $id, array $data): self
    {
        return new self(
            $id,
            (string) ($data['name'] ?? ''),
            (string) ($data['url'] ?? '')
        );
    }
}
