<?php 
$script = $_SERVER['SCRIPT_NAME'];   // /~u202304056/PhpProject/index.php
$base = dirname($script);          // /~u202304056/PhpProject
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace($base, '', $uri);
$uri = str_replace('/index.php', '', $uri);
$uri = ($uri === '' || $uri === null) ? '/' : $uri;
$depth = count(array_filter(explode('/', trim($uri, '/'))));
$base_prefix = str_repeat('../', $depth); 
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

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_prefix ?>assets/css/style.css">

    <!-- Custom JS -->
    <script src="<?php echo $base_prefix ?>/assets/js/main.js" defer></script>
    <script src="<?php echo $base_prefix ?>/assets/js/custom-datatable.js" defer></script>
    
</head>

<body>

    <main class="error-page">
        <div class="error-container">
            <div class="error">
                <p class="error-note">Oops! Access Denied</p>
                <p class="error-code"><span>4</span><span>0</span><span>3</span></p>
            </div>
            <p class="error-text">Access to this page is restricted. Please make sure you're logged in with the correct account or return to the homepage.</p>
            <a href="<?php echo APP_BASE; ?>/" class="btn btn-primary z-3 mt-4">Go to Home</a>
        </div>
    </main>

    <!-- Bootstrap JS bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YUe2LzesAfftltw+PEaao2tjU/QATaW/rOitAq67e0CT0Zi2VVRL0oC4+gAaeBKu"
        crossorigin="anonymous"></script>
</body>

</html>