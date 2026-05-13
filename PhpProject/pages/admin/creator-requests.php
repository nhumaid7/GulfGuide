<?php
requireLogin();

$userId = (int) $_SESSION['user_id'];
$errors = [];
$success = '';

$stmt = $pdo->prepare("SELECT user_id, username, role FROM dbProj_user WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: ' . REDIRECT_LOGIN);
    exit;
}

if ($user['role'] === ROLE_CREATOR) {
    $success = 'You are already a creator.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] !== ROLE_CREATOR) {
    $reason = trim($_POST['reason'] ?? '');

    if ($reason === '') {
        $errors[] = 'Please write why you want to become a creator.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("
            SELECT request_id, status
            FROM dbProj_creator_request
            WHERE user_id = ?
            ORDER BY requested_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $existingRequest = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingRequest && $existingRequest['status'] === REQUEST_PENDING) {
            $errors[] = 'You already have a pending creator request.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO dbProj_creator_request
                    (status, reason, requested_at, user_id)
                VALUES
                    (?, ?, NOW(), ?)
            ");

            $stmt->execute([
                REQUEST_PENDING,
                $reason,
                $userId
            ]);

            $success = 'Your creator request has been sent successfully.';
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h2 class="mb-2">Upgrade to Creator</h2>

                    <p class="text-muted">
                        Send a request to become a creator. After admin approval, you will be able to create and manage travel content.
                    </p>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <div><?= htmlspecialchars($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($user['role'] !== ROLE_CREATOR): ?>
                        <form method="POST" action="<?= APP_BASE ?>/upgrade-to-creator">
                            <div class="mb-3">
                                <label class="form-label">Why do you want to become a creator?</label>
                                <textarea 
                                    name="reason" 
                                    class="form-control" 
                                    rows="5" 
                                    required
                                ><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Send Request
                            </button>

                            <a href="<?= REDIRECT_VISITOR ?>" class="btn btn-light">
                                Cancel
                            </a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>