<?php
$totalUsers = 0;
$totalPosts = 0;
$totalCreators = 0;

try {
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM dbProj_user")->fetchColumn();
} catch (Throwable $e) {
    $totalUsers = 0;
}

try {
    $totalPosts = $pdo->query("SELECT COUNT(*) FROM dbProj_post")->fetchColumn();
} catch (Throwable $e) {
    $totalPosts = 0;
}

try {
    $creatorRole = defined('ROLE_CREATOR') ? ROLE_CREATOR : 'creator';

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM dbProj_user WHERE role = ?");
    $stmt->execute([$creatorRole]);
    $totalCreators = $stmt->fetchColumn();
} catch (Throwable $e) {
    $totalCreators = 0;
}

$latestPosts = [];

try {
    $stmt = $pdo->query("
        SELECT 
            p.*,
            u.username AS author_username,
            u.role AS author_role,
            c.name AS country_name
        FROM dbProj_post p
        LEFT JOIN dbProj_user u 
            ON p.user_id = u.user_id
        LEFT JOIN dbProj_country c 
            ON p.country_id = c.country_id
        ORDER BY p.post_id DESC
        LIMIT 10
    ");

    $latestPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    try {
        $stmt = $pdo->query("
            SELECT *
            FROM dbProj_post
            ORDER BY post_id DESC
            LIMIT 10
        ");

        $latestPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e2) {
        $latestPosts = [];
    }
}

$ggValue = function (array $row, array $keys, string $default = '—'): string {
    foreach ($keys as $key) {
        if (isset($row[$key]) && trim((string)$row[$key]) !== '') {
            return (string)$row[$key];
        }
    }

    return $default;
};

$ggPostTitle = function (array $post) use ($ggValue): string {
    $title = $ggValue($post, [
        'title',
        'post_title',
        'caption',
        'content',
        'body',
        'description',
        'text',
        'post_content'
    ], 'Untitled post');

    return mb_strimwidth($title, 0, 85, '...');
};

$ggPostDate = function (array $post) use ($ggValue): string {
    $dateValue = $ggValue($post, [
        'created_at',
        'posted_at',
        'updated_at',
        'date_created',
        'post_date'
    ], '');

    if ($dateValue === '') {
        return '—';
    }

    $timestamp = strtotime($dateValue);

    if (!$timestamp) {
        return $dateValue;
    }

    return date('d M Y - h:i A', $timestamp);
};

$ggPostStatus = function (array $post): string {
    if (isset($post['status']) && trim((string)$post['status']) !== '') {
        return (string)$post['status'];
    }

    if (isset($post['approval_status']) && trim((string)$post['approval_status']) !== '') {
        return (string)$post['approval_status'];
    }

    if (isset($post['is_approved'])) {
        return ((int)$post['is_approved'] === 1) ? 'Approved' : 'Pending';
    }

    return 'Active';
};

$ggStatusClass = function (string $status): string {
    $lower = strtolower(trim($status));

    if (
        str_contains($lower, 'published') ||
        str_contains($lower, 'approve') ||
        str_contains($lower, 'confirmed') ||
        str_contains($lower, 'active')
    ) {
        return 'tp-status-published';
    }

    if (str_contains($lower, 'draft')) {
        return 'tp-status-draft';
    }

    if (
        str_contains($lower, 'pending') ||
        str_contains($lower, 'review') ||
        str_contains($lower, 'scheduled') ||
        str_contains($lower, 'checked in')
    ) {
        return 'tp-status-pending';
    }

    if (
        str_contains($lower, 'rejected') ||
        str_contains($lower, 'cancelled') ||
        str_contains($lower, 'deleted')
    ) {
        return 'tp-status-rejected';
    }

    if (str_contains($lower, 'archived')) {
        return 'tp-status-archived';
    }

    return 'tp-status-neutral';
};
?>

<style>
    .tp-dashboard-shell {
        background: #ffffff;
        min-height: 100vh;
        font-family: inherit;
    }

    .tp-dashboard-header {
        background: #4169e1;
        min-height: 96px;
        padding: 0 58px;
        display: grid;
        grid-template-columns: 190px 1fr 120px;
        align-items: center;
        color: #ffffff;
    }

    .tp-header-logo {
        color: #ffffff;
        text-decoration: none;
        font-weight: 900;
        line-height: 0.92;
        letter-spacing: 0.5px;
        display: inline-flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .tp-logo-small {
        font-size: 16px;
        letter-spacing: 1.8px;
    }

    .tp-logo-big {
        font-size: 25px;
        letter-spacing: 1.1px;
    }

    .tp-header-nav {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 70px;
    }

    .tp-header-nav a {
        color: #ffffff;
        text-decoration: none;
        font-size: 15px;
        font-weight: 900;
        text-transform: lowercase;
    }

    .tp-header-nav a:hover {
        opacity: 0.85;
    }

    .tp-header-admin {
        justify-self: end;
        color: #ffffff;
        font-size: 16px;
        font-weight: 900;
    }

    .tp-dashboard-main {
        background: #ffffff;
        padding: 82px 84px 88px;
        min-height: calc(100vh - 270px);
    }

    .tp-title {
        font-size: 35px;
        font-weight: 900;
        color: #050505;
        margin-bottom: 24px;
        letter-spacing: -0.7px;
    }

    .tp-stats-row {
        margin-bottom: 62px;
    }

    .tp-stat-card {
        background: #d9f4fb;
        border: 4px solid #7fd7f2;
        border-radius: 16px;
        min-height: 150px;
        padding: 26px 34px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: 0.2s ease;
    }

    .tp-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 28px rgba(65, 105, 225, 0.12);
    }

    .tp-stat-number {
        font-size: 48px;
        font-weight: 500;
        color: #000000;
        line-height: 1;
        margin-bottom: 14px;
    }

    .tp-stat-label {
        font-size: 22px;
        color: #555555;
        margin: 0;
        text-transform: lowercase;
    }

    .tp-stat-icon {
        font-size: 74px;
        color: #071f4a;
        line-height: 1;
    }

    .tp-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 34px;
    }

    .tp-section-title {
        font-size: 34px;
        font-weight: 900;
        color: #050505;
        margin: 0;
        letter-spacing: -0.6px;
        text-transform: lowercase;
    }

    .tp-outline-btn {
        background: #ffffff;
        color: #4169e1;
        border: 1.5px solid #4169e1;
        border-radius: 999px;
        padding: 14px 28px;
        font-size: 16px;
        font-weight: 800;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: 0.2s ease;
        white-space: nowrap;
    }

    .tp-outline-btn:hover {
        background: #4169e1;
        color: #ffffff;
        box-shadow: 0 10px 22px rgba(65, 105, 225, 0.16);
        transform: translateY(-1px);
    }

    .tp-posts-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 0;
        overflow: hidden;
        margin-bottom: 62px;
    }

    .tp-table {
        margin: 0;
        font-size: 14px;
    }

    .tp-table thead th {
        background: #ffffff;
        color: #10213f;
        font-weight: 900;
        padding: 16px 18px;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
        font-size: 13px;
    }

    .tp-table tbody td {
        padding: 16px 18px;
        vertical-align: middle;
        border-bottom: 1px solid #e5e7eb;
        color: #10213f;
    }

    .tp-table tbody tr:hover {
        background: #fbfdff;
    }

    .tp-author {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .tp-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4169e1, #75d5f4);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        font-weight: 900;
        flex-shrink: 0;
        box-shadow: 0 8px 16px rgba(65, 105, 225, 0.16);
    }

    .tp-author-name {
        font-weight: 900;
        color: #10213f;
        margin-bottom: 2px;
    }

    .tp-author-role {
        font-size: 12px;
        color: #667085;
        text-transform: capitalize;
    }

    .tp-post-title {
        color: #10213f;
        font-weight: 600;
        max-width: 520px;
        line-height: 1.35;
    }

    .tp-post-id {
        display: block;
        color: #667085;
        font-size: 12px;
        margin-top: 4px;
    }

    .tp-location-pill {
        background: #f1f7ff;
        color: #2f55c8;
        border: 1px solid #d4e5ff;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 800;
        display: inline-block;
        white-space: nowrap;
    }

.tp-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border-radius: 999px;
    padding: 7px 14px;
    font-size: 12px;
    font-weight: 800;
    text-transform: capitalize;
    white-space: nowrap;
    border: 1px solid transparent;
    letter-spacing: 0.2px;
}

.tp-status::before {
    content: "";
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

/* Published - green */
.tp-status-published {
    background: #eafaf1;
    color: #157347;
    border-color: #b7ebc9;
}

.tp-status-published::before {
    background: #22c55e;
}

/* Draft - purple/blue */
.tp-status-draft {
    background: #f3efff;
    color: #6f42c1;
    border-color: #d6c5ff;
}

.tp-status-draft::before {
    background: #8b5cf6;
}

/* Pending - orange/yellow */
.tp-status-pending {
    background: #fff7e8;
    color: #b26a00;
    border-color: #f6d28b;
}

.tp-status-pending::before {
    background: #f59e0b;
}

/* Rejected / Cancelled - red */
.tp-status-rejected {
    background: #fff1f2;
    color: #c1121f;
    border-color: #ffb3ba;
}

.tp-status-rejected::before {
    background: #ef4444;
}

/* Archived - gray */
.tp-status-archived {
    background: #f3f4f6;
    color: #4b5563;
    border-color: #d1d5db;
}

.tp-status-archived::before {
    background: #9ca3af;
}

/* Neutral - soft blue */
.tp-status-neutral {
    background: #eef4ff;
    color: #3155c9;
    border-color: #c9d8ff;
}

.tp-status-neutral::before {
    background: #4169e1;
}

    .tp-review-btn {
        background: #ffffff;
        color: #4169e1;
        border: 1px solid #c9d6ff;
        border-radius: 9px;
        padding: 8px 12px;
        font-size: 13px;
        font-weight: 800;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: 0.2s ease;
    }

    .tp-review-btn:hover {
        background: #4169e1;
        color: #ffffff;
        border-color: #4169e1;
    }

    .tp-actions-section {
        margin-top: 10px;
    }

    .tp-actions-title {
        font-size: 24px;
        font-weight: 900;
        color: #050505;
        margin-bottom: 18px;
    }

    .tp-action-grid {
        max-width: 720px;
        margin: 0 auto;
    }

    .tp-action-card {
        background: #f8f9fc;
        border: 2px solid #e1e6ef;
        border-radius: 15px;
        min-height: 88px;
        padding: 18px 22px;
        display: flex;
        align-items: center;
        gap: 16px;
        color: #111827;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .tp-action-card:hover {
        background: #ffffff;
        color: #111827;
        border-color: #4169e1;
        transform: translateY(-3px);
        box-shadow: 0 10px 22px rgba(65, 105, 225, 0.14);
    }

    .tp-action-icon {
        width: 36px;
        height: 36px;
        border-radius: 11px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        color: #4169e1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .tp-action-title {
        margin: 0 0 3px;
        font-weight: 900;
        font-size: 15px;
        color: #101828;
    }

    .tp-action-text {
        margin: 0;
        font-size: 13px;
        color: #667085;
    }

    .tp-dashboard-footer {
        background: #4169e1;
        min-height: 175px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        color: #ffffff;
        text-align: center;
    }

    .tp-footer-logo {
        font-size: 22px;
        font-weight: 900;
        line-height: 0.95;
        margin-bottom: 14px;
        letter-spacing: 0.4px;
    }

    .tp-footer-logo span {
        display: block;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 1.5px;
        margin-top: 5px;
    }

    .tp-footer-text {
        font-size: 13px;
        margin: 0;
        opacity: 0.9;
    }

    @media (max-width: 992px) {
        .tp-dashboard-header {
            padding: 18px 24px;
            grid-template-columns: 1fr;
            gap: 16px;
            text-align: center;
        }

        .tp-header-logo {
            align-items: center;
        }

        .tp-header-nav {
            flex-wrap: wrap;
            gap: 18px;
        }

        .tp-header-admin {
            justify-self: center;
        }

        .tp-dashboard-main {
            padding: 36px 22px 60px;
        }

        .tp-section-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .tp-title,
        .tp-section-title {
            font-size: 28px;
        }

        .tp-stat-card {
            min-height: 120px;
        }
    }
</style>

<div class="tp-dashboard-shell">

    <header class="tp-dashboard-header">
        <a href="<?= APP_BASE ?>/admin/" class="tp-header-logo">
            <span class="tp-logo-small">TRAVEL</span>
            <span class="tp-logo-big">PULSE</span>
        </a>

        <nav class="tp-header-nav">
            <a href="<?= APP_BASE ?>/admin/analytics">generate reports</a>
            <a href="<?= APP_BASE ?>/admin/moderate-posts">moderate content</a>
            <a href="<?= APP_BASE ?>/admin/manage-accounts">account management</a>
        </nav>

        <div class="tp-header-admin">
            Admin
        </div>
    </header>

    <main class="tp-dashboard-main">

        <h1 class="tp-title">Admin dashboard</h1>

        <div class="row g-4 tp-stats-row">

            <div class="col-lg-4 col-md-6">
                <div class="tp-stat-card">
                    <div>
                        <div class="tp-stat-number"><?= htmlspecialchars($totalUsers) ?></div>
                        <p class="tp-stat-label">users</p>
                    </div>
                    <div class="tp-stat-icon">
                        <i class="ph ph-user"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="tp-stat-card">
                    <div>
                        <div class="tp-stat-number"><?= htmlspecialchars($totalPosts) ?></div>
                        <p class="tp-stat-label">posts</p>
                    </div>
                    <div class="tp-stat-icon">
                        <i class="ph ph-article"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="tp-stat-card">
                    <div>
                        <div class="tp-stat-number"><?= htmlspecialchars($totalCreators) ?></div>
                        <p class="tp-stat-label">creators</p>
                    </div>
                    <div class="tp-stat-icon">
                        <i class="ph ph-user-check"></i>
                    </div>
                </div>
            </div>

        </div>

        <section class="tp-posts-section">
            <div class="tp-section-header">
                <h2 class="tp-section-title">checkout latest posts</h2>

                <a href="<?= APP_BASE ?>/admin/moderate-posts" class="tp-outline-btn">
                    moderate Posts
                </a>
            </div>

            <div class="tp-posts-card">
                <div class="table-responsive">
                    <table class="table tp-table align-middle">
                        <thead>
                        <tr>
                            <th>Creator</th>
                            <th>Post Title</th>
                            <th>Date & Time</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php if (!$latestPosts): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No posts found.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($latestPosts as $post): ?>
                            <?php
                            $authorName = $ggValue($post, ['author_username', 'username', 'name'], 'Unknown user');
                            $authorRole = $ggValue($post, ['author_role', 'role'], 'creator');
                            $postStatus = $ggPostStatus($post);
                            $postId = $ggValue($post, ['post_id', 'id'], '—');
                            ?>

                            <tr>
                                <td>
                                    <div class="tp-author">
                                        <div class="tp-avatar">
                                            <?= htmlspecialchars(mb_strtoupper(mb_substr($authorName, 0, 1))) ?>
                                        </div>

                                        <div>
                                            <div class="tp-author-name">
                                                <?= htmlspecialchars($authorName) ?>
                                            </div>
                                            <div class="tp-author-role">
                                                <?= htmlspecialchars($authorRole) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="tp-post-title">
                                        <?= htmlspecialchars($ggPostTitle($post)) ?>
                                    </div>
                                    <span class="tp-post-id">
                                        Post ID: <?= htmlspecialchars($postId) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars($ggPostDate($post)) ?>
                                </td>

                                <td>
                                    <span class="tp-location-pill">
                                        <?= htmlspecialchars($ggValue($post, ['country_name'], 'No location')) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="tp-status <?= $ggStatusClass($postStatus) ?>">
                                        <?= htmlspecialchars($postStatus) ?>
                                    </span>
                                </td>

                                <td>
                                    <a href="<?= APP_BASE ?>/admin/moderate-posts" class="tp-review-btn">
                                        <i class="ph ph-dots-three-outline-vertical"></i>
                                        Review
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>
            </div>
        </section>

        <section class="tp-actions-section">
            <h2 class="tp-actions-title text-center">Quick Actions</h2>

            <div class="tp-action-grid">
                <div class="row g-3">

                    <div class="col-md-6">
                        <a href="<?= APP_BASE ?>/admin/analytics" class="tp-action-card">
                            <div class="tp-action-icon">
                                <i class="ph ph-chart-line-up"></i>
                            </div>
                            <div>
                                <p class="tp-action-title">View analytics</p>
                                <p class="tp-action-text">Open reports and insights.</p>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-6">
                        <a href="<?= APP_BASE ?>/admin/add-location" class="tp-action-card">
                            <div class="tp-action-icon">
                                <i class="ph ph-plus"></i>
                            </div>
                            <div>
                                <p class="tp-action-title">Add new location</p>
                                <p class="tp-action-text">Create a new travel location.</p>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-6">
                        <a href="<?= APP_BASE ?>/admin/moderate-posts" class="tp-action-card">
                            <div class="tp-action-icon">
                                <i class="ph ph-article"></i>
                            </div>
                            <div>
                                <p class="tp-action-title">Moderate posts</p>
                                <p class="tp-action-text">Review user content.</p>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-6">
                        <a href="<?= APP_BASE ?>/admin/manage-accounts" class="tp-action-card">
                            <div class="tp-action-icon">
                                <i class="ph ph-users"></i>
                            </div>
                            <div>
                                <p class="tp-action-title">Manage accounts</p>
                                <p class="tp-action-text">View and manage users.</p>
                            </div>
                        </a>
                    </div>

                </div>
            </div>
        </section>

    </main>

    <footer class="tp-dashboard-footer">
        <div class="tp-footer-logo">
            TRAVEL
            <span>PULSE</span>
        </div>

        <p class="tp-footer-text">
            © 2026 Travel Pulse. All rights reserved.
        </p>
    </footer>

</div>