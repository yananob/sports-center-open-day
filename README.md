# sports-center-open-day

スポーツセンターの開放デー情報を自動収集・解析し、ダッシュボード上に一覧表示する Cloud Functions アプリケーションです。

---

## 概要

本システムは、対象となる各スポーツセンターのWebサイトから開放デーの開催情報をWebクローリング (Jina Reader API) 経由で取得し、OpenAI API を用いて開催日・時間帯・場所・内容などの構造化データを抽出します。抽出されたデータは Google Cloud Firestore に保存され、Web画面上で簡単に閲覧・Googleカレンダーへの登録が可能です。

---

## 主な機能

- **開放デー一覧表示（ダッシュボード）**
  - 開催日および時間帯によるソート機能
  - 過去の日程や卓球以外のスポーツ情報をグレーアウト／表示切り替え機能
  - ワンクリックで Google カレンダーに予定を登録するリンク生成
- **手動更新機能**
  - ダッシュボードの「情報を更新」ボタンからWebクローリングとAI解析をオンデマンド実行
- **収集対象スポーツセンター管理機能 (`/targets`)**
  - 収集対象のスポーツセンター（ID、名称、URL）の追加・編集・削除
- **定期バッチ収集 (Cloud Event / Pub/Sub)**
  - `main_event` 関数を通じて定期的に自動データ収集を実行

---

## アーキテクチャとディレクトリ構成

本アプリケーションは Google Cloud Functions (PHP 8.2+) 上で動作し、ドメイン駆動設計 (DDD) の考え方を取り入れたレイヤー構造を採用しています。

```
sports-center-open-day/
├── src/
│   ├── Controllers/
│   │   └── DashboardController.php   # 画面表示・リクエスト処理
│   ├── UseCases/
│   │   └── CollectionUseCase.php     # スケジュール収集の業務ロジック
│   ├── Services/
│   │   ├── CrawlerService.php        # Jina Reader APIによるWebページ取得
│   │   └── AiParserService.php       # OpenAI APIによる情報抽出
│   ├── Repositories/
│   │   └── FirestoreRepository.php   # Firestoreデータベース操作
│   ├── Models/
│   │   ├── OpenDaySchedule.php       # スケジュール情報のドメインモデル
│   │   └── TargetCenter.php          # 収集対象センターのドメインモデル
│   └── AppConfig.php                 # アプリケーション設定管理
├── views/
│   ├── dashboard.blade.php           # メインダッシュボード画面 (BladeOne + Tailwind CSS)
│   └── targets.blade.php             # 収集対象管理画面
├── tests/
│   └── Unit/                         # ユニットテスト (PHPUnit)
├── index.php                         # Cloud Functions エントリポイント
└── README.md
```

---

## 環境変数

動作に必要な主な環境変数は以下の通りです。

| 環境変数名 | 必須 | 説明 |
| :--- | :---: | :--- |
| `APP_ENV` | **必須** | 実行環境の指定 (`production`, `test`, `local`) |
| `OPENAI_KEY_SPORTS_CENTER_OPEN_DAY` | **必須** | スケジュール抽出に使用する OpenAI API キー |
| `JINA_API_KEY` | 任意 | Jina Reader API の API キー (設定時は認証ヘッダーを付与) |

---

## ローカル開発とテスト

### 1. 依存パッケージのインストール

```bash
composer install --ignore-platform-reqs
```

### 2. 静的解析の実行

```bash
vendor/bin/phpstan analyze src/ index.php
```

### 3. ユニットテストの実行

```bash
./vendor/bin/phpunit tests/Unit
```

---

## 実装方針

実装の詳細や規約については [docs/implementation_policy.md](docs/implementation_policy.md) を参照してください。
