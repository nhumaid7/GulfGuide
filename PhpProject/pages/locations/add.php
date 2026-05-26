<?php
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
        try {
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

            echo "<script>window.location.href='" . APP_BASE . "/admin/location-list';</script>";
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Add failed: ' . $e->getMessage();
        }
    }
}
?>

<style>
    .gg-form-page {
        padding: 38px 48px 70px;
        background: #f4f7fb;
        min-height: calc(100vh - 70px);
    }

    .gg-form-hero {
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

    .gg-form-hero::after {
        content: "";
        position: absolute;
        width: 250px;
        height: 250px;
        border-radius: 50%;
        right: -90px;
        top: -120px;
        background: rgba(255, 255, 255, 0.08);
    }

    .gg-form-hero-content {
        position: relative;
        z-index: 2;
    }

    .gg-form-title {
        font-size: 34px;
        font-weight: 900;
        margin-bottom: 8px;
        letter-spacing: -0.7px;
        color: #ffffff;
    }

    .gg-form-subtitle {
        color: rgba(255, 255, 255, 0.82);
        margin: 0;
        font-size: 15px;
    }

    .gg-back-btn {
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

    .gg-back-btn:hover {
        background: #ffffff;
        color: #2446bb;
    }

    .gg-form-card {
        background: #ffffff;
        border: 1px solid #e5ebf3;
        border-radius: 22px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.07);
        overflow: hidden;
    }

    .gg-form-card-header {
        padding: 24px 28px;
        border-bottom: 1px solid #e8eef6;
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
    }

    .gg-form-card-title {
        margin: 0;
        font-size: 22px;
        font-weight: 900;
        color: #101828;
    }

    .gg-form-card-text {
        margin: 6px 0 0;
        color: #667085;
        font-size: 13px;
    }

    .gg-form-card-body {
        padding: 28px;
    }

    .gg-label {
        font-size: 14px;
        font-weight: 800;
        color: #344054;
        margin-bottom: 8px;
    }

    .gg-input,
    .gg-textarea {
        border: 1px solid #d9e2ef;
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 14px;
        transition: 0.2s ease;
    }

    .gg-input:focus,
    .gg-textarea:focus {
        border-color: #4169e1;
        box-shadow: 0 0 0 4px rgba(65, 105, 225, 0.10);
    }

    .gg-help {
        font-size: 12px;
        color: #7b8794;
        margin-top: 6px;
    }

    .gg-alert {
        border-radius: 16px;
        border: 1px solid #ffd1d1;
        background: #fff5f5;
        color: #b42318;
        padding: 16px 18px;
        margin-bottom: 22px;
        font-size: 14px;
        font-weight: 600;
    }

    .gg-form-actions {
        display: flex;
        gap: 12px;
        margin-top: 26px;
        padding-top: 22px;
        border-top: 1px solid #eef2f6;
    }

    .gg-save-btn {
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

    .gg-save-btn:hover {
        background: #3155c9;
        color: #ffffff;
        transform: translateY(-1px);
    }

    .gg-cancel-btn {
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

    .gg-cancel-btn:hover {
        background: #eaf3ff;
        color: #2446bb;
        border-color: #cfe1ff;
    }
</style>

<div class="gg-form-page">

    <section class="gg-form-hero">
        <div class="gg-form-hero-content">
            <h1 class="gg-form-title">Add Location</h1>
            <p class="gg-form-subtitle">
                Add a new GulfGuide country or travel location.
            </p>
        </div>

        <a href="<?= APP_BASE ?>/admin/location-list" class="gg-back-btn">
            ← Back to Locations
        </a>
    </section>

    <?php if ($errors): ?>
        <div class="gg-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section class="gg-form-card">
        <div class="gg-form-card-header">
            <h2 class="gg-form-card-title">Location Details</h2>
            <p class="gg-form-card-text">
                Fill in the details below to create a new location.
            </p>
        </div>

        <div class="gg-form-card-body">
            <form method="POST" action="<?= APP_BASE ?>/admin/add-location">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label gg-label">Location Name</label>
                        <input
                            type="text"
                            name="name"
                            class="form-control gg-input"
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                            placeholder="Example: Kingdom of Bahrain"
                            required
                        >
                        <div class="gg-help">Enter the country or travel location name.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label gg-label">Official Tourism Website</label>
                        <input
                            type="text"
                            name="official_tourism_website"
                            class="form-control gg-input"
                            value="<?= htmlspecialchars($_POST['official_tourism_website'] ?? '') ?>"
                            placeholder="https://example.com"
                            required
                        >
                        <div class="gg-help">Add the official tourism website link.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label gg-label">Flag Image Path</label>
                        <input
                            type="text"
                            name="flag_image"
                            class="form-control gg-input"
                            value="<?= htmlspecialchars($_POST['flag_image'] ?? '') ?>"
                            placeholder="assets/images/flags/bahrain.png"
                            required
                        >
                        <div class="gg-help">Use a valid flag image path or URL.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label gg-label">Display Image Path</label>
                        <input
                            type="text"
                            name="display_image"
                            class="form-control gg-input"
                            value="<?= htmlspecialchars($_POST['display_image'] ?? '') ?>"
                            placeholder="assets/images/countries/bahrain.jpg"
                            required
                        >
                        <div class="gg-help">This image appears on the location page.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label gg-label">Description</label>
                        <textarea
                            name="description"
                            class="form-control gg-textarea"
                            rows="6"
                            placeholder="Write a short description about this location..."
                            required
                        ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="gg-form-actions">
                    <button type="submit" class="gg-save-btn">
                        Save Location
                    </button>

                    <a href="<?= APP_BASE ?>/admin/location-list" class="gg-cancel-btn">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </section>

</div>