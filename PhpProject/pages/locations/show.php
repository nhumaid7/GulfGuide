<?php
$attractionId = (int)($params['id'] ?? 0);

if ($attractionId <= 0) abort(404);

// ── Fetch attraction with country + type ──────────────────────────────────
$stmt = $pdo->prepare("
    SELECT a.*,
           c.name  AS country_name,
           c.flag_image,
           c.official_tourism_website,
           t.name  AS type_name
    FROM   dbProj_attraction a
    LEFT JOIN dbProj_country         c ON a.country_id = c.country_id
    LEFT JOIN dbProj_attraction_type t ON a.type_id    = t.type_id
    WHERE  a.attraction_id = :id
");
$stmt->execute([':id' => $attractionId]);
$a = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$a) abort(404);


// ── Fetch media for this attraction ──────────────────────────────────────
$mediaStmt = $pdo->prepare("
    SELECT * FROM dbProj_attraction_media
    WHERE attraction_id = :id
");
$mediaStmt->execute([':id' => $attractionId]);
$mediaFiles = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

// ── Fetch related attractions (same country, exclude current) ────────────
$relStmt = $pdo->prepare("
    SELECT a.attraction_id, a.name, a.cover_image, t.name AS type_name
    FROM   dbProj_attraction a
    LEFT JOIN dbProj_attraction_type t ON a.type_id = t.type_id
    WHERE  a.country_id    = :cid
    AND    a.attraction_id != :aid
    LIMIT  3
");
$relStmt->execute([':cid' => $a['country_id'], ':aid' => $attractionId]);
$related = $relStmt->fetchAll(PDO::FETCH_ASSOC);

$baseUrl = rtrim(str_replace('/index.php', '', APP_BASE), '/');
?>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/locations.css">

<?php require_once __DIR__ . '/../../partials/user/navbar.php'; ?>

<!-- ── Page heading ──────────────────────────────────────────────────────── -->
<div class="locations-page pb-0">
    <div class="d-flex align-items-center gap-2 mb-1 mt-3">
        <?php if (!empty($a['type_name'])): ?>
        <span class="attraction-card__type" style="position:static; background:var(--royal-blue-100); color:var(--brand-primary);">
            <?= htmlspecialchars($a['type_name']) ?>
        </span>
        <?php endif; ?>
    </div>
    <h2 class="fw-bold mb-1"><?= htmlspecialchars($a['name']) ?></h2>
    <p class="text-muted mb-0">
        <i class="ph ph-map-pin me-1"></i><?= htmlspecialchars($a['country_name'] ?? '—') ?>
    </p>
</div>

<!-- ── Main content ──────────────────────────────────────────────────────── -->
<div class="locations-page">
    <div class="row g-4">

        <!-- Left: details -->
        <div class="col-lg-8">

            <!-- Description card -->
            <div class="show-card mb-4">
                <h5 class="show-card__title">
                    <i class="ph ph-info me-2"></i>About this Attraction
                </h5>
                <p class="show-card__text"><?= nl2br(htmlspecialchars($a['description'] ?? '')) ?></p>
            </div>

            <!-- Media gallery -->
            <?php if (!empty($mediaFiles)): ?>
            <div class="show-card mb-4">
                <h5 class="show-card__title">
                    <i class="ph ph-images me-2"></i>Gallery
                </h5>
                <div class="show-gallery">
                    <?php foreach ($mediaFiles as $m): ?>
                    <?php if ($m['media_type'] === 'image'): ?>
                        <div class="show-gallery__item">
                            <img src="<?= htmlspecialchars($m['file_path']) ?>"
                                 alt="Gallery image"
                                 onclick="openLightbox(this.src)">
                        </div>
                    <?php elseif ($m['media_type'] === 'video'): ?>
                        <div class="show-gallery__item show-gallery__item--video">
                            <video controls>
                                <source src="<?= htmlspecialchars($m['file_path']) ?>">
                            </video>
                        </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- Right: info sidebar -->
        <div class="col-lg-4">

            <!-- Quick info -->
            <div class="show-card mb-4">
                <h5 class="show-card__title">
                    <i class="ph ph-list-bullets me-2"></i>Quick Info
                </h5>
                <ul class="show-info-list">
                    <li>
                        <span class="show-info-label">Country</span>
                        <span class="show-info-value">
                            <?php if (!empty($a['flag_image'])): ?>
                                <img src="<?= htmlspecialchars($a['flag_image']) ?>" alt="flag"
                                     style="width:20px;height:14px;object-fit:cover;border-radius:2px;">
                            <?php endif; ?>
                            <?= htmlspecialchars($a['country_name'] ?? '—') ?>
                        </span>
                    </li>
                    <li>
                        <span class="show-info-label">Type</span>
                        <span class="show-info-value"><?= htmlspecialchars($a['type_name'] ?? '—') ?></span>
                    </li>
                    <li>
                        <span class="show-info-label">Added</span>
                        <span class="show-info-value">
                            <?= !empty($a['created_at']) ? (new DateTime($a['created_at']))->format('M j, Y') : '—' ?>
                        </span>
                    </li>
                    <?php if (!empty($a['official_tourism_website'])): ?>
                    <li>
                        <span class="show-info-label">Tourism Site</span>
                        <a href="<?= htmlspecialchars($a['official_tourism_website']) ?>"
                           target="_blank" class="show-info-value show-info-link">
                            Visit <i class="ph ph-arrow-square-out"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Back button -->
            <a href="<?= APP_BASE ?>/locations/all" class="btn btn-outline-secondary w-100">
                <i class="ph ph-arrow-left me-1"></i>Back to Locations
            </a>
        </div>

    </div>

    <!-- Related attractions -->
    <?php if (!empty($related)): ?>
    <div class="mt-5">
        <h5 class="fw-bold mb-3">More from <?= htmlspecialchars($a['country_name'] ?? 'this country') ?></h5>
        <div class="row g-3">
            <?php foreach ($related as $r): ?>
            <div class="col-sm-4">
                <a href="<?= APP_BASE ?>/locations/<?= $r['attraction_id'] ?>" class="text-decoration-none">
                    <div class="attraction-card">
                        <div class="attraction-card__img" style="height:140px;">
                            <?php if (!empty($r['cover_image'])): ?>
                                <img src="<?= htmlspecialchars($r['cover_image']) ?>"
                                     alt="<?= htmlspecialchars($r['name']) ?>">
                            <?php else: ?>
                                <div class="attraction-card__img--placeholder"><i class="ph ph-image"></i></div>
                            <?php endif; ?>
                            <?php if (!empty($r['type_name'])): ?>
                            <span class="attraction-card__type"><?= htmlspecialchars($r['type_name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="attraction-card__body" style="padding:.75rem 1rem;">
                            <h6 class="attraction-card__name" style="font-size:var(--font-size-p-s);">
                                <?= htmlspecialchars($r['name']) ?>
                            </h6>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /.locations-page -->

<!-- Lightbox -->
<div id="lightbox" class="lightbox d-none" onclick="closeLightbox()">
    <img id="lightboxImg" src="" alt="Full image">
</div>

<script>
function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.remove('d-none');
}
function closeLightbox() {
    document.getElementById('lightbox').classList.add('d-none');
}
</script>
