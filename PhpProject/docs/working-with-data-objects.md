# a description of all functions in the classes

> Go to classes/[className].php


## Before Working
include the class file at the top of any page that needs them:

```php
require_once 'classes/User.php';
```

## fromArray()
`fromArray()`: converts a database row directly into an object, which you'll use constantly after a `fetchAll()`:

```php
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$post = Post::fromArray($row);
echo $post->getTitle();
```

## toArray() 
`toArray()`: converts an object back to an array, useful when inserting or updating:

```php
$post = new Post($userId, $countryId, "My Trip", "Content...", "thumb.jpg");
$data = $post->toArray(); // use $data in your INSERT query
```


## Helper methods
each class has role/state checks so you avoid hardcoding strings everywhere

```php
if ($user->isAdmin()) { ... }
if ($post->isDraft()) { ... }
if ($request->isPending()) { ... }
$request->accept(); // automatically sets reviewed_at too
```