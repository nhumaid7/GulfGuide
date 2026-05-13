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
    $message = trim($_POST['message'] ?? '');

    if ($message === '') {
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
                    (user_id, reason, status, requested_at)
                VALUES
                    (?, ?, ?, NOW())
            ");

            $stmt->execute([
                $userId,
                $message,
                REQUEST_PENDING
            ]);

            $success = 'Your request has been submitted successfully.';
        }
    }
}
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <h1>Request Creator Status</h1>
            <p>Submit your request to become a creator on GulfGuide</p>

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
                <form method="POST" action="<?= APP_BASE ?>/upgrade-to-creator" class="mt-4">
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>

                        <textarea
                            class="form-control"
                            id="message"
                            name="message"
                            rows="5"
                            required
                            placeholder="Tell us why you want to be a creator..."
                        ><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Submit Request
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>