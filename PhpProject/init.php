<?php
session_start();

require_once __DIR__ . "/config/dbConn.php";
require_once __DIR__ . "/config/constants.php";
require_once __DIR__ . "/config/helpers.php";

require_once __DIR__ . "/classes/User.php";
require_once __DIR__ . "/classes/Post.php";

define("APP_NAME", "GulfGuide");
?>