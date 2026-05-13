<?php
requireRole(ROLE_ADMIN);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $flagImage = trim($_POST['flag_image'] ?? '');
    $tourismWebsite = trim($_POST['official_tourism_website'] ?? '');
    $displayImage = trim($_POST['display_image'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $errors[] = 'Location name is required.';
    }

    if ($flagImage === '') {
        $errors[] = 'Flag image path is required.';
    }

    if ($tourismWebsite === '') {
        $errors[] = 'Official tourism website is required.';
    }

    if ($displayImage === '') {
        $errors[] = 'Display image path is required.';
    }

    if ($description === '') {
        $errors[] = 'Description is required.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("
            INSERT INTO dbProj_country
                (flag_image, official_tourism_website, display_image, name, description)
            VALUES
                (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $flagImage,
            $tourismWebsite,
            $displayImage,
            $name,
            $description
        ]);

        $_SESSION['status'] = 'Location added successfully.';
        $_SESSION['status_code'] = 'success';

        header('Location: ' . APP_BASE . '/locations?role=admin');
        exit;
    }
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="mb-1">Add Location</h2>
            <p class="text-muted mb-0">Create a new travel location.</p>
        </div>

        <a href="<?= APP_BASE ?>/locations?role=admin" class="btn btn-light">
            Back to Locations
        </a>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body">
            <form method="POST" action="<?= APP_BASE ?>/admin/add-location">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Location Name</label>
                        <input 
                            type="text" 
                            name="name" 
                            class="form-control" 
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" 
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Official Tourism Website</label>
                        <input 
                            type="text" 
                            name="official_tourism_website" 
                            class="form-control" 
                            value="<?= htmlspecialchars($_POST['official_tourism_website'] ?? '') ?>" 
                            placeholder="https://example.com"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Flag Image Path</label>
                        <input 
                            type="text" 
                            name="flag_image" 
                            class="form-control" 
                            value="<?= htmlspecialchars($_POST['flag_image'] ?? '') ?>" 
                            placeholder="assets/images/flags/bahrain.png"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Display Image Path</label>
                        <input 
                            type="text" 
                            name="display_image" 
                            class="form-control" 
                            value="<?= htmlspecialchars($_POST['display_image'] ?? '') ?>" 
                            placeholder="assets/images/countries/bahrain.jpg"
                            required
                        >
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea 
                            name="description" 
                            class="form-control" 
                            rows="5" 
                            required
                        ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        Save Location
                    </button>

                    <a href="<?= APP_BASE ?>/locations?role=admin" class="btn btn-light">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>