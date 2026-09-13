# 🏠 Gary 個人主頁（PHP 版）

[简体中文](README.md) | **繁體中文** | [English](README.en.md) | [日本語](README.ja.md)

> 原專案：[DRheEheAMGary/DRheEheAM_Gary-Homepage](https://github.com/DRheEheAMGary/DRheEheAM_Gary-Homepage)

由 **DRheEheAM_Gary-Homepage**（React 19 + Vite）重構而來的 **PHP 伺服器端渲染** 版本。

頁面結構與互動在瀏覽器中與 React 版保持一致，但內容由 PHP 直接渲染，
認證與打卡資料則透過 PHP 代理轉發至原本的 WordPress REST 後端。

## ✨ 功能

- 🎨 原生 Canvas 粒子背景（取代 tsParticles）
- 🌓 明暗主題切換（auto / light / dark，支援 View Transition）
- 📝 歌詞打字機效果（歌詞資料由伺服器下發）
- 📊 GitHub 貢獻熱力圖（PHP 伺服器端抓取 + 檔案快取 + 自訂 Tooltip）
- 📅 每日打卡 + 運勢（PHP 代理 WordPress API）
- 🔐 登入 / 註冊（JWT + Cloudflare Turnstile）
- 📱 響應式設計
- 🖱️ 滾動吸附 + Tab 自動高亮 + 元素入場動畫

## 🚀 快速開始

任何支援 PHP 7.4+ 的伺服器，將專案放入 Web 根目錄即可存取 `index.php`：

```bash
php -S localhost:8000
# 然後開啟 http://localhost:8000
```

也可直接放入 Nginx / Apache 網站目錄。

### 環境需求

- PHP >= 7.4，啟用擴充：`curl`、`json`、`session`
- 可連線至 `https://blog.dreamgary.cn`（原本的 WordPress 後端）的網路

## 📁 專案結構

```
Gary-homepage/
├── index.php                 # 入口（伺服器端渲染整個頁面）
├── config.php                # 網站 / 後端 / Turnstile 設定
├── data/
│   └── profile.php           # 個人資料（由 profile.js 轉換）
├── includes/
│   ├── functions.php         # 通用函式、Session、認證狀態
│   ├── wp-client.php         # WordPress REST 客戶端 + GitHub 資料抓取
│   ├── header.php            # <head> + 頂欄
│   └── footer.php            # 收尾 + 全域資料 + 指令碼
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
├── wordpress-plugin/         # 部落格端頭像介面外掛
└── cache/                    # GitHub 貢獻資料快取（自動產生）
```

## 🔧 與 React 版的對應關係

| React 元件 | PHP 對應 |
| --- | --- |
| `App.jsx` | `index.php` + `includes/header.php` / `footer.php` |
| `contexts/AuthContext.jsx` | `includes/functions.php`（Session）+ `api/auth.php` |
| `api/wordpress.js` | `includes/wp-client.php` + `api/*.php` |
| `components/*.jsx` | `components/*.php` |
| `pages/*.jsx` | `pages/*.php` |
| `data/profile.js` | `data/profile.php` |
| `hooks/useTheme.jsx` | `assets/js/theme.js` |
| `components/ParticleBackground.jsx` | `assets/js/particles.js` |
| 其餘互動 | `assets/js/*.js` |

## 🧩 WordPress 外掛（頭像 API）

部分網站的頭像由多個外掛共同作用（如 Simple Local Avatars 與 One User Avatar），
導致 WordPress REST 回傳的頭像並非前台實際顯示的那張。本倉庫提供一個小外掛，
依使用者 ID 回傳「實際生效頭像」，主頁會自動優先使用它。

安裝：將 `wordpress-plugin/gary-avatar-api.php` 放到部落格的
`wp-content/plugins/gary-avatar-api/`（或 `wp-content/mu-plugins/`）並啟用，無需任何設定。
主頁端介面為 `GET /wp-json/gary/v1/avatar`（需登入）。

## 🔐 設定

編輯 `config.php` 修改：

- `WP_BASE`：WordPress REST 位址
- `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET`：Cloudflare Turnstile 金鑰
- `COOKIE_DOMAIN`：跨子網域共用登入 Cookie 的網域
- `CACHE_DIR` / `GITHUB_CACHE_TTL`：貢獻圖快取目錄與有效期限

## 📝 說明

- 登入狀態儲存於 PHP Session，同時寫入跨子網域 Cookie，以便與部落格共用登入。
- 打卡資料：已登入使用者走雲端（PHP 代理 WordPress），本機 `localStorage` 作為備援快取。
- 未登入使用者無法打卡，與原版行為一致。
