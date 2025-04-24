<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت محصولات</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        @media (max-width: 640px) { .container { max-width: 100%; } }
        @media (min-width: 641px) { .container { max-width: 1200px; } }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 8px; border-bottom: 1px solid #ddd; }
        .table th { background: #f4f4f4; text-align: right; }
        .thumbnail { width: 100px; height: 100px; object-fit: cover; }
    </style>
</head>
<body class="bg-gray-100 p-4">
    <div class="container mx-auto">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">مدیریت محصولات</h1>

        <nav class="mb-6">
            <a href="dashboard.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mr-2">برگشت به داشبورد</a>
            <a href="shop.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mr-2">صفحه اصلی</a>
            <a href="manage_items.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mr-2">مدیریت تگ‌ها و کتگوری‌ها</a>
            <form action="addons/clear_products.php" method="POST" class="inline">
                <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600" onclick="return confirm('آیا مطمئن هستید که می‌خواهید همه محصولات را پاک کنید؟')">پاک کردن دیتابیس</button>
            </form>
        </nav>

        <div class="mb-4">
            <form method="GET" class="flex gap-4 flex-wrap">
                <div>
                    <label class="block font-semibold mb-1">جستجو بر اساس نام:</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="p-2 border rounded">
                </div>
                <div>
                    <label class="block font-semibold mb-1">فیلتر کتگوری:</label>
                    <select name="category" class="p-2 border rounded">
                        <option value="">همه کتگوری‌ها</option>
                        <?php
                        require_once 'vendor/autoload.php';
                        use MongoDB\Client;
                        try {
                            $client = new Client("mongodb://localhost:27017");
                            $db = $client->store;
                            $categories = $db->categories->find();
                            foreach ($categories as $cat) {
                                $selected = ($_GET['category'] ?? '') == $cat['name'] ? 'selected' : '';
                                echo "<option value='{$cat['name']}' $selected>{$cat['name']}</option>";
                            }
                        } catch (Exception $e) {
                            echo "<option value=''>خطا در بارگذاری کتگوری‌ها</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold mb-1">فیلتر وضعیت:</label>
                    <select name="status" class="p-2 border rounded">
                        <option value="">همه وضعیت‌ها</option>
                        <option value="موجود" <?php echo ($_GET['status'] ?? '') == 'موجود' ? 'selected' : ''; ?>>موجود</option>
                        <option value="ناموجود" <?php echo ($_GET['status'] ?? '') == 'ناموجود' ? 'selected' : ''; ?>>ناموجود</option>
                        <option value="در حال تولید" <?php echo ($_GET['status'] ?? '') == 'در حال تولید' ? 'selected' : ''; ?>>در حال تولید</option>
                    </select>
                </div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">فیلتر</button>
            </form>
        </div>

        <?php
        $query = [];
        if (!empty($_GET['search'])) {
            $query['productName'] = ['$regex' => $_GET['search'], '$options' => 'i'];
        }
        if (!empty($_GET['category'])) {
            $query['categories'] = $_GET['category'];
        }
        if (!empty($_GET['status'])) {
            $query['status'] = $_GET['status'];
        }
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $skip = ($page - 1) * $limit;
        $products = $db->products->find($query, ['skip' => $skip, 'limit' => $limit]);
        $totalProducts = $db->products->count($query);
        $totalPages = ceil($totalProducts / $limit);
        ?>

        <p class="mb-2 text-gray-600">تعداد محصولات: <?php echo $totalProducts; ?></p>
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>نام محصول</th>
                        <th>کتگوری‌ها</th>
                        <th>وضعیت</th>
                        <th>تاریخ ثبت</th>
                        <th>تامب‌نیل</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($totalProducts == 0) {
                        echo "<tr><td colspan='6' class='text-center py-4'>محصولی یافت نشد!</td></tr>";
                    } else {
                        foreach ($products as $product) {
                            // مدیریت فیلد categories
                            $categories = isset($product['categories']) ? $product['categories'] : [];
                            
                            // اگر categories یک شیء Traversable است (مثل MongoDB\Model\BSONArray)
                            if (is_object($categories) && $categories instanceof Traversable) {
                                $categoriesArray = iterator_to_array($categories);
                            } 
                            // اگر categories یک آرایه معمولی است
                            elseif (is_array($categories)) {
                                $categoriesArray = $categories;
                            }
                            // اگر categories وجود ندارد یا null است
                            else {
                                $categoriesArray = ['بدون دسته‌بندی'];
                            }
                        
                            // مدیریت thumbnail
                            $thumbnail = 'placeholder.jpg';
                            if (isset($product['thumbnail'])) {
                                $thumbnail = $product['thumbnail'];
                            } elseif (!empty($product['media']) && isset($product['media'][0]['url'])) {
                                $thumbnail = $product['media'][0]['url'];
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['productName'] ?? 'بدون نام'); ?></td>
                                <td><?php echo implode(', ', $categoriesArray); ?></td>
                                <td><?php echo htmlspecialchars($product['status'] ?? 'نامشخص'); ?></td>
                                <td>
                                    <?php 
                                    if (isset($product['createdAt']) && $product['createdAt'] instanceof MongoDB\BSON\UTCDateTime) {
                                        echo $product['createdAt']->toDateTime()->format('Y-m-d H:i:s');
                                    } else {
                                        echo 'نامشخص';
                                    }
                                    ?>
                                </td>
                                <td><img src="<?php echo htmlspecialchars($thumbnail); ?>" class="thumbnail" loading="lazy" alt="تصویر محصول"></td>
                                <td>
                                    <a href="edit_product.php?id=<?php echo $product['_id']; ?>" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600">ویرایش</a>
                                    <a href="delete_product.php?id=<?php echo $product['_id']; ?>" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex justify-center gap-2">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($_GET['search'] ?? ''); ?>&category=<?php echo urlencode($_GET['category'] ?? ''); ?>&status=<?php echo urlencode($_GET['status'] ?? ''); ?>" class="px-3 py-1 rounded <?php echo $i == $page ? 'bg-blue-500 text-white' : 'bg-gray-200'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </div>
</body>
</html>