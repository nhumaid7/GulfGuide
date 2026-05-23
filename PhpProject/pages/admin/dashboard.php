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
        background: #f4f7fb;
    }

    .tp-admin-wrapper {
        background: #ffffff;
        min-height: 100vh;
        font-family: inherit;
    }

    .tp-admin-header {
        background: linear-gradient(135deg, #4169e1 0%, #3154d4 55%, #2446bb 100%);
        min-height: 78px;
        padding: 0 58px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 8px 24px rgba(65, 105, 225, 0.18);
    }

    .tp-logo {
        color: #ffffff;
        font-weight: 900;
        font-size: 18px;
        line-height: 0.9;
        text-decoration: none;
        letter-spacing: 0.4px;
    }

    .tp-logo span {
        display: block;
        font-size: 11px;
        letter-spacing: 1.5px;
        margin-top: 5px;
    }

    .tp-admin-nav {
        display: flex;
        align-items: center;
        gap: 55px;
    }

    .tp-admin-nav a {
        color: #ffffff;
        text-decoration: none;
        font-size: 13px;
        font-weight: 800;
        text-transform: lowercase;
        opacity: 0.95;
    }

    .tp-admin-nav a:hover {
        opacity: 1;
        text-decoration: underline;
        text-underline-offset: 6px;
    }

    .tp-admin-name {
        color: #ffffff;
        font-size: 14px;
        font-weight: 800;
    }

    .tp-admin-main {
        padding: 48px 58px 75px;
        min-height: 650px;
    }

    .tp-hero {
        background: linear-gradient(135deg, #ffffff 0%, #f5f8ff 100%);
        border: 1px solid #e8eef8;
        border-radius: 20px;
        padding: 30px 34px;
        margin-bottom: 28px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }

    .tp-title {
        font-size: 34px;
        font-weight: 900;
        color: #070707;
        margin-bottom: 8px;
        letter-spacing: -0.7px;
    }

    .tp-subtitle {
        color: #5f6b7a;
        font-size: 15px;
        margin: 0;
    }

    .tp-hero-badge {
        background: #eaf3ff;
        color: #2f55c8;
        border: 1px solid #cfe1ff;
        border-radius: 999px;
        padding: 10px 18px;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
    }

    .tp-stats-row {
        margin-bottom: 36px;
    }

    .tp-stat-card {
        background: linear-gradient(135deg, #d9f4fb 0%, #ecfbff 100%);
        border: 2px solid #75d5f4;
        border-radius: 16px;
        min-height: 112px;
        padding: 22px 26px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: 0.2s ease;
        box-shadow: 0 8px 18px rgba(20, 120, 180, 0.07);
    }

    .tp-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 28px rgba(20, 120, 180, 0.14);
    }

    .tp-stat-number {
        font-size: 36px;
        font-weight: 800;
        color: #06152f;
        line-height: 1;
        margin-bottom: 9px;
    }

    .tp-stat-label {
        color: #5a6574;
        font-size: 14px;
        margin: 0;
        text-transform: lowercase;
        font-weight: 600;
    }

    .tp-stat-icon {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        background: rgba(65, 105, 225, 0.12);
        color: #2446bb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        font-weight: 900;
    }

    .tp-section-title {
        font-size: 21px;
        font-weight: 900;
        color: #080808;
        margin-bottom: 16px;
    }

    .tp-table-box {
        background: #ffffff;
        border-radius: 18px;
        border: 1px solid #e7edf5;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
        margin-bottom: 44px;
        overflow: hidden;
    }

    .tp-table {
        margin: 0;
        font-size: 14px;
    }

    .tp-table thead th {
        background: #f8fbff;
        color: #111111;
        font-weight: 900;
        padding: 16px 18px;
        border-bottom: 1px solid #dde5ef;
        white-space: nowrap;
    }

    .tp-table tbody td {
        padding: 14px 18px;
        vertical-align: middle;
        border-bottom: 1px solid #eef2f6;
        color: #111111;
    }

    .tp-table tbody tr:hover {
        background: #f8fbff;
    }

    .tp-attraction-name {
        font-weight: 900;
        color: #101828;
        margin-bottom: 3px;
    }

    .tp-attraction-desc {
        font-size: 13px;
        color: #667085;
    }

    .tp-edit-btn {
        background: #f3f6fb;
        color: #1f2937;
        border: 1px solid #e3e8f0;
        border-radius: 9px;
        padding: 7px 15px;
        font-size: 13px;
        font-weight: 800;
        text-decoration: none;
        display: inline-block;
    }

    .tp-edit-btn:hover {
        background: #4169e1;
        color: #ffffff;
        border-color: #4169e1;
    }

    .tp-manage-btn {
        background: #4169e1;
        color: #ffffff;
        border: 0;
        border-radius: 10px;
        padding: 11px 20px;
        font-size: 14px;
        font-weight: 800;
        text-decoration: none;
        display: inline-block;
        margin: 18px;
        box-shadow: 0 8px 18px rgba(65, 105, 225, 0.22);
    }

    .tp-manage-btn:hover {
        background: #3155c9;
        color: #ffffff;
    }

    .tp-quick-section {
        background: linear-gradient(135deg, #ffffff 0%, #f7faff 100%);
        border: 1px solid #e7edf5;
        border-radius: 20px;
        padding: 28px;
        max-width: 760px;
        margin: 0 auto 72px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
    }

    .tp-quick-actions {
        max-width: 620px;
        margin: 0 auto;
    }

    .tp-quick-card {
        background: #f8f9fc;
        border: 2px solid #e1e6ef;
        border-radius: 14px;
        min-height: 82px;
        padding: 18px 24px;
        display: flex;
        align-items: center;
        gap: 18px;
        color: #111111;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .tp-quick-card:hover {
        background: #ffffff;
        color: #111111;
        border-color: #4169e1;
        transform: translateY(-3px);
        box-shadow: 0 10px 22px rgba(65, 105, 225, 0.14);
    }

    .tp-quick-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        font-weight: 900;
        background: #ffffff;
        border: 1px solid #e2e8f0;
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
        font-size: 15px;
        font-weight: 700;
    }

    .tp-admin-footer {
        background: linear-gradient(135deg, #4169e1 0%, #3154d4 55%, #2446bb 100%);
        min-height: 190px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        color: #ffffff;
        text-align: center;
        box-shadow: 0 -8px 24px rgba(65, 105, 225, 0.12);
    }

    .tp-footer-logo {
        font-size: 22px;
        font-weight: 900;
        line-height: 0.9;
        margin-bottom: 16px;
        letter-spacing: 0.4px;
    }

    .tp-footer-logo span {
        display: block;
        font-size: 12px;
        letter-spacing: 1.5px;
        margin-top: 5px;
    }

    .tp-footer-text {
        font-size: 13px;
        margin: 0;
        opacity: 0.9;
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
            padding: 32px 20px 60px;
        }

        .tp-hero {
            flex-direction: column;
            align-items: flex-start;
        }

        .tp-title {
            font-size: 28px;
        }
    }
</style>

<div class="tp-admin-wrapper">

    <header class="tp-admin-header">
        <a href="<?= APP_BASE ?>/admin/dashboard" class="tp-logo">
            TRAVEL
            <span>PULSE</span>
        </a>

        <nav class="tp-admin-nav">
            <a href="<?= APP_BASE ?>/analytics">generate reports</a>
            <a href="<?= APP_BASE ?>/moderate-posts">moderate content</a>
            <a href="<?= APP_BASE ?>/manage-accounts">account management</a>
        </nav>

        <div class="tp-admin-name">Admin</div>
    </header>

    <main class="tp-admin-main">

        <section class="tp-hero">
            <div>
                <h1 class="tp-title">Admin dashboard</h1>
                <p class="tp-subtitle">
                    Manage travel content, attractions, locations, posts, and users from one place.
                </p>
            </div>

            <div class="tp-hero-badge">
                Travel Pulse Control Panel
            </div>
        </section>

        <div class="row g-4 tp-stats-row">

            <div class="col-lg-3 col-md-6">
                <div class="tp-stat-card">
                    <div>
                        <div class="tp-stat-number"><?= htmlspecialchars($totalUsers) ?></div>
                        <p class="tp-stat-label">users</p>
                    </div>
                    <div class="tp-stat-icon">👤</div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="tp-stat-card">
                    <div>
                        <div class="tp-stat-number"><?= htmlspecialchars($totalPosts) ?></div>
                        <p class="tp-stat-label">posts</p>
                    </div>
                    <div class="tp-stat-icon">✦</div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="tp-stat-card">
                    <div>
                        <div class="tp-stat-number"><?= htmlspecialchars($totalAttractions) ?></div>
                        <p class="tp-stat-label">attractions</p>
                    </div>
                    <div class="tp-stat-icon">⌖</div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="tp-stat-card">
                    <div>
                        <div class="tp-stat-number"><?= htmlspecialchars($totalLocations) ?></div>
                        <p class="tp-stat-label">locations</p>
                    </div>
                    <div class="tp-stat-icon">◎</div>
                </div>
            </div>

        </div>

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
                                        <?= htmlspecialchars(mb_strimwidth($attraction['attraction_description'] ?? '', 0, 85, '...')) ?>
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

        <section class="tp-quick-section">
            <h2 class="tp-section-title text-center">Quick Actions</h2>

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
                            <div class="tp-quick-icon tp-purple">＋</div>
                            <p class="tp-quick-title">Add new location</p>
                        </a>
                    </div>

                    <div class="col-md-6">
                        <a href="<?= APP_BASE ?>/moderate-posts" class="tp-quick-card">
                            <div class="tp-quick-icon tp-blue">□</div>
                            <p class="tp-quick-title">moderate posts</p>
                        </a>
                    </div>

                    <div class="col-md-6">
                        <a href="<?= APP_BASE ?>/manage-accounts" class="tp-quick-card">
                            <div class="tp-quick-icon tp-blue">⚙</div>
                            <p class="tp-quick-title">manage accounts</p>
                        </a>
                    </div>

                </div>
            </div>
        </section>

    </main>

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