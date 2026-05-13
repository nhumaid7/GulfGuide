<?php
requireRole(ROLE_ADMIN);

$totalLocations = $pdo->query("SELECT COUNT(*) FROM dbProj_country")->fetchColumn();
$totalPosts = $pdo->query("SELECT COUNT(*) FROM dbProj_post")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM dbProj_user")->fetchColumn();

$stmt = $pdo->query("
    SELECT 
        c.country_id,
        c.name,
        c.description,
        COUNT(DISTINCT a.attraction_id) AS attractions_count,
        COUNT(DISTINCT p.post_id) AS posts_count
    FROM dbProj_country c
    LEFT JOIN dbProj_attraction a ON c.country_id = a.country_id
    LEFT JOIN dbProj_post p ON c.country_id = p.country_id
    GROUP BY c.country_id, c.name, c.description
    ORDER BY c.country_id DESC
    LIMIT 6
");

$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="mb-1">Admin Dashboard</h2>
            <p class="text-muted mb-0">Manage locations, posts, and users.</p>
        </div>

        <a href="<?= APP_BASE ?>/admin/add-location" class="btn btn-primary">
            Add Location
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <p class="text-muted mb-1">Locations</p>
                    <h2 class="mb-0"><?= htmlspecialchars($totalLocations) ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <p class="text-muted mb-1">Posts</p>
                    <h2 class="mb-0"><?= htmlspecialchars($totalPosts) ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <p class="text-muted mb-1">Users</p>
                    <h2 class="mb-0"><?= htmlspecialchars($totalUsers) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <h5 class="mb-3">Quick Actions</h5>

            <div class="row g-3">
                <div class="col-md-6">
                    <a href="<?= APP_BASE ?>/locations?role=admin" class="text-decoration-none">
                        <div class="border rounded-4 p-3 h-100">
                            <strong class="d-block text-dark">View Locations</strong>
                            <span class="text-muted">Edit and delete locations.</span>
                        </div>
                    </a>
                </div>

                <div class="col-md-6">
                    <a href="<?= APP_BASE ?>/admin/add-location" class="text-decoration-none">
                        <div class="border rounded-4 p-3 h-100">
                            <strong class="d-block text-dark">Add Location</strong>
                            <span class="text-muted">Create a new travel location.</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body">
            <h5 class="mb-3">Latest Locations</h5>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Location</th>
                        <th>Attractions</th>
                        <th>Posts</th>
                        <th>Action</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php if (!$locations): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No locations found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($locations as $location): ?>
                        <tr>
                            <td><?= htmlspecialchars($location['country_id']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($location['name']) ?></strong>
                                <div class="text-muted small">
                                    <?= htmlspecialchars(mb_strimwidth($location['description'] ?? '', 0, 70, '...')) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($location['attractions_count']) ?></td>
                            <td><?= htmlspecialchars($location['posts_count']) ?></td>
                            <td>
                                <a href="<?= APP_BASE ?>/admin/edit-location?id=<?= htmlspecialchars($location['country_id']) ?>" class="btn btn-sm btn-light">
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