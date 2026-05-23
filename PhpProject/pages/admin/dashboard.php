<?php
$totalLocations = $pdo->query("SELECT COUNT(*) FROM dbProj_country")->fetchColumn();
$totalAttractions = $pdo->query("SELECT COUNT(*) FROM dbProj_attraction")->fetchColumn();
$totalPosts = $pdo->query("SELECT COUNT(*) FROM dbProj_post")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM dbProj_user")->fetchColumn();

$pendingCreatorRequests = 0;
try {
    $pendingCreatorRequests = $pdo
        ->query("SELECT COUNT(*) FROM dbProj_creator_request WHERE status = 'pending'")
        ->fetchColumn();
} catch (Throwable $e) {
    $pendingCreatorRequests = 0;
}

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
    .gg-admin-page {
        padding: 38px 48px 70px;
        background: #f4f7fb;
        min-height: calc(100vh - 70px);
    }

    .gg-hero {
        background: linear-gradient(135deg, #223f72 0%, #31558f 100%);
        color: #fff;
        border-radius: 24px;
        padding: 34px 38px;
        margin-bottom: 28px;
        box-shadow: 0 18px 38px rgba(31, 63, 110, 0.22);
        position: relative;
        overflow: hidden;
    }

    .gg-hero::after {
        content: "";
        position: absolute;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        right: -90px;
        top: -125px;
        background: rgba(255, 255, 255, 0.08);
    }

    .gg-hero-content {
        position: relative;
        z-index: 2;
    }

    .gg-title {
        font-size: 34px;
        font-weight: 900;
        margin-bottom: 8px;
        letter-spacing: -0.7px;
    }

    .gg-subtitle {
        color: rgba(255, 255, 255, 0.78);
        margin: 0;
        font-size: 15px;
    }

    .gg-stats {
        margin-top: 32px;
        position: relative;
        z-index: 2;
    }

    .gg-stat-card {
        background: rgba(255, 255, 255, 0.13);
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 18px;
        padding: 22px 24px;
        min-height: 112px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: 0.2s ease;
    }

    .gg-stat-card:hover {
        transform: translateY(-4px);
        background: rgba(255, 255, 255, 0.18);
    }

    .gg-stat-number {
        font-size: 36px;
        font-weight: 900;
        line-height: 1;
        margin-bottom: 8px;
    }

    .gg-stat-label {
        color: rgba(255, 255, 255, 0.78);
        font-size: 14px;
        margin: 0;
        font-weight: 600;
    }

    .gg-stat-icon {
        width: 46px;
        height: 46px;
        border-radius: 15px;
        background: rgba(255, 255, 255, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 21px;
    }

    .gg-card {
        background: #fff;
        border: 1px solid #e5ebf3;
        border-radius: 22px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.07);
        margin-bottom: 34px;
        overflow: hidden;
    }

    .gg-card-header {
        padding: 22px 24px;
        border-bottom: 1px solid #e8eef6;
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: center;
    }

    .gg-card-title {
        margin: 0;
        font-size: 22px;
        font-weight: 900;
        color: #101828;
    }

    .gg-card-text {
        margin: 5px 0 0;
        color: #667085;
        font-size: 13px;
    }

    .gg-badge {
        background: #eaf3ff;
        color: #2f55c8;
        border: 1px solid #cfe1ff;
        border-radius: 999px;
        padding: 8px 14px;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
    }

    .gg-table {
        margin: 0;
        font-size: 14px;
    }

    .gg-table thead th {
        background: #fbfdff;
        color: #344054;
        font-weight: 900;
        padding: 15px 20px;
        border-bottom: 1px solid #dde5ef;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .gg-table tbody td {
        padding: 16px 20px;
        vertical-align: middle;
        border-bottom: 1px solid #eef2f6;
    }

    .gg-table tbody tr:hover {
        background: #f8fbff;
    }

    .gg-id {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #eef6ff;
        color: #2f55c8;
        border: 1px solid #cfe1ff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 13px;
    }

    .gg-attraction-name {
        font-weight: 900;
        color: #101828;
        margin-bottom: 4px;
    }

    .gg-desc {
        font-size: 13px;
        color: #667085;
        max-width: 520px;
    }

    .gg-pill {
        background: #f1f7ff;
        color: #2f55c8;
        border: 1px solid #d4e5ff;
        border-radius: 999px;
        padding: 7px 13px;
        font-size: 13px;
        font-weight: 800;
        display: inline-block;
    }

    .gg-btn {
        background: #4169e1;
        color: #fff;
        border: 0;
        border-radius: 11px;
        padding: 11px 18px;
        font-size: 14px;
        font-weight: 900;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        box-shadow: 0 8px 18px rgba(65, 105, 225, 0.22);
        transition: 0.2s ease;
    }

    .gg-btn:hover {
        background: #3155c9;
        color: #fff;
        transform: translateY(-1px);
    }

    .gg-btn-light {
        background: #fff;
        color: #2446bb;
        border: 1px solid #b9ccff;
        box-shadow: none;
    }

    .gg-btn-light:hover {
        background: #4169e1;
        color: #fff;
        border-color: #4169e1;
    }

    .gg-actions {
        padding: 28px;
    }

    .gg-action-card {
        background: #f8f9fc;
        border: 2px solid #e1e6ef;
        border-radius: 16px;
        min-height: 92px;
        padding: 20px 22px;
        display: flex;
        align-items: center;
        gap: 16px;
        color: #111827;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .gg-action-card:hover {
        background: #fff;
        color: #111827;
        border-color: #4169e1;
        transform: translateY(-3px);
        box-shadow: 0 10px 22px rgba(65, 105, 225, 0.14);
    }

    .gg-action-icon {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        background: #fff;
        border: 1px solid #e2e8f0;
        color: #4169e1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .gg-action-title {
        margin: 0 0 3px;
        font-weight: 900;
        font-size: 15px;
    }

    .gg-action-text {
        margin: 0;
        font-size: 13px;
        color: #667085;
    }

    @media (max-width: 768px) {
        .gg-admin-page {
            padding: 28px 18px 50px;
        }

        .gg-title {
            font-size: 28px;
        }

        .gg-card-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="gg-admin-page">

    <section class="gg-hero">
        <div class="gg-hero-content">
            <h1 class="gg-title">Admin Dashboard</h1>
            <p class="gg-subtitle">
                Manage GulfGuide locations, attractions, posts, creator requests, and users from one control panel.
            </p>
        </div>

        <div class="row g-4 gg-stats">
            <div class="col-lg-3 col-md-6">
                <div class="gg-stat-card">
                    <div>
                        <div class="gg-stat-number"><?= htmlspecialchars($totalUsers) ?></div>
                        <p class="gg-stat-label">Users</p>
                    </div>
                    <div class="gg-stat-icon"><i class="ph ph-users"></i></div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="gg-stat-card">
                    <div>
                        <div class="gg-stat-number"><?= htmlspecialchars($totalLocations) ?></div>
                        <p class="gg-stat-label">Locations</p>
                    </div>
                    <div class="gg-stat-icon"><i class="ph ph-map-pin"></i></div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="gg-stat-card">
                    <div>
                        <div class="gg-stat-number"><?= htmlspecialchars($totalAttractions) ?></div>
                        <p class="gg-stat-label">Attractions</p>
                    </div>
                    <div class="gg-stat-icon"><i class="ph ph-compass"></i></div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="gg-stat-card">
                    <div>
                        <div class="gg-stat-number"><?= htmlspecialchars($pendingCreatorRequests) ?></div>
                        <p class="gg-stat-label">Pending Requests</p>
                    </div>
                    <div class="gg-stat-icon"><i class="ph ph-user-check"></i></div>
                </div>
            </div>
        </div>
    </section>

    <section class="gg-card">
        <div class="gg-card-header">
            <div>
                <h2 class="gg-card-title">Latest Attractions</h2>
                <p class="gg-card-text">Recent attractions with their location and related post activity.</p>
            </div>
            <span class="gg-badge"><?= htmlspecialchars(count($attractions)) ?> latest records</span>
        </div>

        <div class="table-responsive">
            <table class="table gg-table align-middle">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Attraction</th>
                    <th>Location</th>
                    <th>Posts</th>
                    <th>Action</th>
                </tr>
                </thead>

                <tbody>
                <?php if (!$attractions): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No attractions found.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($attractions as $attraction): ?>
                    <tr>
                        <td>
                            <div class="gg-id"><?= htmlspecialchars($attraction['attraction_id']) ?></div>
                        </td>

                        <td>
                            <div class="gg-attraction-name">
                                <?= htmlspecialchars($attraction['attraction_name']) ?>
                            </div>
                            <div class="gg-desc">
                                <?= htmlspecialchars(mb_strimwidth($attraction['attraction_description'] ?? '', 0, 90, '...')) ?>
                            </div>
                        </td>

                        <td>
                            <span class="gg-pill">
                                <?= htmlspecialchars($attraction['country_name'] ?? 'No location') ?>
                            </span>
                        </td>

                        <td><?= htmlspecialchars($attraction['posts_count']) ?></td>

                        <td>
                            <?php if (!empty($attraction['country_id'])): ?>
                                <a href="<?= APP_BASE ?>/admin/edit-location?id=<?= htmlspecialchars($attraction['country_id']) ?>" class="gg-btn gg-btn-light">
                                    <i class="ph ph-pencil-simple"></i>
                                    Edit Location
                                </a>
                            <?php else: ?>
                                <span class="text-muted">No location</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="p-4 border-top bg-light">
            <a href="<?= APP_BASE ?>/admin/location-list" class="gg-btn">
                <i class="ph ph-map-trifold"></i>
                Manage All Locations
            </a>
        </div>
    </section>

    <section class="gg-card">
        <div class="gg-card-header">
            <div>
                <h2 class="gg-card-title">Quick Actions</h2>
                <p class="gg-card-text">Use these shortcuts to complete your admin tasks faster.</p>
            </div>
        </div>

        <div class="gg-actions">
            <div class="row g-3">
                <div class="col-lg-4 col-md-6">
                    <a href="<?= APP_BASE ?>/admin/add-location" class="gg-action-card">
                        <div class="gg-action-icon"><i class="ph ph-plus"></i></div>
                        <div>
                            <p class="gg-action-title">Add Location</p>
                            <p class="gg-action-text">Create a new travel location.</p>
                        </div>
                    </a>
                </div>

                <div class="col-lg-4 col-md-6">
                    <a href="<?= APP_BASE ?>/admin/location-list" class="gg-action-card">
                        <div class="gg-action-icon"><i class="ph ph-map-pin"></i></div>
                        <div>
                            <p class="gg-action-title">Manage Locations</p>
                            <p class="gg-action-text">Edit or delete locations.</p>
                        </div>
                    </a>
                </div>

                <div class="col-lg-4 col-md-6">
                    <a href="<?= APP_BASE ?>/admin/creator-request" class="gg-action-card">
                        <div class="gg-action-icon"><i class="ph ph-user-check"></i></div>
                        <div>
                            <p class="gg-action-title">Creator Requests</p>
                            <p class="gg-action-text">Approve or reject applications.</p>
                        </div>
                    </a>
                </div>

                <div class="col-lg-4 col-md-6">
                    <a href="<?= APP_BASE ?>/admin/moderate-posts" class="gg-action-card">
                        <div class="gg-action-icon"><i class="ph ph-article"></i></div>
                        <div>
                            <p class="gg-action-title">Moderate Posts</p>
                            <p class="gg-action-text">Review user content.</p>
                        </div>
                    </a>
                </div>

                <div class="col-lg-4 col-md-6">
                    <a href="<?= APP_BASE ?>/admin/manage-accounts" class="gg-action-card">
                        <div class="gg-action-icon"><i class="ph ph-users-three"></i></div>
                        <div>
                            <p class="gg-action-title">Manage Accounts</p>
                            <p class="gg-action-text">View and manage users.</p>
                        </div>
                    </a>
                </div>

                <div class="col-lg-4 col-md-6">
                    <a href="<?= APP_BASE ?>/admin/analytics" class="gg-action-card">
                        <div class="gg-action-icon"><i class="ph ph-chart-line-up"></i></div>
                        <div>
                            <p class="gg-action-title">Analytics</p>
                            <p class="gg-action-text">View reports and insights.</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

</div>