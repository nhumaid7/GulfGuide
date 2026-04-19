<?php
require_once "Post.php";

// DB connection
// $conn = new mysqli("localhost", "u202304056", "asdASD123!", "your_db");

// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }

$conn=getConnection();

// collect data from JS and make it array
$data = [
    'user_id' => 1, // TEMP (until login system)
    'country_id' => (int)$_POST['country_id'],
    'title' => $_POST['title'],
    'content' => $_POST['content'],
    'thumbnail' => $_POST['thumbnail'],
    'status' => 'draft',
    'attraction_id' => !empty($_POST['attraction_id']) ? (int)$_POST['attraction_id'] : null
];

// create Post object
$post = Post::fromArray($data);

// prepare SQL
$stmt = $conn->prepare("
    INSERT INTO dbProj_post 
    (title, content, thumbnail, status, user_id, country_id, attraction_id)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

// get values from object
$title = $post->getTitle();
$content = $post->getContent();
$thumbnail = $post->getThumbnail();
$status = $post->getStatus();
$user_id = $post->getUserId();
$country_id = $post->getCountryId();
$attraction_id = $post->getAttractionId();

// bind params
$stmt->bind_param(
    "testttt",
    $title,
    $content,
    $thumbnail,
    $status,
    $user_id,
    $country_id,
    $attraction_id
);

// execute
if ($stmt->execute()) {
    echo "Post created successfully!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>