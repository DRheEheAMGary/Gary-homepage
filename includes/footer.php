<?php
/**
 * 页面尾部：关闭布局 + 全局数据 + 脚本
 * 依赖：$view、$profile
 */
$profile = $profile ?? profile();
$view = $view ?? 'main';
?>
      </div><!-- /.app-body -->

      <button
        class="back-to-top"
        id="back-to-top"
        title="回到顶部"
        aria-label="回到顶部"
      ><?= fa_icon('fa-arrow-up') ?></button>
    </div><!-- /.app -->

    <script>
      window.GARY = {
        view: <?= json_encode($view, JSON_UNESCAPED_UNICODE) ?>,
        baseUrl: <?= json_encode(base_url(''), JSON_UNESCAPED_UNICODE) ?>,
        apiBase: <?= json_encode(base_url('api/'), JSON_UNESCAPED_UNICODE) ?>,
        loggedIn: <?= $user ? 'true' : 'false' ?>,
        user: <?= json_encode($user, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        lyrics: <?= json_encode($profile['lyrics'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        turnstileSiteKey: <?= json_encode(TURNSTILE_SITE_KEY, JSON_UNESCAPED_UNICODE) ?>
      };
    </script>
    <script src="<?= e(asset('js/theme.js')) ?>"></script>
    <script src="<?= e(asset('js/particles.js')) ?>"></script>
    <script src="<?= e(asset('js/typewriter.js')) ?>"></script>
    <script src="<?= e(asset('js/navigation.js')) ?>"></script>
    <script src="<?= e(asset('js/auth.js')) ?>"></script>
    <script src="<?= e(asset('js/checkin.js')) ?>"></script>
    <script src="<?= e(asset('js/github-calendar.js')) ?>"></script>
  </body>
</html>
