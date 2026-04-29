<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    abort(403);
}

$id = $params['id'] ?? null;
if (!$id) {
    abort(404);
}
if ($id != null) {
    $stmt = $pdo->prepare('SELECT * FROM dbProj_post p '
            . 'JOIN dbProj_user u ON p.user_id = u.user_id '
            . 'JOIN dbProj_country c ON p.country_id = c.country_id '
            . 'WHERE p.post_id = :postId');
    $stmt->execute([':postId' => $id]);
    $postRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $posts = array_map([Post::class, 'fromArray'], $postRows);
    $post = $posts[0] ?? null;
}
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
        <h3>Post ID: #<?= $id ?></h3>
        <div class="d-flex gap-1">
            <form method="POST" action="<?= APP_BASE ?>/admin/creator-request" class="rejected-record">
                <input type="hidden" name="action" value="reject">
                <button type="submit" name="delete_btn" class="btn btn-sm btn-danger">
                    <i class="ph ph-x-circle"></i>
                    <span>Rejected</span>
                </button>
            </form>
        </div>
    </div>
</div>

<div class="pt-2">
    <div class="row g-4">
        <div class="col-lg-8 col-xxl-9">
            <div class="card-section">
                <div class="card-section--header">
                    <h6>Ganeral</h6>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-xxl-3 vstack gap-4">
            <div class="card-section">
                <div class="card-section--header">
                    <h6>Author Details</h6>
                </div>
                <div class="card-section--body">
                    <table>
                        <tr>
                            <th>Name:</th>
                            <td><?= htmlspecialchars($postRows[0]['name']) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
