<?php
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    echo "<script>window.location.href='" . APP_BASE . "/admin/location-list';</script>";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM dbProj_country WHERE country_id = ?");
$stmt->execute([$id]);
$location = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$location) {
    $_SESSION['status'] = 'Location not found.';
    $_SESSION['status_code'] = 'error';

    echo "<script>window.location.href='" . APP_BASE . "/admin/location-list';</script>";
    exit;
}

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
            UPDATE dbProj_country
            SET
                flag_image = ?,
                official_tourism_website = ?,
                display_image = ?,
                name = ?,
                description = ?
            WHERE country_id = ?
        ");

        $stmt->execute([
            $flagImage,
            $tourismWebsite,
            $displayImage,
            $name,
            $description,
            $id
        ]);

        $_SESSION['status'] = 'Location updated successfully.';
        $_SESSION['status_code'] = 'success';

        echo "<script>window.location.href='" . APP_BASE . "/admin/location-list';</script>";
        exit;
    }
}
?>

<style>
    .edit-location-page {
        padding: 42px 54px 70px;
        background: #f4f7fb;
        min-height: calc(100vh - 70px);
    }

    .edit-location-hero {
        background: linear-gradient(135deg, #233f71 0%, #31558f 100%);
        color: #ffffff;
        border-radius: 22px;
        padding: 32px 36px;
        margin-bottom: 28px;
        box-shadow: 0 18px 38px rgba(31, 63, 110, 0.20);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        position: relative;
        overflow: hidden;
    }

    .edit-location-hero::after {
        content: "";
        position: absolute;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        right: -90px;
        top: -120px;
        background: rgba(255, 255, 255, 0.08);
    }

    .edit-location-hero-content {
        position: relative;
        z-index: 2;
    }

    .edit-location-title {
        font-size: 32px;
        font-weight: 900;
        margin-bottom: 8px;
        letter-spacing: -0.6px;
    }

    .edit-location-subtitle {
        color: rgba(255, 255, 255, 0.78);
        font-size: 15px;
        margin: 0;
    }

    .edit-location-back-btn {
        position: relative;
        z-index: 2;
        background: rgba(255, 255, 255, 0.14);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.28);
        border-radius: 999px;
        padding: 10px 18px;
        font-size: 13px;
        font-weight: 800;
        text-decoration: none;
        transition: 0.2s ease;
        white-space: nowrap;
    }

    .edit-location-back-btn:hover {
        background: #ffffff;
        color: #2446bb;
    }

    .edit-location-card {
        background: #ffffff;
        border: 1px solid #e5ebf3;
        border-radius: 22px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.07);
        overflow: hidden;
    }

    .edit-location-card-header {
        padding: 24px 28px;
        border-bottom: 1px solid #e8eef6;
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
    }

    .edit-location-card-title {
        margin: 0;
        font-size: 22px;
        font-weight: 900;
        color: #101828;
        letter-spacing: -0.3px;
    }

    .edit-location-card-text {
        margin: 5px 0 0;
        color: #667085;
        font-size: 13px;
    }

    .edit-location-id {
        background: #eaf3ff;
        color: #2f55c8;
        border: 1px solid #cfe1ff;
        border-radius: 999px;
        padding: 8px 14px;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
    }

    .edit-location-card-body {
        padding: 28px;
    }

    .edit-location-label {
        font-size: 14px;
        font-weight: 800;
        color: #344054;
        margin-bottom: 8px;
    }

    .edit-location-input,
    .edit-location-textarea {
        border: 1px solid #d9e2ef;
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 14px;
        color: #111827;
        background: #ffffff;
        transition: 0.2s ease;
    }

    .edit-location-input:focus,
    .edit-location-textarea:focus {
        border-color: #4169e1;
        box-shadow: 0 0 0 4px rgba(65, 105, 225, 0.10);
    }

    .edit-location-help {
        font-size: 12px;
        color: #7b8794;
        margin-top: 6px;
    }

    .edit-location-alert {
        border-radius: 16px;
        border: 1px solid #ffd1d1;
        background: #fff5f5;
        color: #b42318;
        padding: 16px 18px;
        margin-bottom: 22px;
        font-size: 14px;
        font-weight: 600;
    }

    .edit-location-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 26px;
        padding-top: 22px;
        border-top: 1px solid #eef2f6;
    }

    .edit-location-save-btn {
        background: #4169e1;
        color: #ffffff;
        border: 0;
        border-radius: 11px;
        padding: 12px 22px;
        font-size: 14px;
        font-weight: 900;
        box-shadow: 0 8px 18px rgba(65, 105, 225, 0.22);
        transition: 0.2s ease;
    }

    .edit-location-save-btn:hover {
        background: #3155c9;
        color: #ffffff;
        transform: translateY(-1px);
    }

    .edit-location-cancel-btn {
        background: #f3f6fb;
        color: #344054;
        border: 1px solid #e3e8f0;
        border-radius: 11px;
        padding: 12px 20px;
        font-size: 14px;
        font-weight: 800;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .edit-location-cancel-btn:hover {
        background: #eaf3ff;
        color: #2446bb;
        border-color: #cfe1ff;
    }

    @media (max-width: 768px) {
        .edit-location-page {
            padding: 28px 18px 50px;
        }

        .edit-location-hero {
            flex-direction: column;
            align-items: flex-start;
            padding: 26px;
        }

        .edit-location-title {
            font-size: 27px;
        }

        .edit-location-card-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .edit-location-card-body {
            padding: 22px;
        }

        .edit-location-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .edit-location-save-btn,
        .edit-location-cancel-btn {
            width: 100%;
            text-align: center;
        }
    }
</style>

<div class="edit-location-page">

    <section class="edit-location-hero">
        <div class="edit-location-hero-content">
            <h1 class="edit-location-title">Edit Location</h1>
            <p class="edit-location-subtitle">
                Update the selected country or travel location information.
            </p>
        </div>

        <a href="<?= APP_BASE ?>/admin/location-list" class="edit-location-back-btn">
            ← Back to Locations
        </a>
    </section>

    <?php if ($errors): ?>
        <div class="edit-location-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section class="edit-location-card">
        <div class="edit-location-card-header">
            <div>
                <h2 class="edit-location-card-title">
                    Edit <?= htmlspecialchars($_POST['name'] ?? $location['name']) ?>
                </h2>
                <p class="edit-location-card-text">
                    Change the details below, then save your updates.
                </p>
            </div>

            <div class="edit-location-id">
                Location ID: <?= htmlspecialchars($id) ?>
            </div>
        </div>

        <div class="edit-location-card-body">
            <form method="POST" action="<?= APP_BASE ?>/admin/edit-location?id=<?= htmlspecialchars($id) ?>">

                <div class="row g-4">

                    <div class="col-md-6">
                        <label class="form-label edit-location-label">Location Name</label>
                        <input
                            type="text"
                            name="name"
                            class="form-control edit-location-input"
                            value="<?= htmlspecialchars($_POST['name'] ?? $location['name']) ?>"
                            placeholder="Example: Kingdom of Bahrain"
                            required
                        >
                        <div class="edit-location-help">
                            Update the country or location name.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label edit-location-label">Official Tourism Website</label>
                        <input
                            type="text"
                            name="official_tourism_website"
                            class="form-control edit-location-input"
                            value="<?= htmlspecialchars($_POST['official_tourism_website'] ?? $location['official_tourism_website']) ?>"
                            placeholder="https://example.com"
                            required
                        >
                        <div class="edit-location-help">
                            Add or update the official tourism link.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label edit-location-label">Flag Image Path</label>
                        <input
                            type="text"
                            name="flag_image"
                            class="form-control edit-location-input"
                            value="<?= htmlspecialchars($_POST['flag_image'] ?? $location['flag_image']) ?>"
                            placeholder="assets/images/flags/bahrain.png"
                            required
                        >
                        <div class="edit-location-help">
                            Use a valid flag image path or URL.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label edit-location-label">Display Image Path</label>
                        <input
                            type="text"
                            name="display_image"
                            class="form-control edit-location-input"
                            value="<?= htmlspecialchars($_POST['display_image'] ?? $location['display_image']) ?>"
                            placeholder="assets/images/countries/bahrain.jpg"
                            required
                        >
                        <div class="edit-location-help">
                            This image appears on the location page.
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label edit-location-label">Description</label>
                        <textarea
                            name="description"
                            class="form-control edit-location-textarea"
                            rows="6"
                            placeholder="Write a short description about this location..."
                            required
                        ><?= htmlspecialchars($_POST['description'] ?? $location['description']) ?></textarea>
                    </div>

                </div>

                <div class="edit-location-actions">
                    <button type="submit" class="edit-location-save-btn">
                        Update Location
                    </button>

                    <a href="<?= APP_BASE ?>/admin/location-list" class="edit-location-cancel-btn">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </section>

</div>