# line-sender

LINEメッセージを送信するためのGoogle Cloud Functions (GCF) アプリケーションです。
HTTPリクエスト（GET/POST）を受け取り、指定されたターゲットにメッセージを送信します。

## 機能概要

- **GETリクエスト**: メッセージ送信用のフォームを表示します。
- **POSTリクエスト**: フォームからのデータ、または直接のAPIコールを受け取り、LINEメッセージを送信します。
- **マルチターゲット**: 複数のLINE BotトークンとターゲットIDを管理し、送信先を選択可能です。

## セットアップ

### 1. 依存関係のインストール

```bash
composer install
```

### 2. サブモジュールの初期化

```bash
git submodule update --init --recursive
```

### 3. 設定ファイルの作成

`configs/line.json` を作成し、LINE Botのトークンと送信先IDを設定します。
`tokens` と `target_ids` のキー名を一致させることで、UIからの送信が可能になります。

```json
{
    "tokens": {
        "my_bot": "YOUR_LINE_CHANNEL_ACCESS_TOKEN"
    },
    "target_ids": {
        "my_bot": "YOUR_LINE_USER_OR_GROUP_ID"
    }
}
```

## ローカルでの実行

```bash
composer start
```

デフォルトでは `http://localhost:8080` でサーバーが起動します。

## デプロイ

Google Cloud SDK (gcloud) がセットアップされている環境で、以下のスクリプトを実行します。

```bash
./deploy.sh
```

## テスト

PHPStanによる静的解析を実行します。

```bash
./tests/run_tests.sh
```
