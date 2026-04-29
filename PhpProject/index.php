<?php
// ─── Bootstrap ───────────────────────────────────────────────────────────────
// session_start() MUST come before anything touches $_SESSION
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load config & classes (paths match your actual folder structure)
require_once __DIR__ . '/config/dbConn.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/classes/user.php';
require_once __DIR__ . "/classes/creatorRequest.php";
require_once __DIR__ . "/classes/post.php";
require_once __DIR__ . "/classes/country.php";

// ─── Routing helpers ─────────────────────────────────────────────────────────
/**
 * Build APP_BASE once: every href = APP_BASE . '/some/path'
 * e.g.  /~u202304056/PhpProject/index.php/login
 */
$script = $_SERVER['SCRIPT_NAME'];   // /~u202304056/PhpProject/index.php
$base = dirname($script);          // /~u202304056/PhpProject

// Strip the base dir and "/index.php" from the request path → clean $uri
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace($base, '', $uri);
$uri = str_replace('/index.php', '', $uri);
$uri = ($uri === '' || $uri === null) ? '/' : $uri;

// Normalize role: DB may store 'user', app uses 'visitor'
if (isset($_SESSION['role']) && $_SESSION['role'] === 'user') {
    $_SESSION['role'] = 'visitor';
}

// ─── Route table ─────────────────────────────────────────────────────────────
$routes = [
    '/' => __DIR__ . '/pages/view-index.php',
    '/home' => __DIR__ . '/pages/home.php',
    '/login' => __DIR__ . '/pages/Auth/login.php',
    '/signup' => __DIR__ . '/pages/Auth/signup.php',
    '/logout' => __DIR__ . '/pages/Auth/logout.php',
    '/country/all' => __DIR__ . '/pages/countries/index.php',
    '/posts/all' => __DIR__ . '/pages/posts/index.php',
    '/locations/all' => __DIR__ . '/pages/locations/index.php',
    '/creator/home' => __DIR__ . '/pages/creator/creator-home.php',
    '/creator/create-post' => __DIR__ . '/pages/posts/add.php',
    '/admin/dashboard' => __DIR__ . '/pages/admin/dashboard.php',
    '/admin/location-list' => __DIR__ . '/pages/locations/index.php',
    '/admin/locations/add-location' => __DIR__ . '/pages/locations/add.php',
    '/admin/manage-accounts' => __DIR__ . '/pages/admin/manage-accounts.php',
    '/admin/creator-request' => __DIR__ . '/pages/admin/creator-requests.php',
    '/admin/delete-account' => __DIR__ . '/pages/admin/delete-user-account.php',
    '/admin/moderate-posts' => __DIR__ . '/pages/admin/moderate-posts.php',
    '/admin/analytics' => __DIR__ . '/pages/admin/analytics.php',
];

// ─── Router ──────────────────────────────────────────────────────────────────
function abort(int $code = 404): void {
    http_response_code($code);
    $errorFile = __DIR__ . "/pages/errors/{$code}.php";
    if (file_exists($errorFile)) {
        require $errorFile;
    } else {
        echo "<h1>{$code} -no file </h1>";
    }
    exit();
}

function resolve(string $uri, array $routes): array {

    // 1. Exact match
    if (array_key_exists($uri, $routes)) {
        return ['page' => $routes[$uri], 'params' => []];
    }

    // 2. Dynamic patterns
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

    // 3. Nothing matched
    abort(404);
}

$route = resolve($uri, $routes);
$page = $route['page'];
$params = $route['params'];

// Guard: make sure the resolved file actually exists
if (!file_exists($page)) {
    abort(404);
}

// ─── Layout helpers ──────────────────────────────────────────────────────────
// How many levels deep is this URI? Used for relative asset paths.
$depth = count(array_filter(explode('/', trim($uri, '/'))));
$base_prefix = str_repeat('../', $depth);   // e.g. '../../' for /admin/foo

$isAdminPage = str_starts_with($uri, '/admin/');

if ($isAdminPage && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    abort(403);
}

ob_start();
require $page;
$content = ob_get_clean();
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
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
              integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.8/css/responsive.bootstrap5.min.css">

        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

        <!-- DataTables -->
        <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css">
        <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>
        <link rel="stylesheet" href="https://cdn.datatables.net/rowreorder/1.5.1/css/rowReorder.dataTables.min.css">
        <script src="https://cdn.datatables.net/rowreorder/1.5.1/js/dataTables.rowReorder.js"></script>
        <script src="https://cdn.datatables.net/rowreorder/1.5.1/js/rowReorder.dataTables.js"></script>
        <script src="https://cdn.datatables.net/responsive/3.0.8/js/dataTables.responsive.js"></script>
        <script src="https://cdn.datatables.net/responsive/3.0.8/js/responsive.dataTables.js"></script>
        <link rel="stylesheet" href="https://cdn.datatables.net/rowreorder/1.5.1/css/rowReorder.dataTables.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.8/css/responsive.dataTables.css">

        <!-- Sweetalert -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <link rel="stylesheet" href="assets/plugins/sweetalert2/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <!-- Custom CSS -->
        <link rel="stylesheet" href="<?php echo $base_prefix ?>assets/css/style.css">
        <?php if ($isAdminPage): ?>
            <link rel="stylesheet" href="<?php echo $base_prefix ?>assets/css/adminStyle.css">
        <?php endif; ?>

        <!-- Custom JS -->
        <script src="<?php echo $base_prefix ?>/assets/js/main.js" defer></script>
        <script src="<?php echo $base_prefix ?>/assets/js/custom-datatable.js" defer></script>
    </head>

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
            <?= $content ?>
        </main>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
        <!-- Bootstrap JS bundle -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
                integrity="sha384-YUe2LzesAfftltw+PEaao2tjU/QATaW/rOitAq67e0CT0Zi2VVRL0oC4+gAaeBKu"
        crossorigin="anonymous"></script>
        <?php if (isset($swal_status)): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    Swal.fire({
                        title: "<?= ($swal_code == 'success') ? 'Great!' : 'Oops...'; ?>",
                        text: "<?= addslashes($swal_status); ?>",
                        icon: "<?= $swal_code; ?>",
                        confirmButtonText: "Done"
                    });
                });
            </script>
        <?php endif; ?>
    </body>

</html>