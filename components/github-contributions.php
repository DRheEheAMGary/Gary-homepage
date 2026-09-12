<?php
/**
 * GitHub 贡献热力图
 * 服务端拉取数据并渲染（带文件缓存），前端仅负责 tooltip
 * 依赖：$profile
 */
$profile = $profile ?? profile();
$username = $profile['githubUsername'];
$data = github_contributions($username);
$contributions = $data['contributions'] ?? [];
$total = $data['total']['lastYear'] ?? 0;

/** 构建按周分组的日历 */
$weeks = [];
$monthLabels = [];
if (!empty($contributions)) {
    $map = [];
    foreach ($contributions as $c) {
        $map[$c['date']] = $c;
    }

    $first = new DateTime($contributions[0]['date']);
    $last = new DateTime($contributions[count($contributions) - 1]['date']);
    $cursor = clone $first;
    $cursor->modify('-' . (int) $cursor->format('w') . ' days');

    $current = [];
    $prevMonth = null;
    while ($cursor <= $last) {
        $key = $cursor->format('Y-m-d');
        $entry = $map[$key] ?? null;
        $current[] = [
            'date'  => $key,
            'count' => $entry['count'] ?? 0,
            'level' => $entry['level'] ?? 0,
            'empty' => $entry === null,
        ];

        $month = (int) $cursor->format('n');
        if (count($current) === 1 && $month !== $prevMonth) {
            $monthLabels[] = ['index' => count($weeks), 'label' => $cursor->format('n') . '月'];
            $prevMonth = $month;
        }

        if (count($current) === 7) {
            $weeks[] = $current;
            $current = [];
        }
        $cursor->modify('+1 day');
    }
    if (!empty($current)) {
        while (count($current) < 7) {
            $current[] = ['date' => '', 'count' => 0, 'level' => 0, 'empty' => true];
        }
        $weeks[] = $current;
    }
}
?>
<div class="github-calendar">
  <h3>GitHub 贡献图</h3>
  <?php if (empty($weeks)): ?>
    <p class="gh-calendar-fallback">暂时无法获取贡献数据，请稍后再试。</p>
  <?php else: ?>
    <div class="calendar-wrapper">
      <div class="gh-months" style="--gh-weeks: <?= count($weeks) ?>">
        <?php foreach ($monthLabels as $m): ?>
          <span class="gh-month" style="grid-column: <?= (int) $m['index'] + 1 ?>"><?= e($m['label']) ?></span>
        <?php endforeach; ?>
      </div>
      <div class="gh-calendar-wrap">
        <div class="gh-day-labels">
          <span></span><span>一</span><span></span><span>三</span><span></span><span>五</span><span></span>
        </div>
        <div class="gh-weeks" role="img" aria-label="GitHub 贡献热力图">
          <?php foreach ($weeks as $week): ?>
            <div class="gh-week">
              <?php foreach ($week as $day): ?>
                <span
                  class="gh-day level-<?= (int) $day['level'] ?><?= $day['empty'] ? ' is-empty' : '' ?>"
                  <?php if (!$day['empty']): ?>
                    data-date="<?= e($day['date']) ?>"
                    data-count="<?= (int) $day['count'] ?>"
                  <?php endif; ?>
                ></span>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
  <p class="github-link">
    过去一年共 <strong><?= (int) $total ?></strong> 次贡献 ·
    <a href="https://github.com/<?= e($username) ?>" target="_blank" rel="noopener noreferrer">
      访问我的 GitHub →
    </a>
  </p>
</div>
