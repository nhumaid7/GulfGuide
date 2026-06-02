<?php
$selectedReport = $_GET['report'] ?? '';
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$countryId = trim($_GET['country'] ?? '');
$sortBy = trim($_GET['sort_by'] ?? 'likes');
$exportType = $_GET['export'] ?? '';
$filterUserId = trim($_GET['filter_user_id'] ?? '');

$validationErrors = [];

$allowedReports = ['', 'popular_posts', 'user_report', 'selet_user_report'];
if (!in_array($selectedReport, $allowedReports, true)) {
    $selectedReport = '';
}

$allowedSorts = ['likes', 'comments', 'newest', 'oldest'];
if (!in_array($sortBy, $allowedSorts, true)) {
    $sortBy = 'likes';
}

$allowedExports = ['', 'pdf', 'excel'];
if (!in_array($exportType, $allowedExports, true)) {
    $exportType = '';
}

if ($dateFrom !== '') {
    $dt = DateTime::createFromFormat('Y-m-d', $dateFrom);
    if (!$dt || $dt->format('Y-m-d') !== $dateFrom) {
        $validationErrors[] = 'Invalid "From Date" format.';
        $dateFrom = '';
    }
}

if ($dateTo !== '') {
    $dt2 = DateTime::createFromFormat('Y-m-d', $dateTo);
    if (!$dt2 || $dt2->format('Y-m-d') !== $dateTo) {
        $validationErrors[] = 'Invalid "To Date" format.';
        $dateTo = '';
    }
}

if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
    $validationErrors[] = '"From Date" cannot be after "To Date".';
    $dateFrom = '';
    $dateTo = '';
}

if ($countryId !== '') {
    if (!ctype_digit($countryId) || (int) $countryId <= 0) {
        $validationErrors[] = 'Invalid country selection.';
        $countryId = '';
    } else {
        $countryId = (string) (int) $countryId;
    }
}

$countries = [];

try {
    $countryStmt = $pdo->query("SELECT country_id, name FROM dbProj_country ORDER BY name ASC");
    if ($countryStmt) {
        $countries = $countryStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
}

if ($selectedReport === 'selet_user_report' && $filterUserId !== '') {
    if (!ctype_digit($filterUserId) || (int) $filterUserId <= 0) {
        $validationErrors[] = 'Invalid user selection.';
        $filterUserId = '';
    }
}

$filterUsersList = [];
try {
    $userListStmt = $pdo->query("SELECT user_id, username, role FROM dbProj_user WHERE role = 'creator' ORDER BY username ASC");
    if ($userListStmt) {
        $filterUsersList = $userListStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
}

$report1Data = [];
$totalPosts = 0;
$totalLikes = 0;
$totalComments = 0;
$attractionTypeStats = [];
$attractionStats = [];
$userStats = [];

if ($selectedReport === 'popular_posts' && empty($validationErrors)) {

    try {
        $sql = "
            SELECT p.post_id, p.title, p.status, p.created_at,
                   u.username,
                   c.name AS country_name,
                   c.flag_image,
                   a.name AS attraction_name,
                   at.name AS attraction_type,
                   (SELECT COUNT(*) FROM dbProj_reaction r WHERE r.post_id = p.post_id AND r.type = 'like') AS reaction_count,
                   (SELECT COUNT(*) FROM dbProj_comment cm WHERE cm.post_id = p.post_id) AS comment_count
            FROM dbProj_post p
            JOIN dbProj_user u ON p.user_id = u.user_id
            JOIN dbProj_country c ON p.country_id = c.country_id
            LEFT JOIN dbProj_attraction a ON p.attraction_id = a.attraction_id
            LEFT JOIN dbProj_attraction_type at ON a.type_id = at.type_id
            WHERE 1=1";

        $bindParams = [];

        if ($dateFrom !== '') {
            $sql .= " AND DATE(p.created_at) >= ?";
            $bindParams[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $sql .= " AND DATE(p.created_at) <= ?";
            $bindParams[] = $dateTo;
        }

        if ($countryId !== '') {
            $sql .= " AND p.country_id = ?";
            $bindParams[] = (int) $countryId;
        }

        switch ($sortBy) {
            case 'comments': $sql .= " ORDER BY comment_count DESC, reaction_count DESC";
                break;
            case 'newest': $sql .= " ORDER BY p.created_at DESC";
                break;
            case 'oldest': $sql .= " ORDER BY p.created_at ASC";
                break;
            default: $sql .= " ORDER BY reaction_count DESC, comment_count DESC";
                break;
        }

        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($bindParams)) {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $report1Data[] = $row;

                $totalLikes += (int) $row['reaction_count'];
                $totalComments += (int) $row['comment_count'];

                $type = $row['attraction_type'] ?: 'Unknown';
                $attrName = $row['attraction_name'] ?: 'No Attraction';
                $usr = $row['username'];

                $attractionTypeStats[$type] = ($attractionTypeStats[$type] ?? 0) + 1;
                $attractionStats[$attrName] = ($attractionStats[$attrName] ?? 0) + 1;
                $userStats[$usr] = ($userStats[$usr] ?? 0) + 1;
            }
        }
    } catch (Throwable $e) {
        $report1Data = [];
    }

    arsort($attractionTypeStats);
    arsort($attractionStats);
    arsort($userStats);

    $totalPosts = count($report1Data);
}

$typeLabels = array_keys($attractionTypeStats);
$typeData = array_values($attractionTypeStats);
$attractionLabels = array_keys($attractionStats);
$attractionData = array_values($attractionStats);
$userLabels = array_keys($userStats);
$userData = array_values($userStats);

$report2Data = [];
$countTotalUsers = 0;
$countAdmins = 0;
$countCreators = 0;
$countVisitors = 0;
$roleDistributionStats = [];
$topCreatorsStats = [];

if ($selectedReport === 'user_report' && empty($validationErrors)) {

    try {
        $sql = "
            SELECT u.user_id, u.username, u.email, u.role, u.created_at,
                   cr.status AS request_status,
                   cr.reason AS request_reason,
                   COUNT(DISTINCT p.post_id) AS total_posts,
                   COALESCE(SUM(lp.likes_received), 0) AS total_likes,
                   COUNT(DISTINCT p.attraction_id) AS distinct_attractions_count,
                   (SELECT a2.name
                    FROM dbProj_post p2
                    JOIN dbProj_attraction a2 ON p2.attraction_id = a2.attraction_id
                    WHERE p2.user_id = u.user_id
                    GROUP BY p2.attraction_id
                    ORDER BY COUNT(p2.post_id) DESC
                    LIMIT 1) AS top_attraction_name
            FROM dbProj_user u
            LEFT JOIN dbProj_creator_request cr ON u.user_id = cr.user_id
            LEFT JOIN dbProj_post p ON u.user_id = p.user_id
            LEFT JOIN (
                SELECT p_sub.user_id, COUNT(r_sub.reaction_id) AS likes_received
                FROM dbProj_post p_sub
                JOIN dbProj_reaction r_sub ON p_sub.post_id = r_sub.post_id
                WHERE r_sub.type = 'like'
                GROUP BY p_sub.user_id
            ) lp ON u.user_id = lp.user_id
            WHERE 1=1";

        $bindParams = [];

        if ($dateFrom !== '') {
            $sql .= " AND DATE(u.created_at) >= ?";
            $bindParams[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $sql .= " AND DATE(u.created_at) <= ?";
            $bindParams[] = $dateTo;
        }

        $sql .= " GROUP BY u.user_id, cr.status, cr.reason ORDER BY total_posts DESC, total_likes DESC";

        $stmt = $pdo->prepare($sql);

        if ($stmt->execute($bindParams)) {
            $uRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($uRows as $uRow) {
                $report2Data[] = $uRow;

                $role = strtolower($uRow['role']);

                if ($role === 'admin')
                    $countAdmins++;
                elseif ($role === 'creator')
                    $countCreators++;
                else
                    $countVisitors++;

                $roleTitle = ucfirst($role);
                $roleDistributionStats[$roleTitle] = ($roleDistributionStats[$roleTitle] ?? 0) + 1;

                if ((int) $uRow['total_posts'] > 0) {
                    $topCreatorsStats[$uRow['username']] = (int) $uRow['total_posts'];
                }
            }
        }
    } catch (Throwable $e) {
        $report2Data = [];
    }

    arsort($topCreatorsStats);
    $countTotalUsers = count($report2Data);
}

$userRoleLabels = array_keys($roleDistributionStats);
$userRoleData = array_values($roleDistributionStats);
$topCreatorLabels = array_slice(array_keys($topCreatorsStats), 0, 5);
$topCreatorData = array_slice(array_values($topCreatorsStats), 0, 5);

$singleUserData = null;
$singleUserPosts = [];
$singleUserStatusStats = ['Published' => 0, 'Draft' => 0];
$singleUserTimelineLabels = [];
$singleUserTimelineData = [];

if ($selectedReport === 'selet_user_report' && !empty($filterUserId) && empty($validationErrors)) {
    try {
        $uMetaSql = "
            SELECT u.user_id, u.username, u.email, u.role, u.created_at,
                   cr.status AS request_status, cr.reason AS request_reason
            FROM dbProj_user u
            LEFT JOIN dbProj_creator_request cr ON u.user_id = cr.user_id
            WHERE u.user_id = ? 
            LIMIT 1";

        $metaStmt = $pdo->prepare($uMetaSql);
        if ($metaStmt) {
            $userIdInt = (int) $filterUserId;
            if ($metaStmt->execute([$userIdInt])) {
                $suData = $metaStmt->fetch(PDO::FETCH_ASSOC);
                if ($suData) {
                    $singleUserData = [
                        'user_id' => $suData['user_id'],
                        'username' => $suData['username'],
                        'email' => $suData['email'],
                        'role' => $suData['role'],
                        'created_at' => $suData['created_at'],
                        'request_status' => $suData['request_status'],
                        'request_reason' => $suData['request_reason'],
                        'total_posts' => 0,
                        'total_likes' => 0,
                        'total_comments' => 0
                    ];
                }
            }
        }

        if ($singleUserData) {
            $pSql = "
                SELECT p.post_id, p.title, p.status, p.created_at,
                       c.name AS country_name,
                       a.name AS attraction_name,
                       (SELECT COUNT(*) FROM dbProj_reaction r WHERE r.post_id = p.post_id AND r.type = 'like') AS reaction_count,
                       (SELECT COUNT(*) FROM dbProj_comment cm WHERE cm.post_id = p.post_id) AS comment_count
                FROM dbProj_post p
                JOIN dbProj_country c ON p.country_id = c.country_id
                LEFT JOIN dbProj_attraction a ON p.attraction_id = a.attraction_id
                WHERE p.user_id = ?";

            $pBindParams = [(int) $filterUserId];

            if ($dateFrom !== '') {
                $pSql .= " AND DATE(p.created_at) >= ?";
                $pBindParams[] = $dateFrom;
            }
            if ($dateTo !== '') {
                $pSql .= " AND DATE(p.created_at) <= ?";
                $pBindParams[] = $dateTo;
            }

            $pSql .= " ORDER BY p.created_at DESC";

            $pStmt = $pdo->prepare($pSql);
            if ($pStmt) {
                if ($pStmt->execute($pBindParams)) {
                    $pRows = $pStmt->fetchAll(PDO::FETCH_ASSOC);
                    $timelineRaw = [];

                    foreach ($pRows as $pRow) {
                        $singleUserPosts[] = $pRow;

                        $singleUserData['total_posts']++;
                        $singleUserData['total_likes'] += (int) $pRow['reaction_count'];
                        $singleUserData['total_comments'] += (int) $pRow['comment_count'];

                        $statusTitle = ucfirst(strtolower($pRow['status'] ?: 'Draft'));
                        $singleUserStatusStats[$statusTitle] = ($singleUserStatusStats[$statusTitle] ?? 0) + 1;

                        $monthKey = date('Y-m', strtotime($pRow['created_at']));
                        $timelineRaw[$monthKey] = ($timelineRaw[$monthKey] ?? 0) + 1;
                    }

                    ksort($timelineRaw);
                    foreach ($timelineRaw as $month => $count) {
                        $singleUserTimelineLabels[] = date('M Y', strtotime($month . '-01'));
                        $singleUserTimelineData[] = $count;
                    }
                }
            }
        }
    } catch (Throwable $e) {
    }
}
$path_parts = explode('/', trim($uri, '/'));
$depth = count(array_filter($path_parts));
$base_prefix = str_repeat('../', $depth);
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
                    echo '<li class="breadcrumb-item active text-dark" aria-current="page">' . htmlspecialchars($label) . '</li>';
                } elseif ($part === 'admin') {
                    echo '<li class="breadcrumb-item">Admin Portal</li>';
                    echo '<li class="breadcrumb-item"><a href="' . APP_BASE . '/admin/dashboard">Dashboard</a></li>';
                } else {
                    echo '<li class="breadcrumb-item"><a href="' . APP_BASE . $currentPath . '">' . htmlspecialchars($label) . '</a></li>';
                }
                $i++;
            }
            ?>
        </ol>
    </nav>
</div>

<?php if (!empty($validationErrors)): ?>
    <div class="alert alert-danger mb-3" role="alert">
        <strong>Please fix the following:</strong>
        <ul class="mb-0 mt-1">
    <?php foreach ($validationErrors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
    <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card-section p-3 mb-4">
    <form id="analyticsForm" method="GET" action="" novalidate>
        <div class="d-flex flex-wrap gap-3 align-items-end">

            <div>
                <label for="report-select" class="form-label small fw-bold">Report Type</label>
                <select id="report-select" name="report" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="" disabled <?= $selectedReport === '' ? 'selected' : '' ?>>Select report…</option>
                    <option value="popular_posts" <?= $selectedReport === 'popular_posts' ? 'selected' : '' ?>>Most Popular Posts</option>
                    <option value="user_report"   <?= $selectedReport === 'user_report' ? 'selected' : '' ?>>User Report</option>
                    <option value="selet_user_report" <?= $selectedReport === 'selet_user_report' ? 'selected' : '' ?>>Creator Report</option>
                </select>
            </div>

<?php if ($selectedReport === 'selet_user_report'): ?>
                <div>
                    <label for="user-filter-select" class="form-label small fw-bold">Select Targeted User</label>
                    <select id="user-filter-select" name="filter_user_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Choose a User</option>
    <?php foreach ($filterUsersList as $fUser): ?>
                            <option value="<?= (int) $fUser['user_id'] ?>" <?= (string) $filterUserId === (string) $fUser['user_id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($fUser['username']) ?> 
                            </option>
    <?php endforeach; ?>
                    </select>
                </div>
<?php endif; ?>

<?php if ($selectedReport !== ''): ?>
                <div>
                    <label for="date_from" class="form-label small fw-bold">From Date</label>
                    <input type="date" id="date_from" name="date_from"
                           class="form-control form-control-sm"
                           value="<?= htmlspecialchars($dateFrom) ?>"
                           max="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label for="date_to" class="form-label small fw-bold">To Date</label>
                    <input type="date" id="date_to" name="date_to"
                           class="form-control form-control-sm"
                           value="<?= htmlspecialchars($dateTo) ?>"
                           max="<?= date('Y-m-d') ?>">
                </div>
                        <?php if ($selectedReport === 'popular_posts'): ?>
                    <div>
                        <label for="country-select" class="form-label small fw-bold">Country</label>
                        <select id="country-select" name="country" class="form-select form-select-sm">
                            <option value="">All Countries</option>
        <?php foreach ($countries as $country): ?>
                                <option value="<?= (int) $country['country_id'] ?>"
            <?= $countryId == $country['country_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($country['name']) ?>
                                </option>
        <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="sort-select" class="form-label small fw-bold">Sort By</label>
                        <select id="sort-select" name="sort_by" class="form-select form-select-sm">
                            <option value="likes"    <?= $sortBy === 'likes' ? 'selected' : '' ?>>Most Likes</option>
                            <option value="comments" <?= $sortBy === 'comments' ? 'selected' : '' ?>>Most Comments</option>
                            <option value="newest"   <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest</option>
                            <option value="oldest"   <?= $sortBy === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                        </select>
                    </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary btn-sm align-self-end">Apply</button>

    <?php
    $hasFilters = !empty($dateFrom) || !empty($dateTo) || !empty($countryId) || ($selectedReport === 'selet_user_report' && !empty($filterUserId)) || (!empty($sortBy) && $sortBy !== 'likes');
    if ($hasFilters):
        ?>
                    <a class="btn btn-secondary btn-sm align-self-end"
                       href="<?= APP_BASE ?>/admin/analytics?report=<?= urlencode($selectedReport) ?>">
                        Clear Filters
                    </a>
    <?php endif; ?>

                <div class="d-flex gap-2 align-self-end ms-auto">
                    <button type="button" class="btn btn-danger btn-sm" onclick="printReport()">
                        <i class="ph ph-file-pdf"></i> Export PDF
                    </button>
                </div>

<?php endif; ?>
        </div>
    </form>
</div>

<?php if ($selectedReport === ''): ?>
    <div class="report-empty-state">
        <i class="ph ph-chart-bar" style="font-size:3rem;color:var(--light-grey-400)"></i>
        <h5 class="mt-3">Select a Report to Get Started</h5>
        <p class="text-muted">Choose "Most Popular Posts", "User Report" or "Single User Report" from the dropdown above.</p>
    </div>
<?php endif; ?>

<?php if ($selectedReport === 'selet_user_report' && empty($filterUserId)): ?>
    <div class="report-empty-state">
        <i class="ph ph-user-focus" style="font-size:3rem;color:var(--light-grey-400)"></i>
        <h5 class="mt-3">No Target User Chosen</h5>
        <p class="text-muted">Please select an individual account from the filter menu to pull deep analytics metrics.</p>
    </div>
<?php endif; ?>

<?php if ($selectedReport === 'selet_user_report' && !empty($filterUserId) && $singleUserData && empty($validationErrors)): ?>
    <div class="card-section p-4 mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h4 class="mb-1"><?= htmlspecialchars($singleUserData['username']) ?></h4>
                <p class="text-muted mb-0 small">
            <?= htmlspecialchars($singleUserData['email']) ?> &nbsp;|&nbsp; Joined: <?= date('d M Y', strtotime($singleUserData['created_at'])) ?>
                </p>
            </div>
    <?php if ($singleUserData['request_status']): ?>
                <div class="col-auto text-end">
                    <span class="small fw-bold text-muted d-block mb-1 text-uppercase">Creator Verification</span>
                    <span class="gulfguide-badge gulfguide-badge--rounded <?= match (strtolower($singleUserData['request_status'])) { 'approved' => 'gulfguide-badge--success', 'rejected' => 'gulfguide-badge--danger', default => 'gulfguide-badge--warning'
        } ?>">
        <?= htmlspecialchars(ucfirst($singleUserData['request_status'])) ?>
                    </span>
                </div>
    <?php endif; ?>
        </div>
    </div>

    <div class="row g-3 mb-4 align-items-stretch">
        <div class="col-12 col-sm-4">
            <div class="card-section p-4">
                <span class="text-muted small fw-bold text-uppercase">Submissions/span>
                <h3 class="mt-1"><?= number_format($singleUserData['total_posts']) ?></h3>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card-section p-4">
                <span class="text-muted small fw-bold text-uppercase">Total Likes</span>
                <h3 class="mt-1"><?= number_format($singleUserData['total_likes']) ?></h3>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card-section p-4">
                <span class="text-muted small fw-bold text-uppercase">Total Comments</span>
                <h3 class="mt-1"><?= number_format($singleUserData['total_comments']) ?></h3>
            </div>
        </div>
    </div>

    <!-- Individual Post Output -->
    <div class="card-section mb-4">
        <div class="card-section--header">
            <h5 class="mb-0">Content Performance Log</h5>
        </div>
        <hr class="card-section--divider m-0">
        <div class="card-section--body table-responsive">
            <table class="datatable-gulfguide table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Post Title</th>
                        <th>Country</th>
                        <th>Attraction</th>
                        <th>Status</th>
                        <th>Likes</th>
                        <th>Comments</th>
                        <th>Creation Date</th>
                    </tr>
                </thead>
                <tbody>
    <?php if (empty($singleUserPosts)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No posts registered to this account within the specified metrics window.</td></tr>
    <?php else: ?>
        <?php foreach ($singleUserPosts as $pRow): ?>
                            <tr>
                                <td data-label="Post Title"><strong><?= htmlspecialchars($pRow['title']) ?></strong></td>
                                <td data-label="Associated Country"><?= htmlspecialchars($pRow['country_name']) ?></td>
                                <td data-label="Attraction Space"><?= htmlspecialchars($pRow['attraction_name'] ?: 'General / Unlinked') ?></td>
                                <td data-label="Workflow Status">
                                    <span class="gulfguide-badge gulfguide-badge--rounded <?= match (strtolower($pRow['status'] ?? '')) { 'published' => 'gulfguide-badge--success', 'draft' => 'gulfguide-badge--secondary', default => 'gulfguide-badge--info'
                } ?>">
            <?= htmlspecialchars(ucfirst($pRow['status'] ?? 'Draft')) ?>
                                    </span>
                                </td>
                                <td data-label="Likes"><?= number_format($pRow['reaction_count']) ?></td>
                                <td data-label="Comments"><?= number_format($pRow['comment_count']) ?></td>
                                <td data-label="Creation Date"><?= date('d M Y', strtotime($pRow['created_at'])) ?></td>
                            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Graphical Metrics Layout -->
    <div class="row g-4">
        <div class="col-md-5">
            <div class="card-section p-3">
                <canvas id="singleUserStatusChart"></canvas>
                <h6 class="text-center mt-2 text-muted">Lifecycle Breakdown</h6>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card-section p-3">
                <canvas id="singleUserTimelineChart"></canvas>
                <h6 class="text-center mt-2 text-muted">Submission Velocity Tracking</h6>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const cssVar = (v) => getComputedStyle(document.documentElement).getPropertyValue(v).trim();
            const primaryColor = cssVar('--brand-primary') || '#4169e1';
            const successColor = cssVar('--semantic-success') || '#2e7d32';
            const mutedColor = cssVar('--medium-grey-300') || '#9e9e9e';

            new Chart(document.getElementById('singleUserStatusChart'), {
                type: 'pie',
                data: {
                    labels: <?= json_encode(array_keys($singleUserStatusStats)) ?>,
                    datasets: [{
                            data: <?= json_encode(array_values($singleUserStatusStats)) ?>,
                            backgroundColor: [successColor, mutedColor]
                        }]
                },
                options: {plugins: {legend: {position: 'bottom'}}}
            });

            new Chart(document.getElementById('singleUserTimelineChart'), {
                type: 'line',
                data: {
                    labels: <?= json_encode($singleUserTimelineLabels) ?>,
                    datasets: [{
                            label: 'Items Published',
                            data: <?= json_encode($singleUserTimelineData) ?>,
                            borderColor: primaryColor,
                            backgroundColor: primaryColor + '20',
                            fill: true,
                            tension: 0.2
                        }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {beginAtZero: true, ticks: {precision: 0}}
                    }
                }
            });
        })();
    </script>
<?php endif; ?>

<?php if ($selectedReport === 'popular_posts' && empty($validationErrors)): ?>
    <div class="row g-3 mb-4 align-items-stretch">
        <div class="col-12 col-sm-4">
            <div class="card-section p-4">
                <span class="text-muted small fw-bold text-uppercase">Total Posts</span>
                <h3 class="mt-1"><?= number_format($totalPosts) ?></h3>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card-section p-4">
                <span class="text-muted small fw-bold text-uppercase">Total Likes</span>
                <h3 class="mt-1"><?= number_format($totalLikes) ?></h3>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card-section p-4">
                <span class="text-muted small fw-bold text-uppercase">Total Comments</span>
                <h3 class="mt-1"><?= number_format($totalComments) ?></h3>
            </div>
        </div>
    </div>

    <div class="card-section mb-4">
        <div class="card-section--header">
            <h5 class="mb-0">Posts</h5>
        </div>
        <hr class="card-section--divider m-0">
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
                        <th>Published</th>
                    </tr>
                </thead>
                <tbody>
    <?php if (empty($report1Data)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No posts found for the selected filters.</td></tr>
                            <?php else: ?>
                                <?php foreach ($report1Data as $row): ?>
                            <tr>
                                <td data-label="Title"><?= htmlspecialchars($row['title']) ?></td>
                                <td data-label="Country">
                                    <div class="d-flex align-items-center gap-2">
                                    <?php if (!empty($row['flag_image'])): ?>
                                            <img src="<?= htmlspecialchars($row['flag_image']) ?>" alt="" width="20" style="border-radius:2px">
                                    <?php endif; ?>
                                        <span><?= htmlspecialchars($row['country_name']) ?></span>
                                    </div>
                                </td>
                                <td data-label="Attraction">
                                    <?php if (!empty($row['attraction_name'])): ?>
                                        <?= htmlspecialchars($row['attraction_name']) ?>
                                        <?php if (!empty($row['attraction_type'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($row['attraction_type']) ?></small>
                                              <?php endif; ?>
                                              <?php else: ?>
                                        <span class="text-muted small">None</span>
            <?php endif; ?>
                                </td>
                                <td data-label="Creator"><?= htmlspecialchars($row['username']) ?></td>
                                <td data-label="Status">
                                    <span class="gulfguide-badge gulfguide-badge--rounded
                            <?=
                            match (strtolower($row['status'] ?? '')) {
                                'published' => 'gulfguide-badge--success',
                                'draft' => 'gulfguide-badge--secondary',
                                default => 'gulfguide-badge--info'
                            }
                            ?>">
            <?= htmlspecialchars(ucfirst($row['status'] ?? 'Draft')) ?>
                                    </span>
                                </td>
                                <td data-label="Likes"><?= number_format($row['reaction_count'] ?? 0) ?></td>
                                <td data-label="Comments"><?= number_format($row['comment_count'] ?? 0) ?></td>
                                <td data-label="Published"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card-section p-3">
                <canvas id="typeChart"></canvas>
                <h6 class="text-center mt-2 text-muted">Posts by Attraction Type</h6>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-section p-3">
                <canvas id="attractionChart"></canvas>
                <h6 class="text-center mt-2 text-muted">Posts by Attraction</h6>
            </div>
        </div>
        <div class="col-12">
            <div class="card-section p-3">
                <canvas id="userChart"></canvas>
                <h6 class="text-center mt-2 text-muted">Posts by Creator</h6>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const cssVar = (v) => getComputedStyle(document.documentElement).getPropertyValue(v).trim();
            const blue = cssVar('--royal-blue-500');
            const green = cssVar('--semantic-success');
            const orange = cssVar('--semantic-warning');
            const palette = [blue, green, orange,
                cssVar('--royal-blue-300'), cssVar('--royal-blue-700'), cssVar('--medium-grey-300')];

            new Chart(document.getElementById('typeChart'), {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($typeLabels) ?>,
                    datasets: [{data: <?= json_encode($typeData) ?>, backgroundColor: palette}]
                },
                options: {plugins: {legend: {position: 'bottom'}}}
            });

            new Chart(document.getElementById('attractionChart'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode($attractionLabels) ?>,
                    datasets: [{label: 'Posts', data: <?= json_encode($attractionData) ?>, backgroundColor: blue}]
                },
                options: {responsive: true, scales: {y: {beginAtZero: true, ticks: {precision: 0}}}}
            });

            new Chart(document.getElementById('userChart'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode($userLabels) ?>,
                    datasets: [{label: 'Posts Created', data: <?= json_encode($userData) ?>, backgroundColor: green}]
                },
                options: {responsive: true, indexAxis: 'y', scales: {x: {beginAtZero: true, ticks: {precision: 0}}}}
            });
        })();
    </script>

<?php endif; ?>

<?php if ($selectedReport === 'user_report' && empty($validationErrors)): ?>

    <!-- Summary cards -->
    <div class="row g-3 mb-4 align-items-stretch">
        <div class="col-6 col-md-3">
            <div class="card-section p-4">
                <span class="text-muted small fw-bold text-uppercase">Total Users</span>
                <h3 class="mt-1"><?= number_format($countTotalUsers) ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-section p-4">
                <span class="text-muted small fw-bold text-uppercase">Creators</span>
                <h3 class="mt-1"><?= number_format($countCreators) ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-section p-4">
                <span class="text-muted small fw-bold text-uppercase">Visitors</span>
                <h3 class="mt-1"><?= number_format($countVisitors) ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-section p-4">
                <span class="text-muted small fw-bold text-uppercase">Admins</span>
                <h3 class="mt-1"><?= number_format($countAdmins) ?></h3>
            </div>
        </div>
    </div>

    <!-- Users table -->
    <div class="card-section mb-4">
        <div class="card-section--header">
            <h5 class="mb-0">Users</h5>
        </div>
        <hr class="card-section--divider m-0">
        <div class="card-section--body table-responsive">
            <table class="datatable-gulfguide table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Posts</th>
                        <th>Likes</th>
                        <th>Top Attraction</th>
                        <th>Creator Request</th>
                    </tr>
                </thead>
                <tbody>
                            <?php if (empty($report2Data)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($report2Data as $uRow): ?>
                            <tr>
                                <td data-label="Username">
                                    <strong><?= htmlspecialchars($uRow['username']) ?></strong>
                                </td>
                                <td data-label="Email"><?= htmlspecialchars($uRow['email']) ?></td>
                                <td data-label="Role">
            <?php
            $roleLower = strtolower($uRow['role']);
            $roleBadge = match ($roleLower) {
                'admin' => 'gulfguide-badge--danger',
                'creator' => 'gulfguide-badge--success',
                default => 'gulfguide-badge--secondary',
            };
            ?>
                                    <span class="gulfguide-badge gulfguide-badge--rounded <?= $roleBadge ?>">
                                    <?= htmlspecialchars(ucfirst($uRow['role'])) ?>
                                    </span>
                                </td>
                                <td data-label="Joined"><?= date('d M Y', strtotime($uRow['created_at'])) ?></td>
                                <td data-label="Posts"><?= number_format($uRow['total_posts']) ?></td>
                                <td data-label="Likes"><?= number_format($uRow['total_likes']) ?></td>
                                <td data-label="Top Attraction">
                                    <?php if (!empty($uRow['top_attraction_name'])): ?>
                                            <?= htmlspecialchars($uRow['top_attraction_name']) ?>
                                        <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Creator Request">
            <?php if ($uRow['request_status']): ?>
                                <?php
                                $reqBadge = match (strtolower($uRow['request_status'])) {
                                    'approved' => 'gulfguide-badge--success',
                                    'rejected' => 'gulfguide-badge--danger',
                                    default => 'gulfguide-badge--warning',
                                };
                                ?>
                                        <span class="gulfguide-badge gulfguide-badge--rounded <?= $reqBadge ?>">
                <?= htmlspecialchars(ucfirst($uRow['request_status'])) ?>
                                        </span>
            <?php else: ?>
                                        <span class="text-muted small">No request</span>
            <?php endif; ?>
                                </td>
                            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card-section p-3">
                <canvas id="userRolesChart"></canvas>
                <h6 class="text-center mt-2 text-muted">Users by Role</h6>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-section p-3">
                <canvas id="topCreatorsChart"></canvas>
                <h6 class="text-center mt-2 text-muted">Top 5 Creators by Posts</h6>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const cssVar = (v) => getComputedStyle(document.documentElement).getPropertyValue(v).trim();
            const palette = [
                cssVar('--brand-primary'), cssVar('--semantic-success'),
                cssVar('--semantic-warning'), cssVar('--semantic-failure'), cssVar('--semantic-info')
            ];

            new Chart(document.getElementById('userRolesChart'), {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($userRoleLabels) ?>,
                    datasets: [{data: <?= json_encode($userRoleData) ?>, backgroundColor: palette}]
                },
                options: {plugins: {legend: {position: 'bottom'}}}
            });

            new Chart(document.getElementById('topCreatorsChart'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode($topCreatorLabels) ?>,
                    datasets: [{
                            label: 'Posts',
                            data: <?= json_encode($topCreatorData) ?>,
                            backgroundColor: cssVar('--semantic-success')
                        }]
                },
                options: {responsive: true, scales: {y: {beginAtZero: true, ticks: {precision: 0}}}}
            });
        })();
    </script>

<?php endif; ?>

<script>
    (function () {
        const form = document.getElementById('analyticsForm');
        if (!form)
            return;

        form.addEventListener('submit', function (e) {
            const dateFrom = form.querySelector('#date_from');
            const dateTo = form.querySelector('#date_to');
            if (!dateFrom || !dateTo)
                return;

            const from = dateFrom.value;
            const to = dateTo.value;
            const today = new Date().toISOString().slice(0, 10);
            const errors = [];

            if (from && from > today)
                errors.push('"From Date" cannot be in the future.');
            if (to && to > today)
                errors.push('"To Date" cannot be in the future.');
            if (from && to && from > to)
                errors.push('"From Date" must be before or equal to "To Date".');

            if (errors.length > 0) {
                e.preventDefault();
                let alert = document.getElementById('js-validation-alert');
                if (!alert) {
                    alert = document.createElement('div');
                    alert.id = 'js-validation-alert';
                    alert.className = 'alert alert-warning alert-dismissible mt-3';
                    alert.role = 'alert';
                    form.parentNode.insertBefore(alert, form.nextSibling);
                }
                alert.innerHTML = '<strong>Please fix:</strong><ul class="mb-0 mt-1">'
                        + errors.map(err => '<li>' + err + '</li>').join('')
                        + '</ul><button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>';
                alert.scrollIntoView({behavior: 'smooth', block: 'center'});
            }
        });

        const df = form.querySelector('#date_from');
        const dt = form.querySelector('#date_to');
        if (df && dt) {
            df.addEventListener('change', function () {
                if (dt.value && dt.value < df.value) {
                    dt.value = df.value;
                }
                dt.min = df.value || '';
            });
            dt.addEventListener('change', function () {
                if (df.value && dt.value < df.value) {
                    df.value = dt.value;
                }
                df.max = dt.value || '<?= date('Y-m-d') ?>';
            });
        }
    })();
</script>

<style>
    @media print {
        .sidebar,
        .topbar,
        .breadcrumb,
        #analyticsForm,
        .card-section.p-3.mb-4,
        .report-empty-state,
        .btn, button {
            display: none !important;
        }

        .main {
            margin-left: 0 !important;
            padding: 0 !important;
        }

        body {
            background: #fff !important;
            font-size: 11px;
            color: #1f1f1f;
        }

        .row {
            display: flex !important;
            flex-wrap: wrap !important;
        }
        .col-12, .col-sm-4, .col-6, .col-md-3, .col-md-6 {
            flex: 1 1 auto !important;
            max-width: none !important;
        }

        .card-section {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
            break-inside: avoid;
        }

        .datatable-gulfguide {
            font-size: 10px !important;
            width: 100% !important;
        }
        .datatable-gulfguide thead th {
            background: #4169e1 !important;
            color: #fff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        canvas {
            max-width: 100% !important;
        }

        .row.g-4 {
            break-before: page;
        }

        #print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 24px;
            border-bottom: 2px solid #4169e1;
            padding-bottom: 12px;
        }
        #print-header h1 {
            font-size: 22px;
            color: #4169e1;
            margin: 0 0 4px 0;
        }
        #print-header p  {
            font-size: 11px;
            color: #666;
            margin: 0;
        }

        #print-cover {
            display: flex !important;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            page-break-after: always;
            text-align: center;
        }
        #print-cover img {
            max-width: 200px;
            margin-bottom: 20px;
        }
        #print-cover h1 {
            font-size: 36px;
            color: #4169e1;
            margin-bottom: 10px;
        }
        #print-cover h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 30px;
        }
        #print-cover p {
            font-size: 14px;
            color: #666;
            margin: 5px 0;
        }
    }

    #print-header, #print-cover {
        display: none;
    }
</style>

<script>
    function printReport() {
    const reportLabels = {
        'popular_posts': 'Most Popular Posts',
        'user_report': 'User Report',
        'selet_user_report': 'Single User Report'
    };

    const reportName = reportLabels[<?= json_encode($selectedReport) ?>] || 'Analytics Report';
    const dateFrom = <?= json_encode($dateFrom ?: 'All time') ?>;
    const dateTo = <?= json_encode($dateTo ?: 'All time') ?>;

    let cover = document.getElementById('print-cover');
    if (!cover) {
        cover = document.createElement('div');
        cover.id = 'print-cover';
        const main = document.querySelector('.main') || document.body;
        main.insertBefore(cover, main.firstChild);
    }

    const userRole = <?= json_encode($_SESSION['username'] ?? 'Admin') ?>;
    const generated = new Date().toLocaleString();
    const filterText = 'Filters: Date From: ' + dateFrom + ', Date To: ' + dateTo;

    cover.innerHTML =
        '<img src="<?= $base_prefix ?>assets/images/logo.svg" alt="Logo"  width="100">' +
        '<h1>GulfGuide Analytics</h1>' +
        '<h2>' + reportName + '</h2>' +
        '<p>Generated by: ' + userRole + '</p>' +
        '<p>Date Generated: ' + generated + '</p>' +
        '<p>' + filterText + '</p>';

    let hdr = document.getElementById('print-header');
    if (!hdr) {
        hdr = document.createElement('div');
        hdr.id = 'print-header';
        const main = document.querySelector('.main') || document.body;
        main.insertBefore(hdr, cover.nextSibling);
    }

    hdr.innerHTML =
        '<h1>' +
        '<img src="<?= $base_prefix ?>assets/images/icon.svg" alt="Icon" width="25"> ' +
        'GulfGuide &mdash; ' + reportName +
        '</h1>';

    window.print();
}
</script>