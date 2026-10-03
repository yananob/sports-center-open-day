<?php

declare(strict_types=1);

namespace App;

/**
 * アプリケーション環境に基づいて設定値を提供するクラス。
 * 環境は `APP_ENV` 環境変数によって決定されます。
 * サポートされる環境: 'production', 'test', 'local'。
 */
class AppConfig
{
    /**
     * 現在のアプリケーション環境を取得します。
     *
     * @return string 現在の環境 ('production', 'test', 'local' など)。設定されていない場合は空文字列。
     */
    public static function getEnvironment(): string
    {
        return (string) (getenv('APP_ENV') ?: '');
    }

    /**
     * Firestoreのルートコレクション名を取得します。
     *
     * @return string Firestoreコレクション名。
     */
    public static function getFirestoreRootCollection(): string
    {
        return match (self::getEnvironment()) {
            'production' => 'sports-center-open-day',
            'test' => 'sports-center-open-day-test',
            default => 'sports-center-open-day-test',
        };
    }

    /**
     * アプリケーションのベースパスを取得します。
     *
     * @return string ベースパス。
     */
    public static function getBasePath(): string
    {
        return match (self::getEnvironment()) {
            'production' => '/sports-center-open-day',
            'test' => '/sports-center-open-day-test',
            default => '',
        };
    }

    /**
     * OpenAIのAPIキーを取得します。
     *
     * @return string APIキー。設定されていない場合は空文字列。
     */
    public static function getOpenAiApiKey(): string
    {
        return (string) (getenv('OPENAI_KEY_SPORTS_CENTER_OPEN_DAY') ?: '');
    }

    /**
     * JinaのAPIキーを取得します。
     *
     * @return string APIキー。設定されていない場合は空文字列。
     */
    public static function getJinaApiKey(): string
    {
        return (string) (getenv('JINA_API_KEY') ?: '');
    }
}
