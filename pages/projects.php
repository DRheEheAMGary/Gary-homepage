<?php
/** 项目页 */
$profile = $profile ?? profile();
?>
<div class="page projects-page">
  <h2>项目 &amp; 动态</h2>

  <div class="project-section">
    <h3><?= fa_icon('fa-github') ?> GitHub 项目</h3>
    <?php foreach ($profile['githubProjects'] as $p): ?>
      <a href="<?= e($p['url']) ?>" target="_blank" rel="noopener noreferrer" class="project-card">
        <div class="project-icon"><?= fa_icon('fa-github') ?></div>
        <div class="project-info">
          <h4><?= e($p['name']) ?></h4>
          <p><?= e($p['desc']) ?></p>
        </div>
        <?= fa_icon('fa-external-link-alt', 'project-arrow') ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php include __DIR__ . '/../components/github-contributions.php'; ?>
</div>
