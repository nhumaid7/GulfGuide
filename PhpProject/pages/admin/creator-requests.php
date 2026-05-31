<?php
$errors = [];
$success = '';

$creatorRole = defined('ROLE_CREATOR') ? ROLE_CREATOR : 'creator';
$pendingStatus = defined('REQUEST_PENDING') ? REQUEST_PENDING : 'pending';
$approvedStatus = defined('REQUEST_APPROVED') ? REQUEST_APPROVED : 'approved';
$rejectedStatus = defined('REQUEST_REJECTED') ? REQUEST_REJECTED : 'rejected';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';

    if (!$requestId) {
        $errors[] = 'Invalid creator request.';
    }

    if (!in_array($action, ['approve', 'reject'], true)) {
        $errors[] = 'Invalid action.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("
            SELECT cr.request_id, cr.user_id, cr.status, u.username
            FROM dbProj_creator_request cr
            INNER JOIN dbProj_user u ON cr.user_id = u.user_id
            WHERE cr.request_id = ?
            LIMIT 1
        ");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            $errors[] = 'Creator request not found.';
        } elseif ($request['status'] !== $pendingStatus) {
            $errors[] = 'This request has already been reviewed.';
        } else {
            try {
                $pdo->beginTransaction();

                if ($action === 'approve') {
                    $stmt = $pdo->prepare("
                        UPDATE dbProj_creator_request
                        SET status = ?
                        WHERE request_id = ?
                    ");
                    $stmt->execute([$approvedStatus, $requestId]);

                    $stmt = $pdo->prepare("
                        UPDATE dbProj_user
                        SET role = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$creatorRole, $request['user_id']]);

                    $success = 'Creator request approved successfully.';
                }

                if ($action === 'reject') {
                    $stmt = $pdo->prepare("
                        UPDATE dbProj_creator_request
                        SET status = ?
                        WHERE request_id = ?
                    ");
                    $stmt->execute([$rejectedStatus, $requestId]);

                    $success = 'Creator request rejected successfully.';
                }

                $pdo->commit();

                echo "<script>window.location.href='" . APP_BASE . "/admin/creator-request';</script>";
                exit;

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Action failed: ' . $e->getMessage();
            }
        }
    }
}

$stmt = $pdo->query("
    SELECT 
        cr.request_id,
        cr.user_id,
        cr.reason,
        cr.status,
        cr.requested_at,
        u.username,
        u.email,
        u.role
    FROM dbProj_creator_request cr
    INNER JOIN dbProj_user u ON cr.user_id = u.user_id
    ORDER BY 
        CASE WHEN cr.status = 'pending' THEN 0 ELSE 1 END,
        cr.requested_at DESC
");

$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .gg-request-page{padding:38px 48px 70px;background:#f4f7fb;min-height:calc(100vh - 70px)}
    .gg-request-hero{background:linear-gradient(135deg,#223f72 0%,#31558f 100%);color:#fff;border-radius:24px;padding:34px 38px;margin-bottom:28px;box-shadow:0 18px 38px rgba(31,63,110,.22);position:relative;overflow:hidden}
    .gg-request-title{font-size:34px;font-weight:900;margin-bottom:8px;letter-spacing:-.7px}
    .gg-request-subtitle{color:rgba(255,255,255,.78);margin:0;font-size:15px}
    .gg-request-card{background:#fff;border:1px solid #e5ebf3;border-radius:22px;box-shadow:0 14px 34px rgba(15,23,42,.07);overflow:hidden}
    .gg-request-header{padding:22px 24px;border-bottom:1px solid #e8eef6;background:linear-gradient(135deg,#fff 0%,#f8fbff 100%);display:flex;justify-content:space-between;gap:18px;align-items:center}
    .gg-request-card-title{margin:0;font-size:22px;font-weight:900;color:#101828}
    .gg-request-card-text{margin:5px 0 0;color:#667085;font-size:13px}
    .gg-table{margin:0;font-size:14px}
    .gg-table thead th{background:#fbfdff;color:#344054;font-weight:900;padding:15px 20px;border-bottom:1px solid #dde5ef;font-size:13px;text-transform:uppercase;letter-spacing:.3px}
    .gg-table tbody td{padding:16px 20px;vertical-align:middle;border-bottom:1px solid #eef2f6}
    .gg-table tbody tr:hover{background:#f8fbff}
    .gg-user-name{font-weight:900;color:#101828;margin-bottom:4px}
    .gg-user-email{font-size:13px;color:#667085}
    .gg-reason{max-width:520px;color:#344054;font-size:14px;line-height:1.45}
    .gg-status{border-radius:999px;padding:7px 13px;font-size:13px;font-weight:900;display:inline-block;text-transform:capitalize}
    .gg-status-pending{background:#fff8e6;color:#b7791f;border:1px solid #f6d98b}
    .gg-status-approved{background:#ecfdf3;color:#027a48;border:1px solid #b7ebc6}
    .gg-status-rejected{background:#fff5f5;color:#b42318;border:1px solid #ffd1d1}
    .gg-approve-btn{background:#12b76a;color:#fff;border:0;border-radius:999px;padding:8px 15px;font-size:13px;font-weight:900}
    .gg-approve-btn:hover{background:#039855}
    .gg-reject-btn{background:#fff;color:#b42318;border:1px solid #f5b5b5;border-radius:999px;padding:8px 15px;font-size:13px;font-weight:900}
    .gg-reject-btn:hover{background:#dc3545;color:#fff}
    .gg-alert{border-radius:16px;padding:15px 18px;margin-bottom:20px;font-size:14px;font-weight:700}
</style>

<div class="gg-request-page">

    <section class="gg-request-hero">
        <h1 class="gg-request-title">Creator Requests</h1>
        <p class="gg-request-subtitle">
            Review user applications and decide who can become a GulfGuide creator.
        </p>
    </section>

    <?php if ($errors): ?>
        <div class="gg-alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="gg-alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <section class="gg-request-card">
        <div class="gg-request-header">
            <div>
                <h2 class="gg-request-card-title">Applications</h2>
                <p class="gg-request-card-text">Approve or reject creator access requests.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table gg-table align-middle">
                <thead>
                <tr>
                    <th>User</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Requested At</th>
                    <th>Action</th>
                </tr>
                </thead>

                <tbody>
                <?php if (!$requests): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            No creator requests found.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($requests as $request): ?>
                    <?php
                    $statusClass = 'gg-status-pending';
                    if ($request['status'] === $approvedStatus) {
                        $statusClass = 'gg-status-approved';
                    } elseif ($request['status'] === $rejectedStatus) {
                        $statusClass = 'gg-status-rejected';
                    }
                    ?>

                    <tr>
                        <td>
                            <div class="gg-user-name"><?= htmlspecialchars($request['username']) ?></div>
                            <div class="gg-user-email"><?= htmlspecialchars($request['email'] ?? 'No email') ?></div>
                        </td>

                        <td>
                            <div class="gg-reason">
                                <?= htmlspecialchars(mb_strimwidth($request['reason'] ?? '', 0, 140, '...')) ?>
                            </div>
                        </td>

                        <td>
                            <span class="gg-status <?= $statusClass ?>">
                                <?= htmlspecialchars($request['status']) ?>
                            </span>
                        </td>

                        <td>
                            <?= htmlspecialchars($request['requested_at']) ?>
                        </td>

                        <td>
                            <?php if ($request['status'] === $pendingStatus): ?>
                                <div class="d-flex gap-2 flex-wrap">
                                    <form method="POST" action="<?= APP_BASE ?>/admin/creator-request">
                                        <input type="hidden" name="request_id" value="<?= htmlspecialchars($request['request_id']) ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="gg-approve-btn">
                                            Approve
                                        </button>
                                    </form>

                                    <form method="POST" action="<?= APP_BASE ?>/admin/creator-request">
                                        <input type="hidden" name="request_id" value="<?= htmlspecialchars($request['request_id']) ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="gg-reject-btn">
                                            Reject
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">Reviewed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>