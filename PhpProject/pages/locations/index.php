<?php
$isAdminView = isset($_GET['role']) && $_GET['role'] === 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_location'])) {
    if (!$isAdminView) {
        abort(403);
    }

    $countryId = filter_input(INPUT_POST, 'country_id', FILTER_VALIDATE_INT);

    if ($countryId) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                DELETE FROM dbProj_post_media
                WHERE post_id IN (
                    SELECT post_id FROM dbProj_post WHERE country_id = ?
                )
            ");
            $stmt->execute([$countryId]);

            $stmt = $pdo->prepare("
                DELETE FROM dbProj_comment
                WHERE post_id IN (
                    SELECT post_id FROM dbProj_post WHERE country_id = ?
                )
            ");
            $stmt->execute([$countryId]);

            $stmt = $pdo->prepare("
                DELETE FROM dbProj_reaction
                WHERE post_id IN (
                    SELECT post_id FROM dbProj_post WHERE country_id = ?
                )
            ");
            $stmt->execute([$countryId]);

            $stmt = $pdo->prepare("DELETE FROM dbProj_post WHERE country_id = ?");
            $stmt->execute([$countryId]);

            $stmt = $pdo->prepare("
                DELETE FROM dbProj_attraction_media
                WHERE attraction_id IN (
                    SELECT attraction_id FROM dbProj_attraction WHERE country_id = ?
                )
            ");
            $stmt->execute([$countryId]);

            $stmt = $pdo->prepare("DELETE FROM dbProj_attraction WHERE country_id = ?");
            $stmt->execute([$countryId]);

            $stmt = $pdo->prepare("DELETE FROM dbProj_country WHERE country_id = ?");
            $stmt->execute([$countryId]);

            $pdo->commit();

            $_SESSION['status'] = 'Location deleted successfully.';
            $_SESSION['status_code'] = 'success';
        } catch (Throwable $e) {
            $pdo->rollBack();

            $_SESSION['status'] = 'Delete failed: ' . $e->getMessage();
            $_SESSION['status_code'] = 'error';
        }
    }

    header('Location: ' . APP_BASE . '/locations?role=admin');
    exit;
}

$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $stmt = $pdo->prepare("
        SELECT
            c.country_id,
            c.name,
            c.description,
            c.flag_image,
            c.display_image,
            c.official_tourism_website,
            COUNT(DISTINCT a.attraction_id) AS attractions_count,
            COUNT(DISTINCT p.post_id) AS posts_count
        FROM dbProj_country c
        LEFT JOIN dbProj_attraction a ON c.country_id = a.country_id
        LEFT JOIN dbProj_post p ON c.country_id = p.country_id
        WHERE c.name LIKE ?
        GROUP BY
            c.country_id,
            c.name,
            c.description,
            c.flag_image,
            c.display_image,
            c.official_tourism_website
        ORDER BY c.country_id DESC
    ");
    $stmt->execute(['%' . $search . '%']);
} else {
    $stmt = $pdo->query("
        SELECT
            c.country_id,
            c.name,
            c.description,
            c.flag_image,
            c.display_image,
            c.official_tourism_website,
            COUNT(DISTINCT a.attraction_id) AS attractions_count,
            COUNT(DISTINCT p.post_id) AS posts_count
        FROM dbProj_country c
        LEFT JOIN dbProj_attraction a ON c.country_id = a.country_id
        LEFT JOIN dbProj_post p ON c.country_id = p.country_id
        GROUP BY
            c.country_id,
            c.name,
            c.description,
            c.flag_image,
            c.display_image,
            c.official_tourism_website
        ORDER BY c.country_id DESC
    ");
}

$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="mb-1"><?= $isAdminView ? 'Manage Locations' : 'Locations' ?></h2>
            <p class="text-muted mb-0">
                <?= $isAdminView ? 'Edit and delete travel locations.' : 'Explore GulfGuide locations.' ?>
            </p>
        </div>

        <?php if ($isAdminView): ?>
            <a href="<?= APP_BASE ?>/admin/add-location" class="btn btn-primary">
                Add Location
            </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($_SESSION['status'])): ?>
        <div class="alert alert-<?= ($_SESSION['status_code'] ?? '') === 'success' ? 'success' : 'danger' ?>">
            <?= htmlspecialchars($_SESSION['status']) ?>
        </div>
        <?php unset($_SESSION['status'], $_SESSION['status_code']); ?>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <h5 class="mb-0">Locations List</h5>

                <form method="GET" action="<?= APP_BASE ?>/locations" class="d-flex gap-2">
                    <?php if ($isAdminView): ?>
                        <input type="hidden" name="role" value="admin">
                    <?php endif; ?>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Search locations..."
                        value="<?= htmlspecialchars($search) ?>"
                    >

                    <button type="submit" class="btn btn-primary">
                        Search
                    </button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Location</th>
                        <th>Tourism Website</th>
                        <th>Attractions</th>
                        <th>Posts</th>
                        <th>Description</th>

                        <?php if ($isAdminView): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                    </thead>

                    <tbody>
                    <?php if (!$locations): ?>
                        <tr>
                            <td colspan="<?= $isAdminView ? '8' : '7' ?>" class="text-center text-muted">
                                No locations found.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($locations as $location): ?>
                        <tr>
                            <td><?= htmlspecialchars($location['country_id']) ?></td>

                            <td>
                                <?php if (!empty($location['display_image'])): ?>
                                    <img
                                        src="<?= htmlspecialchars($location['display_image']) ?>"
                                        alt="<?= htmlspecialchars($location['name']) ?>"
                                        style="width: 70px; height: 50px; object-fit: cover; border-radius: 10px;"
                                    >
                                <?php else: ?>
                                    <span class="text-muted">No image</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <strong><?= htmlspecialchars($location['name']) ?></strong>
                            </td>

                            <td>
                                <?php if (!empty($location['official_tourism_website'])): ?>
                                    <a href="<?= htmlspecialchars($location['official_tourism_website']) ?>" target="_blank">
                                        Visit
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>

                            <td><?= htmlspecialchars($location['attractions_count']) ?></td>
                            <td><?= htmlspecialchars($location['posts_count']) ?></td>

                            <td>
                                <?= htmlspecialchars(mb_strimwidth($location['description'] ?? '', 0, 80, '...')) ?>
                            </td>

                            <?php if ($isAdminView): ?>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a
                                            href="<?= APP_BASE ?>/admin/edit-location?id=<?= htmlspecialchars($location['country_id']) ?>"
                                            class="btn btn-sm btn-light"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            action="<?= APP_BASE ?>/locations?role=admin"
                                            onsubmit="return confirm('Are you sure you want to delete this location? This will also delete related attractions and posts.');"
                                        >
                                            <input
                                                type="hidden"
                                                name="country_id"
                                                value="<?= htmlspecialchars($location['country_id']) ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="delete_location"
                                                class="btn btn-sm btn-danger"
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>