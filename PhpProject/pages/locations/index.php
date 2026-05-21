<?php
require_once __DIR__ . '/../../classes/attraction.php';

//
$isAdminView = isset($_GET['role']) && $_GET['role'] === 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_location'])) {
    if (!$isAdminView) abort(403);

    $countryId = filter_input(INPUT_POST, 'country_id', FILTER_VALIDATE_INT);
    if ($countryId) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM dbProj_post_media WHERE post_id IN (SELECT post_id FROM dbProj_post WHERE country_id = ?)")->execute([$countryId]);
            $pdo->prepare("DELETE FROM dbProj_comment WHERE post_id IN (SELECT post_id FROM dbProj_post WHERE country_id = ?)")->execute([$countryId]);
            $pdo->prepare("DELETE FROM dbProj_reaction WHERE post_id IN (SELECT post_id FROM dbProj_post WHERE country_id = ?)")->execute([$countryId]);
            $pdo->prepare("DELETE FROM dbProj_post WHERE country_id = ?")->execute([$countryId]);
            $pdo->prepare("DELETE FROM dbProj_attraction_media WHERE attraction_id IN (SELECT attraction_id FROM dbProj_attraction WHERE country_id = ?)")->execute([$countryId]);
            $pdo->prepare("DELETE FROM dbProj_attraction WHERE country_id = ?")->execute([$countryId]);
            $pdo->prepare("DELETE FROM dbProj_country WHERE country_id = ?")->execute([$countryId]);
            $pdo->commit();
            $_SESSION['status']      = 'Location deleted successfully.';
            $_SESSION['status_code'] = 'success';
        } catch (Throwable $e) {
            $pdo->rollBack();
            $_SESSION['status']      = 'Delete failed: ' . $e->getMessage();
            $_SESSION['status_code'] = 'error';
        }
    }
    header('Location: ' . APP_BASE . '/locations/all?role=admin');
    exit;
}

// ── Search ────────────────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');

// ── Pagination ────────────────────────────────────────────────────────────
$perPage     = 9;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;

// Count total for pagination
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

// Fetch attractions
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

<!-- ── Main content ──────────────────────────────────────────────────────── -->
<div class="locations-page">

    <!-- Flash message -->
    <?php if (!empty($_SESSION['status'])): ?>
    <div class="alert alert-<?= ($_SESSION['status_code'] ?? '') === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
        <?= htmlspecialchars($_SESSION['status']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['status'], $_SESSION['status_code']); ?>
    <?php endif; ?>

    <!-- Section header + search -->
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

    <!-- Cards grid -->
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

                <!-- Cover image -->
                <div class="attraction-card__img">
                    <?php if (!empty($a['cover_image'])): ?>
                        <img src="<?= htmlspecialchars($a['cover_image']) ?>"
                             alt="<?= htmlspecialchars($a['name']) ?>">
                    <?php else: ?>
                        <div class="attraction-card__img--placeholder">
                            <i class="ph ph-image"></i>
                        </div>
                    <?php endif; ?>

                    <!-- Type badge -->
                    <?php if (!empty($a['type_name'])): ?>
                    <span class="attraction-card__type"><?= htmlspecialchars($a['type_name']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Body -->
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
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav class="mt-4 d-flex justify-content-center" aria-label="Pagination">
        <ul class="pagination">
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $currentPage - 1 ?><?= $search ? '&search='.urlencode($search) : '' ?>">
                    <i class="ph ph-caret-left"></i>
                </a>
            </li>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $p ?><?= $search ? '&search='.urlencode($search) : '' ?>">
                    <?= $p ?>
                </a>
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

</div><!-- /.locations-page -->
