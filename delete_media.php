<?php
require_once 'vendor/autoload.php';
use MongoDB\Client;

header('Content-Type: application/json');

try {
    $client = new Client("mongodb://localhost:27017");
    $db = $client->store;

    $data = json_decode(file_get_contents('php://input'), true);
    $productId = new MongoDB\BSON\ObjectId($data['productId']);
    $mediaIndex = (int)$data['mediaIndex'];

    // دریافت محصول
    $product = $db->products->findOne(['_id' => $productId]);
    if (!$product) {
        throw new Exception("محصول یافت نشد!");
    }

    // تبدیل رسانه‌ها به آرایه
    $media = iterator_to_array($product['media']);

    // حذف رسانه با index مشخص
    if (isset($media[$mediaIndex])) {
        unset($media[$mediaIndex]);
        $media = array_values($media); // بازسازی آرایه
    }

    // به‌روزرسانی محصول در پایگاه داده
    $db->products->updateOne(
        ['_id' => $productId],
        ['$set' => ['media' => $media]]
    );

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}