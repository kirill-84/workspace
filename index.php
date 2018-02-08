<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Testing</title>
</head>
<body>
<?php  
$db_host = '';
$db_user = '';
$db_password = '';
$db_name = '';

include_once('db.class.php');
  
try {
$db = new DB($db_host, $db_user, $db_password, $db_name);

echo "<p>Table cart items</p>";

$books = $db->query("SELECT * FROM books");
  
foreach($books as $book){
  echo "<p><b>{$book['title']}</b> <a href='cart.php?action=add&id={$book['id']}'>add to cart</a></p>";
}
  
} catch (Exception $e) {
  echo $e->getMessage() . ':-(';
}
?>

<p><a href="cart.php">Show basket</a></p>

</body>
</html>
