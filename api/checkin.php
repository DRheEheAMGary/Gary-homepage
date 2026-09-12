<?php
/**
 * 打卡 API（需登录）
 * GET  ?action=dates                     获取所有打卡日期
 * POST ?action=dates    {date}           添加打卡日期
 * GET  ?action=fortune                   获取今日运势
 * POST ?action=fortune  {value,luck}     保存今日运势
 */

require __DIR__ . '/../includes/functions.php';

// 先触发会话恢复（可能来自跨子域 Cookie），再读取 token
if (!is_logged_in()) {
    json_out(['message' => '未登录'], 401);
}
$token = current_token();
if (empty($token)) {
    json_out(['message' => '未登录'], 401);
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch ($action) {

    case 'dates':
        if ($method === 'POST') {
            $in = json_input();
            $date = trim((string) ($in['date'] ?? ''));
            if ($date === '') {
                json_out(['message' => '缺少日期'], 400);
            }
            list($ok, $data, $status) = wp_request('POST', CHECKIN_DATES_PATH, ['date' => $date], $token);
            if (!$ok) {
                json_out(['message' => $data['message'] ?? '保存失败'], $status ?: 500);
            }
            json_out($data);
        }

        list($ok, $data, $status) = wp_request('GET', CHECKIN_DATES_PATH, null, $token);
        if (!$ok) {
            json_out(['message' => $data['message'] ?? '获取失败'], $status ?: 500);
        }
        json_out($data);

    case 'fortune':
        if ($method === 'POST') {
            $in = json_input();
            $fortune = [
                'value' => isset($in['value']) ? (int) $in['value'] : 0,
                'luck'  => (string) ($in['luck'] ?? ''),
            ];
            list($ok, $data, $status) = wp_request('POST', CHECKIN_FORTUNE_PATH, $fortune, $token);
            if (!$ok) {
                json_out(['message' => $data['message'] ?? '保存失败'], $status ?: 500);
            }
            json_out($data);
        }

        list($ok, $data, $status) = wp_request('GET', CHECKIN_FORTUNE_PATH, null, $token);
        if (!$ok) {
            json_out(['message' => $data['message'] ?? '获取失败'], $status ?: 500);
        }
        json_out($data);

    default:
        json_out(['message' => '未知操作'], 404);
}
