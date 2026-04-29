<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    abort(403);
}

$stmt = $pdo->query('SELECT * FROM dbProj_creator_request CR ORDER BY request_id DESC');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$applications = array_map([CreatorRequest::class, 'fromArray'], $rows);
$usersstmt = $pdo->query('SELECT * FROM dbProj_user ORDER BY created_at DESC');
$usersRows = $usersstmt->fetchAll(PDO::FETCH_ASSOC);
$users = array_map([User::class, 'fromArray'], $usersRows);

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
    <h2>Creator Requests</h2>
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
        <p class="h5-style">Applications List</p>
        <?php
        $totalApplications = count($applications);

        $pendingCount = 0;
        $approvedCount = 0;
        $rejectedCount = 0;

        foreach ($applications as $app) {
            $status = strtolower(trim($app->getStatus()));

            if ($status === 'pending')
                $pendingCount++;
            elseif ($status === 'approved')
                $approvedCount++;
            elseif ($status === 'rejected')
                $rejectedCount++;
        }
        ?>  
        <div class="d-flex gap-1 flex-wrap">
            <span class="gulfguide-badge">Total: <?= $totalApplications ?> applications</span>
            <span class="gulfguide-badge gulfguide-badge--warning"><?= $pendingCount ?> pending</span>
            <span class="gulfguide-badge gulfguide-badge--success"><?= $approvedCount ?> approved</span>
            <span class="gulfguide-badge gulfguide-badge--danger"><?= $rejectedCount ?> rejected</span>
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
                            <th>Username</th>
                            <th>Status</th>
                            <th>Requested At</th>
                            <th>Reason</th>
                            <th>Email</th>
                            <th>Reviewed At</th>
                            <th class="action-th text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($applications as $application):
                            $filtered = array_filter($users, function ($u) use ($application) {
                                return $u->getUserId() === $application->getUserId();
                            });

                            $user = reset($filtered);
                            ?>
                            <tr>
                                <td><?= $application->getRequestId() ?></td>
                                <td><?= htmlspecialchars($user->getUsername()) ?></td>
                                <td>
                                    <?php
                                    $statusMap = [
                                        'pending' => [
                                            'class' => 'gulfguide-badge--warning',
                                            'icon' => 'ph-clock'
                                        ],
                                        'approved' => [
                                            'class' => 'gulfguide-badge--success',
                                            'icon' => 'ph-check-circle'
                                        ],
                                        'rejected' => [
                                            'class' => 'gulfguide-badge--danger',
                                            'icon' => 'ph-x-circle'
                                        ],
                                    ];
                                    ?>
                                    <?php
                                    $status = $statusMap[$application->getStatus()] ?? $roleMap['pending'];
                                    $statusText = strtolower(trim($application->getStatus()));
                                    ?>
                                    <span class="gulfguide-badge <?= $status['class'] ?> gulfguide-badge--rounded">
                                        <?= ucfirst($application->getStatus()) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars(((new DateTime($application->getRequestedAt()))->format("F j, Y, g:i a"))) ?></td>
                                <td><?= htmlspecialchars($application->getReason()) ?></td>
                                <td><a href="mailto:<?= htmlspecialchars($user->getEmail()) ?>">
                                        <?= htmlspecialchars($user->getEmail()) ?>
                                    </a></td>
                                <td><?=
                                    $application->getReviewedAt() ?
                                            htmlspecialchars(((new DateTime($application->getReviewedAt()))->format("F j, Y, g:i a"))) :
                                            "have not reviewed"
                                    ?></td>
                                <td class="text-center">
                                    <?php if ($statusText !== 'approved'): ?>
                                        <form method="POST" action="<?= APP_BASE ?>/admin/creator-request" class="approved-record">
                                            <input type="hidden" name="dusername"
                                                   value="<?= htmlspecialchars($user->getUsername()) ?>">
                                            <input type="hidden" name="request_id" value="<?= $application->getRequestId() ?>">
                                            <input type="hidden" name="user_id" value="<?= $user->getUserId() ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" name="delete_btn" class="btn btn-sm btn-outline-success">
                                                <i class="ph ph-check-circle"></i>
                                                <span class="d-block d-md-none">Approved</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($statusText !== 'rejected'): ?>
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
