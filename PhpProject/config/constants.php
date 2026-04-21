<?php
// User roles
define('ROLE_VISITOR',  'visitor');
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
define('REDIRECT_ADMIN',   'pages/admin/dashboard.php');
define('REDIRECT_CREATOR', 'pages/creator/dashboard.php');
define('REDIRECT_VISITOR', 'pages/home.php');
define('REDIRECT_LOGIN',   'login.php')
?>