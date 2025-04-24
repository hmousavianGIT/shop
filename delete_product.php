<?php
require_once 'vendor/autoload.php';
use MongoDB\Client;

try {
    $client = new Client("mongodb://localhost:27017");
    $db = $client->store;

    $productId = new MongoDB\BSON\ObjectId($_GET['id']);
    $db->products->deleteOne(['_id' => $productId]);
    header("Location: manage_products.php");
    exit;
} catch (Exception $e) {
    echo "خطا: " . $e->getMessage();
}
?>