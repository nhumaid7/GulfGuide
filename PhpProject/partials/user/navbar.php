<?php
$_navUsername   = $_SESSION['username'] ?? null;
$_navRole       = $_SESSION['role']     ?? 'guest';
$_navIsLoggedIn = isset($_SESSION['user_id']);
$_navInitials   = $_navIsLoggedIn ? strtoupper(substr($_navUsername, 0, 2)) : '?';
?>
<style>
.gg-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .75rem 1.5rem;
    border-bottom: 1px solid #e5e5e5;
    background: #fff;
    position: sticky;
    top: 0;
    z-index: 999;
}
.nav-logo {
    text-decoration: none;
    font-weight: 800;
    font-size: 1.15rem;
    color: #1f1f1f;
    letter-spacing: .3px;
}
.nav-links {
    display: flex;
    gap: .25rem;
    list-style: none;
    margin: 0;
    padding: 0;
}
.nav-links a {
    text-decoration: none;
    padding: .35rem .7rem;
    border-radius: 6px;
    color: #4d4d4d;
    font-size: .9rem;
    font-weight: 500;
    transition: background .15s, color .15s;
}
.nav-links a:hover { background: #f0f4ff; color: #4169e1; }
.nav-right {
    display: flex;
    align-items: center;
    gap: .5rem;
}
.nav-avatar {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: #4169e1;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .75rem;
    font-weight: 700;
}
.nav-username { font-size: .875rem; font-weight: 600; color: #1f1f1f; }
.nav-logout {
    text-decoration: none;
    border: 1px solid #d4d4d4;
    padding: .3rem .7rem;
    border-radius: 6px;
    font-size: .875rem;
    color: #4d4d4d;
    transition: border-color .15s, color .15s;
}
.nav-logout:hover { border-color: #4169e1; color: #4169e1; }
.nav-btn {
    text-decoration: none;
    background: #4169e1;
    color: #fff;
    padding: .3rem .8rem;
    border-radius: 6px;
    font-size: .875rem;
    font-weight: 600;
    transition: background .15s;
}
.nav-btn:hover { background: #3758c0; color: #fff; }
@media (max-width: 768px) {
    .nav-links { display: none; }
    .nav-username { display: none; }
}
</style>
<nav class="gg-nav">
    <a href="<?= APP_BASE ?>/" class="nav-logo">Gulf<span style="color:var(--brand-primary)">Guide</span></a>

    <ul class="nav-links">
        <li><a href="<?= APP_BASE ?>/">Home</a></li>
        <li><a href="<?= APP_BASE ?>/country/all">Countries</a></li>
        <li><a href="<?= APP_BASE ?>/locations/all">Locations</a></li>
        <li><a href="<?= APP_BASE ?>/posts/all">Posts</a></li>
        <?php if ($_navRole === 'creator' || $_navRole === 'admin'): ?>
            <li><a href="<?= APP_BASE ?>/creator/">My Dashboard</a></li>
        <?php endif; ?>
    </ul>

    <div class="nav-right">
        <?php if ($_navIsLoggedIn): ?>
            <div class="nav-avatar"><?= htmlspecialchars($_navInitials) ?></div>
            <span style="font-size:var(--font-size-p-s); font-weight:600;">
                <?= htmlspecialchars($_navUsername) ?>
            </span>
            <a href="<?= APP_BASE ?>/logout" class="nav-logout">
                <i class="ph ph-sign-out"></i> Logout
            </a>
        <?php else: ?>
            <a href="<?= APP_BASE ?>/login"  class="nav-logout">Sign in</a>
            <a href="<?= APP_BASE ?>/signup" class="nav-btn">Create account</a>
        <?php endif; ?>
    </div>
</nav>
