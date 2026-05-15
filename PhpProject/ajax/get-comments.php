<?php
session_start();

require_once dirname(__DIR__) . '/config/dbConn.php';
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/helpers.php';

// Only creators/admins can use this endpoint
if (!isLoggedIn() || (!isCreator() && !isAdmin())) {
    echo '<p class="text-danger text-center small">Unauthorized.</p>';
    exit;
}

$postId  = (int)($_GET['post_id'] ?? 0);
$ownerId = currentUserId();

if ($postId <= 0) {
    echo '<p class="text-muted text-center small">Invalid request.</p>';
    exit;
}

// Verify the post belongs to this creator
$check = $pdo->prepare("SELECT post_id FROM dbProj_post WHERE post_id = :id AND user_id = :uid");
$check->execute([':id' => $postId, ':uid' => $ownerId]);

if ($check->rowCount() === 0) {
    echo '<p class="text-danger text-center small">Access denied.</p>';
    exit;
}

// Fetch visible comments with the commenter's username
$stmt = $pdo->prepare("
    SELECT cm.comment_id, cm.content, cm.created_at, u.username
    FROM   dbProj_comment cm
    JOIN   dbProj_user    u  ON cm.user_id = u.user_id
    WHERE  cm.post_id    = :post_id
    AND    cm.is_visible = 1
    ORDER  BY cm.created_at DESC
");
$stmt->execute([':post_id' => $postId]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($comments)) {
    echo '<p class="text-muted text-center small py-2">No comments yet.</p>';
    exit;
}

foreach ($comments as $c):
    $date = (new DateTime($c['created_at']))->format('M j, Y · g:i a');
?>
<div class="comment-item">
    <div>
        <span class="comment-item__author"><?= htmlspecialchars($c['username']) ?></span>
        <span class="comment-item__date"><?= $date ?></span>
    </div>
    <div class="comment-item__text"><?= htmlspecialchars($c['content']) ?></div>
</div>
<?php endforeach; ?>
