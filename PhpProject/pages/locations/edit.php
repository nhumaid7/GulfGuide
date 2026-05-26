<?php
$errors = [];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    echo "<script>window.location.href='" . APP_BASE . "/admin/location-list';</script>";
    exit;
}

$countries = [];
$types = [];

try {
    $countriesStmt = $pdo->query("
        SELECT country_id, name
        FROM dbProj_country
        ORDER BY name ASC
    ");
    $countries = $countriesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $countries = [];
}

try {
    $typesStmt = $pdo->query("
        SELECT type_id, name
        FROM dbProj_attraction_type
        ORDER BY name ASC
    ");
    $types = $typesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $types = [];
}

$stmt = $pdo->prepare("
    SELECT *
    FROM dbProj_attraction
    WHERE attraction_id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$attraction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$attraction) {
    $_SESSION['status'] = 'Attraction not found.';
    $_SESSION['status_code'] = 'error';

    echo "<script>window.location.href='" . APP_BASE . "/admin/location-list';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $countryId = filter_input(INPUT_POST, 'country_id', FILTER_VALIDATE_INT);
    $typeId = filter_input(INPUT_POST, 'type_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $coverImage = trim($_POST['cover_image'] ?? '');

    if (!$countryId) {
        $errors[] = 'Please select a location/country.';
    }

    if (!$typeId) {
        $errors[] = 'Please select an attraction type.';
    }

    if ($name === '') {
        $errors[] = 'Attraction name is required.';
    }

    if ($description === '') {
        $errors[] = 'Description is required.';
    }

    if ($coverImage === '') {
        $errors[] = 'Cover image path is required.';
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare("
                UPDATE dbProj_attraction
                SET
                    country_id = ?,
                    type_id = ?,
                    name = ?,
                    description = ?,
                    cover_image = ?
                WHERE attraction_id = ?
            ");

            $stmt->execute([
                $countryId,
                $typeId,
                $name,
                $description,
                $coverImage,
                $id
            ]);

            $_SESSION['status'] = 'Attraction updated successfully.';
            $_SESSION['status_code'] = 'success';

            echo "<script>window.location.href='" . APP_BASE . "/admin/location-list';</script>";
            exit;

        } catch (Throwable $e) {
            $errors[] = 'Update failed: ' . $e->getMessage();
        }
    }
}

$currentCountryId = $_POST['country_id'] ?? $attraction['country_id'];
$currentTypeId = $_POST['type_id'] ?? $attraction['type_id'];
$currentName = $_POST['name'] ?? $attraction['name'];
$currentDescription = $_POST['description'] ?? $attraction['description'];
$currentCoverImage = $_POST['cover_image'] ?? $attraction['cover_image'];
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
    }

    .gg-form-subtitle {
        color: rgba(255, 255, 255, 0.78);
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
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
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

    .gg-id-pill {
        background: #eaf3ff;
        color: #2f55c8;
        border: 1px solid #cfe1ff;
        border-radius: 999px;
        padding: 8px 14px;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
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
    .gg-select,
    .gg-textarea {
        border: 1px solid #d9e2ef;
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 14px;
        color: #111827;
        background: #ffffff;
        transition: 0.2s ease;
    }

    .gg-input:focus,
    .gg-select:focus,
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

    .gg-preview-box {
        background: #f8fbff;
        border: 1px solid #e3ecf8;
        border-radius: 16px;
        padding: 16px;
        display: flex;
        gap: 14px;
        align-items: center;
        margin-bottom: 24px;
    }

    .gg-preview-img {
        width: 74px;
        height: 74px;
        border-radius: 18px;
        overflow: hidden;
        background: linear-gradient(135deg, #4169e1, #75d5f4);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 24px;
        flex-shrink: 0;
    }

    .gg-preview-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .gg-preview-title {
        font-weight: 900;
        color: #101828;
        margin-bottom: 4px;
    }

    .gg-preview-text {
        color: #667085;
        font-size: 13px;
        margin: 0;
    }

    .gg-form-actions {
        display: flex;
        align-items: center;
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

    @media (max-width: 768px) {
        .gg-form-page {
            padding: 28px 18px 50px;
        }

        .gg-form-hero {
            flex-direction: column;
            align-items: flex-start;
            padding: 28px;
        }

        .gg-form-title {
            font-size: 28px;
        }

        .gg-form-card-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .gg-form-card-body {
            padding: 22px;
        }

        .gg-preview-box {
            flex-direction: column;
            align-items: flex-start;
        }

        .gg-form-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .gg-save-btn,
        .gg-cancel-btn {
            width: 100%;
            text-align: center;
        }
    }
</style>

<div class="gg-form-page">

    <section class="gg-form-hero">
        <div class="gg-form-hero-content">
            <h1 class="gg-form-title">Edit Attraction</h1>
            <p class="gg-form-subtitle">
                Update attraction information, location, type, description, and cover image.
            </p>
        </div>

        <a href="<?= APP_BASE ?>/admin/location-list" class="gg-back-btn">
            ← Back to Manage Locations
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
            <div>
                <h2 class="gg-form-card-title">
                    Edit <?= htmlspecialchars($currentName) ?>
                </h2>
                <p class="gg-form-card-text">
                    Change the attraction details below, then save your updates.
                </p>
            </div>

            <span class="gg-id-pill">
                Attraction ID: <?= htmlspecialchars($id) ?>
            </span>
        </div>

        <div class="gg-form-card-body">

            <div class="gg-preview-box">
                <div class="gg-preview-img">
                    <?php if (!empty($currentCoverImage)): ?>
                        <img
                            src="<?= htmlspecialchars($currentCoverImage) ?>"
                            alt="<?= htmlspecialchars($currentName) ?>"
                        >
                    <?php else: ?>
                        <?= htmlspecialchars(mb_strtoupper(mb_substr($currentName ?? 'A', 0, 1))) ?>
                    <?php endif; ?>
                </div>

                <div>
                    <div class="gg-preview-title">
                        <?= htmlspecialchars($currentName) ?>
                    </div>
                    <p class="gg-preview-text">
                        Preview of the selected attraction cover image and name.
                    </p>
                </div>
            </div>

            <form method="POST" action="<?= APP_BASE ?>/admin/edit-location?id=<?= htmlspecialchars($id) ?>">

                <div class="row g-4">

                    <div class="col-md-6">
                        <label class="form-label gg-label">Location / Country</label>
                        <select name="country_id" class="form-select gg-select" required>
                            <option value="">Select location</option>

                            <?php foreach ($countries as $country): ?>
                                <option
                                    value="<?= htmlspecialchars($country['country_id']) ?>"
                                    <?= ((string)$currentCountryId === (string)$country['country_id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($country['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="gg-help">Choose the existing country/location for this attraction.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label gg-label">Attraction Type</label>
                        <select name="type_id" class="form-select gg-select" required>
                            <option value="">Select type</option>

                            <?php foreach ($types as $type): ?>
                                <option
                                    value="<?= htmlspecialchars($type['type_id']) ?>"
                                    <?= ((string)$currentTypeId === (string)$type['type_id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($type['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="gg-help">Choose the category/type for this attraction.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label gg-label">Attraction Name</label>
                        <input
                            type="text"
                            name="name"
                            class="form-control gg-input"
                            value="<?= htmlspecialchars($currentName) ?>"
                            required
                        >
                        <div class="gg-help">Update the attraction name shown to users.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label gg-label">Cover Image Path</label>
                        <input
                            type="text"
                            name="cover_image"
                            class="form-control gg-input"
                            value="<?= htmlspecialchars($currentCoverImage) ?>"
                            required
                        >
                        <div class="gg-help">Use a valid image path or URL for the attraction card.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label gg-label">Description</label>
                        <textarea
                            name="description"
                            class="form-control gg-textarea"
                            rows="6"
                            required
                        ><?= htmlspecialchars($currentDescription) ?></textarea>
                    </div>

                </div>

                <div class="gg-form-actions">
                    <button type="submit" class="gg-save-btn">
                        Update Attraction
                    </button>

                    <a href="<?= APP_BASE ?>/admin/location-list" class="gg-cancel-btn">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </section>

</div>