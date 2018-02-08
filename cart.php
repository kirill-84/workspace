<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Cart</title>
</head>
 <body>
<?php
require_once('cart.class.php');
require_once('cookie.class.php');
require_once('db.class.php');

$db_host = '';
$db_user = '';
$db_password = '';
$db_name = '';

$cart = new Cart();

$db = new DB($db_host, $db_user, $db_password, $db_name);

$action = isset($_GET['id']) ? $_GET['id'] : 'list';

if($action == 'add'){
  $id = $_GET['id'];
  $cart->addProducts($id);
  
  header('Location: index.php');
} elseif ($action == 'delete') {
  $id = $_GET['id'];
  $cart->deleteProduct($id);
  
  header('Location: cart.php');
} elseif ($action == 'clear'){
  $cart->clear();
  header('Location: cart.php');
} else {
  if($cart->isEmpty()){
    echo "Cart is empty";
  } else {
    $for_sql = $cart->getProducts(true);
    $sql = "SELECT * FROM books WHERE id IN ({$for_sql})";
    
    $books = $db->query($sql);
    
    foreach($books as $book){
      echo "<p><b>{$book['title']}</b> <a href='cart.php?action=delete&id={$book['id']}'>Delete</a></p>";
    }
  }
}
?>

<p><a href="cart.php?action=clear">Clear basket</a></p>
</body>
</html>
