# karakuri_craft

軽量なモジュラー型 PHP Web サービス基盤です。Web 起動のインストーラで初期構築し、最小コア + モジュールで実行します。

## 日本語

### 現在の実装範囲

- Web 起動インストーラ: `karakuri.php`
- 最小コアランタイム:
  - `core/loader.php`
  - `core/router.php`
  - `core/module_loader.php`
  - `core/helpers.php`
  - `core/security.php`
  - `core/storage.php`
  - `core/contracts/*`（Request/Response/Server/Member/Storage の手引き付き契約）
- セットアップ:
  - 初回管理者作成（`storage/admin.json`）
  - 初期設定保存（`storage/config.json`）
  - モジュール有効化状態保存（`storage/modules.json`）
  - 完了後ロック（`storage/setup.lock`）
- ダッシュボード:
  - ログイン / ログアウト
  - 基本ステータス表示
  - モジュール有効/無効切替
  - 管理者パスワード変更
- サンプルモジュール:
  - `modules/welcome`
  - `/` と `/health` を提供

### クイックスタート

1. PHP が動く環境にこのプロジェクトを配置
2. ブラウザで `karakuri.php` を開く
3. `public/index.php/setup` で初期設定を完了
4. `public/index.php/dashboard/login` へログイン

### 補足

- `storage/` は実行時データ用（Git 管理対象外）
- インストール方式は Web が主、CLI は補助
- モジュール配布は Git 優先、ZIP は互換用途
- 人/AI が最初に読む入口:
  - `core/contracts/*.php`（使える情報の手引き）
  - `modules/welcome/module.php`（利用例）

## English

### Current Scope

- Web-first bootstrap installer: `karakuri.php`
- Minimal core runtime:
  - `core/loader.php`
  - `core/router.php`
  - `core/module_loader.php`
  - `core/helpers.php`
  - `core/security.php`
  - `core/storage.php`
  - `core/contracts/*` (guided contracts for Request/Response/Server/Member/Storage)
- Setup flow:
  - Create first admin (`storage/admin.json`)
  - Save initial config (`storage/config.json`)
  - Save enabled modules (`storage/modules.json`)
  - Lock setup after completion (`storage/setup.lock`)
- Dashboard:
  - Login / logout
  - Basic system summary
  - Module enable/disable manager
  - Admin password update
- Sample module:
  - `modules/welcome`
  - Provides `/` and `/health`

### Quick Start

1. Put this project under your PHP web environment.
2. Open `karakuri.php` in a browser.
3. Complete setup at `public/index.php/setup`.
4. Login at `public/index.php/dashboard/login`.

### Notes

- `storage/` holds runtime data and is ignored by git.
- Install mode policy: Web is primary, CLI is helper mode.
- Module distribution policy: Git-first, ZIP as compatibility path.
- First files to read for human/AI users:
  - `core/contracts/*.php` (guided API surface)
  - `modules/welcome/module.php` (usage example)
