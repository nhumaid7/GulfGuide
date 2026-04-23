<?php
// init.php is already loaded by index.php router — do NOT require it again

if (isLoggedIn()) {
    redirectByRole();
}

$username_err = $email_err = $password_err = $confirm_err = "";
$username = $email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        $username = sanitize(trim($_POST["username"]));
        $stmt = $pdo->prepare("SELECT user_id FROM dbProj_user WHERE username = :username");
        $stmt->execute([':username' => $username]);
        if ($stmt->rowCount() > 0) {
            $username_err = "This username is already taken.";
        }
    }

    // Email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email address.";
    } else {
        $email = sanitize(trim($_POST["email"]));
        $stmt = $pdo->prepare("SELECT user_id FROM dbProj_user WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->rowCount() > 0) {
            $email_err = "This email is already registered.";
        }
    }

    // Password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must be at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_err = "Please confirm your password.";
    } elseif (trim($_POST["confirm_password"]) !== ($password ?? '')) {
        $confirm_err = "Passwords do not match.";
    }

    // Insert if no errors
    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_err)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $sql  = "INSERT INTO dbProj_user (username, email, hashed_password, `role`, created_at)
                 VALUES (:username, :email, :hashed_password, :role, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':username'        => $username,
            ':email'           => $email,
            ':hashed_password' => $hashed,
            ':role'            => ROLE_VISITOR,
        ]);

        // Auto-login after registration
        $user_id = $pdo->lastInsertId();
        $_SESSION['user_id']  = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['email']    = $email;
        $_SESSION['role']     = ROLE_VISITOR;

        redirectByRole();
    }
}
?>

<div class="container my-5">
    <h2 class="text-center mb-4">Create Account</h2>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <form action="" method="post" novalidate>

                <div class="mb-3">
                    <label for="username" class="form-label">Username *</label>
                    <input
                        type="text"
                        class="form-control <?= !empty($username_err) ? 'is-invalid' : '' ?>"
                        id="username"
                        name="username"
                        value="<?= htmlspecialchars($username) ?>"
                        required
                    >
                    <?php if (!empty($username_err)): ?>
                        <div class="invalid-feedback"><?= $username_err ?></div>
                    <?php endif; ?>
                </div>

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

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                    <input
                        type="password"
                        class="form-control <?= !empty($confirm_err) ? 'is-invalid' : '' ?>"
                        id="confirm_password"
                        name="confirm_password"
                        required
                    >
                    <?php if (!empty($confirm_err)): ?>
                        <div class="invalid-feedback"><?= $confirm_err ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary w-100">Register</button>
            </form>

            <p class="mt-3 text-center">
                Already have an account? <a href="<?= APP_BASE ?>/login">Login here</a>.
            </p>
        </div>
    </div>
</div>