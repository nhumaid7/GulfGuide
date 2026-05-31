<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    abort(403);
}

$postId = (int) ($params['id'] ?? 0);

if ($postId <= 0) {
    abort(404);
}

$stmt = $pdo->prepare("SELECT p.*, 
    u.*, 
    c.name AS country_name,
    c.display_image,
    c.flag_image,
    c.official_tourism_website,
    c.description AS country_description,
    a.name AS attraction_name,
    a.description AS attraction_description,
    a.cover_image AS attraction_cover,
    at.name AS attraction_type
    FROM dbProj_post p 
    JOIN dbProj_user u ON p.user_id = u.user_id JOIN dbProj_country c ON p.country_id = c.country_id LEFT JOIN dbProj_attraction a 
    ON p.attraction_id = a.attraction_id LEFT JOIN dbProj_attraction_type at ON a.type_id = at.type_id
    WHERE p.post_id = :id
");
$stmt->execute([':id' => $postId]);
$postRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$postRow) {
    abort(404);
}

$post = Post::fromArray($postRow);

$userId = $_SESSION['user_id'] ?? 1;

if (isset($_POST['action']) && $_POST['action']) {
    if ($_POST['action'] === 'reject_post') {
        try {
            $stmt = $pdo->prepare("UPDATE dbProj_post  SET status = 'rejected' WHERE post_id = :id");
            $stmt->execute([':id' => $postId]);

            $_SESSION['status'] = "Post rejected successfully!";
            $_SESSION['status_code'] = "success";
        } catch (PDOException $e) {
            $_SESSION['status'] = "Failed to reject post: " . $e->getMessage();
            $_SESSION['status_code'] = "error";
        }
    }
    if ($_POST['action'] === 'delete_comment') {
        $commentId = (int) $_POST['comment_id'];
        try {
            $delStmt = $pdo->prepare("DELETE FROM dbProj_comment WHERE comment_id = :id");
            $delStmt->execute([':id' => $commentId]);
            $_SESSION['status'] = "Comment deleted successfully";
            $_SESSION['status_code'] = "success";
        } catch (PDOException $e) {
            $_SESSION['status'] = "Failed to delete comment: " . $e->getMessage();
            $_SESSION['status_code'] = "error";
        }
    }
    if ($_POST['action'] === 'toggle_comment_visibility') {
        $commentId = (int) $_POST['comment_id'];

        try {
            $stmt = $pdo->prepare("
            UPDATE dbProj_comment 
            SET is_visible = NOT is_visible 
            WHERE comment_id = :id
        ");
            $stmt->execute([':id' => $commentId]);

            $_SESSION['status'] = "Comment visibility updated!";
            $_SESSION['status_code'] = "success";
        } catch (PDOException $e) {
            $_SESSION['status'] = "Failed to update comment: " . $e->getMessage();
            $_SESSION['status_code'] = "error";
        }
    }
    if ($_POST['action'] === 'add_comment') {
        $commentContent = $_POST['comment_content'] ?? '';

        if (trim(strip_tags($commentContent)) === '') {
            $_SESSION['status'] = "Comment cannot be empty!";
            $_SESSION['status_code'] = "error";
        } else {
            try {
                $addStmt = $pdo->prepare("INSERT INTO dbProj_comment (post_id, user_id, content, is_visible, created_at) VALUES (:post_id, :user_id, :content, 1, NOW())");
                $addStmt->execute([
                    ':post_id' => $postId,
                    ':user_id' => $userId,
                    ':content' => $commentContent
                ]);

                $_SESSION['status'] = "Comment added successfully!";
                $_SESSION['status_code'] = "success";
            } catch (PDOException $e) {
                $_SESSION['status'] = "Failed to add comment: " . $e->getMessage();
                $_SESSION['status_code'] = "error";
            }
        }
    }
    header("Location: " . APP_BASE . "/admin/moderate-posts/" . $postId);
    exit;
}

$commentsStmt = $pdo->prepare("
    SELECT cm.*, u.username FROM dbProj_comment cm JOIN dbProj_user u ON cm.user_id = u.user_id WHERE cm.post_id = :post_id 
    ORDER BY cm.created_at DESC");
$commentsStmt->execute([':post_id' => $postId]);
$comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
?>


<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3">
    <h2>Posts Details</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <?php
            $parts = array_filter(explode('/', $uri));

            echo '<li class="breadcrumb-item"><a href="' . APP_BASE . '/">GulfGuide</a></li>';

            $currentPath = '';
            $count = count($parts);
            $i = 1;

            foreach ($parts as $part) {
                $currentPath .= '/' . $part;
                $isLast = ($i === $count);
                $label = ucwords(str_replace(['-', '_'], ' ', $part));

                if ($isLast) {
                    echo '<li class="breadcrumb-item active text-dark" aria-current="page">' . $label . '</li>';
                } else {
                    if ($part == 'admin') {
                        echo '<li class="breadcrumb-item"> Admin Portal </li>';
                        echo '<li class="breadcrumb-item">';
                        echo '<a href="' . APP_BASE . '/admin/dashboard"> Dashboard </a>';
                        echo '</li>';
                    } else {
                        echo '<li class="breadcrumb-item">';
                        echo '<a href="' . APP_BASE . $currentPath . '">' . $label . '</a>';
                        echo '</li>';
                    }
                }
                $i++;
            }
            ?>
        </ol>
    </nav>
</div>

<div class="card-section">
    <div class="card-section--header">
        <h3>Post ID: #<?= $postId ?></h3>
        <?php if ($postRow['status'] != 'rejected'): ?>
            <div class="d-flex gap-1">
                <form method="POST" action="" class="rejected-record">
                    <input type="hidden" name="action" value="reject_post">
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="ph ph-x-circle"></i>
                        <span>Reject Post</span>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="pt-2">
    <div class="row g-4">
        <div class="col-lg-7 col-xxl-8 vstack gap-2">
            <div class="card-section">
                <div class="card-section--header">
                    <div>
                        <h3 class="mb-1 fw-bold text-dark">
                            <?= htmlspecialchars($postRow['title']) ?>
                        </h3>

                        <div class="text-muted small d-flex flex-wrap gap-2 align-items-center">
                            <span class="gulfguide-badge 
                            <?= $postRow['status'] === 'published' ? 'gulfguide-badge--success' : '' ?>
                            <?= $postRow['status'] === 'draft' ? 'gulfguide-badge--warning' : '' ?>
                            <?= $postRow['status'] === 'rejected' ? 'gulfguide-badge--danger' : '' ?>
                                  gulfguide-badge--rounded
                                  ">
                                      <?= ucfirst(htmlspecialchars($postRow['status'])) ?>
                            </span>
                            <span> <?= (new DateTime($postRow['created_at']))->format("F j, Y") ?></span>
                        </div>
                    </div>
                </div>
                <hr class="card-section--divider">
                <div class="card-section--body">

                    <?php if (!empty($postRow['thumbnail'])): ?>
                        <div class="mb-3 img-controler">
                            <img src="<?= $baseUrl . '/' . htmlspecialchars($postRow['thumbnail']) ?>"
                                 class="img-fluid rounded"
                                 alt="Post Thumbnail">
                        </div>
                    <?php endif; ?>

                    <div class="post-content-text">
                        <?= nl2br(htmlspecialchars($postRow['content'])) ?>
                    </div>

                </div>
            </div>
            <div class="card-section">
                <div class="card-section--header">
                    <p class="h5-style mb-0">Comments (<?= count($comments) ?>)</p>
                </div>
                <hr class="card-section--divider">
                <div class="card-section--body">
                    <?php if (count($comments) === 0): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="ph ph-chat-circle-slash d-block mb-2 fs-1"></i>
                            No comments on this post yet.
                        </div>
                    <?php else: ?>
                        <div class="comment-list">
                            <?php foreach ($comments as $c): ?>
                                <div class="comment">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                                        <div>
                                            <span class="comment-username"><?= htmlspecialchars($c['username']) ?></span>
                                            <span class="comment-date  small ms-2"><?= htmlspecialchars(((new DateTime($c['created_at']))->format("F j, Y, g:i a"))) ?></span>
                                            <?php if (!$c['is_visible']): ?>
                                                <span class="gulfguide-badge gulfguide-badge--info gulfguide-badge--rounded">Hidden</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex">
                                            <form method="POST" action="">
                                                <input type="hidden" name="action" value="toggle_comment_visibility">
                                                <input type="hidden" name="comment_id" value="<?= $c['comment_id'] ?>">
                                                <button type="submit"
                                                        class="btn btn-sm <?= $c['is_visible'] ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                                                            <?php if ($c['is_visible']): ?>
                                                        <i class="ph ph-eye-slash"></i>
                                                        <span class="d-md-none d-inline">Hide</span>
                                                    <?php else: ?>
                                                        <i class="ph ph-eye"></i>
                                                        <span class="d-md-none dinline">Show</span>
                                                    <?php endif; ?>

                                                </button>
                                            </form>
                                            <form method="POST" action="" >
                                                <input type="hidden" name="action" value="delete_comment">
                                                <input type="hidden" name="comment_id" value="<?= $c['comment_id'] ?>">

                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="ph ph-trash"></i>
                                                    <span class="d-md-none dinline">Delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-secondary" style="white-space: pre-wrap;"><?= htmlspecialchars_decode($c['content']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <h5 class="mt-4"> Add Comment</h5>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="add_comment">
                            <div class="mb-3">
                                <textarea id="richtext-editor" name="comment_content" rows="4" class="form-control" placeholder="Enter your comment..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Post Comment</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5 col-xxl-4 vstack gap-2">
            <div class="card-section">
                <div class="card-section--header">
                    <h6>Author Details</h6>
                </div>
                <hr class="card-section--divider">
                <div class="card-section--body">
                    <div class="details-list">
                        <div class="detail-item">
                            <span class="detail-label">Username:</span>
                            <span class="detail-value"><?= htmlspecialchars($postRow['username']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value"><?= htmlspecialchars($postRow['email']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($postRow['attraction_id'])): ?>
                <div class="card-section">
                    <div class="card-section--header">
                        <h6>Attraction Details</h6>
                    </div>
                    <hr class="card-section--divider">

                    <div class="card-section--body">

                        <?php if (!empty($postRow['attraction_cover'])): ?>
                            <div class="img-controler mb-3">
                                <img src="<?= htmlspecialchars($postRow['attraction_cover']) ?>"
                                     alt="Attraction Image">
                            </div>
                        <?php endif; ?>

                        <div class="details-list">

                            <div class="detail-item">
                                <span class="detail-label">Attraction Name:</span>
                                <div class="detail-value">
                                    <?= htmlspecialchars($postRow['attraction_name'] ?? 'Not Specified') ?>
                                </div>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">Type:</span>
                                <div class="detail-value">
                                    <?= htmlspecialchars($postRow['attraction_type'] ?? 'Not Specified') ?>
                                </div>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">Description:</span>
                                <div class="detail-value">
                                    <?= htmlspecialchars($postRow['attraction_description'] ?? 'No description available.') ?>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card-section">
                <div class="card-section--header">
                    <h6>Country Details</h6>
                </div>
                <hr class="card-section--divider">
                <div class="card-section--body">
                    <?php if (!empty($postRow['display_image'])): ?>
                        <div class="img-controler mb-3">
                            <img src="<?= htmlspecialchars($postRow['display_image']) ?>"
                                 alt="Country Image">
                        </div>
                    <?php endif; ?>
                    <div class="details-list">
                        <div class="detail-item">
                            <span class="detail-label">Country Name:</span>
                            <div class="detail-value d-flex"> 
                                <?php if (!empty($postRow['flag_image'])): ?>
                                    <div class="img-controler">
                                        <img src="<?= htmlspecialchars($postRow['flag_image']) ?>" width="36"  class="flag-icon me-1" alt="flag">
                                    </div>
                                <?php endif; ?>
                                <?= htmlspecialchars($postRow['country_name'] ?? 'Not Specified') ?>
                            </div>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Tourism Website:</span>
                            <div class="detail-value">
                                <?php if (!empty($postRow['official_tourism_website'])): ?>
                                    <a href="<?= htmlspecialchars($postRow['official_tourism_website']) ?>"
                                       target="_blank">
                                        visit website
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">Description:</span>
                            <div class="detail-value">
                                <?= htmlspecialchars($postRow['country_description'] ?? 'No description available.') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).on('submit', '.delete-comment-form', function (e) {
        e.preventDefault();
        let form = this;

        Swal.fire({
            title: 'Delete comment?',
            text: 'Are you sure you want to permanently delete this comment?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
</script>

<?php if (isset($_SESSION['status']) && $_SESSION['status'] != ''): ?>
    <script>
        Swal.fire({
            title: "<?= $_SESSION['status_code'] === 'success' ? 'Success!' : 'Status' ?>",
            text: "<?= htmlspecialchars($_SESSION['status']) ?>",
            icon: "<?= htmlspecialchars($_SESSION['status_code'] ?? 'info') ?>",
            confirmButtonColor: '#4169e1'
        });
    </script>
    <?php
    unset($_SESSION['status']);
    unset($_SESSION['status_code']);
    ?>
<?php endif; ?>
