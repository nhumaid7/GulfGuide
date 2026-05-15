<?php
session_start();

require_once dirname(__DIR__) . '/config/dbConn.php';
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/helpers.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Login required.']);
    exit;
}

$postId = (int)($_POST['post_id'] ?? 0);
$type   = trim($_POST['type'] ?? '');

if ($postId <= 0 || !in_array($type, ['like', 'dislike'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

$userId = currentUserId();

try {
    // Check if user already has a reaction on this post
    $check = $pdo->prepare("
        SELECT reaction_id, type
        FROM   dbProj_reaction
        WHERE  post_id = :post_id AND user_id = :user_id
    ");
    $check->execute([':post_id' => $postId, ':user_id' => $userId]);
    $existing = $check->fetch();

    if (!$existing) {
        // No reaction yet → insert new
        $pdo->prepare("
            INSERT INTO dbProj_reaction (post_id, user_id, type)
            VALUES (:post_id, :user_id, :type)
        ")->execute([':post_id' => $postId, ':user_id' => $userId, ':type' => $type]);
        $userReaction = $type;

    } elseif ($existing['type'] === $type) {
        // Same reaction clicked again → remove (toggle off)
        $pdo->prepare("DELETE FROM dbProj_reaction WHERE reaction_id = :id")
            ->execute([':id' => $existing['reaction_id']]);
        $userReaction = null;

    } else {
        // Different reaction → switch
        $pdo->prepare("UPDATE dbProj_reaction SET type = :type WHERE reaction_id = :id")
            ->execute([':type' => $type, ':id' => $existing['reaction_id']]);
        $userReaction = $type;
    }

    // Return updated counts
    $counts = $pdo->prepare("
        SELECT
            SUM(type = 'like')    AS likes,
            SUM(type = 'dislike') AS dislikes
        FROM dbProj_reaction
        WHERE post_id = :post_id
    ");
    $counts->execute([':post_id' => $postId]);
    $result = $counts->fetch();

    echo json_encode([
        'success'       => true,
        'likes'         => (int)($result['likes']    ?? 0),
        'dislikes'      => (int)($result['dislikes'] ?? 0),
        'user_reaction' => $userReaction,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
