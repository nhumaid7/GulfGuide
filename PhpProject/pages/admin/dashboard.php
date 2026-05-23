<?php
$totalLocations = $pdo->query("SELECT COUNT(*) FROM dbProj_country")->fetchColumn();
$totalAttractions = $pdo->query("SELECT COUNT(*) FROM dbProj_attraction")->fetchColumn();
$totalPosts = $pdo->query("SELECT COUNT(*) FROM dbProj_post")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM dbProj_user")->fetchColumn();

$stmt = $pdo->query("
    SELECT 
        a.attraction_id,
        a.name AS attraction_name,
        a.description AS attraction_description,
        c.country_id,
        c.name AS country_name,
        COUNT(DISTINCT p.post_id) AS posts_count
    FROM dbProj_attraction a
    LEFT JOIN dbProj_country c 
        ON a.country_id = c.country_id
    LEFT JOIN dbProj_post p 
        ON c.country_id = p.country_id
    GROUP BY 
        a.attraction_id,
        a.name,
        a.description,
        c.country_id,
        c.name
    ORDER BY a.attraction_id DESC
    LIMIT 6
");

$attractions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    body {
        background: #f5f5f5;
    }

    .tp-admin-wrapper {
        min-height: 100vh;
        background: #ffffff;
        font-family: inherit;
    }

    /* Header */
    .tp-admin-header {
        background: #4169e1;
        min-height: 72px;
        padding: 0 60px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .tp-logo {
        color: #ffffff;
        font-weight: 800;
        font-size: 20px;
        line-height: 1;
        text-decoration: none;
        letter-spacing: 0.5px;
    }

    .tp-logo span {
        display: block;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 1.6px;
        margin-top: 2px;
    }

    .tp-admin-nav {
        display: flex;
        align-items: center;
        gap: 55px;
    }

    .tp-admin-nav a {
        color: #ffffff;
        text-decoration: none;
        font-size: 14px;
        font-weight: 700;
        text-transform: lowercase;
    }

    .tp-admin-nav a:hover {
        opacity: 0.85;
    }

    .tp-admin-name {
        color: #ffffff;
        font-size: 14px;
        font-weight: 700;
    }

    /* Main Content */
    .tp-admin-main {
        padding: 52px 68px 90px;
        min-height: 650px;
    }

    .tp-title {
        font-size: 32px;
        font-weight: 800;
        color: #050505;
        margin-bottom: 26px;
    }

    /* Stats */
    .tp-stats-row {
        margin-bottom: 40px;
    }

    .tp-stat-card {
        background: #d9f4fb;
        border: 3px solid #76d4f4;
        border-radius: 10px;
        min-height: 105px;
        padding: 20px 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .tp-stat-number {
        font-size: 36px;
        font-weight: 500;
        color: #000000;
        line-height: 1;
        margin-bottom: 8px;
    }

    .tp-stat-label {
        color: #555555;
        font-size: 15px;
        margin: 0;
        text-transform: lowercase;
    }

    .tp-stat-icon {
        font-size: 48px;
        color: #071f4a;
        line-height: 1;
    }

    /* Table */
    .tp-section-title {
        font-size: 21px;
        font-weight: 800;
        color: #050505;
        margin-bottom: 18px;
    }

    .tp-table-box {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #edf0f5;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
        padding: 0;
        margin-bottom: 45px;
        overflow: hidden;
    }

    .tp-table {
        margin: 0;
        font-size: 14px;
    }

    .tp-table thead th {
        background: #ffffff;
        color: #111111;
        font-weight: 800;
        padding: 16px 18px;
        border-bottom: 1px solid #dfe4ea;
        white-space: nowrap;
    }

    .tp-table tbody td {
        padding: 14px 18px;
        vertical-align: middle;
        border-bottom: 1px solid #edf0f3;
        color: #111111;
    }

    .tp-table tbody tr:hover {
        background: #fafcff;
    }

    .tp-attraction-name {
        font-weight: 800;
        color: #111111;
        margin-bottom: 3px;
    }

    .tp-attraction-desc {
        font-size: 13px;
        color: #777777;
    }

    .tp-edit-btn {
        background: #f5f6f8;
        color: #111111;
        border: 0;
        border-radius: 8px;
        padding: 7px 14px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
    }

    .tp-edit-btn:hover {
        background: #4169e1;
        color: #ffffff;
    }

    .tp-manage-btn {
        background: #4169e1;
        color: #ffffff;
        border: 0;
        border-radius: 8px;
        padding: 10px 18px;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        display: inline-block;
        margin: 18px;
    }

    .tp-manage-btn:hover {
        background: #3155c9;
        color: #ffffff;
    }

    /* Quick Actions */
    .tp-quick-actions {
        max-width: 620px;
    }

    .tp-quick-card {
        background: #f8f9fc;
        border: 3px solid #e5e9f0;
        border-radius: 12px;
        min-height: 88px;
        padding: 18px 22px;
        display: flex;
        align-items: center;
        gap: 16px;
        color: #111111;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .tp-quick-card:hover {
        background: #ffffff;
        color: #111111;
        transform: translateY(-2px);
        box-shadow: 0 7px 18px rgba(0, 0, 0, 0.08);
    }

    .tp-quick-icon {
        font-size: 22px;
        width: 28px;
        text-align: center;
    }

    .tp-orange {
        color: #f4a000;
    }

    .tp-purple {
        color: #6c63ff;
    }

    .tp-blue {
        color: #1f7aff;
    }

    .tp-quick-title {
        margin: 0;
        font-size: 16px;
        font-weight: 500;
    }

    /* Footer */
    .tp-admin-footer {
        background: #4169e1;
        min-height: 190px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        color: #ffffff;
        text-align: center;
        margin-top: 20px;
    }

    .tp-footer-logo {
        font-size: 22px;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 18px;
        letter-spacing: 0.5px;
    }

    .tp-footer-logo span {
        display: block;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 1.5px;
    }

    .tp-footer-text {
        font-size: 13px;
        margin: 0;
    }

    @media (max-width: 992px) {
        .tp-admin-header {
            padding: 18px 25px;
            flex-direction: column;
            gap: 16px;
        }

        .tp-admin-nav {
            flex-wrap: wrap;
            justify-content: center;
            gap: 18px;
        }

        .tp-admin-main {
            padding: 35px 22px 65px;
        }

        .tp-title {
            font-size: 27px;
        }
    }
</style>

<div class="tp-admin-wrapper">

    <!-- Header -->
    <header class="tp-admin-header">
        <a href="<?= APP_BASE ?>/admin/dashboard" class="tp-logo">
            TRAVEL
            <span>PULSE</span>
        </a>

        <nav class="tp-admin-nav">
            <a href="<?= APP_BASE ?>/analytics">generate reports</a>
            <a href="<?= APP_BASE ?>/admin/moderate-posts">moderate content</a>
            <a href="<?= APP_BASE ?>/admin/manage-accounts">account management</a>
        </nav>

        <div class="tp-admin-name">
            Admin
        </div>
    </header>

    <!-- Main -->
    <main class="tp-admin-main">

        <h1 class="tp-title">Admin dashboard</h1>

        <!-- Stats -->
        <div class="row g-4 tp-stats-row">

            <div class="col-lg-3 col-md-6">
                <div class="tp-stat-card">
                    <div>
                        <div class="tp-stat-number"><?= htmlspecialchars($totalUsers) ?></div>
                        <p class="tp-stat-label">users</p>
                    </div>
                    <div class="tp-stat-icon">♡</div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="tp-stat-card">
                    <div>
                        <div class="tp-stat-number"><?= htmlspecialchars($totalPosts) ?></div>
                        <p class="tp-stat-label">posts</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="tp-stat-card">
                    <div>
                        <div class="tp-stat-number"><?= htmlspecialchars($totalAttractions) ?></div>
                        <p class="tp-stat-label">attractions</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="tp-stat-card">
                    <div>
                        <div class="tp-stat-number"><?= htmlspecialchars($totalLocations) ?></div>
                        <p class="tp-stat-label">locations</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Latest Attractions -->
        <h2 class="tp-section-title">checkout latest attractions</h2>

        <div class="tp-table-box">
            <div class="table-responsive">
                <table class="table tp-table align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Attraction</th>
                            <th>Country</th>
                            <th>Posts</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!$attractions): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No attractions found.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($attractions as $attraction): ?>
                            <tr>
                                <td><?= htmlspecialchars($attraction['attraction_id']) ?></td>

                                <td>
                                    <div class="tp-attraction-name">
                                        <?= htmlspecialchars($attraction['attraction_name']) ?>
                                    </div>
                                    <div class="tp-attraction-desc">
                                        <?= htmlspecialchars(mb_strimwidth($attraction['attraction_description'] ?? '', 0, 80, '...')) ?>
                                    </div>
                                </td>

                                <td>
                                    <?= htmlspecialchars($attraction['country_name'] ?? 'No country') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($attraction['posts_count']) ?>
                                </td>

                                <td>
                                    <a href="<?= APP_BASE ?>/admin/edit-location?id=<?= htmlspecialchars($attraction['country_id']) ?>" class="tp-edit-btn">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <a href="<?= APP_BASE ?>/locations?role=admin" class="tp-manage-btn">
                Manage All Locations
            </a>
        </div>

        <!-- Quick Actions -->
        <h2 class="tp-section-title">Quick Actions</h2>

        <div class="tp-quick-actions">
            <div class="row g-3">

                <div class="col-md-6">
                    <a href="<?= APP_BASE ?>/analytics" class="tp-quick-card">
                        <div class="tp-quick-icon tp-orange">◴</div>
                        <p class="tp-quick-title">view analytics</p>
                    </a>
                </div>

                <div class="col-md-6">
                    <a href="<?= APP_BASE ?>/admin/add-location" class="tp-quick-card">
                        <div class="tp-quick-icon tp-purple">▣</div>
                        <p class="tp-quick-title">Add new location</p>
                    </a>
                </div>

                <div class="col-md-6">
                    <a href="<?= APP_BASE ?>/admin/moderate-posts" class="tp-quick-card">
                        <div class="tp-quick-icon tp-blue">▢</div>
                        <p class="tp-quick-title">moderate posts</p>
                    </a>
                </div>

                <div class="col-md-6">
                    <a href="<?= APP_BASE ?>/admin/manage-accounts" class="tp-quick-card">
                        <div class="tp-quick-icon tp-blue">▢</div>
                        <p class="tp-quick-title">manage accounts</p>
                    </a>
                </div>

            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="tp-admin-footer">
        <div class="tp-footer-logo">
            TRAVEL
            <span>PULSE</span>
        </div>

        <p class="tp-footer-text">
            © 2023 Travel Pulse. All rights reserved.
        </p>
    </footer>

</div>