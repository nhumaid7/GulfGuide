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
    SELECT p.*, c.name AS country_name
    FROM   dbProj_post p
    LEFT JOIN dbProj_country c ON p.country_id = c.country_id
    WHERE  p.user_id = :uid
    ORDER  BY p.created_at DESC
");
$stmt->execute([':uid' => $userId]);
$postRows = $stmt->fetchAll();
$posts    = array_map([Post::class, 'fromArray'], $postRows);//Each database row gets converted into a Post object

$total     = count($posts);
$published = count(array_filter($posts, fn($p) => $p->isPublished()));
$drafts    = $total - $published;
//here
// Base URL for the AJAX handler (strips /index.php from APP_BASE)
$ajaxBase = rtrim(str_replace('/index.php', '', APP_BASE), '/');

// Session flash
$flashMsg  = $_SESSION['status']      ?? null;
$flashCode = $_SESSION['status_code'] ?? null;
unset($_SESSION['status'], $_SESSION['status_code']);
?>

<!-- SweetAlert2 fallback (loaded here in case index.php doesn't include it yet) -->
<script>
if (typeof Swal === 'undefined') {
    document.write('<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"><\/script>');
}
</script>

<!-- ── Page header ─────────────────────────────────────────────────────────── -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3">
    <h2>My Dashboard</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/">GulfGuide</a></li>
            <li class="breadcrumb-item active" aria-current="page">Creator Dashboard</li>
        </ol>
    </nav>
</div>

<!-- ── Stats cards ─────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card-section p-4 text-center">
            <div class="fw-bold" style="font-size:2rem; color:var(--brand-primary);"><?= $total ?></div>
            <div class="text-muted" style="font-size:var(--font-size-p-s);">Total Posts</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card-section p-4 text-center">
            <div class="fw-bold" style="font-size:2rem; color:var(--semantic-success);"><?= $published ?></div>
            <div class="text-muted" style="font-size:var(--font-size-p-s);">Published</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card-section p-4 text-center">
            <div class="fw-bold" style="font-size:2rem; color:var(--semantic-warning);"><?= $drafts ?></div>
            <div class="text-muted" style="font-size:var(--font-size-p-s);">Drafts</div>
        </div>
    </div>
</div>

<!-- ── Posts table ─────────────────────────────────────────────────────────── -->
<div class="card-section">
    <div class="card-section--header">
        <p class="h5-style mb-0">My Posts</p>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <!-- Status filter tabs (jQuery client-side) -->
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
            <!-- Live search (jQuery) -->
            <input type="text" id="postSearch" class="form-control form-control-sm"
                   placeholder="Search by title…" style="width:200px">
            <!-- New post button -->
            <a href="<?= APP_BASE ?>/creator/create-post" class="btn btn-sm btn-primary">
                <i class="ph ph-plus me-1"></i>New Post
            </a>
        </div>
    </div>
    <hr class="card-section--divider m-0">
    <div class="card-section--body">

        <?php if (empty($posts)): ?>
        <div class="text-center py-5 text-muted">
            <i class="ph ph-article" style="font-size:3rem; opacity:.4;"></i>
            <p class="mt-2 mb-0">No posts yet.
                <a href="<?= APP_BASE ?>/creator/create-post">Create your first post!</a>
            </p>
        </div>

        <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle" id="postsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Country</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="postsBody">
                    <?php foreach ($posts as $i => $post): ?>
                    <tr data-status="<?= $post->getStatus() ?>"
                        data-title="<?= strtolower(htmlspecialchars($post->getTitle())) ?>">
                        <td><?= $post->getPostId() ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($post->getTitle()) ?></td>
                        <td><?= htmlspecialchars($postRows[$i]['country_name'] ?? '—') ?></td>
                        <td>
                            <span class="gulfguide-badge <?= $post->isPublished() ? 'gulfguide-badge--success' : 'gulfguide-badge--warning' ?> gulfguide-badge--rounded"
                                  id="badge-<?= $post->getPostId() ?>">
                                <i class="ph-fill <?= $post->isPublished() ? 'ph-check-circle' : 'ph-clock' ?>"
                                   id="badge-icon-<?= $post->getPostId() ?>"></i>
                                <span id="badge-text-<?= $post->getPostId() ?>"><?= ucfirst($post->getStatus()) ?></span>
                            </span>
                        </td>
                        <td><?= (new DateTime($post->getCreatedAt()))->format('M j, Y') ?></td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center flex-wrap">

                                <!-- AJAX: toggle publish / draft -->
                                <button class="btn btn-sm btn-outline-primary toggle-status-btn"
                                        data-post-id="<?= $post->getPostId() ?>"
                                        data-status="<?= $post->getStatus() ?>"
                                        title="<?= $post->isPublished() ? 'Set to Draft' : 'Publish' ?>">
                                    <i class="ph <?= $post->isPublished() ? 'ph-arrow-counter-clockwise' : 'ph-paper-plane-tilt' ?>"></i>
                                    <span class="d-none d-lg-inline">
                                        <?= $post->isPublished() ? 'Unpublish' : 'Publish' ?>
                                    </span>
                                </button>

                                <!-- View -->
                                <a href="<?= APP_BASE ?>/posts/<?= $post->getPostId() ?>"
                                   class="btn btn-sm btn-outline-secondary" title="View">
                                    <i class="ph ph-eye"></i>
                                </a>

                                <!-- Edit -->
                                <a href="<?= APP_BASE ?>/posts/<?= $post->getPostId() ?>/edit"
                                   class="btn btn-sm btn-outline-dark" title="Edit">
                                    <i class="ph ph-pencil-simple"></i>
                                </a>

                                <!-- Delete (SweetAlert2 confirmation) -->
                                <form method="POST" action="<?= APP_BASE ?>/creator/" class="delete-post-form d-inline">
                                    <input type="hidden" name="action"     value="delete_post">
                                    <input type="hidden" name="post_id"    value="<?= $post->getPostId() ?>">
                                    <input type="hidden" name="post_title" value="<?= htmlspecialchars($post->getTitle()) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="ph ph-trash"></i>
                                    </button>
                                </form>

                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Shown by jQuery when no rows match filter/search -->
        <div id="noResults" class="text-center py-4 text-muted d-none">
            <i class="ph ph-magnifying-glass" style="font-size:2rem; opacity:.4;"></i>
            <p class="mt-1 mb-0">No posts match your search.</p>
        </div>

    </div>
</div>

<!-- ── JavaScript ──────────────────────────────────────────────────────────── -->
<script>
(function ($) {
    'use strict';

    const AJAX_URL = '<?= $ajaxBase ?>/ajax/toggle-post-status.php';

    // ── 1. Status filter tabs (jQuery) ────────────────────────────────────────
    $('#statusFilter .btn').on('click', function () {
        $('#statusFilter .btn').removeClass('active');
        $(this).addClass('active');
        applyFilters();
    });

    // ── 2. Live title search (jQuery) ─────────────────────────────────────────
    $('#postSearch').on('input', applyFilters);

    function applyFilters() {
        const filter = $('#statusFilter .btn.active').data('filter');
        const search = $('#postSearch').val().toLowerCase().trim();
        let visible  = 0;

        $('#postsBody tr').each(function () {
            const rowStatus = $(this).data('status');
            const rowTitle  = $(this).data('title') || '';
            const okFilter  = (filter === 'all') || (rowStatus === filter);
            const okSearch  = (search === '')     || rowTitle.includes(search);

            if (okFilter && okSearch) {
                $(this).show();
                visible++;
            } else {
                $(this).hide();
            }
        });

        $('#noResults').toggleClass('d-none', visible > 0);
    }

    // ── 3. AJAX status toggle ─────────────────────────────────────────────────
    $(document).on('click', '.toggle-status-btn', function () {
        const $btn      = $(this);
        const postId    = $btn.data('post-id');
        const current   = $btn.data('status');
        const newStatus = (current === 'published') ? 'draft' : 'published';

        $btn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm"></span>');

        $.post(AJAX_URL, { post_id: postId, new_status: newStatus }, function (res) {
            if (res.success) {
                const $badge = $('#badge-' + postId);
                const $icon  = $('#badge-icon-' + postId);
                const $text  = $('#badge-text-' + postId);

                $badge.removeClass('gulfguide-badge--success gulfguide-badge--warning');
                if (newStatus === 'published') {
                    $badge.addClass('gulfguide-badge--success');
                    $icon.attr('class', 'ph-fill ph-check-circle');
                } else {
                    $badge.addClass('gulfguide-badge--warning');
                    $icon.attr('class', 'ph-fill ph-clock');
                }
                $text.text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));

                $btn.closest('tr').data('status', newStatus).attr('data-status', newStatus);

                const newIcon  = newStatus === 'published' ? 'ph-arrow-counter-clockwise' : 'ph-paper-plane-tilt';
                const newLabel = newStatus === 'published' ? 'Unpublish' : 'Publish';
                $btn.data('status', newStatus)
                    .attr('title', newLabel)
                    .prop('disabled', false)
                    .html('<i class="ph ' + newIcon + '"></i>'
                        + '<span class="d-none d-lg-inline"> ' + newLabel + '</span>');

                applyFilters();

                Swal.fire({
                    icon: 'success', title: 'Status Updated',
                    text: 'Post is now ' + newStatus + '.',
                    timer: 1800, showConfirmButton: false
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Could not update status.' });
                const origIcon  = current === 'published' ? 'ph-arrow-counter-clockwise' : 'ph-paper-plane-tilt';
                const origLabel = current === 'published' ? 'Unpublish' : 'Publish';
                $btn.prop('disabled', false)
                    .html('<i class="ph ' + origIcon + '"></i>'
                        + '<span class="d-none d-lg-inline"> ' + origLabel + '</span>');
            }
        }, 'json').fail(function () {
            Swal.fire({ icon: 'error', title: 'Network Error', text: 'Request failed. Please try again.' });
            const origIcon  = current === 'published' ? 'ph-arrow-counter-clockwise' : 'ph-paper-plane-tilt';
            const origLabel = current === 'published' ? 'Unpublish' : 'Publish';
            $btn.prop('disabled', false)
                .html('<i class="ph ' + origIcon + '"></i>'
                    + '<span class="d-none d-lg-inline"> ' + origLabel + '</span>');
        });
    });

    // ── 4. Delete confirmation (SweetAlert2 + jQuery) ─────────────────────────
    $(document).on('submit', '.delete-post-form', function (e) {
        e.preventDefault();
        const $form = $(this);
        const title = $form.find('input[name="post_title"]').val();

        Swal.fire({
            title: 'Delete Post',
            text: 'Are you sure you want to delete "' + title + '"? This cannot be undone.',
            icon: 'warning',
            showCancelButton:    true,
            confirmButtonColor:  '#dc3545',
            cancelButtonColor:   '#6c757d',
            confirmButtonText:   'Yes, delete it',
            cancelButtonText:    'Cancel'
        }).then(function (result) {
            if (result.isConfirmed) { $form[0].submit(); }
        });
    });

    // ── 5. Flash message on page load ─────────────────────────────────────────
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
