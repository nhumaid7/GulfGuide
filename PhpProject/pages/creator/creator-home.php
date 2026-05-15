<?php
require_once __DIR__ . '/../../classes/post.php';
requireRole(ROLE_CREATOR);

$userId = currentUserId();

// ── Handle delete POST ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_post') {
    $postId = (int)($_POST['post_id'] ?? 0);

    $check = $pdo->prepare("SELECT post_id FROM dbProj_post WHERE post_id = :id AND user_id = :uid");
    $check->execute([':id' => $postId, ':uid' => $userId]);

    if ($check->rowCount() > 0) {
        try {
            $del = $pdo->prepare("DELETE FROM dbProj_post WHERE post_id = :id AND user_id = :uid");
            $del->execute([':id' => $postId, ':uid' => $userId]);
            $_SESSION['status']      = 'Post deleted successfully.';
            $_SESSION['status_code'] = 'success';
        } catch (PDOException $e) {
            $_SESSION['status']      = 'Delete failed: ' . $e->getMessage();
            $_SESSION['status_code'] = 'error';
        }
    } else {
        $_SESSION['status']      = 'You do not have permission to delete this post.';
        $_SESSION['status_code'] = 'error';
    }

    header('Location: ' . APP_BASE . '/creator/');
    exit;
}

// ── Fetch creator's posts ─────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT p.*,
           c.name AS country_name,
           (SELECT COUNT(*) FROM dbProj_reaction r
            WHERE r.post_id = p.post_id AND r.type = 'like')    AS likes_count,
           (SELECT COUNT(*) FROM dbProj_reaction r
            WHERE r.post_id = p.post_id AND r.type = 'dislike') AS dislikes_count,
           (SELECT COUNT(*) FROM dbProj_comment cm
            WHERE cm.post_id = p.post_id AND cm.is_visible = 1) AS comments_count
    FROM   dbProj_post p
    LEFT JOIN dbProj_country c ON p.country_id = c.country_id
    WHERE  p.user_id = :uid
    ORDER  BY p.created_at DESC
");
$stmt->execute([':uid' => $userId]);
$postRows = $stmt->fetchAll();
$posts    = array_map([Post::class, 'fromArray'], $postRows);

$total     = count($posts);
$published = count(array_filter($posts, fn($p) => $p->isPublished()));
$drafts    = $total - $published;

$baseUrl  = rtrim(str_replace('/index.php', '', APP_BASE), '/');
$ajaxBase = $baseUrl;

$flashMsg  = $_SESSION['status']      ?? null;
$flashCode = $_SESSION['status_code'] ?? null;
unset($_SESSION['status'], $_SESSION['status_code']);

function excerptContent(string $content, int $len = 220): string {
    $content = preg_replace('/^\[Traveled in: \d{4}\]\n\n/', '', $content);
    return mb_strlen($content) > $len ? mb_substr($content, 0, $len) . '…' : $content;
}
?>

<!-- SweetAlert2 fallback -->
<script>
if (typeof Swal === 'undefined') {
    document.write('<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"><\/script>');
}
</script>

<style>
/* Page-level layout */
.creator-page { max-width: 1200px; margin: 0 auto; padding: 0 0.5rem 2rem; }
</style>

<!-- ── Hero (full width, outside the container) ────────────────────────────── -->
<div class="creator-hero">
    <div class="creator-hero__overlay"></div>
    <div class="creator-hero__content">
        <h1 class="creator-hero__title">Share your Travel Experience</h1>
        <p class="creator-hero__sub">Let your stories inspire other travellers</p>
    </div>
</div>

<div class="creator-page">

<!-- ── Stats ───────────────────────────────────────────────────────────────── -->
<!-- ── Section header ──────────────────────────────────────────────────────── -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 mt-4">
    <div>
        <h4 class="mb-0 fw-bold">Your Latest Blogs</h4>
        <p class="text-muted mb-0" style="font-size:var(--font-size-p-s);">
            Travellers want to see more reviews of these places
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <div class="btn-group btn-group-sm" id="statusFilter" role="group">
            <button class="btn btn-outline-secondary active" data-filter="all">
                All <span class="badge bg-secondary ms-1"><?= $total ?></span>
            </button>
            <button class="btn btn-outline-success" data-filter="published">
                Published <span class="badge bg-success ms-1"><?= $published ?></span>
            </button>
            <button class="btn btn-outline-warning text-dark" data-filter="draft">
                Draft <span class="badge bg-warning text-dark ms-1"><?= $drafts ?></span>
            </button>
        </div>
        <input type="text" id="postSearch" class="form-control form-control-sm"
               placeholder="Search…" style="width:150px">
        <a href="<?= APP_BASE ?>/creator/create-post" class="btn btn-sm btn-outline-primary fw-semibold">
            <i class="ph ph-pencil-simple me-1"></i>Write New Review
        </a>
    </div>
</div>

<!-- No filter results -->
<div id="noResults" class="text-center py-5 text-muted d-none">
    <i class="ph ph-magnifying-glass" style="font-size:2.5rem;opacity:.35;"></i>
    <p class="mt-2 mb-0">No posts match your search.</p>
</div>

<!-- ── Empty state ─────────────────────────────────────────────────────────── -->
<?php if (empty($posts)): ?>
<div class="text-center py-5 text-muted">
    <i class="ph ph-newspaper" style="font-size:3rem;opacity:.35;"></i>
    <p class="mt-2 mb-1 fw-semibold">No posts yet</p>
    <a href="<?= APP_BASE ?>/creator/create-post" class="btn btn-primary btn-sm mt-1">
        Create your first post
    </a>
</div>

<?php else: ?>

<!-- ── Blog cards ──────────────────────────────────────────────────────────── -->
<div id="postsBody">
<?php foreach ($posts as $i => $post):
    $thumbSrc    = !empty($post->getThumbnail()) ? $baseUrl . '/' . $post->getThumbnail() : null;
    $countryName = htmlspecialchars($postRows[$i]['country_name'] ?? '');
    $postDate    = (new DateTime($post->getCreatedAt()))->format('M j, Y');
    $excerpt     = htmlspecialchars(excerptContent($post->getContent()));
?>
<div class="blog-card mb-3"
     data-status="<?= $post->getStatus() ?>"
     data-title="<?= strtolower(htmlspecialchars($post->getTitle())) ?>">

    <!-- Thumbnail -->
    <div class="blog-card__thumb">
        <?php if ($thumbSrc): ?>
            <img src="<?= $thumbSrc ?>" alt="<?= htmlspecialchars($post->getTitle()) ?>">
        <?php else: ?>
            <div class="blog-card__thumb--placeholder">
                <i class="ph ph-image"></i>
            </div>
        <?php endif; ?>
    </div>

    <!-- Body -->
    <div class="blog-card__body">
        <div class="blog-card__meta">
            <span><?= $countryName ?></span>
            <span class="gulfguide-badge <?= $post->isPublished() ? 'gulfguide-badge--success' : 'gulfguide-badge--warning' ?> gulfguide-badge--rounded ms-2"
                  id="badge-<?= $post->getPostId() ?>">
                <i class="ph-fill <?= $post->isPublished() ? 'ph-check-circle' : 'ph-clock' ?>"
                   id="badge-icon-<?= $post->getPostId() ?>"></i>
                <span id="badge-text-<?= $post->getPostId() ?>"><?= ucfirst($post->getStatus()) ?></span>
            </span>
        </div>

        <h5 class="blog-card__title"><?= htmlspecialchars($post->getTitle()) ?></h5>
        <p class="blog-card__excerpt"><?= $excerpt ?></p>

        <!-- Likes / dislikes / comments row -->
        <div class="blog-card__stats">
            <span title="Likes">
                <i class="ph-fill ph-thumbs-up" style="color:var(--semantic-success);"></i>
                <?= (int)($postRows[$i]['likes_count'] ?? 0) ?>
            </span>
            <span title="Dislikes">
                <i class="ph-fill ph-thumbs-down" style="color:var(--semantic-failure);"></i>
                <?= (int)($postRows[$i]['dislikes_count'] ?? 0) ?>
            </span>
            <button class="btn btn-sm btn-link p-0 view-comments-btn"
                    data-post-id="<?= $post->getPostId() ?>"
                    title="View comments">
                <i class="ph ph-chat-circle-text"></i>
                <?= (int)($postRows[$i]['comments_count'] ?? 0) ?> Comments
            </button>
        </div>

        <div class="blog-card__actions">
            <!-- AJAX toggle -->
            <button class="btn btn-sm btn-outline-primary toggle-status-btn"
                    data-post-id="<?= $post->getPostId() ?>"
                    data-status="<?= $post->getStatus() ?>">
                <i class="ph <?= $post->isPublished() ? 'ph-arrow-counter-clockwise' : 'ph-paper-plane-tilt' ?>"></i>
                <?= $post->isPublished() ? 'Unpublish' : 'Publish' ?>
            </button>

            <a href="<?= APP_BASE ?>/posts/<?= $post->getPostId() ?>/edit"
               class="btn btn-sm btn-outline-dark">
                <i class="ph ph-pencil-simple me-1"></i>Edit
            </a>

            <form method="POST" action="<?= APP_BASE ?>/creator/" class="delete-post-form d-inline">
                <input type="hidden" name="action"     value="delete_post">
                <input type="hidden" name="post_id"    value="<?= $post->getPostId() ?>">
                <input type="hidden" name="post_title" value="<?= htmlspecialchars($post->getTitle()) ?>">
                <button type="submit" class="btn btn-sm btn-danger">
                    <i class="ph ph-trash me-1"></i>Delete
                </button>
            </form>
        </div>

        <div class="text-muted mt-1" style="font-size:var(--font-size-p-xs);"><?= $postDate ?></div>

        <!-- Comments panel (loaded via AJAX) -->
        <div class="comments-panel d-none" id="comments-panel-<?= $post->getPostId() ?>">
            <hr class="my-2">
            <div class="comments-body" id="comments-body-<?= $post->getPostId() ?>">
                <div class="text-center py-2">
                    <span class="spinner-border spinner-border-sm text-secondary"></span>
                </div>
            </div>
        </div>
    </div>

</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

</div><!-- /.creator-page -->

<!-- ── External CSS ────────────────────────────────────────────────────────── -->
<link rel="stylesheet" href="<?= $base_prefix ?>assets/css/creator-home.css">

<!-- ── JS config (PHP values passed to external JS file) ──────────────────── -->
<script>
const CREATOR_CONFIG = {
    ajaxUrl:     '<?= $ajaxBase ?>/ajax/toggle-post-status.php',
    commentsUrl: '<?= $ajaxBase ?>/ajax/get-comments.php',
    flash: <?= $flashMsg
        ? json_encode(['msg' => $flashMsg, 'code' => $flashCode])
        : 'null' ?>
};
</script>

<!-- ── External JS ─────────────────────────────────────────────────────────── -->
<script src="<?= $base_prefix ?>assets/js/creator-home.js"></script>
