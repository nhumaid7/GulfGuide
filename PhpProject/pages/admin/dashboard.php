<?php
$totalLocations = 0;
$totalAttractions = 0;
$totalPosts = 0;
$totalUsers = 0;

try {
    $totalLocations = (int) $pdo->query("SELECT COUNT(*) FROM dbProj_country")->fetchColumn();
} catch (Throwable $e) {
    $totalLocations = 0;
}

try {
    $totalAttractions = (int) $pdo->query("SELECT COUNT(*) FROM dbProj_attraction")->fetchColumn();
} catch (Throwable $e) {
    $totalAttractions = 0;
}

try {
    $totalPosts = (int) $pdo->query("SELECT COUNT(*) FROM dbProj_post")->fetchColumn();
} catch (Throwable $e) {
    $totalPosts = 0;
}

try {
    $totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM dbProj_user")->fetchColumn();
} catch (Throwable $e) {
    $totalUsers = 0;
}

$search = trim($_GET['search'] ?? '');
$attractions = [];

$sql = "
    SELECT 
        a.attraction_id,
        a.name AS attraction_name,
        a.description AS attraction_description,
        a.cover_image,
        a.country_id,
        a.type_id,
        a.created_at,
        c.name AS country_name,
        t.name AS type_name,
        COUNT(DISTINCT p.post_id) AS posts_count
    FROM dbProj_attraction a
    LEFT JOIN dbProj_country c
        ON a.country_id = c.country_id
    LEFT JOIN dbProj_attraction_type t
        ON a.type_id = t.type_id
    LEFT JOIN dbProj_post p
        ON a.country_id = p.country_id
";

$params = [];

if ($search !== '') {
    $sql .= "
        WHERE
            CAST(a.attraction_id AS CHAR) LIKE :search
            OR a.name LIKE :search
            OR a.description LIKE :search
            OR c.name LIKE :search
            OR t.name LIKE :search
    ";
    $params[':search'] = '%' . $search . '%';
}

$sql .= "
    GROUP BY
        a.attraction_id,
        a.name,
        a.description,
        a.cover_image,
        a.country_id,
        a.type_id,
        a.created_at,
        c.name,
        t.name
    ORDER BY a.attraction_id DESC
    LIMIT 10
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attractions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .gg-admin-dashboard {
        background: #f4f7fb;
        min-height: calc(100vh - 70px);
        padding: 38px 48px 70px;
    }

    .gg-dashboard-hero {
        background: linear-gradient(135deg, #223f72 0%, #31558f 100%);
        color: #ffffff;
        border-radius: 24px;
        padding: 34px 38px;
        margin-bottom: 30px;
        box-shadow: 0 18px 38px rgba(31, 63, 110, 0.22);
        position: relative;
        overflow: hidden;
    }

    .gg-dashboard-hero::after {
        content: "";
        position: absolute;
        width: 270px;
        height: 270px;
        border-radius: 50%;
        right: -90px;
        top: -130px;
        background: rgba(255, 255, 255, 0.08);
    }

    .gg-dashboard-hero-content,
    .gg-stats-row {
        position: relative;
        z-index: 2;
    }

    .gg-dashboard-title {
        font-size: 35px;
        font-weight: 900;
        margin-bottom: 8px;
        letter-spacing: -0.7px;
        color: #ffffff;
    }

    .gg-dashboard-subtitle {
        color: rgba(255, 255, 255, 0.82);
        margin: 0;
        font-size: 15px;
        max-width: 760px;
    }

    .gg-stats-row {
        margin-top: 32px;
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
        color: #ffffff;
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
        color: #ffffff;
    }

    .gg-card {
        background: #ffffff;
        border: 1px solid #e5ebf3;
        border-radius: 22px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.07);
        margin-bottom: 34px;
        overflow: hidden;
    }

    .gg-card-header {
        padding: 24px 26px;
        border-bottom: 1px solid #e8eef6;
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
        flex-wrap: wrap;
    }

    .gg-card-title {
        margin: 0;
        font-size: 24px;
        font-weight: 900;
        color: #101828;
    }

    .gg-card-text {
        margin: 5px 0 0;
        color: #667085;
        font-size: 13px;
    }

    .gg-search-form {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .gg-search-input {
        border: 1px solid #d9e2ef;
        border-radius: 12px;
        padding: 10px 14px;
        font-size: 14px;
        width: 270px;
        background: #ffffff;
    }

    .gg-search-input:focus {
        border-color: #4169e1;
        box-shadow: 0 0 0 4px rgba(65, 105, 225, 0.10);
        outline: none;
    }

    .gg-btn {
        background: #4169e1;
        color: #ffffff;
        border: 0;
        border-radius: 11px;
        padding: 10px 17px;
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
        color: #ffffff;
        transform: translateY(-1px);
    }

    .gg-btn-light {
        background: #ffffff;
        color: #2446bb;
        border: 1px solid #b9ccff;
        box-shadow: none;
    }

    .gg-btn-light:hover {
        background: #4169e1;
        color: #ffffff;
        border-color: #4169e1;
    }

    .gg-clear-btn {
        background: #f3f6fb;
        color: #344054;
        border: 1px solid #e3e8f0;
        border-radius: 11px;
        padding: 10px 16px;
        font-size: 14px;
        font-weight: 800;
        text-decoration: none;
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
        white-space: nowrap;
    }

    .gg-table tbody td {
        padding: 16px 20px;
        vertical-align: middle;
        border-bottom: 1px solid #eef2f6;
        color: #111827;
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

    .gg-attraction-cell {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .gg-attraction-image {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        overflow: hidden;
        background: linear-gradient(135deg, #4169e1, #75d5f4);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 900;
        flex-shrink: 0;
    }

    .gg-attraction-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .gg-attraction-name {
        font-weight: 900;
        color: #101828;
        margin-bottom: 4px;
    }

    .gg-desc {
        font-size: 13px;
        color: #667085;
        max-width: 560px;
        line-height: 1.35;
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
        white-space: nowrap;
    }

    .gg-type-pill {
        background: #f3efff;
        color: #6f42c1;
        border: 1px solid #d6c5ff;
        border-radius: 999px;
        padding: 7px 13px;
        font-size: 13px;
        font-weight: 800;
        display: inline-block;
        white-space: nowrap;
    }

    .gg-post-pill {
        background: #ecfdf3;
        color: #027a48;
        border: 1px solid #b7ebc6;
        border-radius: 999px;
        padding: 7px 13px;
        font-size: 13px;
        font-weight: 800;
        display: inline-block;
        min-width: 42px;
        text-align: center;
    }

    .gg-manage-row {
        padding: 20px 24px;
        background: #4169e1;
        border-top: 1px solid #3155c9;
    }

    .gg-manage-row .gg-btn {
        color: #ffffff !important;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.35);
        box-shadow: none;
    }

    .gg-manage-row .gg-btn:hover {
        background: #ffffff;
        color: #2446bb !important;
    }

    .gg-dashboard-footer {
        background: #4169e1;
        min-height: 155px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        color: #ffffff;
        text-align: center;
        border-radius: 22px;
        margin-top: 34px;
    }

    .gg-dashboard-footer-logo {
        font-size: 22px;
        font-weight: 900;
        line-height: 1;
        margin-bottom: 10px;
    }

    .gg-dashboard-footer-text {
        margin: 0;
        font-size: 13px;
        opacity: 0.9;
    }
</style>

<div class="gg-admin-dashboard">

    <section class="gg-dashboard-hero">
        <div class="gg-dashboard-hero-content">
            <h1 class="gg-dashboard-title">Admin Dashboard</h1>
            <p class="gg-dashboard-subtitle">
                Manage GulfGuide locations, attractions, posts, creator requests, and users from one control panel.
            </p>
        </div>

        <div class="row g-4 gg-stats-row">
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
                        <div class="gg-stat-number"><?= htmlspecialchars($totalPosts) ?></div>
                        <p class="gg-stat-label">Posts</p>
                    </div>
                    <div class="gg-stat-icon"><i class="ph ph-article"></i></div>
                </div>
            </div>
        </div>
    </section>

    <section class="gg-card">
        <div class="gg-card-header">
            <div>
                <h2 class="gg-card-title">Latest Attractions</h2>
                <p class="gg-card-text">
                    <?php if ($search && $attractions): ?>
                        Full-text search results for "<?= htmlspecialchars($search) ?>".
                    <?php elseif ($search && !$attractions): ?>
                        No attraction results found for "<?= htmlspecialchars($search) ?>". Try another keyword.
                    <?php else: ?>
                        Recent attractions with their location, type, and related post activity.
                    <?php endif; ?>
                </p>
            </div>

            <form method="GET" action="<?= APP_BASE ?>/admin/" class="gg-search-form">
                <input
                    type="text"
                    name="search"
                    class="gg-search-input"
                    placeholder="Search attractions..."
                    value="<?= htmlspecialchars($search) ?>"
                >

                <button type="submit" class="gg-btn">
                    <i class="ph ph-magnifying-glass"></i>
                    Search
                </button>

                <?php if ($search): ?>
                    <a href="<?= APP_BASE ?>/admin/" class="gg-clear-btn">
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table gg-table align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Attraction</th>
                        <th>Location</th>
                        <th>Type</th>
                        <th>Posts</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$attractions): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <?= $search ? 'No attractions matched your search.' : 'No attractions found.' ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($attractions as $attraction): ?>
                        <tr>
                            <td>
                                <div class="gg-id">
                                    <?= htmlspecialchars($attraction['attraction_id']) ?>
                                </div>
                            </td>

                            <td>
                                <div class="gg-attraction-cell">
                                    <div class="gg-attraction-image">
                                        <?php if (!empty($attraction['cover_image'])): ?>
                                            <img src="<?= htmlspecialchars($attraction['cover_image']) ?>" alt="<?= htmlspecialchars($attraction['attraction_name']) ?>">
                                        <?php else: ?>
                                            <?= htmlspecialchars(mb_strtoupper(mb_substr($attraction['attraction_name'] ?? 'A', 0, 1))) ?>
                                        <?php endif; ?>
                                    </div>

                                    <div>
                                        <div class="gg-attraction-name">
                                            <?= htmlspecialchars($attraction['attraction_name']) ?>
                                        </div>

                                        <div class="gg-desc">
                                            <?= htmlspecialchars(mb_strimwidth($attraction['attraction_description'] ?? '', 0, 95, '...')) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="gg-pill">
                                    <?= htmlspecialchars($attraction['country_name'] ?? 'No location') ?>
                                </span>
                            </td>

                            <td>
                                <span class="gg-type-pill">
                                    <?= htmlspecialchars($attraction['type_name'] ?? 'No type') ?>
                                </span>
                            </td>

                            <td>
                                <span class="gg-post-pill">
                                    <?= htmlspecialchars($attraction['posts_count']) ?>
                                </span>
                            </td>

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

        <div class="gg-manage-row">
            <a href="<?= APP_BASE ?>/admin/location-list" class="gg-btn">
                <i class="ph ph-map-trifold"></i>
                Manage Locations
            </a>
        </div>
    </section>

    <footer class="gg-dashboard-footer">
        <div class="gg-dashboard-footer-logo">GulfGuide</div>
        <p class="gg-dashboard-footer-text">© 2026 GulfGuide. All rights reserved.</p>
    </footer>

</div>