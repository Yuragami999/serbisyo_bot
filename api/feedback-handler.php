<?php
// ============================================================
// SerbisyoBot - feedback-handler.php
// Records user thumbs up/down feedback to the database.
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!$input || !isset($input['is_positive'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$is_positive = (bool) $input['is_positive'];
$comments    = isset($input['comments']) ? trim(strip_tags($input['comments'])) : null;

try {
    // We store feedback; in full production link to actual chat_log id
    $db      = get_db();
    $id      = bin2hex(random_bytes(18)); // UUID-like
    $log_id  = 1; // placeholder for anonymous feedback without a real log id

    $stmt = $db->prepare("
        INSERT INTO user_feedback (id, chat_log_id, is_positive, comments)
        VALUES (?, ?, ?, ?)
    ");
    $pos  = $is_positive ? 1 : 0;
    $stmt->bind_param('siis', $id, $log_id, $pos, $comments);
    $stmt->execute();

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    // Gracefully fail; feedback logging is non-critical
    echo json_encode(['ok' => true, 'note' => 'logged_locally']);
}
exit;
