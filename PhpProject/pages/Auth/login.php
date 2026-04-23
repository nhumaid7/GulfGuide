<?php

if (isLoggedIn()) {
    redirectByRole();
}

$email_err = $password_err = $login_err = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = sanitize(trim($_POST["email"]));
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Attempt login if no validation errors
    if (empty($email_err) && empty($password_err)) {
        $sql  = "SELECT user_id, username, email, hashed_password, `role`
                 FROM dbProj_user
                 WHERE email = :email
                 LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        $passwordOk = false;

        if ($user) {
            if (password_verify($password, $user['hashed_password'])) {
                // Properly hashed — normal path
                $passwordOk = true;
            } elseif ($password === $user['hashed_password']) {
                // Plain text match (old test account) — upgrade the hash now
                $passwordOk = true;
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE dbProj_user SET hashed_password = :h WHERE user_id = :id");
                $upd->execute([':h' => $newHash, ':id' => $user['user_id']]);
            }
        }

        if ($passwordOk) {
            $_SESSION['user_id']  = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email']    = $user['email'];
            $_SESSION['role']     = $user['role'];

            redirectByRole();
        } else {
            $login_err = "Invalid email or password.";
        }
    }
}
?>

<div class="container my-5">
    <h2 class="text-center mb-4">Login</h2>
    <div class="row justify-content-center">
        <div class="col-md-6">

            <?php if (!empty($login_err)): ?>
                <div class="alert alert-danger"><?= $login_err ?></div>
            <?php endif; ?>

            <form action="" method="post">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address *</label>
                    <input
                        type="email"
                        class="form-control <?= !empty($email_err) ? 'is-invalid' : '' ?>"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($email) ?>"
                        required
                    >
                    <?php if (!empty($email_err)): ?>
                        <div class="invalid-feedback"><?= $email_err ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password *</label>
                    <input
                        type="password"
                        class="form-control <?= !empty($password_err) ? 'is-invalid' : '' ?>"
                        id="password"
                        name="password"
                        required
                    >
                    <?php if (!empty($password_err)): ?>
                        <div class="invalid-feedback"><?= $password_err ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>

            <p class="mt-3 text-center">
                Don't have an account? <a href="<?= APP_BASE ?>/signup">Register here</a>
            </p>
        </div>
    </div>
</div>