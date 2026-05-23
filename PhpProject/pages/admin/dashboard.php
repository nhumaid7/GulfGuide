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
        background: #f4f7fb;
        min-height: 100vh;
        font-family: inherit;
    }

    .tp-admin-header {
        background: linear-gradient(135deg, #4169e1 0%, #3154d4 55%, #2446bb 100%);
        min-height: 74px;
        padding: 0 58px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 8px 22px rgba(65, 105, 225, 0.18);
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
        gap: 48px;
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
        padding: 42px 58px 72px;
    }

    .tp-control-card {
        background: linear-gradient(135deg, #233f71 0%, #31558f 100%);
        color: #ffffff;
        border-radius: 22px;
        padding: 32px 36px;
        margin-bottom: 32px;
        box-shadow: 0 18px 38px rgba(31, 63, 110, 0.22);
        position: relative;
        overflow: hidden;
    }

    .tp-control-card::after {
        content: "";
        position: absolute;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        right: -80px;
        top: -120px;
        background: rgba(255, 255, 255, 0.08);
    }

    .tp-control-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        position: relative;
        z-index: 2;
    }

    .tp-title {
        font-size: 32px;
        font-weight: 900;
        margin-bottom: 8px;
        letter-spacing: -0.6px;
    }

    .tp-subtitle {
        color: rgba(255, 255, 255, 0.78);
        font-size: 15px;
        margin: 0;
    }

    .tp-status-pill {
        background: rgba(255, 255, 255, 0.16);
        border: 1px solid rgba(255, 255, 255, 0.24);
        color: #ffffff;
        border-radius: 999px;
        padding: 10px 18px;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
    }

    .tp-stats-inside {
        margin-top: 34px;
        position: relative;
        z-index: 2;
    }

    .tp-stat-card {
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.20);
        border-radius: 18px;
        padding: 22px 24px;
        min-height: 112px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        backdrop-filter: blur(8px);
        transition: 0.2s ease;
    }

    .tp-stat-card:hover {
        transform: translateY(-4px);
        background: rgba(255, 255, 255, 0.17);
    }

    .tp-stat-number {
        font-size: 36px;
        font-weight: 900;
        color: #ffffff;
        line-height: 1;
        margin-bottom: 9px;
    }

    .tp-stat-label {
        color: rgba(255, 255, 255, 0.76);
        font-size: 14px;
        margin: 0;
        text-transform: lowercase;
        font-weight: 600;
    }

    .tp-stat-icon {
        width: 46px;
        height: 46px;
        border-radius: 15px;
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 21px;
        font-weight: 900;
    }

    .tp-table-box {
        background: #ffffff;
        border-radius: 22px;
        border: 1px solid #e5ebf3;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.07);
        margin-bottom: 42px;
        overflow: hidden;
    }

    .tp-table-header {
        padding: 22px 24px;
        border-bottom: 1px solid #e8eef6;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
    }

    .tp-table-title {
        margin: 0;
        font-size: 22px;
        font-weight: 900;
        color: #101828;
        letter-spacing: -0.3px;
    }

    .tp-table-subtitle {
        margin: 4px 0 0;
        font-size: 13px;
        color: #667085;
    }

    .tp-table-count {
        background: #eaf3ff;
        color: #2f55c8;
        border: 1px solid #cfe1ff;
        border-radius: 999px;
        padding: 8px 14px;
        font-size: 13px;
        font-weight: 800;
    }

    .tp-table {
        margin: 0;
        font-size: 14px;
    }

    .tp-table thead th {
        background: #fbfdff;
        color: #344054;
        font-weight: 900;
        padding: 15px 20px;
        border-bottom: 1px solid #dde5ef;
        white-space: nowrap;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .tp-table tbody td {
        padding: 16px 20px;
        vertical-align: middle;
        border-bottom: 1px solid #eef2f6;
        color: #111827;
    }

    .tp-table tbody tr:hover {
        background: #f8fbff;
    }

    .tp-id-badge {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #eef6ff;
        color: #2f55c8;
        border: 1px solid #cfe1ff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 900;
    }

    .tp-attraction-cell {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .tp-attraction-avatar {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: linear-gradient(135deg, #4169e1, #75d5f4);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 17px;
        flex-shrink: 0;
        box-shadow: 0 8px 16px rgba(65, 105, 225, 0.18);
    }

    .tp-attraction-name {
        font-weight: 900;
        color: #101828;
        margin-bottom: 4px;
    }

    .tp-attraction-desc {
        font-size: 13px;
        color: #667085;
        max-width: 540px;
        line-height: 1.35;
    }

    .tp-country-pill {
        background: #f1f7ff;
        color: #2f55c8;
        border: 1px solid #d4e5ff;
        border-radius: 999px;
        padding: 7px 13px;
        font-size: 13px;
        font-weight: 800;
        display: inline-block;
        white-space: nowrap;
    }

    .tp-post-pill {
        background: #f8f9fc;
        color: #344054;
        border: 1px solid #e4e7ec;
        border-radius: 999px;
        padding: 7px 13px;
        font-size: 13px;
        font-weight: 800;
        display: inline-block;
        min-width: 42px;
        text-align: center;
    }

    .tp-edit-location-btn {
        background: #ffffff;
        color: #2446bb;
        border: 1px solid #b9ccff;
        border-radius: 999px;
        padding: 8px 15px;
        font-size: 13px;
        font-weight: 900;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: 0.2s ease;
        white-space: nowrap;
    }

    .tp-edit-location-btn:hover {
        background: #4169e1;
        color: #ffffff;
        border-color: #4169e1;
        box-shadow: 0 8px 18px rgba(65, 105, 225, 0.22);
        transform: translateY(-1px);
    }

    .tp-disabled-btn {
        background: #f2f4f7;
        color: #98a2b3;
        border: 1px solid #e4e7ec;
        border-radius: 999px;
        padding: 8px 15px;
        font-size: 13px;
        font-weight: 800;
        display: inline-block;
        white-space: nowrap;
    }

    .tp-manage-row {
        padding: 18px 22px;
        background: #fbfdff;
        border-top: 1px solid #eef2f6;
    }

    .tp-manage-btn {
        background: #4169e1;
        color: #ffffff;
        border: 0;
        border-radius: 11px;
        padding: 11px 20px;
        font-size: 14px;
        font-weight: 900;
        text-decoration: none;
        display: inline-block;
        box-shadow: 0 8px 18px rgba(65, 105, 225, 0.22);
    }

    .tp-manage-btn:hover {
        background: #3155c9;
        color: #ffffff;
        transform: translateY(-1px);
    }

    .tp-quick-section {
        background: #ffffff;
        border: 1px solid #e7edf5;
        border-radius: 22px;
        padding: 30px;
        max-width: 780px;
        margin: 0 auto 72px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
    }

    .tp-section-title {
        font-size: 22px;
        font-weight: 900;
        color: #080808;
        margin-bottom: 18px;
        letter-spacing: -0.3px;
    }

    .tp-quick-actions {
        max-width: 640px;
        margin: 0 auto;
    }

    .tp-quick-card {
        background: #f8f9fc;
        border: 2px solid #e1e6ef;
        border-radius: 15px;
        min-height: 84px;
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
        width: 36px;
        height: 36px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
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
        font-weight: 800;
    }

    .tp-admin-footer {
        background: linear-gradient(135deg, #4169e1 0%, #3154d4 55%, #2446bb 100%);
        min-height: 180px;
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

        .tp-control-top {
            flex-direction: column;
            align-items: flex-start;
        }

        .tp-title {
            font-size: 28px;
        }

        .tp-table-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
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
            <a href="<?= APP_BASE ?>/admin/moderate-posts">moderate content</a>
            <a href="<?= APP_BASE ?>/admin/manage-accounts">account management</a>
        </nav>

        <div class="tp-admin-name">Admin</div>
    </header>

    <main class="tp-admin-main">

        <section class="tp-control-card">
            <div class="tp-control-top">
                <div>
                    <h1 class="tp-title">Admin dashboard</h1>
                    <p class="tp-subtitle">
                        Manage travel content, attractions, locations, posts, and users from one place.
                    </p>
                </div>

                <div class="tp-status-pill">
                    Travel Pulse Control Panel
                </div>
            </div>

            <div class="row g-4 tp-stats-inside">
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
        </section>

        <section class="tp-table-box">
            <div class="tp-table-header">
                <div>
                    <h2 class="tp-table-title">Checkout latest attractions</h2>
                    <p class="tp-table-subtitle">
                        Recent attractions with their location and related post activity.
                    </p>
                </div>

                <div class="tp-table-count">
                    <?= htmlspecialchars(count($attractions)) ?> latest records
                </div>
            </div>

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
                                <td>
                                    <div class="tp-id-badge">
                                        <?= htmlspecialchars($attraction['attraction_id']) ?>
                                    </div>
                                </td>

                                <td>
                                    <div class="tp-attraction-cell">
                                        <div class="tp-attraction-avatar">
                                            <?= htmlspecialchars(mb_substr($attraction['attraction_name'] ?? 'A', 0, 1)) ?>
                                        </div>

                                        <div>
                                            <div class="tp-attraction-name">
                                                <?= htmlspecialchars($attraction['attraction_name']) ?>
                                            </div>

                                            <div class="tp-attraction-desc">
                                                <?= htmlspecialchars(mb_strimwidth($attraction['attraction_description'] ?? '', 0, 95, '...')) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="tp-country-pill">
                                        <?= htmlspecialchars($attraction['country_name'] ?? 'No country') ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="tp-post-pill">
                                        <?= htmlspecialchars($attraction['posts_count']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if (!empty($attraction['country_id'])): ?>
                                        <a href="<?= APP_BASE ?>/admin/edit-location?id=<?= htmlspecialchars($attraction['country_id']) ?>" class="tp-edit-location-btn">
                                            ✎ Edit Location
                                        </a>
                                    <?php else: ?>
                                        <span class="tp-disabled-btn">No location</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

<a href="<?= APP_BASE ?>/admin/location-list" class="tp-manage-btn">
    Manage All Locations
</a>
            </div>
        </section>

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
                        <a href="<?= APP_BASE ?>/admin/moderate-posts" class="tp-quick-card">
                            <div class="tp-quick-icon tp-blue">□</div>
                            <p class="tp-quick-title">moderate posts</p>
                        </a>
                    </div>

                    <div class="col-md-6">
                        <a href="<?= APP_BASE ?>/admin/manage-accounts" class="tp-quick-card">
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