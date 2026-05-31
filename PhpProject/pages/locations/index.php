<?php
$isAdminLocationList = isset($uri) && $uri === '/admin/location-list';

if ($isAdminLocationList) {
    if (function_exists('requireRole') && defined('ROLE_ADMIN')) {
        requireRole(ROLE_ADMIN);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_location'])) {
        $locationId = filter_input(INPUT_POST, 'location_id', FILTER_VALIDATE_INT);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_location'])) {
        $locationId = filter_input(INPUT_POST, 'location_id', FILTER_VALIDATE_INT);

        if ($locationId) {
            try {
                $pdo->beginTransaction();

                $pdo->prepare("
                    DELETE FROM dbProj_attraction_media
                    WHERE attraction_id = ?
                ")->execute([$locationId]);

                $deleteStmt = $pdo->prepare("
                    DELETE FROM dbProj_attraction
                    WHERE attraction_id = ?
                ");
                $deleteStmt->execute([$locationId]);

                if ($deleteStmt->rowCount() < 1) {
                    throw new RuntimeException('Location not found or already deleted.');
                }

                $pdo->commit();

                $_SESSION['status'] = 'Location deleted successfully.';
                $_SESSION['status_code'] = 'success';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $_SESSION['status'] = 'Delete failed: ' . $e->getMessage();
                $_SESSION['status_code'] = 'error';
            }
        } else {
            $_SESSION['status'] = 'Invalid location selected.';
        if ($locationId) {
            try {
                $pdo->beginTransaction();

                $pdo->prepare("
                    DELETE FROM dbProj_attraction_media
                    WHERE attraction_id = ?
                ")->execute([$locationId]);

                $deleteStmt = $pdo->prepare("
                    DELETE FROM dbProj_attraction
                    WHERE attraction_id = ?
                ");
                $deleteStmt->execute([$locationId]);

                if ($deleteStmt->rowCount() < 1) {
                    throw new RuntimeException('Location not found or already deleted.');
                }

                $pdo->commit();

                $_SESSION['status'] = 'Location deleted successfully.';
                $_SESSION['status_code'] = 'success';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $_SESSION['status'] = 'Delete failed: ' . $e->getMessage();
                $_SESSION['status_code'] = 'error';
            }
        } else {
            $_SESSION['status'] = 'Invalid location selected.';
            $_SESSION['status_code'] = 'error';
        }

        echo "<script>window.location.href='" . APP_BASE . "/admin/location-list';</script>";
        exit;
    }

    $adminSearch = trim($_GET['search'] ?? '');
    $locations = [];

    $sql = "
        SELECT
            a.attraction_id,
            a.name,
            a.description,
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

    if ($adminSearch !== '') {
        $sql .= "
            WHERE
                CAST(a.attraction_id AS CHAR) LIKE :id_search
                OR a.name LIKE :name_search
                OR a.description LIKE :description_search
                OR c.name LIKE :country_search
                OR t.name LIKE :type_search
        ";

        $params = [
            ':id_search' => '%' . $adminSearch . '%',
            ':name_search' => '%' . $adminSearch . '%',
            ':description_search' => '%' . $adminSearch . '%',
            ':country_search' => '%' . $adminSearch . '%',
            ':type_search' => '%' . $adminSearch . '%',
        ];
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
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <style>
        .gg-admin-location-page {
            background: #f4f7fb;
            min-height: calc(100vh - 70px);
            padding: 38px 48px 70px;
        }

        .gg-admin-location-page,
        .gg-admin-location-page * {
            box-sizing: border-box;
        }

        .gg-location-hero {
            background: linear-gradient(135deg, #223f72 0%, #31558f 100%);
            color: #ffffff;
            border-radius: 24px;
            padding: 34px 38px;
            margin-bottom: 28px;
            box-shadow: 0 18px 38px rgba(31, 63, 110, 0.22);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            position: relative;
            overflow: hidden;
        }

        .gg-location-hero::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            right: -90px;
            top: -120px;
            background: rgba(255, 255, 255, 0.08);
        }

        .gg-location-hero-content {
            position: relative;
            z-index: 2;
        }

        .gg-location-title {
            font-size: 34px;
            font-weight: 900;
            margin-bottom: 8px;
            letter-spacing: -0.7px;
            color: #ffffff;
        }

        .gg-location-subtitle {
            color: rgba(255, 255, 255, 0.82);
            margin: 0;
            font-size: 15px;
            max-width: 720px;
        }

        .gg-location-add-btn,
        .gg-primary-btn {
            position: relative;
            z-index: 2;
            background: #ffffff;
            color: #2446bb;
            border: 0;
            border-radius: 999px;
            padding: 11px 18px;
            font-size: 13px;
            font-weight: 900;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 10px 22px rgba(20, 47, 108, 0.18);
            transition: 0.2s ease;
            white-space: nowrap;
        }

        .gg-location-add-btn:hover,
        .gg-primary-btn:hover {
            transform: translateY(-1px);
            background: #eaf1ff;
            color: #1b3ea5;
        }

        .gg-flash {
            border-radius: 16px;
            padding: 15px 18px;
            margin-bottom: 22px;
            font-size: 14px;
            font-weight: 700;
        }

        .gg-flash.alert-success {
            background: #edfdf4;
            color: #027a48;
            border: 1px solid #abefc6;
        }

        .gg-flash.alert-danger {
            background: #fff5f5;
            color: #b42318;
            border: 1px solid #f5b5b5;
        }

        .gg-location-card {
            background: #ffffff;
            border: 1px solid #e5ebf3;
            border-radius: 22px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.07);
            overflow: hidden;
        }

        .gg-location-card-header {
            padding: 24px 28px;
            border-bottom: 1px solid #e8eef6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            background: linear-gradient(135deg, #ffffff 0%, #f7faff 100%);
        }

        .gg-location-card-title {
            margin: 0 0 6px;
            font-size: 22px;
            font-weight: 900;
            color: #1f2937;
        }

        .gg-location-card-text {
            margin: 0;
            font-size: 14px;
            color: #667085;
        }

        .gg-location-search {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .gg-location-search input {
            min-width: 240px;
            border: 1px solid #d6deea;
            border-radius: 999px;
            padding: 11px 16px;
            font-size: 14px;
            outline: none;
            transition: 0.2s ease;
        }

        .gg-location-search input:focus {
            border-color: #4169e1;
            box-shadow: 0 0 0 4px rgba(65, 105, 225, 0.10);
        }

        .gg-clear-btn {
            background: #f3f6fb;
            color: #344054;
            border: 1px solid #e3e8f0;
            border-radius: 999px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            transition: 0.2s ease;
        }

        .gg-clear-btn:hover {
            background: #eaf3ff;
            color: #2446bb;
            border-color: #cfe1ff;
        }

        .gg-table {
            margin: 0;
        }

        .gg-table thead th {
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #667085;
            background: #f8fbff;
            padding: 16px 22px;
            border-bottom: 1px solid #e8eef6;
            white-space: nowrap;
        }

        .gg-table tbody td {
            padding: 20px 22px;
            vertical-align: middle;
            border-top: 1px solid #eef2f6;
        }

        .gg-location-id {
            background: #eef4ff;
            color: #2446bb;
            border-radius: 999px;
            padding: 7px 13px;
            font-size: 13px;
            font-weight: 800;
            display: inline-block;
            min-width: 42px;
            text-align: center;
            white-space: nowrap;
        }

        .gg-location-cell {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 260px;
        }

        .gg-location-image {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, #4169e1 0%, #6f8cff 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 20px;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 10px 24px rgba(65, 105, 225, 0.22);
        }

        .gg-location-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .gg-location-name {
            font-size: 15px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 4px;
        }

        .gg-location-desc {
            font-size: 13px;
            color: #667085;
            margin: 0;
            line-height: 1.5;
            word-break: break-word;
        }

        .gg-count-pill {
            background: #eff8ff;
            color: #175cd3;
            border: 1px solid #bfdcff;
            border-radius: 999px;
            padding: 7px 13px;
            font-size: 13px;
            font-weight: 800;
            display: inline-block;
            min-width: 42px;
            text-align: center;
            white-space: nowrap;
        }

        .gg-post-pill {
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

        .gg-edit-btn {
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

        .gg-edit-btn:hover {
            background: #4169e1;
            color: #ffffff;
            border-color: #4169e1;
            box-shadow: 0 8px 18px rgba(65, 105, 225, 0.20);
        }

        .gg-delete-btn {
            background: #ffffff;
            color: #b42318;
            border: 1px solid #f5b5b5;
            border-radius: 999px;
            padding: 8px 15px;
            font-size: 13px;
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s ease;
            white-space: nowrap;
        }

        .gg-delete-btn:hover {
            background: #dc3545;
            color: #ffffff;
            border-color: #dc3545;
            box-shadow: 0 8px 18px rgba(220, 53, 69, 0.20);
        }

        .gg-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .gg-admin-location-page {
                padding: 28px 18px 50px;
            }

            .gg-location-hero {
                flex-direction: column;
                align-items: flex-start;
                padding: 28px;
            }

            .gg-location-title {
                font-size: 28px;
            }

            .gg-location-card-header {
                align-items: flex-start;
            }

            .gg-location-search input {
                width: 100%;
                min-width: 100%;
            }
        }
    </style>

    <div class="gg-admin-location-page">

        <section class="gg-location-hero">
            <div class="gg-location-hero-content">
                <h1 class="gg-location-title">Manage Locations</h1>
                <p class="gg-location-subtitle">
                    View, search, edit, and delete GulfGuide locations.
                </p>
            </div>

            <a href="<?= APP_BASE ?>/admin/add-location" class="gg-location-add-btn">
                <i class="ph ph-plus"></i>
                Add Location
            </a>
        </section>

        <?php if (!empty($_SESSION['status'])): ?>
            <div class="gg-flash alert-<?= ($_SESSION['status_code'] ?? '') === 'success' ? 'success' : 'danger' ?>">
                <?= htmlspecialchars($_SESSION['status']) ?>
            </div>
            <?php unset($_SESSION['status'], $_SESSION['status_code']); ?>
        <?php endif; ?>

        <section class="gg-location-card">
            <div class="gg-location-card-header">
                <div>
                    <h2 class="gg-location-card-title">All Locations</h2>
                    <p class="gg-location-card-text">
                        <?= $adminSearch ? 'Search results for "' . htmlspecialchars($adminSearch) . '"' : 'Browse all saved locations.' ?>
                    </p>
                </div>

                <form method="GET" action="<?= APP_BASE ?>/admin/location-list" class="gg-location-search">
                    <input
                        type="text"
                        name="search"
                        placeholder="Search locations..."
                        value="<?= htmlspecialchars($adminSearch) ?>"
                    >

                    <button type="submit" class="gg-primary-btn">
                        <i class="ph ph-magnifying-glass"></i>
                        Search
                    </button>

                    <?php if ($adminSearch): ?>
                        <a href="<?= APP_BASE ?>/admin/location-list" class="gg-clear-btn">
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
                            <th>Location</th>
                            <th>Country</th>
                            <th>Type</th>
                            <th>Posts</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!$locations): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <?= $adminSearch ? 'No locations matched your search.' : 'No locations found.' ?>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($locations as $location): ?>
                            <tr>
                                <td>
                                    <div class="gg-location-id">
                                        <?= htmlspecialchars((string) $location['attraction_id']) ?>
                                    </div>
                                </td>

                                <td>
                                    <div class="gg-location-cell">
                                        <div class="gg-location-image">
                                            <?php if (!empty($location['cover_image'])): ?>
                                                <img
                                                    src="<?= htmlspecialchars($location['cover_image']) ?>"
                                                    alt="<?= htmlspecialchars($location['name']) ?>"
                                                >
                                            <?php else: ?>
                                                <?= htmlspecialchars(mb_strtoupper(mb_substr($location['name'] ?? 'L', 0, 1))) ?>
                                            <?php endif; ?>
                                        </div>

                                        <div>
                                            <div class="gg-location-name">
                                                <?= htmlspecialchars($location['name']) ?>
                                            </div>

                                            <p class="gg-location-desc">
                                                <?= htmlspecialchars(mb_strimwidth($location['description'] ?? '', 0, 95, '...')) ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="gg-count-pill">
                                        <?= htmlspecialchars($location['country_name'] ?? 'No country') ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="gg-post-pill">
                                        <?= htmlspecialchars($location['type_name'] ?? 'No type') ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="gg-post-pill">
                                        <?= htmlspecialchars((string) $location['posts_count']) ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="gg-actions">
                                        <a href="<?= APP_BASE ?>/admin/edit-location?id=<?= htmlspecialchars((string) $location['attraction_id']) ?>" class="gg-edit-btn">
                                            <i class="ph ph-pencil-simple"></i>
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            action="<?= APP_BASE ?>/admin/location-list"
                                            onsubmit="return confirm('Are you sure you want to delete this location? This will also delete its attraction media.');"
                                        >
                                            <input
                                                type="hidden"
                                                name="location_id"
                                                value="<?= htmlspecialchars((string) $location['attraction_id']) ?>"
                                            >

                                            <button type="submit" name="delete_location" class="gg-delete-btn">
                                                <i class="ph ph-trash"></i>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <?php
    return;
}

// ── Public: /locations/all ────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');

$perPage     = 9;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;

if ($search !== '') {
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM dbProj_attraction a WHERE a.name LIKE :s OR a.description LIKE :s2
    ");
    $countStmt->execute([':s' => '%'.$search.'%', ':s2' => '%'.$search.'%']);
} else {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM dbProj_attraction");
}
$totalRows  = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($totalRows / $perPage);

if ($search !== '') {
    $stmt = $pdo->prepare("
        SELECT a.*,
               c.name AS country_name,
               t.name AS type_name
        FROM   dbProj_attraction a
        LEFT JOIN dbProj_country        c ON a.country_id = c.country_id
        LEFT JOIN dbProj_attraction_type t ON a.type_id   = t.type_id
        WHERE  a.name LIKE :s OR a.description LIKE :s2
        ORDER  BY a.created_at DESC
        LIMIT  :limit OFFSET :offset
    ");
    $stmt->bindValue(':s',      '%'.$search.'%');
    $stmt->bindValue(':s2',     '%'.$search.'%');
    $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("
        SELECT a.*,
               c.name AS country_name,
               t.name AS type_name
        FROM   dbProj_attraction a
        LEFT JOIN dbProj_country        c ON a.country_id = c.country_id
        LEFT JOIN dbProj_attraction_type t ON a.type_id   = t.type_id
        ORDER  BY a.created_at DESC
        LIMIT  :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();
}
$attractions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$baseUrl = rtrim(str_replace('/index.php', '', APP_BASE), '/');
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/locations.css">

<?php require_once __DIR__ . '/../../partials/user/navbar.php'; ?>

<div class="locations-page">

    <?php if (!empty($_SESSION['status'])): ?>
    <div class="alert alert-<?= ($_SESSION['status_code'] ?? '') === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
        <?= htmlspecialchars($_SESSION['status']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['status'], $_SESSION['status_code']); ?>
    <?php endif; ?>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-0 fw-bold">
                All Attractions
                <span class="location-count-badge"><?= $totalRows ?></span>
            </h4>
            <p class="text-muted mb-0" style="font-size:var(--font-size-p-s);">
                <?= $search ? 'Search results for "' . htmlspecialchars($search) . '"' : 'Browse all available attractions' ?>
            </p>
        </div>
        <form method="GET" action="<?= APP_BASE ?>/locations/all" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm"
                   placeholder="Search attractions…" value="<?= htmlspecialchars($search) ?>"
                   style="width:220px">
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="ph ph-magnifying-glass me-1"></i>Search
            </button>
            <?php if ($search): ?>
            <a href="<?= APP_BASE ?>/locations/all" class="btn btn-sm btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($attractions)): ?>
    <div class="text-center py-5 text-muted">
        <i class="ph ph-map-pin-slash" style="font-size:3rem;opacity:.35;"></i>
        <p class="mt-2 fw-semibold">No attractions found.</p>
    </div>

    <?php else: ?>
    <div class="row g-4" id="attractionsGrid">
        <?php foreach ($attractions as $a): ?>
        <div class="col-sm-6 col-lg-4">
            <div class="attraction-card">
                <div class="attraction-card__img">
                    <?php if (!empty($a['cover_image'])): ?>
                        <img src="<?= htmlspecialchars($a['cover_image']) ?>"
                             alt="<?= htmlspecialchars($a['name']) ?>">
                    <?php else: ?>
                        <div class="attraction-card__img--placeholder">
                            <i class="ph ph-image"></i>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($a['type_name'])): ?>
                    <span class="attraction-card__type"><?= htmlspecialchars($a['type_name']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="attraction-card__body">
                    <div class="attraction-card__country">
                        <i class="ph ph-map-pin"></i>
                        <?= htmlspecialchars($a['country_name'] ?? '—') ?>
                    </div>
                    <h5 class="attraction-card__name"><?= htmlspecialchars($a['name']) ?></h5>
                    <p class="attraction-card__desc">
                        <?= htmlspecialchars(mb_strimwidth($a['description'] ?? '', 0, 100, '…')) ?>
                    </p>
                    <div class="attraction-card__footer">
                        <a href="<?= APP_BASE ?>/locations/<?= $a['attraction_id'] ?>"
                           class="btn btn-sm btn-primary attraction-card__btn">
                            View More <i class="ph ph-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="mt-4 d-flex justify-content-center">
        <ul class="pagination">
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $currentPage - 1 ?><?= $search ? '&search='.urlencode($search) : '' ?>">
                    <i class="ph ph-caret-left"></i>
                </a>
            </li>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $p ?><?= $search ? '&search='.urlencode($search) : '' ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $currentPage + 1 ?><?= $search ? '&search='.urlencode($search) : '' ?>">
                    <i class="ph ph-caret-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
    <?php endif; ?>

</div>
