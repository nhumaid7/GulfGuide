<?php
require_once __DIR__ . '/../../classes/post.php';
require_once __DIR__ . '/../../classes/country.php';

requireRole(ROLE_CREATOR);

$userId = currentUserId();
$postId = (int)($params['id'] ?? 0);

// ── Load the post & verify ownership ─────────────────────────────────────────
$postStmt = $pdo->prepare("
    SELECT p.*, c.name AS country_name
    FROM   dbProj_post p
    LEFT JOIN dbProj_country c ON p.country_id = c.country_id
    WHERE  p.post_id = :id AND p.user_id = :uid
");
$postStmt->execute([':id' => $postId, ':uid' => $userId]);
$postRow = $postStmt->fetch();

if (!$postRow) {
    abort(403);
}

$post = Post::fromArray($postRow);

// ── Fetch countries for dropdown ──────────────────────────────────────────────
$cStmt     = $pdo->query("SELECT country_id, name FROM dbProj_country ORDER BY name ASC");
$countries = $cStmt->fetchAll();

// ── Validation helpers ────────────────────────────────────────────────────────
$errors = [];

// ── Handle POST submission ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action']      ?? 'draft';
    $title      = trim($_POST['title']      ?? '');
    $content    = trim($_POST['content']    ?? '');
    $countryId  = (int)($_POST['country_id'] ?? 0);
    $travelYear = trim($_POST['travel_year'] ?? '');

    // Server-side validation
    if (strlen($title) < 5)    $errors['title']      = 'Title must be at least 5 characters.';
    if (strlen($content) < 20) $errors['content']    = 'Review must be at least 20 characters.';
    if ($countryId <= 0)       $errors['country_id'] = 'Please select a destination country.';

    // Thumbnail — keep existing unless removed or replaced
    $thumbnailPath = $post->getThumbnail();

    if (!empty($_POST['remove_thumbnail'])) {
        $thumbnailPath = '';
    }

    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $fileType = mime_content_type($_FILES['thumbnail']['tmp_name']);
        $fileSize = $_FILES['thumbnail']['size'];

        if (!in_array($fileType, $allowed)) {
            $errors['thumbnail'] = 'Only JPEG, PNG, WebP, or GIF images are allowed.';
        } elseif ($fileSize > 5 * 1024 * 1024) {
            $errors['thumbnail'] = 'Image must be smaller than 5 MB.';
        } else {
            $uploadDir = realpath(__DIR__ . '/../../uploads') . DIRECTORY_SEPARATOR;

            if (!$uploadDir || !is_dir($uploadDir)) {
                $errors['thumbnail'] = 'Upload directory not found.';
            } elseif (!is_writable($uploadDir)) {
                $errors['thumbnail'] = 'Upload folder is not writable. Set uploads/ permissions to 755 in cPanel.';
            } else {
                $ext         = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
                $filename    = 'post_' . uniqid() . '.' . strtolower($ext);
                $destination = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $destination)) {
                    $thumbnailPath = 'uploads/' . $filename;
                } else {
                    $errors['thumbnail'] = 'Failed to save the image. Please try again.';
                }
            }
        }
    }

    if (empty($errors)) {
        $finalContent = $content;
        if ($travelYear !== '') {
            $finalContent = '[Traveled in: ' . htmlspecialchars($travelYear) . "]\n\n" . $content;
        }

        $status = ($action === 'published') ? 'published' : 'draft';

        try {
            $upd = $pdo->prepare("
                UPDATE dbProj_post
                SET    title = :title,
                       content = :content,
                       thumbnail = :thumbnail,
                       country_id = :country_id,
                       status = :status
                WHERE  post_id = :post_id AND user_id = :user_id
            ");
            $upd->execute([
                ':title'      => $title,
                ':content'    => $finalContent,
                ':thumbnail'  => $thumbnailPath,
                ':country_id' => $countryId,
                ':status'     => $status,
                ':post_id'    => $postId,
                ':user_id'    => $userId,
            ]);

            $_SESSION['status']      = 'Post updated successfully!';
            $_SESSION['status_code'] = 'success';

            header('Location: ' . APP_BASE . '/creator/');
            exit;
        } catch (PDOException $e) {
            $errors['db'] = 'Could not update post: ' . $e->getMessage();
        }
    }

    // On error, keep the submitted values for re-display
    $post->setTitle($title);
    $post->setContent($content);
    $post->setStatus($status ?? $post->getStatus());
}

// Strip travel-year prefix from content for display in textarea
$displayContent = preg_replace('/^\[Traveled in: \d{4}\]\n\n/', '', $post->getContent());

// Extract travel year if present
preg_match('/^\[Traveled in: (\d{4})\]/', $post->getContent(), $yearMatch);
$displayYear = $yearMatch[1] ?? '';

// Years dropdown
$currentYear = (int)date('Y');
$years       = range($currentYear, $currentYear - 10);

$baseUrl = rtrim(str_replace('/index.php', '', APP_BASE), '/');
?>

<!-- SweetAlert2 fallback -->
<script>
if (typeof Swal === 'undefined') {
    document.write('<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"><\/script>');
}
</script>

<div style="max-width:1200px; margin:0 auto; padding:0 0.5rem 2rem;">

<!-- ── DB error ────────────────────────────────────────────────────────────── -->
<?php if (!empty($errors['db'])): ?>
<div class="alert alert-danger alert-dismissible fade show mb-3">
    <?= htmlspecialchars($errors['db']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ── Page heading ────────────────────────────────────────────────────────── -->
<div class="mb-4">
    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/">GulfGuide</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/creator/">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Post</li>
        </ol>
    </nav>
    <h2 class="fw-bold mb-1" style="font-size:clamp(1.4rem,4vw,2rem);">Edit Your Post</h2>
    <p class="text-muted mb-0">Update your travel story</p>
</div>

<!-- ── Form card ───────────────────────────────────────────────────────────── -->
<div class="card-section">
    <div class="card-section--body" style="padding:2rem;">
        <form id="editPostForm"
              method="POST"
              action="<?= APP_BASE ?>/posts/<?= $postId ?>/edit"
              enctype="multipart/form-data"
              novalidate>

            <div class="row g-4">

                <!-- ── LEFT: image upload ──────────────────────────────────── -->
                <div class="col-lg-5">
                    <label class="form-label fw-semibold">Cover Image</label>

                    <div id="dropZone"
                         class="d-flex flex-column align-items-center justify-content-center gap-2 rounded-3 position-relative"
                         style="min-height:320px; border:2px dashed var(--light-grey-400);
                                background:var(--light-grey-50); cursor:pointer;
                                transition:border-color .2s, background .2s;">

                        <!-- existing or preview image -->
                        <?php if (!empty($post->getThumbnail())): ?>
                        <img id="imgPreview"
                             src="<?= $baseUrl . '/' . htmlspecialchars($post->getThumbnail()) ?>"
                             alt="Cover"
                             class="rounded-3 w-100 h-100"
                             style="object-fit:cover; position:absolute; inset:0;">
                        <div id="dropPlaceholder" class="text-center p-3 d-none">
                        <?php else: ?>
                        <img id="imgPreview" src="" alt="Preview"
                             class="d-none rounded-3 w-100 h-100"
                             style="object-fit:cover; position:absolute; inset:0;">
                        <div id="dropPlaceholder" class="text-center p-3">
                        <?php endif; ?>
                            <i class="ph ph-camera-plus" style="font-size:3rem; color:var(--light-grey-700);"></i>
                            <p class="mb-1 fw-semibold" style="color:var(--text-secondary);">
                                Click or drag & drop to replace
                            </p>
                            <p class="mb-0 text-muted" style="font-size:var(--font-size-p-xs);">
                                JPEG, PNG, WebP · max 5 MB
                            </p>
                        </div>

                        <input type="file" id="thumbnail" name="thumbnail"
                               accept="image/jpeg,image/png,image/webp,image/gif"
                               class="position-absolute w-100 h-100 opacity-0"
                               style="cursor:pointer; top:0; left:0;">
                    </div>

                    <!-- Hidden flag: set to 1 when user removes the image -->
                    <input type="hidden" id="removeThumbnailFlag" name="remove_thumbnail" value="0">

                    <button type="button" id="removeImg"
                            class="btn btn-sm btn-outline-danger mt-2 w-100 <?= empty($post->getThumbnail()) ? 'd-none' : '' ?>">
                        <i class="ph ph-x me-1"></i>Remove image
                    </button>

                    <div id="thumbnailError" class="invalid-feedback d-block mt-1"
                         style="<?= empty($errors['thumbnail']) ? 'display:none!important' : '' ?>">
                        <?= htmlspecialchars($errors['thumbnail'] ?? '') ?>
                    </div>
                </div>

                <!-- ── RIGHT: form fields ──────────────────────────────────── -->
                <div class="col-lg-7">

                    <!-- Title -->
                    <div class="mb-3">
                        <label for="title" class="form-label fw-semibold">Title of your review</label>
                        <input type="text" id="title" name="title"
                               class="form-control <?= !empty($errors['title']) ? 'is-invalid' : '' ?>"
                               placeholder="Summarize your Travel Journey"
                               value="<?= htmlspecialchars($post->getTitle()) ?>"
                               maxlength="255">
                        <div class="invalid-feedback">
                            <?= htmlspecialchars($errors['title'] ?? 'Title must be at least 5 characters.') ?>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="mb-3">
                        <label for="content" class="form-label fw-semibold">Your review</label>
                        <textarea id="richtest-editor" name="content" rows="5"
                                  class="form-control <?= !empty($errors['content']) ? 'is-invalid' : '' ?>"
                                  placeholder="A detailed review of your Travel Journey…"><?= htmlspecialchars($displayContent) ?></textarea>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars($errors['content'] ?? 'Review must be at least 20 characters.') ?>
                        </div>
                        <div class="text-muted text-end mt-1" style="font-size:var(--font-size-p-xs);">
                            <span id="charCount"><?= mb_strlen($displayContent) ?></span> characters
                        </div>
                    </div>

                    <!-- Country + Travel year -->
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="country_id" class="form-label fw-semibold">Country</label>
                            <select id="country_id" name="country_id"
                                    class="form-select <?= !empty($errors['country_id']) ? 'is-invalid' : '' ?>">
                                <option value="">Select destination…</option>
                                <?php foreach ($countries as $c): ?>
                                <option value="<?= $c['country_id'] ?>"
                                        <?= $c['country_id'] == $post->getCountryId() ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['country_id'] ?? 'Please select a country.') ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label for="travel_year" class="form-label fw-semibold">When did you travel?</label>
                            <select id="travel_year" name="travel_year" class="form-select">
                                <option value="">Select year…</option>
                                <?php foreach ($years as $yr): ?>
                                <option value="<?= $yr ?>" <?= $displayYear == $yr ? 'selected' : '' ?>>
                                    <?= $yr ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Current status info -->
                    <div class="mb-3">
                        <span class="text-muted" style="font-size:var(--font-size-p-xs);">
                            Current status:
                            <strong><?= ucfirst($post->getStatus()) ?></strong>
                        </span>
                    </div>

                    <!-- Action buttons -->
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" name="action" value="draft"
                                class="btn btn-outline-secondary"
                                onclick="setAction('draft')">
                            <i class="ph ph-floppy-disk me-1"></i>Save as Draft
                        </button>
                        <button type="submit" name="action" value="published"
                                id="publishBtn" class="btn btn-primary"
                                onclick="setAction('published')">
                            <i class="ph ph-paper-plane-tilt me-1"></i>
                            <?= $post->isPublished() ? 'Update & Keep Published' : 'Publish' ?>
                        </button>
                        <a href="<?= APP_BASE ?>/creator/" class="btn btn-link text-muted">Cancel</a>
                    </div>

                </div>
            </div>
        </form>
    </div>
</div>

</div><!-- /page wrapper -->

<!-- ── JavaScript ──────────────────────────────────────────────────────────── -->
<script>
(function ($) {
    'use strict';

    let pendingAction = '<?= $post->getStatus() ?>';
    window.setAction  = function (val) { pendingAction = val; };

    // Image preview
    const $drop        = $('#dropZone');
    const $input       = $('#thumbnail');
    const $preview     = $('#imgPreview');
    const $placeholder = $('#dropPlaceholder');
    const $removeBtn   = $('#removeImg');

    $drop.on('click', function (e) {
        if (!$(e.target).is('#removeImg, #removeImg *')) $input.trigger('click');
    });

    $drop.on('dragover', function (e) {
        e.preventDefault();
        $drop.css({ borderColor: 'var(--brand-primary)', background: 'var(--royal-blue-50)' });
    }).on('dragleave drop', function () {
        $drop.css({ borderColor: 'var(--light-grey-400)', background: 'var(--light-grey-50)' });
    });

    $drop.on('drop', function (e) {
        e.preventDefault();
        const file = e.originalEvent.dataTransfer.files[0];
        if (file) applyFile(file);
    });

    $input.on('change', function () { if (this.files[0]) applyFile(this.files[0]); });

    function applyFile(file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            $preview.attr('src', e.target.result).removeClass('d-none');
            $placeholder.addClass('d-none');
            $removeBtn.removeClass('d-none');
            $drop.css({ borderColor: 'var(--brand-primary)', borderStyle: 'solid' });
        };
        reader.readAsDataURL(file);
    }

    $removeBtn.on('click', function (e) {
        e.stopPropagation();
        $input.val('');
        $preview.addClass('d-none').attr('src', '');
        $placeholder.removeClass('d-none');
        $removeBtn.addClass('d-none');
        $drop.css({ borderColor: 'var(--light-grey-400)', borderStyle: 'dashed' });
        $('#removeThumbnailFlag').val('1'); // tell server to clear the thumbnail
    });

    // If user picks a new image, cancel any removal flag
    $input.on('change', function () {
        $('#removeThumbnailFlag').val('0');
    });

    // Character counter
    $('#content').on('input', function () { $('#charCount').text($(this).val().length); });

    // Form validation
    $('#editPostForm').on('submit', function (e) {
        e.preventDefault();
        if (!validateForm()) return;
        this.submit();
    });

    function validateForm() {
        let valid = true;

        if ($('#title').val().trim().length < 5) {
            $('#title').addClass('is-invalid'); valid = false;
        } else {
            $('#title').removeClass('is-invalid').addClass('is-valid');
        }

        if ($('#content').val().trim().length < 20) {
            $('#content').addClass('is-invalid'); valid = false;
        } else {
            $('#content').removeClass('is-invalid').addClass('is-valid');
        }

        if (!$('#country_id').val()) {
            $('#country_id').addClass('is-invalid'); valid = false;
        } else {
            $('#country_id').removeClass('is-invalid').addClass('is-valid');
        }

        if (!valid) $('html, body').animate({ scrollTop: 0 }, 300);
        return valid;
    }

})(jQuery);
</script>
