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
    }
}
?>

<style>
    .add-location-page {
        padding: 42px 54px 70px;
        background: #f4f7fb;
        min-height: calc(100vh - 70px);
    }

    .add-location-hero {
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

    .add-location-hero::after {
        content: "";
        position: absolute;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        right: -90px;
        top: -120px;
        background: rgba(255, 255, 255, 0.08);
    }

    .add-location-hero-content {
        position: relative;
        z-index: 2;
    }

    .add-location-title {
        font-size: 32px;
        font-weight: 900;
        margin-bottom: 8px;
        letter-spacing: -0.6px;
    }

    .add-location-subtitle {
        color: rgba(255, 255, 255, 0.78);
        font-size: 15px;
        margin: 0;
    }

    .add-location-back-btn {
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

    .add-location-back-btn:hover {
        background: #ffffff;
        color: #2446bb;
    }

    .add-location-card {
        background: #ffffff;
        border: 1px solid #e5ebf3;
        border-radius: 22px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.07);
        overflow: hidden;
    }

    .add-location-card-header {
        padding: 24px 28px;
        border-bottom: 1px solid #e8eef6;
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
    }

    .add-location-card-title {
        margin: 0;
        font-size: 22px;
        font-weight: 900;
        color: #101828;
        letter-spacing: -0.3px;
    }

    .add-location-card-text {
        margin: 5px 0 0;
        color: #667085;
        font-size: 13px;
    }

    .add-location-card-body {
        padding: 28px;
    }

    .add-location-label {
        font-size: 14px;
        font-weight: 800;
        color: #344054;
        margin-bottom: 8px;
    }

    .add-location-input,
    .add-location-textarea {
        border: 1px solid #d9e2ef;
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 14px;
        color: #111827;
        background: #ffffff;
        transition: 0.2s ease;
    }

    .add-location-input:focus,
    .add-location-textarea:focus {
        border-color: #4169e1;
        box-shadow: 0 0 0 4px rgba(65, 105, 225, 0.10);
    }

    .add-location-help {
        font-size: 12px;
        color: #7b8794;
        margin-top: 6px;
    }

    .add-location-alert {
        border-radius: 16px;
        border: 1px solid #ffd1d1;
        background: #fff5f5;
        color: #b42318;
        padding: 16px 18px;
        margin-bottom: 22px;
        font-size: 14px;
        font-weight: 600;
    }

    .add-location-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 26px;
        padding-top: 22px;
        border-top: 1px solid #eef2f6;
    }

    .add-location-save-btn {
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

    .add-location-save-btn:hover {
        background: #3155c9;
        color: #ffffff;
        transform: translateY(-1px);
    }

    .add-location-cancel-btn {
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

    .add-location-cancel-btn:hover {
        background: #eaf3ff;
        color: #2446bb;
        border-color: #cfe1ff;
    }

    @media (max-width: 768px) {
        .add-location-page {
            padding: 28px 18px 50px;
        }

        .add-location-hero {
            flex-direction: column;
            align-items: flex-start;
            padding: 26px;
        }

        .add-location-title {
            font-size: 27px;
        }

        .add-location-card-body {
            padding: 22px;
        }

        .add-location-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .add-location-save-btn,
        .add-location-cancel-btn {
            width: 100%;
            text-align: center;
        }
    }
</style>

<div class="add-location-page">

    <section class="add-location-hero">
        <div class="add-location-hero-content">
            <h1 class="add-location-title">Add Location</h1>
            <p class="add-location-subtitle">
                Create a new country or travel location for GulfGuide.
            </p>
        </div>

        <a href="<?= APP_BASE ?>/admin/location-list" class="add-location-back-btn">
            ← Back to Locations
        </a>
    </section>

    <?php if ($errors): ?>
        <div class="add-location-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section class="add-location-card">
        <div class="add-location-card-header">
            <h2 class="add-location-card-title">Location Details</h2>
            <p class="add-location-card-text">
                Fill in the required information to add a new location.
            </p>
        </div>

        <div class="add-location-card-body">
            <form method="POST" action="<?= APP_BASE ?>/admin/add-location">

                <div class="row g-4">

                    <div class="col-md-6">
                        <label class="form-label add-location-label">Location Name</label>
                        <input
                            type="text"
                            name="name"
                            class="form-control add-location-input"
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                            placeholder="Example: Kingdom of Bahrain"
                            required
                        >
                        <div class="add-location-help">
                            Enter the country or location name.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label add-location-label">Official Tourism Website</label>
                        <input
                            type="text"
                            name="official_tourism_website"
                            class="form-control add-location-input"
                            value="<?= htmlspecialchars($_POST['official_tourism_website'] ?? '') ?>"
                            placeholder="https://example.com"
                            required
                        >
                        <div class="add-location-help">
                            Add the official tourism link.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label add-location-label">Flag Image Path</label>
                        <input
                            type="text"
                            name="flag_image"
                            class="form-control add-location-input"
                            value="<?= htmlspecialchars($_POST['flag_image'] ?? '') ?>"
                            placeholder="assets/images/flags/bahrain.png"
                            required
                        >
                        <div class="add-location-help">
                            Use a valid image path or URL.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label add-location-label">Display Image Path</label>
                        <input
                            type="text"
                            name="display_image"
                            class="form-control add-location-input"
                            value="<?= htmlspecialchars($_POST['display_image'] ?? '') ?>"
                            placeholder="assets/images/countries/bahrain.jpg"
                            required
                        >
                        <div class="add-location-help">
                            This image appears on the location page.
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label add-location-label">Description</label>
                        <textarea
                            name="description"
                            class="form-control add-location-textarea"
                            rows="6"
                            placeholder="Write a short description about this location..."
                            required
                        ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                </div>

                <div class="add-location-actions">
                    <button type="submit" class="add-location-save-btn">
                        Save Location
                    </button>

                    <a href="<?= APP_BASE ?>/admin/location-list" class="add-location-cancel-btn">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </section>

</div>