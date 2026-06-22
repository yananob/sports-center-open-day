<?php

declare(strict_types=1);

namespace App\Services;

use App\AppConfig;
use App\Models\OpenDaySchedule;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;

class AiParserService
{
    private Client $client;
    private string $apiKey;

    public function __construct(
        private LoggerInterface $logger
    )
    {
        $this->client = new Client([
            'timeout' => 60.0,
        ]);
        $this->apiKey = AppConfig::getOpenAiApiKey();
    }

    /**
     * @return OpenDaySchedule[]
     */
    public function parseSchedules(string $centerId, string $centerName, string $markdown, string $centerUrl): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception("OpenAI API key is not set.");
        }

        $year = Carbon::now()->year;
        $prompt = <<<EOT
あなたはスポーツセンターの開放デー情報を抽出するアシスタントです。
提供されたMarkdown形式のテキストから、開放デーのスケジュール情報を全て抽出してください。
表形式の部分だけでなく、テキスト全体をくまなく解析して、開催予定を漏れなく抽出してください。

出力は以下のJSON形式の配列にしてください。
{
    "schedules": [
        {
            "date": "YYYY-MM-DD",
            "timeSlot": "時間帯（例：09:00～13:00、17:30～21:00。時刻は常に2桁・前ゼロ詰めで）",
            "location": "開催場所",
            "activities": "内容（バドミントン、卓球など）"
        }
    ]
}

※日付は現在の年を想定してください。現在は {$year} 年です。
※Markdown内の情報が「6月13日(土)」のような形式の場合、適切な日付（YYYY-MM-DD）に変換してください。
※「毎週◯曜日」といった記述がある場合は、指定された月の該当するすべての日付を抽出してください。
※「開放DAY開催日の夜」といった記述からも、該当する日程と時間を正確に抽出してください。
※情報が見つからない場合は空の配列を返してください。
※JSON以外のテキストは出力しないでください。

Markdownテキスト:
$markdown
EOT;

        $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-5.4-mini', // ユーザー指定のモデル名
                // 'model' => 'gpt-5.1', // ユーザー指定のモデル名
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object'],
            ],
        ]);

        $body = json_decode((string)$response->getBody(), true);
        $content = $body['choices'][0]['message']['content'] ?? '[]';
        $this->logger->info("AI Parser Response: " . $content);
        $data = json_decode($content, true);

        // キーが入れ子になっている可能性を考慮（json_object指定時）
        if (isset($data['schedules'])) {
            $data = $data['schedules'];
        } elseif (!isset($data[0]) && count($data) > 0) {
            // // 単一のオブジェクトとして返ってきた場合など
            $data = [$data];
        }

        $schedules = [];
        $now = Carbon::now()->toDateTimeString();
        foreach ($data as $item) {
            $schedules[] = new OpenDaySchedule(
                $centerId,
                $centerName,
                $item['date'] ?? '',
                $this->normalizeTimeSlot($item['timeSlot'] ?? ''),
                $item['location'] ?? '',
                $item['activities'] ?? '',
                $now,
                $centerUrl
            );
        }

        return $schedules;
    }

    /**
     * 時刻の値を時間・分ともに2桁、前ゼロ詰めに正規化します。
     * 例：9:00～13:00 -> 09:00～13:00
     */
    private function normalizeTimeSlot(string $timeSlot): string
    {
        return preg_replace_callback('/(\d{1,2}):(\d{2})/', function ($matches) {
            return sprintf('%02d:%02d', (int)$matches[1], (int)$matches[2]);
        }, $timeSlot);
    }
}
