<?php
session_start();

require_once dirname(__DIR__) . '/config/dbConn.php';
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/helpers.php';

header('Content-Type: application/json');

// Auth check
if (!isLoggedIn() || (!isCreator() && !isAdmin())) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$postId    = (int)($_POST['post_id']    ?? 0);
$newStatus = trim($_POST['new_status'] ?? '');

if ($postId <= 0 || !in_array($newStatus, ['published', 'draft'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

$userId = currentUserId();

try {
    // Verify the post belongs to this creator
    $check = $pdo->prepare("SELECT post_id FROM dbProj_post WHERE post_id = :id AND user_id = :uid");
    $check->execute([':id' => $postId, ':uid' => $userId]);

    if ($check->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Post not found or access denied.']);
        exit;
    }

    $upd = $pdo->prepare("UPDATE dbProj_post SET status = :status WHERE post_id = :id AND user_id = :uid");
    $upd->execute([':status' => $newStatus, ':id' => $postId, ':uid' => $userId]);

    echo json_encode(['success' => true, 'new_status' => $newStatus]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
