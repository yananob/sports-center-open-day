<?php

declare(strict_types=1);

namespace App\Services;

use App\AppConfig;
use GuzzleHttp\Client;

class CrawlerService
{
    private Client $client;

    const BASE_URL = 'https://r.jina.ai/';

    public function __construct(?Client $client = null)
    {
        if ($client !== null) {
            $this->client = $client;
            return;
        }

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36',
        ];

        $jinaApiKey = AppConfig::getJinaApiKey();
        if ($jinaApiKey !== '') {
            $headers['Authorization'] = 'Bearer ' . $jinaApiKey;
        }

        $this->client = new Client([
            'timeout' => 30.0,
            'headers' => $headers,
        ]);
    }

    public function fetchAsMarkdown(string $url): string
    {
        $response = $this->client->get(self::BASE_URL . $url);
        return (string) $response->getBody();
    }
}
