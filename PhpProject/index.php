<?php
ob_start();
// ─── Bootstrap ───────────────────────────────────────────────────────────────
// session_start() MUST come before anything touches $_SESSION
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/config/dbConn.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/classes/user.php';
require_once __DIR__ . "/classes/creatorRequest.php";
require_once __DIR__ . '/classes/post.php';
require_once __DIR__ . '/classes/comment.php';


$script = $_SERVER['SCRIPT_NAME']; 
$base   = dirname($script);     


$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace($base, '', $uri);
$uri = str_replace('/index.php', '', $uri);
$uri = ($uri === '' || $uri === null) ? '/' : $uri;

if (isset($_SESSION['role']) && $_SESSION['role'] === 'user') {
    $_SESSION['role'] = 'visitor';
}

$routes = [
    '/'                        => __DIR__ . '/pages/view-index.php',

    '/login'                   => __DIR__ . '/pages/Auth/login.php',
    '/signup'                  => __DIR__ . '/pages/Auth/signup.php',
    '/logout'                  => __DIR__ . '/pages/Auth/logout.php',

    '/country/all'             => __DIR__ . '/pages/countries/index.php',
    '/posts/all'               => __DIR__ . '/pages/posts/index.php',
    '/locations/all'           => __DIR__ . '/pages/locations/index.php',
    '/creator-request'   => __DIR__ . '/pages/creator-request.php',
    '/upgrade-to-creator'   => __DIR__ . '/pages/upgrade-to-creator.php',

    '/creator/'                => __DIR__ . '/pages/creator/creator-home.php',
    '/creator/create-post'     => __DIR__ . '/pages/posts/add.php',

    
    '/admin/'                  => __DIR__ . '/pages/admin/dashboard.php',
    '/admin/location-list'     => __DIR__ . '/pages/locations/index.php',
    '/admin/add-location'      => __DIR__ . '/pages/locations/add.php',
    '/admin/edit-location'      => __DIR__ . '/pages/locations/edit.php',
    '/admin/manage-accounts'   => __DIR__ . '/pages/admin/manage-accounts.php',
    '/admin/creator-request'   => __DIR__ . '/pages/admin/creator-requests.php',
    '/admin/moderate-posts'    => __DIR__ . '/pages/admin/moderate-posts.php',
    '/admin/analytics'         => __DIR__ . '/pages/admin/analytics.php',
];

function abort(int $code = 404): void
{
    http_response_code($code);
    $errorFile = __DIR__ . "/pages/errors/{$code}.php";
    if (file_exists($errorFile)) {
        require $errorFile;
    } else {
        echo "<h1>{$code}</h1>";
    }
    exit();
}

function resolve(string $uri, array $routes): array
{
    if (array_key_exists($uri, $routes)) {
        return ['page' => $routes[$uri], 'params' => []];
    }

    $patterns = [
        '#^/country/(\d+)$#' => __DIR__ . '/pages/countries/show.php',
        '#^/posts/(\d+)$#' => __DIR__ . '/pages/posts/show.php',
        '#^/locations/(\d+)$#' => __DIR__ . '/pages/locations/show.php',
        '#^/posts/(\d+)/edit$#' => __DIR__ . '/pages/posts/edit.php',
        '#^/admin/moderate-posts/(\d+)$#' => __DIR__ . '/pages/admin/moderate-posts-show.php',
    ];

    foreach ($patterns as $pattern => $page) {
        if (preg_match($pattern, $uri, $m)) {
            return ['page' => $page, 'params' => ['id' => $m[1]]];
        }
    }

    // if nothing matched > display 404 error message
    abort(404);
}

$route = resolve($uri, $routes);
$page = $route['page'];
$params = $route['params'];

if (!file_exists($page)) {
    abort(404);
}


$depth       = count(array_filter(explode('/', trim($uri, '/'))));
$base_prefix = str_repeat('../', $depth);  

$isAdminPage = str_starts_with($uri, '/admin/');
if ($isAdminPage) {
    requireRole(ROLE_ADMIN);
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>GulfGuide</title>

        <!-- Phosphor Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">

        <!-- Bootstrap 5 -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
              rel="stylesheet"
              integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
              crossorigin="anonymous">

        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"
                integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
        crossorigin="anonymous"></script>

        <!-- Sweetalert -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <link rel="stylesheet" href="assets/plugins/sweetalert2/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <!-- tinymce -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.1.0/tinymce.min.js" referrerpolicy="origin"></script>
        
         <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <!-- Custom CSS -->

    <!-- Custom CSS -->
<?php if ($isAdminPage): ?>
<link rel="stylesheet" href="<?= $base_prefix ?>assets/css/adminStyle.css">
    <?php endif; ?>

 <link rel="stylesheet" href="<?= $base_prefix ?>assets/css/style.css">
    <!-- Custom JS -->
    <script src="<?= $base_prefix ?>assets/js/main.js" defer></script>

</head>

<body>
    <?php if ($isAdminPage): ?>
        <?php if (file_exists(__DIR__ . '/partials/admin/sidebar.phtml')): ?>
            <?php require __DIR__ . '/partials/admin/sidebar.phtml'; ?>
        <?php endif; ?>
    <?php endif; ?>
    <body>
        <?php if ($isAdminPage): ?>
            <?php if (file_exists(__DIR__ . '/partials/admin/sidebar.phtml')): ?>
                <?php require __DIR__ . '/partials/admin/sidebar.phtml'; ?>
            <?php endif; ?>
            <?php if (file_exists(__DIR__ . '/partials/admin/topbar.phtml')): ?>
                <?php require __DIR__ . '/partials/admin/topbar.phtml'; ?>
            <?php endif; ?>
        <?php endif; ?>

        <main class="main">
            <?php require $page; ?>
        </main>

    <!-- Bootstrap JS bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
            crossorigin="anonymous"></script>
</body>
</html>