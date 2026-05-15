<?php
require_once __DIR__ . '/../../classes/post.php';
require_once __DIR__ . '/../../classes/country.php';

requireRole(ROLE_CREATOR);

$userId = currentUserId();

// ── Fetch countries for dropdown ──────────────────────────────────────────────
$cStmt     = $pdo->query("SELECT country_id, name FROM dbProj_country ORDER BY name ASC");
$countries = $cStmt->fetchAll();

// ── Validation helpers ────────────────────────────────────────────────────────
$errors  = [];
$old     = [];

// ── Handle POST submission ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action']     ?? 'draft';
    $title     = trim($_POST['title']     ?? '');
    $content   = trim($_POST['content']   ?? '');
    $countryId = (int)($_POST['country_id'] ?? 0);
    $travelYear = trim($_POST['travel_year'] ?? '');

    // Keep old values to refill form on error
    $old = compact('title', 'content', 'countryId', 'travelYear');

    // Server-side validation
    if (strlen($title) < 5)   $errors['title']      = 'Title must be at least 5 characters.';
    if (strlen($content) < 20) $errors['content']   = 'Review must be at least 20 characters.';
    if ($countryId <= 0)       $errors['country_id'] = 'Please select a destination country.';

    // Thumbnail upload
    $thumbnailPath = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $allowed   = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $fileType  = mime_content_type($_FILES['thumbnail']['tmp_name']);
        $fileSize  = $_FILES['thumbnail']['size'];

        if (!in_array($fileType, $allowed)) {
            $errors['thumbnail'] = 'Only JPEG, PNG, WebP, or GIF images are allowed.';
        } elseif ($fileSize > 5 * 1024 * 1024) {
            $errors['thumbnail'] = 'Image must be smaller than 5 MB.';
        } else {
            $uploadDir = realpath(__DIR__ . '/../../uploads') . DIRECTORY_SEPARATOR;

            if (!$uploadDir || !is_dir($uploadDir)) {
                $errors['thumbnail'] = 'Upload directory not found. Please contact the administrator.';
            } else {
                $ext         = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
                $filename    = 'post_' . uniqid() . '.' . strtolower($ext);
                $destination = $uploadDir . $filename;

                if (is_writable($uploadDir) && move_uploaded_file($_FILES['thumbnail']['tmp_name'], $destination)) {
                    $thumbnailPath = 'uploads/' . $filename;
                } elseif (!is_writable($uploadDir)) {
                    $errors['thumbnail'] = 'Upload folder is not writable. Please set the uploads/ folder permissions to 755 in cPanel.';
                } else {
                    $errors['thumbnail'] = 'Failed to move the uploaded file. Please try again.';
                }
            }
        }
    }
    // Image is optional for now — post saves with empty thumbnail if none provided

    // If no errors, insert post
    if (empty($errors)) {
        // Prepend travel year to content if provided
        $finalContent = $content;
        if ($travelYear !== '') {
            $finalContent = '[Traveled in: ' . htmlspecialchars($travelYear) . "]\n\n" . $content;
        }

        $status = ($action === 'published') ? 'published' : 'draft';

        try {
            $stmt = $pdo->prepare("
                INSERT INTO dbProj_post (user_id, country_id, title, content, thumbnail, status, created_at)
                VALUES (:user_id, :country_id, :title, :content, :thumbnail, :status, NOW())
            ");
            $stmt->execute([
                ':user_id'    => $userId,
                ':country_id' => $countryId,
                ':title'      => $title,
                ':content'    => $finalContent,
                ':thumbnail'  => $thumbnailPath,
                ':status'     => $status,
            ]);

            $newPostId = (int)$pdo->lastInsertId();

            $_SESSION['status']      = $status === 'published'
                ? 'Your post has been published successfully!'
                : 'Post saved as draft.';
            $_SESSION['status_code'] = 'success';

            header('Location: ' . APP_BASE . '/creator/');
            exit;
        } catch (PDOException $e) {
            $errors['db'] = 'Could not save your post: ' . $e->getMessage();
        }
    }
}

// Years for the "When did you travel?" dropdown (last 10 years)
$currentYear = (int)date('Y');
$years = range($currentYear, $currentYear - 10);
?>

<!-- SweetAlert2 fallback -->
<script>
if (typeof Swal === 'undefined') {
    document.write('<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"><\/script>');
}
</script>

<div style="max-width: 1200px; margin: 0 auto; padding: 0 0.5rem 2rem;">

<!-- ── DB error banner ─────────────────────────────────────────────────────── -->
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
            <li class="breadcrumb-item active" aria-current="page">Write Review</li>
        </ol>
    </nav>
    <h2 class="fw-bold mb-1" style="font-size:clamp(1.4rem,4vw,2rem);">
        Let's Share Your Experience!
    </h2>
    <p class="text-muted mb-0">Write Reviews</p>
</div>

<!-- ── Form card ───────────────────────────────────────────────────────────── -->
<div class="card-section">
    <div class="card-section--body" style="padding: 2rem;">
        <form id="createPostForm"
              method="POST"
              action="<?= APP_BASE ?>/creator/create-post"
              enctype="multipart/form-data"
              novalidate>

            <div class="row g-4">

                <!-- ── LEFT: image upload ──────────────────────────────────── -->
                <div class="col-lg-5">
                    <label class="form-label fw-semibold">Cover Image</label>

                    <!-- drop zone -->
                    <div id="dropZone"
                         class="d-flex flex-column align-items-center justify-content-center gap-2 rounded-3 position-relative"
                         style="min-height:320px; border:2px dashed var(--light-grey-400);
                                background:var(--light-grey-50); cursor:pointer;
                                transition:border-color .2s, background .2s;">

                        <!-- preview image (hidden until a file is chosen) -->
                        <img id="imgPreview" src="" alt="Preview"
                             class="d-none rounded-3 w-100 h-100"
                             style="object-fit:cover; position:absolute; inset:0;">

                        <!-- placeholder icon + text -->
                        <div id="dropPlaceholder" class="text-center p-3">
                            <i class="ph ph-camera-plus" style="font-size:3rem; color:var(--light-grey-700);"></i>
                            <p class="mb-1 fw-semibold" style="color:var(--text-secondary);">
                                Click or drag & drop to upload
                            </p>
                            <p class="mb-0 text-muted" style="font-size:var(--font-size-p-xs);">
                                JPEG, PNG, WebP · max 5 MB
                            </p>
                        </div>

                        <!-- hidden file input -->
                        <input type="file" id="thumbnail" name="thumbnail"
                               accept="image/jpeg,image/png,image/webp,image/gif"
                               class="position-absolute w-100 h-100 opacity-0"
                               style="cursor:pointer; top:0; left:0;">
                    </div>

                    <!-- remove button (shown after image selected) -->
                    <button type="button" id="removeImg"
                            class="btn btn-sm btn-outline-danger mt-2 d-none w-100">
                        <i class="ph ph-x me-1"></i>Remove image
                    </button>

                    <!-- error -->
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
                        <input type="text"
                               id="title"
                               name="title"
                               class="form-control <?= !empty($errors['title']) ? 'is-invalid' : '' ?>"
                               placeholder="Summarize your Travel Journey"
                               value="<?= htmlspecialchars($old['title'] ?? '') ?>"
                               maxlength="255">
                        <div class="invalid-feedback">
                            <?= htmlspecialchars($errors['title'] ?? 'Title must be at least 5 characters.') ?>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="mb-3">
                        <label for="content" class="form-label fw-semibold">Your review</label>
                        <textarea id="content"
                                  name="content"
                                  rows="5"
                                  class="form-control <?= !empty($errors['content']) ? 'is-invalid' : '' ?>"
                                  placeholder="A detailed review of your Travel Journey. Travellers will love to know your experience…"><?= htmlspecialchars($old['content'] ?? '') ?></textarea>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars($errors['content'] ?? 'Review must be at least 20 characters.') ?>
                        </div>
                        <div class="text-muted text-end mt-1" style="font-size:var(--font-size-p-xs);">
                            <span id="charCount">0</span> characters
                        </div>
                    </div>

                    <!-- Country + Travel year row -->
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="country_id" class="form-label fw-semibold">Country</label>
                            <select id="country_id"
                                    name="country_id"
                                    class="form-select <?= !empty($errors['country_id']) ? 'is-invalid' : '' ?>">
                                <option value="">Select destination…</option>
                                <?php foreach ($countries as $c): ?>
                                <option value="<?= $c['country_id'] ?>"
                                        <?= (($old['countryId'] ?? 0) == $c['country_id']) ? 'selected' : '' ?>>
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
                                <option value="<?= $yr ?>"
                                        <?= (($old['travelYear'] ?? '') == $yr) ? 'selected' : '' ?>>
                                    <?= $yr ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Certification checkbox -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   id="certification" name="certification" value="1">
                            <label class="form-check-label text-muted"
                                   style="font-size:var(--font-size-p-xs);" for="certification">
                                I certify that the information in this post is based solely on my own
                                experience. I have no personal or professional affiliation with any
                                business mentioned, and I have not been given any incentives or payment
                                to write this post. I am aware that fake or misleading content is
                                strictly prohibited on GulfGuide.
                            </label>
                        </div>
                        <div id="certError" class="text-danger mt-1 d-none"
                             style="font-size:var(--font-size-p-xs);">
                            You must agree to the certification before publishing.
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" name="action" value="draft"
                                class="btn btn-outline-secondary"
                                onclick="setAction('draft')">
                            <i class="ph ph-floppy-disk me-1"></i>Save as Draft
                        </button>
                        <button type="submit" name="action" value="published"
                                id="publishBtn"
                                class="btn btn-primary"
                                onclick="setAction('published')">
                            <i class="ph ph-paper-plane-tilt me-1"></i>Publish Post
                        </button>
                        <a href="<?= APP_BASE ?>/creator/" class="btn btn-link text-muted">
                            Cancel
                        </a>
                    </div>

                </div><!-- /col-lg-7 -->
            </div><!-- /row -->
        </form>
    </div>
</div>

<!-- ── JavaScript ──────────────────────────────────────────────────────────── -->
<script>
(function ($) {
    'use strict';

    let pendingAction = 'draft';

    window.setAction = function (val) { pendingAction = val; };

    // ── Image upload preview (jQuery) ──────────────────────────────────────────
    const $drop        = $('#dropZone');
    const $input       = $('#thumbnail');
    const $preview     = $('#imgPreview');
    const $placeholder = $('#dropPlaceholder');
    const $removeBtn   = $('#removeImg');

    // Click anywhere on drop zone
    $drop.on('click', function (e) {
        if (!$(e.target).is('#removeImg, #removeImg *')) {
            $input.trigger('click');
        }
    });

    // Drag-over styling
    $drop.on('dragover', function (e) {
        e.preventDefault();
        $drop.css({ borderColor: 'var(--brand-primary)', background: 'var(--royal-blue-50)' });
    }).on('dragleave drop', function () {
        $drop.css({ borderColor: 'var(--light-grey-400)', background: 'var(--light-grey-50)' });
    });

    // File dropped
    $drop.on('drop', function (e) {
        e.preventDefault();
        const file = e.originalEvent.dataTransfer.files[0];
        if (file) applyFile(file);
    });

    // File selected via input
    $input.on('change', function () {
        if (this.files[0]) applyFile(this.files[0]);
    });

    function applyFile(file) {
        if (!file.type.startsWith('image/')) {
            showFieldError('thumbnailError', 'Please select a valid image file.');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            showFieldError('thumbnailError', 'Image must be smaller than 5 MB.');
            return;
        }
        clearFieldError('thumbnailError');

        const reader = new FileReader();
        reader.onload = function (e) {
            $preview.attr('src', e.target.result).removeClass('d-none');
            $placeholder.addClass('d-none');
            $removeBtn.removeClass('d-none');
            $drop.css({ borderColor: 'var(--brand-primary)', borderStyle: 'solid' });
        };
        reader.readAsDataURL(file);
    }

    // Remove image
    $removeBtn.on('click', function (e) {
        e.stopPropagation();
        $input.val('');
        $preview.addClass('d-none').attr('src', '');
        $placeholder.removeClass('d-none');
        $removeBtn.addClass('d-none');
        $drop.css({ borderColor: 'var(--light-grey-400)', borderStyle: 'dashed' });
    });

    // ── Character counter (jQuery) ────────────────────────────────────────────
    $('#content').on('input', function () {
        $('#charCount').text($(this).val().length);
    });

    // ── Form validation (JS) ──────────────────────────────────────────────────
    $('#createPostForm').on('submit', function (e) {
        e.preventDefault();
        if (!validateForm(pendingAction)) return;

        // Extra SweetAlert confirm before publishing
        if (pendingAction === 'published') {
            Swal.fire({
                icon: 'question',
                title: 'Publish this post?',
                text: 'Your post will be visible to all visitors.',
                showCancelButton: true,
                confirmButtonColor: '#4169e1',
                cancelButtonColor:  '#6c757d',
                confirmButtonText:  'Yes, publish it',
                cancelButtonText:   'Not yet'
            }).then(function (result) {
                if (result.isConfirmed) { document.getElementById('createPostForm').submit(); }
            });
        } else {
            this.submit();
        }
    });

    function validateForm(action) {
        let valid = true;

        // Title
        const title = $('#title').val().trim();
        if (title.length < 5) {
            $('#title').addClass('is-invalid');
            valid = false;
        } else {
            $('#title').removeClass('is-invalid').addClass('is-valid');
        }

        // Content
        const content = $('#content').val().trim();
        if (content.length < 20) {
            $('#content').addClass('is-invalid');
            valid = false;
        } else {
            $('#content').removeClass('is-invalid').addClass('is-valid');
        }

        // Country
        if (!$('#country_id').val()) {
            $('#country_id').addClass('is-invalid');
            valid = false;
        } else {
            $('#country_id').removeClass('is-invalid').addClass('is-valid');
        }

        // Image is optional — clear any previous error
        clearFieldError('thumbnailError');

        // Certification required only when publishing
        if (action === 'published' && !$('#certification').is(':checked')) {
            $('#certError').removeClass('d-none');
            valid = false;
        } else {
            $('#certError').addClass('d-none');
        }

        if (!valid) {
            $('html, body').animate({ scrollTop: 0 }, 300);
        }

        return valid;
    }

    function showFieldError(id, msg) {
        const $el = $('#' + id);
        $el.text(msg).css('display', 'block');
    }

    function clearFieldError(id) {
        $('#' + id).css('display', 'none').text('');
    }

})(jQuery);
</script>

</div><!-- /page wrapper -->
