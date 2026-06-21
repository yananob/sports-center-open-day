<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;

class CrawlerService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://r.jina.ai/',
            'timeout'  => 30.0,
        ]);
    }

    public function fetchAsMarkdown(string $url): string
    {
        $response = $this->client->get($url);
        return (string) $response->getBody();
    }
}
