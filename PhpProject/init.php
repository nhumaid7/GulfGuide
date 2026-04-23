<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/config/dbConn.php";
require_once __DIR__ . "/config/constants.php";
require_once __DIR__ . "/config/helpers.php";

require_once __DIR__ . "/classes/user.php";
require_once __DIR__ . "/classes/post.php";

define("APP_NAME", "GulfGuide");
?>