<?php
// view-index.php — loaded by index.php router at '/'
$username   = $_SESSION['username'] ?? null;
$role       = $_SESSION['role']     ?? 'guest';
$isLoggedIn = isset($_SESSION['user_id']);

$badgeMap = [
    'admin'   => ['label' => 'Admin',   'class' => 'badge-admin'],
    'creator' => ['label' => 'Creator', 'class' => 'badge-creator'],
    'visitor' => ['label' => 'Member',  'class' => 'badge-member'],
];
$badge    = $badgeMap[$role] ?? ['label' => 'Guest', 'class' => 'badge-guest'];
$initials = $isLoggedIn ? strtoupper(substr($username, 0, 2)) : '?';
?>
<style>
 /* Reset */
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: sans-serif;
  min-height: 100vh;
}

/* Navbar */
.gg-nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid #ccc;
}

.nav-logo {
  text-decoration: none;
  font-weight: bold;
}

.nav-links {
  display: flex;
  gap: 0.5rem;
  list-style: none;
}

.nav-links a {
  text-decoration: none;
  padding: 0.3rem 0.6rem;
}

.nav-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.nav-avatar {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  border: 1px solid #000;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
}

.nav-logout,
.nav-btn {
  text-decoration: none;
  border: 1px solid #000;
  padding: 0.3rem 0.6rem;
}

/* Hero */
.gg-hero {
  padding: 2rem;
}

.hero-inner {
  max-width: 1000px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 2rem;
}

.hero-heading {
  font-size: 2rem;
  margin-bottom: 1rem;
}

.hero-sub {
  font-size: 1rem;
  margin-bottom: 1rem;
}

.hero-cta {
  display: inline-block;
  border: 1px solid #000;
  padding: 0.5rem 1rem;
  text-decoration: none;
}

/* User / Guest Cards */
.user-card,
.guest-card {
  border: 1px solid #000;
  padding: 1.5rem;
  text-align: center;
  min-width: 200px;
}

.user-avatar-lg {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  border: 1px solid #000;
  margin: 0 auto 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.user-name {
  font-size: 1rem;
  margin-bottom: 0.3rem;
}

.user-email {
  font-size: 0.8rem;
  margin-bottom: 0.5rem;
}

.badge-pill {
  font-size: 0.7rem;
  border: 1px solid #000;
  padding: 0.2rem 0.5rem;
  display: inline-block;
}

.card-divider {
  border-top: 1px solid #ccc;
  margin: 1rem 0;
}

.card-stat {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
}

.card-action {
  display: block;
  margin-top: 0.5rem;
  padding: 0.4rem;
  border: 1px solid #000;
  text-decoration: none;
}

/* Section */
.section {
  max-width: 1000px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

.section-title {
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
}

/* Explore grid */
.explore-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1rem;
}

.explore-card {
  border: 1px solid #000;
  padding: 1rem;
  text-decoration: none;
  display: flex;
  flex-direction: column;
}

.explore-name {
  font-size: 1rem;
  margin-bottom: 0.3rem;
}

.explore-desc {
  font-size: 0.8rem;
}

.explore-arrow {
  margin-top: auto;
  font-size: 0.8rem;
}

/* Divider */
.section-divider {
  border-top: 1px solid #ccc;
  margin: 2rem auto;
  max-width: 1000px;
}

/* Responsive */
@media (max-width: 768px) {
  .hero-inner {
    grid-template-columns: 1fr;
  }

  .nav-links {
    display: none;
  }
}
</style>

<!-- ══════════════════════════════════ NAVBAR -->
<nav class="gg-nav">

  <a href="<?= APP_BASE ?>/" class="nav-logo">Gulf<span>Guide</span></a>

  <ul class="nav-links">
    <li><a href="<?= APP_BASE ?>/" class="active"><i class="ph ph-house"></i> Home</a></li>
    <li><a href="<?= APP_BASE ?>/country/all"><i class="ph ph-globe-hemisphere-east"></i> Countries</a></li>
    <li><a href="<?= APP_BASE ?>/locations/all"><i class="ph ph-map-pin"></i> Locations</a></li>
    <li><a href="<?= APP_BASE ?>/posts/all"><i class="ph ph-newspaper"></i> Posts</a></li>
    <?php if ($role === 'creator' || $role === 'admin'): ?>
      <li><a href="<?= APP_BASE ?>/creator/create-post"><i class="ph ph-plus-circle"></i> New Post</a></li>
    <?php endif; ?>
    <?php if ($role === 'admin'): ?>
      <li><a href="<?= APP_BASE ?>/admin/"><i class="ph ph-layout"></i> Dashboard</a></li>
    <?php endif; ?>
  </ul>

  <div class="nav-right">
    <?php if ($isLoggedIn): ?>
      <div class="nav-avatar"><?= $initials ?></div>
      <span class="nav-username"><?= htmlspecialchars($username) ?></span>
      <a href="<?= APP_BASE ?>/logout" class="nav-logout">
        <i class="ph ph-sign-out"></i> Logout
      </a>
    <?php else: ?>    
      <a href="<?= APP_BASE ?>/login"  class="nav-logout">Sign in</a>
      <a href="<?= APP_BASE ?>/signup" class="nav-btn">Create account</a>
    <?php endif; ?>
  </div>

</nav>

<!-- ══════════════════════════════════ HERO -->
<section class="gg-hero">
  <div class="hero-inner">

    <div>
      <p class="hero-eyebrow fade-up">Your Gulf guide awaits</p>

      <?php if ($isLoggedIn): ?>
        <h1 class="hero-heading fade-up delay-1">
          Welcome back,<br><em><?= htmlspecialchars($username) ?></em>
        </h1>
        <p class="hero-sub fade-up delay-2">
          Discover the best places, local stories, and hidden gems across the Gulf region — curated just for you.
        </p>
      <?php else: ?>
        <h1 class="hero-heading fade-up delay-1">
          Explore the<br><em>Gulf Region</em>
        </h1>
        <p class="hero-sub fade-up delay-2">
          Your guide to the best destinations, local stories, and hidden gems across Bahrain, Saudi Arabia, UAE, Kuwait, Qatar, and Oman.
        </p>
      <?php endif; ?>

      <a href="<?= APP_BASE ?>/posts/all" class="hero-cta fade-up delay-3">
        <i class="ph ph-compass"></i> Browse all posts
      </a>
    </div>

    <div>
      <?php if ($isLoggedIn): ?>
        <div class="user-card">
          <div class="user-avatar-lg"><?= $initials ?></div>
          <div class="user-name"><?= htmlspecialchars($username) ?></div>
          <div class="user-email"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></div>
          <span class="badge-pill <?= $badge['class'] ?>"><?= $badge['label'] ?></span>

          <hr class="card-divider">

          <div class="card-stat"><span>Account status</span><span>Active</span></div>
          <div class="card-stat"><span>Role</span><span><?= ucfirst($role) ?></span></div>

          <?php if ($role === 'admin'): ?>
            <a href="<?= APP_BASE ?>/admin/" class="card-action card-action-primary">
              <i class="ph ph-layout"></i> Go to Dashboard
            </a>
          <?php elseif ($role === 'creator'): ?>
            <a href="<?= APP_BASE ?>/creator/" class="card-action card-action-primary">
              <i class="ph ph-pencil-simple"></i> Creator Studio
            </a>
          <?php else: ?>
            <a href="<?= APP_BASE ?>/posts/all" class="card-action card-action-primary">
              <i class="ph ph-map-pin"></i> Explore Posts
            </a>
          <?php endif; ?>

          <a href="<?= APP_BASE ?>/logout" class="card-action card-action-outline">
            <i class="ph ph-sign-out"></i> Sign out
          </a>
        </div>

      <?php else: ?>
        <div class="guest-card">
          <div class="guest-icon"><i class="ph ph-user-circle"></i></div>
          <h3>Join GulfGuide</h3>
          <p>Create a free account to save favourites, follow creators, and get personalised recommendations.</p>
          <a href="<?= APP_BASE ?>/signup" class="card-action card-action-primary">Create account</a>
          <a href="<?= APP_BASE ?>/login"  class="card-action card-action-outline">Sign in</a>
        </div>
      <?php endif; ?>
    </div>

  </div>
</section>

<hr class="section-divider">

<!-- ══════════════════════════════════ EXPLORE -->
<div class="section">
  <p class="section-label">Discover</p>
  <h2 class="section-title">Start exploring</h2>

  <div class="explore-grid">

    <a href="<?= APP_BASE ?>/country/all" class="explore-card fade-up">
      <div class="explore-icon"><i class="ph ph-globe-hemisphere-east"></i></div>
      <div class="explore-name">Countries</div>
      <div class="explore-desc">Browse all six Gulf countries and their unique character.</div>
      <div class="explore-arrow">View countries <i class="ph ph-arrow-right"></i></div>
    </a>

    <a href="<?= APP_BASE ?>/locations/all" class="explore-card fade-up delay-1">
      <div class="explore-icon"><i class="ph ph-map-pin"></i></div>
      <div class="explore-name">Locations</div>
      <div class="explore-desc">Curated spots — from ancient forts to modern waterfronts.</div>
      <div class="explore-arrow">View locations <i class="ph ph-arrow-right"></i></div>
    </a>

    <a href="<?= APP_BASE ?>/posts/all" class="explore-card fade-up delay-2">
      <div class="explore-icon"><i class="ph ph-newspaper"></i></div>
      <div class="explore-name">Posts</div>
      <div class="explore-desc">Stories, guides and tips written by our creator community.</div>
      <div class="explore-arrow">Read posts <i class="ph ph-arrow-right"></i></div>
    </a>

    <?php if ($role === 'creator' || $role === 'admin'): ?>
    <a href="<?= APP_BASE ?>/creator/create-post" class="explore-card fade-up delay-3">
      <div class="explore-icon"><i class="ph ph-plus-circle"></i></div>
      <div class="explore-name">New Post</div>
      <div class="explore-desc">Share your own Gulf experience with the community.</div>
      <div class="explore-arrow">Create post <i class="ph ph-arrow-right"></i></div>
    </a>
    <?php endif; ?>

  </div>
</div>