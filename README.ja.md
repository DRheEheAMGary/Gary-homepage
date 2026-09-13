# 🏠 Gary のホームページ（PHP 版）

[简体中文](README.md) | [繁體中文](README.zh-Hant.md) | [English](README.en.md) | **日本語**

> 元プロジェクト：[DRheEheAMGary/DRheEheAM_Gary-Homepage](https://github.com/DRheEheAMGary/DRheEheAM_Gary-Homepage)

**DRheEheAM_Gary-Homepage**（React 19 + Vite）を **PHP サーバーサイドレンダリング** に
リファクタリングしたバージョンです。

ブラウザ上でのページ構成やインタラクションは React 版と一致しますが、コンテンツは PHP が
直接レンダリングし、認証とチェックインデータは PHP 経由で元の WordPress REST バックエンドへ
プロキシされます。

## ✨ 機能

- 🎨 ネイティブ Canvas のパーティクル背景（tsParticles を置き換え）
- 🌓 ライト／ダークテーマ切替（auto / light / dark、View Transition 対応）
- 📝 歌詞タイプライター演出（歌詞データはサーバーから配信）
- 📊 GitHub コントリビューションヒートマップ（PHP サーバーサイド取得 + ファイルキャッシュ + 独自ツールチップ）
- 📅 毎日チェックイン + 運勢（PHP が WordPress API をプロキシ）
- 🔐 ログイン / 登録（JWT + Cloudflare Turnstile）
- 📱 レスポンシブデザイン
- 🖱️ スクロールスナップ + タブ自動ハイライト + 要素の登場アニメーション

## 🚀 クイックスタート

PHP 7.4+ が動作するサーバーであれば、プロジェクトを Web ルートに置いて `index.php` に
アクセスするだけです：

```bash
php -S localhost:8000
# その後 http://localhost:8000 を開く
```

Nginx / Apache のサイトディレクトリに置いても構いません。

### 動作要件

- PHP >= 7.4、拡張機能：`curl`、`json`、`session`
- `https://blog.dreamgary.cn`（元の WordPress バックエンド）へのネットワーク接続

## 📁 プロジェクト構成

```
Gary-homepage/
├── index.php                 # エントリポイント（ページ全体をサーバーサイドレンダリング）
├── config.php                # サイト / バックエンド / Turnstile 設定
├── data/
│   └── profile.php           # プロフィールデータ（profile.js から変換）
├── includes/
│   ├── functions.php         # 汎用関数、セッション、認証状態
│   ├── wp-client.php         # WordPress REST クライアント + GitHub データ取得
│   ├── header.php            # <head> + トップバー
│   └── footer.php            # 末尾マークアップ + グローバルデータ + スクリプト
├── components/
│   ├── particle-background.php
│   ├── top-bar.php
│   ├── tab-bar.php
│   ├── theme-toggle.php
│   ├── sidebar.php
│   ├── lyrics-typewriter.php
│   ├── daily-checkin.php
│   ├── auth-page.php
│   └── github-contributions.php
├── pages/
│   ├── home.php   links.php   contact.php
│   └── games.php  anime.php   projects.php
├── api/
│   ├── auth.php              # current / login / register / verify-turnstile / logout
│   └── checkin.php           # dates / fortune
├── assets/
│   ├── css/  (colors.css / index.css / app.css)
│   └── js/   (theme / particles / typewriter / navigation / auth / checkin / github-calendar)
├── wordpress-plugin/         # ブログ側アバター API プラグイン
└── cache/                    # GitHub コントリビューションのキャッシュ（自動生成）
```

## 🔧 React 版との対応関係

| React コンポーネント | PHP での対応 |
| --- | --- |
| `App.jsx` | `index.php` + `includes/header.php` / `footer.php` |
| `contexts/AuthContext.jsx` | `includes/functions.php`（セッション）+ `api/auth.php` |
| `api/wordpress.js` | `includes/wp-client.php` + `api/*.php` |
| `components/*.jsx` | `components/*.php` |
| `pages/*.jsx` | `pages/*.php` |
| `data/profile.js` | `data/profile.php` |
| `hooks/useTheme.jsx` | `assets/js/theme.js` |
| `components/ParticleBackground.jsx` | `assets/js/particles.js` |
| その他のインタラクション | `assets/js/*.js` |

## 🧩 WordPress プラグイン（アバター API）

サイトによっては複数のアバタープラグイン（Simple Local Avatars と One User Avatar など）が
併用され、WordPress REST が返すアバターと実際に表示されるアバターが異なることがあります。
本リポジトリは、ユーザー ID から「実際に有効なアバター」を返す小さなプラグインを同梱しています。
ホームページ側はこれを自動的に優先します。

インストール：`wordpress-plugin/gary-avatar-api.php` をブログの
`wp-content/plugins/gary-avatar-api/`（または `wp-content/mu-plugins/`）に配置して有効化するだけです。
設定は不要。エンドポイント：`GET /wp-json/gary/v1/avatar`（要ログイン）。

## 🔐 設定

`config.php` を編集して変更します：

- `WP_BASE`：WordPress REST の URL
- `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET`：Cloudflare Turnstile のキー
- `COOKIE_DOMAIN`：サブドメイン間で共有するログイン Cookie のドメイン
- `CACHE_DIR` / `GITHUB_CACHE_TTL`：コントリビューションキャッシュのディレクトリと有効期間

## 📝 補足

- ログイン状態は PHP セッションに保存し、同時にサブドメイン間共有 Cookie に書き込むことでブログとログインを共有します。
- チェックインデータ：ログインユーザーはクラウド（PHP が WordPress をプロキシ）を利用し、ローカルの `localStorage` をフォールバックキャッシュとして使用します。
- 未ログインユーザーはチェックインできません（元の挙動と同じ）。
