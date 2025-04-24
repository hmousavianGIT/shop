<?php
require_once 'vendor/autoload.php';
use MongoDB\Client;

try {
    $client = new Client("mongodb://localhost:27017");
    $db = $client->store;

    $productName = $_POST['productName'];
    $natures = $_POST['natures'] ?? [];
    $categories = $_POST['categories'] ?? [];
    $tags = json_decode($_POST['tags'], true);
    $tags = $tags ? array_map(function($tag) { return $tag['value']; }, $tags) : [];

    $description = $_POST['description'];
    $status = $_POST['status'];

    // پردازش سایزها
    $sizes = [];
    if (!empty($_POST['sizes'])) {
        foreach ($_POST['sizes'] as $size) {
            $sizes[] = ['type' => is_numeric($size) ? 'numeric' : 'standard', 'name' => $size];
        }
    }

    // پردازش تنوع‌ها
    $variants = [];
    if (!empty($_POST['variants'])) {
        foreach ($_POST['variants'] as $variant) {
            $colorId = $variant['color'] ?? '';
            $size = $variant['size'] ?? '';
            $quantity = (int)($variant['quantity'] ?? 0);
            $price = (int)($variant['price'] ?? 0);
            $discount = (int)($variant['discount'] ?? 0);

            if (!empty($colorId) && !empty($size)) {
                // اعتبارسنجی وجود رنگ
                $colorExists = $db->colors->findOne(['_id' => new MongoDB\BSON\ObjectId($colorId)]);
                if ($colorExists) {
                    $variants[] = [
                        'color' => new MongoDB\BSON\ObjectId($colorId),
                        'size' => $size,
                        'quantity' => $quantity,
                        'price' => $price,
                        'discount' => $discount
                    ];
                }
            }
        }
    }

    // پردازش ویژگی‌ها
    $attributes = [];
    if (!empty($_POST['attributes'])) {
        $attributesInput = json_decode($_POST['attributes'], true);
        foreach ($attributesInput as $attr) {
            if (!empty($attr['value'])) {
                $attributes[] = [
                    'value' => trim($attr['value'])
                ];
            }
        }
    }

    // اضافه کردن تگ‌های جدید
    foreach ($tags as $tag) {
        if (!empty($tag) && !$db->tags->findOne(['name' => $tag])) {
            $db->tags->insertOne(['name' => $tag]);
        }
    }

    // اضافه کردن سایزهای جدید
    foreach ($sizes as $size) {
        if (!empty($size['name']) && !$db->sizes->findOne(['name' => $size['name']])) {
            $db->sizes->insertOne(['name' => $size['name']]);
        }
    }

    // آپلود رسانه‌ها
    $media = [];
    $thumbnailIndex = isset($_POST['thumbnail_index']) ? (int)$_POST['thumbnail_index'] : 0;

    if (isset($_FILES['media']) && !empty($_FILES['media']['name'])) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileCount = is_array($_FILES['media']['name']) ? count($_FILES['media']['name']) : 1;

        for ($i = 0; $i < $fileCount; $i++) {
            $fileName = $_FILES['media']['name'][$i] ?? '';
            $fileError = $_FILES['media']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            $tmpName = $_FILES['media']['tmp_name'][$i] ?? '';

            if ($fileError !== UPLOAD_ERR_OK || empty($fileName)) {
                continue;
            }

            $fileType = $_FILES['media']['type'][$i];
            $uniqueFileName = time() . '_' . $i . '_' . basename($fileName);
            $filePath = $uploadDir . $uniqueFileName;

            if (move_uploaded_file($tmpName, $filePath)) {
                $type = strpos($fileType, 'video') !== false ? 'video' : 'image';
                $media[] = ['type' => $type, 'url' => $filePath];
            }
        }
    }

    if (empty($media)) {
        $media[] = ['type' => 'image', 'url' => 'placeholder.jpg'];
    }
    $thumbnail = $media[$thumbnailIndex]['url'] ?? $media[0]['url'];

    // ذخیره محصول در دیتابیس
    $product = [
        'productName' => $productName,
        'natures' => $natures,
        'categories' => $categories,
        'attributes' => $attributes,
        'tags' => $tags,
        'variants' => $variants,
        'description' => $description,
        'media' => $media,
        'thumbnail' => $thumbnail,
        'status' => $status,
        'createdAt' => new MongoDB\BSON\UTCDateTime(time() * 1000)
    ];
    $db->products->insertOne($product);

    header("Location: dashboard.php");
    exit;
} catch (Exception $e) {
    echo "خطا: " . $e->getMessage();
}
?>