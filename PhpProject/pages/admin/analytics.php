<?php
$selectedReport = $_GET['report'] ?? '';
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$report1Data = [];

if ($selectedReport === 'popular_posts') {
    try {
        $sql = "
            SELECT p.*, 
    u.*, 
    c.name AS country_name,
    c.display_image,
    c.flag_image,
    c.official_tourism_website,
    c.description AS country_description,
    a.name AS attraction_name,
    a.description AS attraction_description,
    a.cover_image AS attraction_cover,
    at.name AS attraction_type,
    (SELECT COUNT(*) FROM dbProj_reaction r WHERE r.post_id = p.post_id AND r.type = 'like') AS reaction_count,
    (SELECT COUNT(*) FROM dbProj_comment cm WHERE cm.post_id = p.post_id) AS comment_count
    FROM dbProj_post p 
    JOIN dbProj_user u ON p.user_id = u.user_id JOIN dbProj_country c ON p.country_id = c.country_id LEFT JOIN dbProj_attraction a 
    ON p.attraction_id = a.attraction_id LEFT JOIN dbProj_attraction_type at ON a.type_id = at.type_id
        ";
        $params = [];

        if ($dateFrom !== '') {
            $sql .= " AND DATE(p.created_at) >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $sql .= " AND DATE(p.created_at) <= :date_to";
            $params[':date_to'] = $dateTo;
        }


        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $report1Data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $report1Data = [];
    }
}

$report2Data = [];
if ($selectedReport === 'creators_by_user') {
    try {
        
    } catch (Throwable $e) {
        $report2Data = [];
    }
}
?>  
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3">
    <h2>Analytics</h2>
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
<div class="card-section p-3">
    <form method="GET" action="">
        <div class="d-flex flex-wrap gap-3 align-items-center">
            <div>
                <label for="report-select" class="form-label small fw-bold">Select Report Type:</label>
                <select id="report-select" name="report" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="" disabled <?= $selectedReport === '' ? 'selected' : '' ?>>Select report</option>
                    <option value="popular_posts" <?= $selectedReport === 'popular_posts' ? 'selected' : '' ?>>Most Popular Posts</option>
                    <option value="creators_by_user" <?= $selectedReport === 'creators_by_user' ? 'selected' : '' ?>>Content Creators Breakdown</option>
                </select>
            </div>

            <?php if ($selectedReport === 'popular_posts'): ?>
                <div>
                    <label for="date_from" class="form-label small fw-bold">From Date</label>
                    <input type="date" id="date_from" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($dateFrom) ?>" onchange="this.form.submit()">
                </div>
                <div>
                    <label for="date_to" class="form-label small fw-bold">To Date</label>
                    <input type="date" id="date_to" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($dateTo) ?>" onchange="this.form.submit()">
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php if ($selectedReport === ''): ?>
    <div class="report-empty-state">
        <h5>Select a Report</h5>
    </div>
<?php endif; ?>
<?php
if ($selectedReport === 'popular_posts'):
    $totalPosts = count($report1Data);
    $totalLikes = 0;
    $totalComments = 0;
    foreach ($report1Data as $row) {
        $totalLikes += ($row['reaction_count'] ?? 0);
        $totalComments += ($row['comment_count'] ?? 0);
    }

    $attractionTypeStats = [];
    $attractionStats = [];
    $userStats = [];

    foreach ($report1Data as $row) {
        $type = $row['attraction_type'] ?: 'Unknown';
        if (!isset($attractionTypeStats[$type])) {
            $attractionTypeStats[$type] = 0;
        }
        $attractionTypeStats[$type]++;
        $attraction = $row['attraction_name'] ?: 'No Attraction';
        if (!isset($attractionStats[$attraction])) {
            $attractionStats[$attraction] = 0;
        }
        $attractionStats[$attraction]++;

        $user = $row['username'];
        if (!isset($userStats[$user])) {
            $userStats[$user] = 0;
        }
        $userStats[$user]++;
    }

    $typeLabels = array_keys($attractionTypeStats);
    $typeData = array_values($attractionTypeStats);

    $attractionLabels = array_keys($attractionStats);
    $attractionData = array_values($attractionStats);

    $userLabels = array_keys($userStats);
    $userData = array_values($userStats);
    ?>
    <div class="row g-3 mt-2">
        <div class="col-12 col-sm-4">
            <div class="card-section p-4">
                <span>Total Dataset Posts</span>
                <h3><?= number_format($totalPosts) ?></h3>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card-section p-4">
                <span>Accumulated Likes</span>
                <h3><?= number_format($totalLikes) ?></h3>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card-section p-4">
                <span>Accumulated Comments</span>
                <h3><?= number_format($totalComments) ?></h3>
            </div>
        </div>
    </div>

    <div class="card-section mt-4">
        <div class="card-section--header p-3">
            <h5 class="mb-0">Most Popular Posts</h5>
        </div>
        <hr class="card-section--divider">
        <div class="card-section--body table-responsive">
            <table class="datatable-gulfguide table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Country</th>
                        <th>Attraction</th>
                        <th>Creator</th>
                        <th>Status</th>
                        <th>Likes</th>
                        <th>Comments</th>
                        <th>Created</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($report1Data as $row): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($row['title']) ?>
                            </td>

                            <td >
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (!empty($row['flag_image'])): ?>
                                        <img src="<?= htmlspecialchars($row['flag_image']) ?>" alt="" width="20">
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($row['country_name']) ?></span>
                                </div>
                            </td>

                            <td>
                                <?php if (!empty($row['attraction_name'])): ?>
                                    <span class="text-dark"><?= htmlspecialchars($row['attraction_name']) ?></span>
                                    <?php if (!empty($row['attraction_type'])): ?>
                                        <br>
                                        <small>
                                            <?= htmlspecialchars($row['attraction_type']) ?>
                                        </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted italic small">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['username']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['status'] ?? 'Draft') ?>
                            </td>
                            <td>
                                <?= number_format($row['reaction_count'] ?? 0) ?>
                            </td>
                            <td>
                                <?= number_format($row['comment_count'] ?? 0) ?>
                            </td>
                            <td>
                                <?= date('d M Y', strtotime($row['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4 mt-3">
        <div class="col-md-6">
            <div class="card-section p-3">
                <canvas id="typeChart"></canvas>
                <h5>Posts by Attraction Type</h5>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-section p-3">
                <canvas id="attractionChart"></canvas>
                <h5>Posts by Attraction</h5>
            </div>
        </div>
        <div class="col-12">
            <div class="card-section p-3">
                <canvas id="userChart"></canvas>
                <h5>Posts by Creator</h5>
            </div>
        </div>
    </div> 
    <script>
        new Chart(document.getElementById('typeChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($typeLabels) ?>,
                datasets: [{
                        data: <?= json_encode($typeData) ?>,
                        backgroundColor: [
                            '#2563eb',
                            '#10b981',
                            '#f59e0b',
                            '#ef4444',
                            '#8b5cf6',
                            '#06b6d4'
                        ]
                    }]
            }
        });

        new Chart(document.getElementById('attractionChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($attractionLabels) ?>,
                datasets: [{
                        label: 'Posts',
                        data: <?= json_encode($attractionData) ?>,
                        backgroundColor: '#2563eb'
                    }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        new Chart(document.getElementById('userChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($userLabels) ?>,
                datasets: [{
                        label: 'Posts Created',
                        data: <?= json_encode($userData) ?>,
                        backgroundColor: '#10b981'
                    }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });

    </script>
<?php endif; ?>
