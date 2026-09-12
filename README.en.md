# 🏠 Gary's Homepage (PHP Edition)

[简体中文](README.md) | **English** | [日本語](README.ja.md)

A **PHP server-side rendered** rebuild of **DRheEheAM_Gary-Homepage** (React 19 + Vite).

The page structure and interactions match the React version in the browser, but the
content is rendered directly by PHP. Authentication and check-in data are proxied
through PHP to the original WordPress REST backend.

## ✨ Features

- 🎨 Native Canvas particle background (replaces tsParticles)
- 🌓 Light/dark theme switching (auto / light / dark, with View Transition support)
- 📝 Lyrics typewriter effect (lyrics delivered by the server)
- 📊 GitHub contribution heatmap (fetched server-side by PHP + file cache + custom tooltip)
- 📅 Daily check-in + fortune (PHP proxy to the WordPress API)
- 🔐 Login / Register (JWT + Cloudflare Turnstile)
- 📱 Responsive design
- 🖱️ Scroll snapping + auto-highlighting tabs + element entrance animations

## 🚀 Quick Start

On any server with PHP 7.4+, just place the project in the web root and access `index.php`:

```bash
php -S localhost:8000
# then open http://localhost:8000
```

You can also drop it into an Nginx / Apache site directory.

### Requirements

- PHP >= 7.4 with extensions: `curl`, `json`, `session`
- Network access to `https://blog.dreamgary.cn` (the original WordPress backend)

## 📁 Project Structure

```
Gary-homepage/
├── index.php                 # Entry point (server-side renders the whole page)
├── config.php                # Site / backend / Turnstile configuration
├── data/
│   └── profile.php           # Profile data (converted from profile.js)
├── includes/
│   ├── functions.php         # Helpers, session, auth state
│   ├── wp-client.php         # WordPress REST client + GitHub data fetching
│   ├── header.php            # <head> + top bar
│   └── footer.php            # Closing markup + global data + scripts
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
└── cache/                    # GitHub contribution cache (auto-generated)
```

## 🔧 Mapping to the React Version

| React component | PHP equivalent |
| --- | --- |
| `App.jsx` | `index.php` + `includes/header.php` / `footer.php` |
| `contexts/AuthContext.jsx` | `includes/functions.php` (session) + `api/auth.php` |
| `api/wordpress.js` | `includes/wp-client.php` + `api/*.php` |
| `components/*.jsx` | `components/*.php` |
| `pages/*.jsx` | `pages/*.php` |
| `data/profile.js` | `data/profile.php` |
| `hooks/useTheme.jsx` | `assets/js/theme.js` |
| `components/ParticleBackground.jsx` | `assets/js/particles.js` |
| Other interactions | `assets/js/*.js` |

## 🔐 Configuration

Edit `config.php` to change:

- `WP_BASE`: WordPress REST URL
- `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET`: Cloudflare Turnstile keys
- `COOKIE_DOMAIN`: domain for the cross-subdomain shared login cookie
- `CACHE_DIR` / `GITHUB_CACHE_TTL`: contribution cache directory and lifetime

## 📝 Notes

- Login state is stored in the PHP session and also written to a cross-subdomain cookie to share login with the blog.
- Check-in data: logged-in users use the cloud (PHP proxies WordPress), with local `localStorage` as a fallback cache.
- Guests cannot check in, matching the original behavior.
