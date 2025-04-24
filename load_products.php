<?php
require_once 'vendor/autoload.php';
use MongoDB\Client;
$client = new Client("mongodb://localhost:27017");
$db = $client->store;

$page = (int)$_GET['page'];
$limit = (int)$_GET['limit'];
$skip = ($page - 1) * $limit;
$products = $db->products->find(['status' => 'موجود'], ['skip' => $skip, 'limit' => $limit]);
foreach ($products as $product) {
    $thumb = $product['media'][0]['url'] ?? 'placeholder.jpg';
    echo "<div class='product-item'><img src='$thumb' alt='{$product['productName']}'><p class='text-center py-2'>{$product['productName']}</p></div>";
}
?>