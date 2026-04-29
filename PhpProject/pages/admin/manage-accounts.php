<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    abort(403);
}

$stmt = $pdo->query('SELECT * FROM dbProj_user ORDER BY created_at DESC');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$users = array_map([User::class, 'fromArray'], $rows);

if (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $id = (int) $_POST['delete_user_id'];
    $userId = $_SESSION['user_id'] ?? null;

    if ($userId == $id) {
        $_SESSION['status'] = "You can't delete your own account";
        $_SESSION['status_code'] = "error";
    } else {
        try {
             $stmt = $pdo->prepare("DELETE FROM dbProj_user WHERE user_id = :id");
             $stmt->execute([':id' => $id]);

            $_SESSION['status'] = "User deleted successfully";
            $_SESSION['status_code'] = "success";
        } catch (PDOException $e) {
            $_SESSION['status'] = "Delete failed: " . $e->getMessage();
            $_SESSION['status_code'] = "error";
        }
    }
    header("Location: " . APP_BASE . "/admin/manage-accounts");
    exit;
}
?>  
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3">
    <h2>Manage Accounts</h2>
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
        <p class="h5-style">All users</p>
        <span class="gulfguide-badge"><?= count($users) ?> users</span>
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
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th class="action-th text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user->getUserId() ?></td>
                                <td><?= htmlspecialchars($user->getUsername()) ?></td>
                                <td><a href="mailto:<?= htmlspecialchars($user->getEmail()) ?>">
                                <?= htmlspecialchars($user->getEmail()) ?></a></td>
                                <td>
                                    <?php
                                    $roleMap = [
                                        'admin' => [
                                            'class' => 'gulfguide-badge--danger',
                                            'icon' => 'ph-shield-star'
                                        ],
                                        'creator' => [
                                            'class' => 'gulfguide-badge--warning',
                                            'icon' => 'ph-pencil'
                                        ],
                                        'visitor' => [
                                            'class' => 'gulfguide-badge--secondary',
                                            'icon' => 'ph-user'
                                        ],
                                    ];
                                    ?>
                                    <?php $role = $roleMap[$user->getRole()] ?? $roleMap['visitor']; ?>
                                    <span class="gulfguide-badge <?= $role['class'] ?> gulfguide-badge--rounded">
                                        <i class="ph-fill <?= $role['icon'] ?>"></i>
                                        <?= ucfirst($user->getRole()) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars(((new DateTime($user->getCreatedAt()))->format("F j, Y, g:i a"))) ?>
                                </td>
                                <td class="text-center">
                                    <form method="POST" action="<?= APP_BASE ?>/admin/manage-accounts" class="delete-record">
                                        <input type="hidden" name="delete_user_id" value="<?= $user->getUserId() ?>">
                                        <input type="hidden" name="delete_username"
                                               value="<?= htmlspecialchars($user->getUsername()) ?>">
                                        <input type="hidden" name="action" value="delete_user">
                                        <button type="submit" name="delete_btn" class="btn btn-sm btn-outline-danger">
                                            <i class="ph ph-trash"></i>
                                            <span class="d-block d-md-none">Delete User</span>
                                        </button>
                                    </form>
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
    $(document).on('submit', '.delete-record', function (e) {
        e.preventDefault();

        let form = $(this);
        let username = form.find('input[name="delete_username"]').val();

        Swal.fire({
            title: 'Delete user',
            text: `Are you sure you want to delete "${username}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4169e1',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete',
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
