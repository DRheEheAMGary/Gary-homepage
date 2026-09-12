<?php
/**
 * 页面头部：<head> + 顶栏 + 主体开始
 * 依赖：$view（main|auth）、$user、$profile
 */
$profile = $profile ?? profile();
$user = $user ?? current_user();
$view = $view ?? 'main';
?>
<!doctype html>
<html lang="zh-CN">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" href="https://r.dreamgary.cn/images/headround.png" type="image/x-icon" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="keywords" content="DRheEheAM,DRheEheAM_Gary,主页,dreamgary,dreamgary.cn" />
    <meta name="description" content="OIer&Student | 毛怪+原p+mc | 二次元" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Ubuntu:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= e(asset('css/colors.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/index.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <title><?= e(SITE_NAME) ?> - 个人主页</title>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <script>
      // 首屏前应用主题，避免闪烁
      (function () {
        try {
          var saved = localStorage.getItem('theme');
          var valid = ['auto', 'light', 'dark'];
          if (valid.indexOf(saved) === -1) saved = 'auto';
          var dark = saved === 'auto'
            ? window.matchMedia('(prefers-color-scheme: dark)').matches
            : saved === 'dark';
          document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
        } catch (e) {}
      })();
    </script>
  </head>
  <body class="view-<?= e($view) ?>">
    <?php include __DIR__ . '/../components/particle-background.php'; ?>

    <div class="app">
      <?php include __DIR__ . '/../components/top-bar.php'; ?>

      <div class="app-body">
