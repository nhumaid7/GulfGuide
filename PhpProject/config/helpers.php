<?php
// for session management 

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function currentUserRole(): ?string {
    return $_SESSION['role'] ?? null;
}

function currentUsername(): ?string {
    return $_SESSION['username'] ?? null;
}

// role checks

function isAdmin(): bool {
    return isLoggedIn() && $_SESSION['role'] === ROLE_ADMIN;
}

function isCreator(): bool {
    return isLoggedIn() && $_SESSION['role'] === ROLE_CREATOR;
}

function isVisitor(): bool {
    return isLoggedIn() && $_SESSION['role'] === ROLE_VISITOR;
}

// access : check if user is logged in - if not, redirect him to login page

function requireLogin(): void {
    if (!isLoggedIn()) {
        header("Location: " . REDIRECT_LOGIN);
        exit();
    }
}

function requireRole(string|array $roles): void {
    requireLogin();

    $roles = (array) $roles;

    if (!in_array($_SESSION['role'], $roles)) {
        http_response_code(403);
        die("
            <h2>Access Denied</h2>
            <p>You do not have permission to view this page.</p>
            <a href='" . REDIRECT_LOGIN . "'>Go back</a>
        ");
    }
}

//     redirect after login based on role 

function redirectByRole(): void {
    switch ($_SESSION['role']) {
        case ROLE_ADMIN:
            header("Location: " . REDIRECT_ADMIN);
            break;
        case ROLE_CREATOR:
            header("Location: " . REDIRECT_CREATOR);
            break;
        default:
            header("Location: " . REDIRECT_VISITOR);
            break;
    }
    exit();
}

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)));
}
?>