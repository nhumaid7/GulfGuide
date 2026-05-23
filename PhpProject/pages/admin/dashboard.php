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

<div class="container-fluid py-4">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="mb-1">Admin Dashboard</h2>
            <p class="text-muted mb-0">Manage attractions, locations, posts, and users.</p>
        </div>

        <a href="<?= APP_BASE ?>/admin/add-location" class="btn btn-primary">
            Add Location
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Locations</p>
                    <h2 class="mb-0"><?= htmlspecialchars($totalLocations) ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Attractions</p>
                    <h2 class="mb-0"><?= htmlspecialchars($totalAttractions) ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Posts</p>
                    <h2 class="mb-0"><?= htmlspecialchars($totalPosts) ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Users</p>
                    <h2 class="mb-0"><?= htmlspecialchars($totalUsers) ?></h2>
                </div>
            </div>
        </div>

    </div>

    <!-- Quick Actions -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <h5 class="mb-3">Quick Actions</h5>

            <div class="row g-3">

                <div class="col-md-6">
                    <a href="<?= APP_BASE ?>/locations?role=admin" class="text-decoration-none">
                        <div class="border rounded-4 p-3 h-100">
                            <strong class="d-block text-dark">View Locations</strong>
                            <span class="text-muted">Edit and delete locations and their attractions.</span>
                        </div>
                    </a>
                </div>

                <div class="col-md-6">
                    <a href="<?= APP_BASE ?>/admin/add-location" class="text-decoration-none">
                        <div class="border rounded-4 p-3 h-100">
                            <strong class="d-block text-dark">Add Location</strong>
                            <span class="text-muted">Create a new travel location with attractions.</span>
                        </div>
                    </a>
                </div>

            </div>
        </div>
    </div>

    <!-- Latest Attractions Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body">
            <h5 class="mb-3">Latest Attractions</h5>

            <div class="table-responsive">
                <table class="table align-middle">
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
                            <td colspan="5" class="text-center text-muted">
                                No attractions found.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($attractions as $attraction): ?>
                        <tr>
                            <td><?= htmlspecialchars($attraction['attraction_id']) ?></td>

                            <td>
                                <strong><?= htmlspecialchars($attraction['attraction_name']) ?></strong>
                                <div class="text-muted small">
                                    <?= htmlspecialchars(mb_strimwidth($attraction['attraction_description'] ?? '', 0, 70, '...')) ?>
                                </div>
                            </td>

                            <td>
                                <?= htmlspecialchars($attraction['country_name'] ?? 'No country') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($attraction['posts_count']) ?>
                            </td>

                            <td>
                                <a href="<?= APP_BASE ?>/admin/edit-location?id=<?= htmlspecialchars($attraction['country_id']) ?>" class="btn btn-sm btn-light">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <a href="<?= APP_BASE ?>/locations?role=admin" class="btn btn-primary mt-2">
                Manage All Locations
            </a>
        </div>
    </div>

</div>