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

<!-- ── Scoped styles ────────────────────────────────────────────────────────── -->
<style>
.creator-hero {
    position: relative;
    min-height: 220px;
    overflow: hidden;
    background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
    display: flex;
    align-items: center;
}
.creator-hero__overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,.35);
}
.creator-hero__content {
    position: relative;
    z-index: 1;
    padding: 2.5rem 2rem;
    color: #fff;
}
.creator-hero__title {
    font-size: clamp(1.4rem, 4vw, 2.2rem);
    font-weight: 800;
    margin-bottom: .4rem;
}
.creator-hero__sub {
    opacity: .8;
    margin-bottom: 1.2rem;
    font-size: var(--font-size-p-m);
}
.blog-card {
    display: flex;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--light-grey-200);
    background: #fff;
    transition: box-shadow .2s;
}
.blog-card:hover { box-shadow: var(--shadow-medium); }
.blog-card__thumb {
    flex-shrink: 0;
    width: 200px;
    min-height: 170px;
    overflow: hidden;
}
.blog-card__thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.blog-card__thumb--placeholder {
    width: 100%;
    height: 100%;
    min-height: 170px;
    background: var(--light-grey-100);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--light-grey-600);
}
.blog-card__body {
    flex: 1;
    padding: 1rem 1.25rem;
    display: flex;
    flex-direction: column;
    gap: .35rem;
}
.blog-card__meta {
    font-size: var(--font-size-p-xs);
    color: var(--text-secondary);
    display: flex;
    align-items: center;
}
.blog-card__title {
    font-size: var(--font-size-h6);
    font-weight: 700;
    margin: 0;
    color: var(--text-primary);
}
.blog-card__excerpt {
    font-size: var(--font-size-p-s);
    color: var(--text-secondary);
    margin: 0;
    line-height: 1.6;
    flex: 1;
}
.blog-card__actions {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
    padding-top: .5rem;
}
.blog-card__stats {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: var(--font-size-p-xs);
    color: var(--text-secondary);
    padding-top: .4rem;
}
.blog-card__stats span {
    display: flex;
    align-items: center;
    gap: .3rem;
}
.comments-panel {
    font-size: var(--font-size-p-s);
}
.comment-item {
    padding: .5rem 0;
    border-bottom: 1px solid var(--light-grey-100);
}
.comment-item:last-child { border-bottom: none; }
.comment-item__author {
    font-weight: 700;
    font-size: var(--font-size-p-xs);
    color: var(--text-primary);
}
.comment-item__date {
    font-size: var(--font-size-p-xs);
    color: var(--text-secondary);
    margin-left: .5rem;
}
.comment-item__text { margin-top: .2rem; }
@media (max-width: 576px) {
    .blog-card { flex-direction: column; }
    .blog-card__thumb { width: 100%; min-height: 180px; }
}
</style>

<!-- ── JavaScript ──────────────────────────────────────────────────────────── -->
<script>
(function ($) {
    'use strict';

    const AJAX_URL = '<?= $ajaxBase ?>/ajax/toggle-post-status.php';

    // 1. Filter tabs
    $('#statusFilter .btn').on('click', function () {
        $('#statusFilter .btn').removeClass('active');
        $(this).addClass('active');
        applyFilters();
    });

    // 2. Live search
    $('#postSearch').on('input', applyFilters);

    function applyFilters() {
        const filter = $('#statusFilter .btn.active').data('filter');
        const search = $('#postSearch').val().toLowerCase().trim();
        let visible  = 0;

        $('#postsBody .blog-card').each(function () {
            const ok = ((filter === 'all') || ($(this).data('status') === filter))
                    && ((search === '')     || ($(this).data('title') || '').includes(search));
            $(this).toggle(ok);
            if (ok) visible++;
        });

        $('#noResults').toggleClass('d-none', visible > 0);
    }

    // Recount all cards by data-status and update filter button badges
    function updateFilterCounts() {
        const total     = $('#postsBody .blog-card').length;
        const published = $('#postsBody .blog-card[data-status="published"]').length;
        const drafts    = $('#postsBody .blog-card[data-status="draft"]').length;

        $('[data-filter="all"]      .badge').text(total);
        $('[data-filter="published"] .badge').text(published);
        $('[data-filter="draft"]     .badge').text(drafts);
    }

    // 3. AJAX toggle status
    $(document).on('click', '.toggle-status-btn', function () {
        const $btn      = $(this);
        const postId    = $btn.data('post-id');
        const current   = $btn.data('status');
        const newStatus = current === 'published' ? 'draft' : 'published';

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.post(AJAX_URL, { post_id: postId, new_status: newStatus }, function (res) {
            if (res.success) {
                const $badge = $('#badge-' + postId);
                $badge.removeClass('gulfguide-badge--success gulfguide-badge--warning')
                      .addClass(newStatus === 'published' ? 'gulfguide-badge--success' : 'gulfguide-badge--warning');
                $('#badge-icon-' + postId).attr('class', 'ph-fill ' + (newStatus === 'published' ? 'ph-check-circle' : 'ph-clock'));
                $('#badge-text-' + postId).text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));

                $btn.closest('.blog-card').data('status', newStatus).attr('data-status', newStatus);

                const newIcon  = newStatus === 'published' ? 'ph-arrow-counter-clockwise' : 'ph-paper-plane-tilt';
                const newLabel = newStatus === 'published' ? 'Unpublish' : 'Publish';
                $btn.data('status', newStatus).prop('disabled', false)
                    .html('<i class="ph ' + newIcon + '"></i> ' + newLabel);

                applyFilters();
                updateFilterCounts();
                Swal.fire({ icon:'success', title:'Updated', text:'Post is now ' + newStatus + '.', timer:1600, showConfirmButton:false });
            } else {
                Swal.fire({ icon:'error', title:'Error', text: res.message || 'Could not update.' });
                const origIcon  = current === 'published' ? 'ph-arrow-counter-clockwise' : 'ph-paper-plane-tilt';
                const origLabel = current === 'published' ? 'Unpublish' : 'Publish';
                $btn.prop('disabled', false).html('<i class="ph ' + origIcon + '"></i> ' + origLabel);
            }
        }, 'json').fail(function () {
            Swal.fire({ icon:'error', title:'Network Error', text:'Please try again.' });
            const origIcon  = current === 'published' ? 'ph-arrow-counter-clockwise' : 'ph-paper-plane-tilt';
            const origLabel = current === 'published' ? 'Unpublish' : 'Publish';
            $btn.prop('disabled', false).html('<i class="ph ' + origIcon + '"></i> ' + origLabel);
        });
    });

    // 4. View comments (AJAX)
    $(document).on('click', '.view-comments-btn', function () {
        const $btn    = $(this);
        const postId  = $btn.data('post-id');
        const $panel  = $('#comments-panel-' + postId);
        const $body   = $('#comments-body-'  + postId);

        if ($panel.hasClass('d-none')) {
            // Open panel — load comments if not already loaded
            $panel.removeClass('d-none');
            if (!$panel.data('loaded')) {
                $.get('<?= $ajaxBase ?>/ajax/get-comments.php', { post_id: postId }, function (html) {
                    $body.html(html);
                    $panel.data('loaded', true);
                }).fail(function () {
                    $body.html('<p class="text-danger text-center">Could not load comments.</p>');
                });
            }
        } else {
            // Close panel
            $panel.addClass('d-none');
        }
    });

    // 5. Delete confirmation
    $(document).on('submit', '.delete-post-form', function (e) {
        e.preventDefault();
        const $form = $(this);
        const title = $form.find('input[name="post_title"]').val();
        Swal.fire({
            title: 'Delete Post',
            text: 'Are you sure you want to delete "' + title + '"?',
            icon: 'warning',
            showCancelButton:   true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor:  '#6c757d',
            confirmButtonText:  'Yes, delete it',
            cancelButtonText:   'Cancel'
        }).then(function (r) { if (r.isConfirmed) $form[0].submit(); });
    });

    // 5. Flash message
    <?php if ($flashMsg): ?>
    $(function () {
        Swal.fire({
            icon:  '<?= $flashCode === 'success' ? 'success' : 'error' ?>',
            title: '<?= $flashCode === 'success' ? 'Success' : 'Error' ?>',
            text:  '<?= addslashes((string)$flashMsg) ?>',
            timer: 2500, showConfirmButton: false
        });
    });
    <?php endif; ?>

})(jQuery);
</script>
