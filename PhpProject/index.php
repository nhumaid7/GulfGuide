<?php
require_once __DIR__ . '/config/dbConn.php';
require_once __DIR__ . '/classes/user.php';

$uri = parse_url($_SERVER['REQUEST_URI'])['path'];
$routes = [
    '/' => __DIR__ . '/pages/view-index.php',
    '/country/all' => __DIR__ . '/pages/countries/index.php',
    '/posts/all' => __DIR__ . '/pages/posts/index.php',
    '/locations/all' => __DIR__ . '/pages/locations/index.php',

    '/login' => __DIR__ . '/pages/Auth/login.php',
    '/signup' => __DIR__ . '/pages/Auth/signup.php',

    '/creator/' => __DIR__ . '/pages/creator-home.php',
    '/creator/create-post' => __DIR__ . '/pages/posts/add.php',

    '/admin/' => __DIR__ . '/pages/admin/dashboard.php',
    '/admin/location-list' => __DIR__ . '/pages/locations/index.php',
    '/admin/add-location' => __DIR__ . '/pages/locations/add.php',
    '/admin/manage-accounts' => __DIR__ . '/pages/admin/manage-accounts.php',
    '/admin/creator-request' => __DIR__ . '/pages/admin/creator-requests.php',
    '/admin/moderate-posts' => __DIR__ . '/pages/admin/moderate-posts.php',
    '/admin/analytics' => __DIR__ . '/pages/admin/analytics.php',
];

function routeToController($uri, $routes)
{
    if (array_key_exists($uri, $routes)) {
       return [
            'page'=>$routes[$uri],
            'params'=>[]
        ];
    } else if (preg_match('#^/country/(\d+)$#', $uri, $matches)) {
        return ['page' => __DIR__ . '/pages/countries/show.php', 'params' => ['id' => $matches[1]]];
    } else if (preg_match('#^/posts/(\d+)$#', $uri, $matches)) {
        return ['page' => __DIR__ . '/pages/posts/show.php', 'params' => ['id' => $matches[1]]];
    } else if (preg_match('#^/locations/(\d+)$#', $uri, $matches)) {
        return ['page' => __DIR__ . '/pages/locations/show.php', 'params' => ['id' => $matches[1]]];
    } else if (preg_match('#^/posts/(\d+)/edit$#', $uri, $matches)) {
        return ['page' => __DIR__ . '/pages/posts/edit.php', 'params' => ['id' => $matches[1]]];
    } else {
        abort();
    }
}

function abort($code = 404)
{
    http_response_code($code);
    require __DIR__ . "/pages/errors/{$code}.php";
    die();
}

$route = routeToController($uri,$routes);
$page = $route['page'];
$params = $route['params'];


$path_parts = explode('/', trim($uri, '/'));
$depth = count(array_filter($path_parts));
$base_prefix = str_repeat('../', $depth);

$currentUser = $_SESSION['user'] ?? new User('test', 'test@example.com', '123456', 'admin');
?>
<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Project/PHP/PHPProject.php to edit this template
-->
<html>

<head>
    <meta charset="UTF-8">
    <title>GulfGuide</title>
    <!-- phosphor icons -->
    <link rel="stylesheet" type="text/css"
        href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css" />

    <!-- bootstrap CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <!-- JQuery -->
    <script src="https://code.jquery.com/jquery-4.0.0.js"
        integrity="sha256-9fsHeVnKBvqh3FB2HYu7g2xseAZ5MlN6Kz/qnkASV8U=" crossorigin="anonymous"></script>

    <!-- custom CSS -->
    <?php if (str_starts_with($uri, '/admin/')) : ?>
        <link rel="stylesheet" type="text/css" href="<?php echo $base_prefix; ?>assets/css/adminStyle.css" />
    <?php endif; ?>
    <link rel="stylesheet" type="text/css" href="<?php echo $base_prefix; ?>assets/css/style.css" />

    <!-- custom JS -->
    <script src="<?php echo $base_prefix; ?>assets/js/main.js"></script>
</head>

<body>
    <?php if (str_starts_with($uri, '/admin/')) : ?>
        <?php require __DIR__ . '/partials/admin/sidebar.phtml'; ?>
        <?php require __DIR__ . '/partials/admin/topbar.phtml'; ?>
    <?php endif; ?>
    <main class="main">
        <?php require $page; ?>
    </main>
</body>

</html>