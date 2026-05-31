<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    abort(403);
}

$stmt = $pdo->query(
    'SELECT * FROM dbProj_post p 
     JOIN dbProj_user u ON p.user_id = u.user_id 
     JOIN dbProj_country c ON p.country_id = c.country_id 
     ORDER BY p.created_at DESC'
);
$postRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                        echo '<a href="' . APP_BASE . '/admin/"> Dashboard </a>';
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
        <p class="h5-style">Moderate Posts</p>
        <div class="d-flex gap-1">
            <span class="gulfguide-badge">Total: <?= count($postRows) ?> posts</span>
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
                            <th>Author</th>
                            <th>Country</th>
                            <th>Created At</th>
                            <th class="action-th text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$postRows): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No posts found.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($postRows as $post): ?>
                            <tr>
                                <td><?= htmlspecialchars($post['post_id']) ?></td>

                                <td><?= htmlspecialchars($post['title'] ?? '') ?></td>

                                <td><?= htmlspecialchars($post['username'] ?? '') ?></td>

                                <td>
                                    <?php if (!empty($post['flag_image'])): ?>
                                        <img
                                            src="<?= htmlspecialchars($post['flag_image']) ?>"
                                            width="24"
                                            alt="flag"
                                        >
                                    <?php else: ?>
                                        <i class="ph ph-flag"></i>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($post['name'] ?? '') ?>
                                </td>

                                <td>
                                    <?php if (!empty($post['created_at'])): ?>
                                        <?= htmlspecialchars((new DateTime($post['created_at']))->format("F j, Y, g:i a")) ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <a class="btn btn-sm btn-outline-success" href="<?= APP_BASE ?>/posts/<?= htmlspecialchars($post['post_id']) ?>">
                                        <i class="ph ph-eye"></i>
                                        <span class="d-block d-md-none">View Post</span>
                                    </a>
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
            text: `Are you sure you want to reject ${username}'s creator request?`,
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
            text: `Are you sure you want to approved ${username}'s creator request?`,
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