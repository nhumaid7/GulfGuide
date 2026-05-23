<?php
$errors = [];
$success = '';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='" . APP_BASE . "/login';</script>";
    exit;
}

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT user_id, username, role FROM dbProj_user WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<script>window.location.href='" . APP_BASE . "/login';</script>";
    exit;
}

if ($user['role'] === ROLE_CREATOR) {
    $success = 'You are already approved as a creator.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] !== ROLE_CREATOR) {
    $message = trim($_POST['message'] ?? '');

    if ($message === '') {
        $errors[] = 'Please explain why you would like to become a creator.';
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
            $errors[] = 'You already have a pending creator application.';
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

            $success = 'Your creator application has been submitted successfully.';
        }
    }
}
?>

<style>
    .creator-application-page {
        background: #f4f7fb;
        min-height: calc(100vh - 80px);
        padding: 60px 20px;
    }

    .creator-application-wrapper {
        max-width: 780px;
        margin: 0 auto;
    }

    .creator-hero {
        background: linear-gradient(135deg, #233f71 0%, #31558f 100%);
        color: #ffffff;
        border-radius: 24px;
        padding: 34px 38px;
        margin-bottom: 28px;
        box-shadow: 0 18px 38px rgba(31, 63, 110, 0.20);
        position: relative;
        overflow: hidden;
    }

    .creator-hero::after {
        content: "";
        position: absolute;
        width: 250px;
        height: 250px;
        border-radius: 50%;
        right: -90px;
        top: -120px;
        background: rgba(255, 255, 255, 0.08);
    }

    .creator-hero-content {
        position: relative;
        z-index: 2;
    }

    .creator-title {
        font-size: 34px;
        font-weight: 900;
        margin-bottom: 8px;
        letter-spacing: -0.6px;
    }

    .creator-subtitle {
        color: rgba(255, 255, 255, 0.78);
        font-size: 15px;
        margin: 0;
        max-width: 560px;
    }

    .creator-card {
        background: #ffffff;
        border: 1px solid #e5ebf3;
        border-radius: 24px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.07);
        overflow: hidden;
    }

    .creator-card-header {
        padding: 24px 28px;
        border-bottom: 1px solid #e8eef6;
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
    }

    .creator-card-title {
        margin: 0;
        font-size: 22px;
        font-weight: 900;
        color: #101828;
        letter-spacing: -0.3px;
    }

    .creator-card-text {
        margin: 6px 0 0;
        color: #667085;
        font-size: 14px;
    }

    .creator-card-body {
        padding: 28px;
    }

    .creator-user-box {
        background: #f8fbff;
        border: 1px solid #e3ecf8;
        border-radius: 16px;
        padding: 16px 18px;
        margin-bottom: 22px;
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: center;
    }

    .creator-user-name {
        font-weight: 900;
        color: #101828;
        margin-bottom: 3px;
    }

    .creator-user-role {
        color: #667085;
        font-size: 13px;
        margin: 0;
    }

    .creator-status {
        background: #eaf3ff;
        color: #2f55c8;
        border: 1px solid #cfe1ff;
        border-radius: 999px;
        padding: 8px 14px;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
    }

    .creator-label {
        font-size: 14px;
        font-weight: 800;
        color: #344054;
        margin-bottom: 8px;
    }

    .creator-textarea {
        border: 1px solid #d9e2ef;
        border-radius: 14px;
        padding: 14px 16px;
        font-size: 14px;
        color: #111827;
        background: #ffffff;
        resize: vertical;
        transition: 0.2s ease;
    }

    .creator-textarea:focus {
        border-color: #4169e1;
        box-shadow: 0 0 0 4px rgba(65, 105, 225, 0.10);
    }

    .creator-help {
        font-size: 12px;
        color: #7b8794;
        margin-top: 7px;
    }

    .creator-alert-danger {
        border-radius: 16px;
        border: 1px solid #ffd1d1;
        background: #fff5f5;
        color: #b42318;
        padding: 16px 18px;
        margin-bottom: 20px;
        font-size: 14px;
        font-weight: 600;
    }

    .creator-alert-success {
        border-radius: 16px;
        border: 1px solid #b7ebc6;
        background: #f0fff4;
        color: #027a48;
        padding: 16px 18px;
        margin-bottom: 20px;
        font-size: 14px;
        font-weight: 700;
    }

    .creator-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 24px;
        padding-top: 22px;
        border-top: 1px solid #eef2f6;
    }

    .creator-submit-btn {
        background: #4169e1;
        color: #ffffff;
        border: 0;
        border-radius: 12px;
        padding: 12px 22px;
        font-size: 14px;
        font-weight: 900;
        box-shadow: 0 8px 18px rgba(65, 105, 225, 0.22);
        transition: 0.2s ease;
    }

    .creator-submit-btn:hover {
        background: #3155c9;
        color: #ffffff;
        transform: translateY(-1px);
    }

    .creator-back-btn {
        background: #f3f6fb;
        color: #344054;
        border: 1px solid #e3e8f0;
        border-radius: 12px;
        padding: 12px 20px;
        font-size: 14px;
        font-weight: 800;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .creator-back-btn:hover {
        background: #eaf3ff;
        color: #2446bb;
        border-color: #cfe1ff;
    }

    @media (max-width: 768px) {
        .creator-application-page {
            padding: 35px 16px;
        }

        .creator-title {
            font-size: 28px;
        }

        .creator-hero {
            padding: 28px;
        }

        .creator-card-body {
            padding: 22px;
        }

        .creator-user-box {
            flex-direction: column;
            align-items: flex-start;
        }

        .creator-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .creator-submit-btn,
        .creator-back-btn {
            width: 100%;
            text-align: center;
        }
    }
</style>

<div class="creator-application-page">
    <div class="creator-application-wrapper">

        <section class="creator-hero">
            <div class="creator-hero-content">
                <h1 class="creator-title">Creator Application</h1>
                <p class="creator-subtitle">
                    Apply to become a GulfGuide creator and share travel posts, experiences, and recommendations with other users.
                </p>
            </div>
        </section>

        <?php if ($errors): ?>
            <div class="creator-alert-danger">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="creator-alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <section class="creator-card">
            <div class="creator-card-header">
                <h2 class="creator-card-title">Request Creator Access</h2>
                <p class="creator-card-text">
                    Tell the admin team why you would like to become a creator.
                </p>
            </div>

            <div class="creator-card-body">

                <div class="creator-user-box">
                    <div>
                        <div class="creator-user-name">
                            <?= htmlspecialchars($user['username']) ?>
                        </div>
                        <p class="creator-user-role">
                            Current role: <?= htmlspecialchars($user['role']) ?>
                        </p>
                    </div>

                    <div class="creator-status">
                        GulfGuide User
                    </div>
                </div>

                <?php if ($user['role'] !== ROLE_CREATOR): ?>
                    <form method="POST" action="<?= APP_BASE ?>/upgrade-to-creator">
                        <div class="mb-3">
                            <label for="message" class="form-label creator-label">
                                Application Message
                            </label>

                            <textarea
                                class="form-control creator-textarea"
                                id="message"
                                name="message"
                                rows="6"
                                required
                                placeholder="Tell us why you want to become a creator and what type of travel content you plan to share..."
                            ><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>

                            <div class="creator-help">
                                Write a short and clear reason. The admin will review your request.
                            </div>
                        </div>

                        <div class="creator-actions">
                            <button type="submit" class="creator-submit-btn">
                                Submit Application
                            </button>

                            <a href="<?= APP_BASE ?>/" class="creator-back-btn">
                                Cancel
                            </a>
                        </div>
                    </form>
                <?php else: ?>
                    <a href="<?= APP_BASE ?>/" class="creator-back-btn">
                        Back to Home
                    </a>
                <?php endif; ?>

            </div>
        </section>

    </div>
</div>