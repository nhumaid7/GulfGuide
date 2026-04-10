<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'u202304056');
define('DB_PASSWORD', "asdASD123!");
define('DB_NAME', 'db202304056');

function getConnection(){
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
return $link ;
}
?>