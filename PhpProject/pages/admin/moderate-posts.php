<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    abort(403);
}

$stmt = $pdo->query('SELECT * FROM dbProj_post p JOIN dbProj_user u ON p.user_id = u.user_id JOIN dbProj_country c ON p.country_id = c.country_id ORDER BY p.created_at DESC');
$postRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$posts = array_map([Post::class, 'fromArray'], $postRows);

if (isset($_POST['action']) && in_array($_POST['action'], ['approve', 'reject'])) {
    $requestId = (int) $_POST['request_id'];
    $action = $_POST['action'];
    $userId = $_POST['user_id'];

    if ($action === 'approve') {
        try {
            $stmt = $pdo->prepare("UPDATE dbProj_creator_request SET status = 'approved', reviewed_at = NOW() WHERE request_id = :requestId");
            $stmt->execute([':requestId' => $requestId]);

            $stmt = $pdo->prepare("UPDATE dbProj_user SET role = 'creator' WHERE user_id = :userId");
            $stmt->execute([':userId' => $userId]);

            $_SESSION['status'] = "User approved as creator successfully";
            $_SESSION['status_code'] = "success";
        } catch (PDOException $e) {
            $_SESSION['status'] = "Delete failed: " . $e->getMessage();
            $_SESSION['status_code'] = "error";
        }
    } elseif ($action === 'reject') {
        try {
            $stmt = $pdo->prepare("UPDATE dbProj_creator_request SET status = 'rejected', reviewed_at = NOW() WHERE request_id = :requestId");
            $stmt->execute([':requestId' => $requestId]);

            $stmt = $pdo->prepare("UPDATE dbProj_user SET role = 'user' WHERE user_id = :userId");
            $stmt->execute([':userId' => $userId]);

            $_SESSION['status'] = "User rejected as creator successfully";
            $_SESSION['status_code'] = "success";
        } catch (PDOException $e) {
            $_SESSION['status'] = "Delete failed: " . $e->getMessage();
            $_SESSION['status_code'] = "error";
        }
    }
    header("Location: " . APP_BASE . "/admin/creator-request");
    exit;
}
?>  
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3">
    <h2>Moderate Posts</h2>
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
        <p class="h5-style">Content Creator Requests</p>
        <div class="d-flex gap-1">
            <span class="gulfguide-badge">Total: <?= count($posts) ?> applications</span>
        </div>
    </div>
    <hr class="card-section--divider">
    <div class="card-section--body">
        <div class="overflow-hidden">
            <div class="">
                <table id="usersTable" class="table datatable-gulfguide max-w-full overflow-x-auto custom-scrollbar">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Country</th>
                            <th>Author</th>
                            <th>Created At</th>
                            <th class="action-th text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $index => $post): ?>
                            <tr>
                                <td><?= $post->getPostId() ?></td>
                                <td><?= htmlspecialchars($post->getTitle()) ?></td>
                                <td><?= htmlspecialchars($postRows[$index]['username']) ?></td>
                                <td>
                                    <?php if (!empty($postRows[$index]['flag_image'])): ?>
                                        <img src="?= htmlspecialchars($postRows[$index]['flag_image']) ?>" 
                                             width="24" alt="flag">
                                         <?php else: ?>
                                        <i class="ph ph-flag"></i>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($postRows[$index]['name']) ?>
                                </td>
                                <td><?= htmlspecialchars(((new DateTime($post->getCreatedAt()))->format("F j, Y, g:i a"))) ?></td>
                                <td class="text-center">
                                    <a class="btn btn-sm btn-outline-success" href="<?= APP_BASE ?>/admin/moderate-posts/<?= $post->getPostId() ?>">
                                                <i class="ph ph-eye"></i>
                                                <span class="d-block d-md-none">View Post</span>
                                    </a>
                                    <?php if (false): ?>
                                        <form method="POST" action="<?= APP_BASE ?>/admin/creator-request" class="rejected-record">
                                            <input type="hidden" name="username"
                                                   value="<?= htmlspecialchars($user->getUsername()) ?>">
                                            <input type="hidden" name="user_id" value="<?= $user->getUserId() ?>">
                                            <input type="hidden" name="request_id" value="<?= $application->getRequestId() ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" name="delete_btn" class="btn btn-sm btn-outline-danger">
                                                <i class="ph ph-x-circle"></i>
                                                <span class="d-block d-md-none">Rejected</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).on('submit', '.rejected-record', function (e) {
        e.preventDefault();

        let form = $(this);
        let username = form.find('input[name="username"]').val();

        Swal.fire({
            title: 'Reject user',
            text: `Are you sure you want to reject ${username}\'s creator request?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4169e1',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'Yes, reject',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                form[0].submit();
            }
        });
    });
    $(document).on('submit', '.approved-record', function (e) {
        e.preventDefault();

        let form = $(this);
        let username = form.find('input[name="username"]').val();

        Swal.fire({
            title: 'Approved user',
            text: `Are you sure you want to approved ${username}\'s creator request?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4169e1',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'Yes, reject',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                form[0].submit();
            }
        });
    });
</script>

<?php if (isset($_SESSION['status']) && $_SESSION['status'] != ''): ?>
    <?php
    $swal_status = $_SESSION['status'];
    $swal_code = $_SESSION['status_code'];
    unset($_SESSION['status']);
    unset($_SESSION['status_code']);
    ?>
<?php endif; ?>
