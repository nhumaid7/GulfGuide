<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    abort(403);
}

$countriesStmt = $pdo->query("SELECT country_id, name FROM dbProj_country ORDER BY name ASC");
$countries = $countriesStmt->fetchAll(PDO::FETCH_ASSOC);

$search = trim($_GET['search'] ?? '');
$advanced = isset($_GET['advanced']) && $_GET['advanced'] === '1';
$countryId = isset($_GET['country_id']) ? (int)$_GET['country_id'] : 0;
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$sql = "SELECT p.*, u.username, c.name, c.flag_image FROM dbProj_post p JOIN dbProj_user u ON p.user_id = u.user_id JOIN dbProj_country c ON p.country_id = c.country_id";

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(MATCH(p.title, p.content, p.status) AGAINST(:search1 IN BOOLEAN MODE))";
    $params[':search1'] = $search;
}

if ($advanced) {
    if ($countryId > 0) {
        $where[] = "p.country_id = :country_id";
        $params[':country_id'] = $countryId;
    }
    if ($dateFrom !== '') {
        $where[] = "p.created_at >= :date_from";
        $params[':date_from'] = $dateFrom . ' 00:00:00';
    }
    if ($dateTo !== '') {
        $where[] = "p.created_at <= :date_to";
        $params[':date_to'] = $dateTo . ' 23:59:59';
    }
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$postRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$posts = array_map([Post::class, 'fromArray'], $postRows);
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
        <p class="h5-style">All Posts</p>
        <div class="d-flex gap-1">
            <span class="gulfguide-badge">Total: <?= count($posts) ?> posts</span>
        </div>
    </div>
    <hr class="card-section--divider">
    <div class="card-section--body">
        <form method="GET" action="<?= APP_BASE ?>/admin/moderate-posts" class="mb-4">
            <div class="row g-2 align-items-center mb-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                    <?php if (!empty($search) || $advanced): ?>
                        <a class="btn btn-secondary w-100" href="<?= APP_BASE ?>/admin/moderate-posts">Clear</a>
                    <?php endif; ?>
                </div>
                <div class="col-md-12">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="advancedSearchToggle" name="advanced" value="1" <?= $advanced ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="advancedSearchToggle">Advanced Filters</label>
                    </div>
                </div>
            </div>

            <div id="advancedFiltersSection" class="<?= $advanced ? '' : 'd-none' ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="country_id" class="form-label small fw-bold">Country</label>
                        <select name="country_id" id="country_id" class="form-select form-select-sm">
                            <option value="">All Countries</option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= $c['country_id'] ?>" <?= $countryId === (int)$c['country_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4"> 
                        <label for="date_from" class="form-label small fw-bold">Created From</label>
                        <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="date_to" class="form-label small fw-bold">Created To</label>
                        <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                </div>
            </div>
        </form>

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
                        <?php if (count($posts) === 0): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No posts found matching the criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($posts as $index => $post): ?>
                                <tr>
                                    <td><?= $post->getPostId() ?></td>
                                    <td><?= htmlspecialchars($post->getTitle()) ?></td>
                                    <td>
                                        <?php if (!empty($postRows[$index]['flag_image'])): ?>
                                            <img src="<?= htmlspecialchars($postRows[$index]['flag_image']) ?>" 
                                                 width="24" alt="flag">
                                        <?php else: ?>
                                            <i class="ph ph-flag"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($postRows[$index]['name']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($postRows[$index]['username']) ?></td>
                                    <td><?= htmlspecialchars(((new DateTime($post->getCreatedAt()))->format("F j, Y, g:i a"))) ?></td>
                                    <td class="text-center">
                                        <a class="btn btn-sm btn-outline-success" href="<?= APP_BASE ?>/admin/moderate-posts/<?= $post->getPostId() ?>">
                                                    <i class="ph ph-eye"></i>
                                                    <span class="d-block d-md-none">View Post</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
