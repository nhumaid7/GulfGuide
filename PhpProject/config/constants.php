<?php
$script = $_SERVER['SCRIPT_NAME'];   // /~u202304056/PhpProject/index.php
$base = dirname($script);          // /~u202304056/PhpProject

define('APP_BASE', $script);

// User roles
define('ROLE_VISITOR',  'user');
define('ROLE_CREATOR',  'creator');
define('ROLE_ADMIN',    'admin');

// Request status
define('REQUEST_PENDING',  'pending');
define('REQUEST_ACCEPTED', 'accepted');
define('REQUEST_DECLINED', 'declined');

// Reaction types
define('REACTION_LIKE',    'like');
define('REACTION_DISLIKE', 'dislike');

// Media types
define('MEDIA_IMAGE', 'image');
define('MEDIA_VIDEO', 'video');



// for redirection purposes only 
define('REDIRECT_ADMIN',   APP_BASE .'/admin/dashboard');
define('REDIRECT_CREATOR', APP_BASE .'/creator/home');
define('REDIRECT_VISITOR', APP_BASE .'/');
define('REDIRECT_LOGIN',   APP_BASE .'/login')
        

?>