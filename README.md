# 🏠 Gary 个人主页（PHP 版）

**简体中文** | [繁體中文](README.zh-Hant.md) | [English](README.en.md) | [日本語](README.ja.md)

> 原项目：[DRheEheAMGary/DRheEheAM_Gary-Homepage](https://github.com/DRheEheAMGary/DRheEheAM_Gary-Homepage)

由 **DRheEheAM_Gary-Homepage**（React 19 + Vite）重构而来的 **PHP 服务端渲染** 版本。

页面结构与交互在浏览器中与 React 版保持一致，但内容由 PHP 直接渲染，
认证与打卡数据通过 PHP 代理转发至原 WordPress REST 后端。

## ✨ 功能

- 🎨 原生 Canvas 粒子背景（替代 tsParticles）
- 🌓 明暗主题切换（auto / light / dark，支持 View Transition）
- 📝 歌词打字机效果（服务端下发歌词数据）
- 📊 GitHub 贡献热力图（PHP 服务端拉取 + 文件缓存 + 自定义 Tooltip）
- 📅 每日打卡 + 运势（PHP 代理 WordPress API）
- 🔐 登录 / 注册（JWT + Cloudflare Turnstile）
- 📱 响应式设计
- 🖱️ 滚动吸附 + Tab 自动高亮 + 元素入场动画

## 🚀 快速开始

任意支持 PHP 7.4+ 的服务器，将项目放入 Web 根目录即可访问 `index.php`：

```bash
php -S localhost:8000
# 然后访问 http://localhost:8000
```

也可直接放入 Nginx / Apache 站点目录。

### 环境要求

- PHP >= 7.4，启用扩展：`curl`、`json`、`session`
- 可访问 `https://blog.dreamgary.cn`（原 WordPress 后端）的网络

## 📁 项目结构

```
Gary-homepage/
├── index.php                 # 入口（服务端渲染整页）
├── config.php                # 站点 / 后端 / Turnstile 配置
├── data/
│   └── profile.php           # 个人信息数据（由 profile.js 转换）
├── includes/
│   ├── functions.php         # 通用函数、会话、认证状态
│   ├── wp-client.php         # WordPress REST 客户端 + GitHub 数据抓取
│   ├── header.php            # <head> + 顶栏
│   └── footer.php            # 收尾 + 全局数据 + 脚本
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
├── wordpress-plugin/         # 博客端头像接口插件
└── cache/                    # GitHub 贡献数据缓存（自动生成）
```

## 🔧 与 React 版的对应关系

| React 组件 | PHP 对应 |
| --- | --- |
| `App.jsx` | `index.php` + `includes/header.php` / `footer.php` |
| `contexts/AuthContext.jsx` | `includes/functions.php`（会话）+ `api/auth.php` |
| `api/wordpress.js` | `includes/wp-client.php` + `api/*.php` |
| `components/*.jsx` | `components/*.php` |
| `pages/*.jsx` | `pages/*.php` |
| `data/profile.js` | `data/profile.php` |
| `hooks/useTheme.jsx` | `assets/js/theme.js` |
| `components/ParticleBackground.jsx` | `assets/js/particles.js` |
| 其余交互 | `assets/js/*.js` |

## 🧩 WordPress 插件（头像接口）

部分站点的头像由多个插件共同作用（如 Simple Local Avatars 与 One User Avatar），
导致 WordPress REST 返回的头像并非前台实际显示的那张。本仓库提供一个小插件，
按用户 ID 返回"实际生效头像"，主页会自动优先使用它。

安装：把 `wordpress-plugin/gary-avatar-api.php` 放到博客的
`wp-content/plugins/gary-avatar-api/` 目录（或 `wp-content/mu-plugins/`）并启用，无需任何配置。
主页端接口为 `GET /wp-json/gary/v1/avatar`（需登录）。

## 🔐 配置

编辑 `config.php` 修改：

- `WP_BASE`：WordPress REST 地址
- `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET`：Cloudflare Turnstile 密钥
- `COOKIE_DOMAIN`：跨子域共享登录 Cookie 的域
- `CACHE_DIR` / `GITHUB_CACHE_TTL`：贡献图缓存目录与有效期

## 📝 说明

- 登录状态保存在 PHP 会话中，同时写入跨子域 Cookie 以与博客共享登录。
- 打卡数据：登录用户走云端（PHP 代理 WordPress），本地 `localStorage` 作为回退缓存。
- 未登录用户无法打卡，与原版行为一致。
