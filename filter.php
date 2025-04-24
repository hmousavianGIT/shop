<?php
require_once 'vendor/autoload.php';
use MongoDB\Client;

$client = new Client("mongodb://localhost:27017");
$db = $client->store;
$colors = $db->colors->find();
$sizes = $db->sizes->find();
$categories = $db->categories->find();
$natures = $db->natures->find();
?>

<div class="flex-auto p-4">
    <form action="userfilter.php" method="post">
        <!-- فیلتر رنگ -->
        <div class="accordion mb-4 border rounded-lg overflow-hidden">
            <button type="button" class="accordion-button flex justify-between items-center w-full p-4 bg-gray-100 hover:bg-gray-200 text-right">
                <span class="font-semibold">رنگ‌ها</span>
                <i class="fas fa-chevron-left text-sm"></i>
            </button>
            <div class="accordion-content bg-white p-4 border-t">
                <?php foreach ($colors as $color): ?>
                <label class="flex items-center mb-2 cursor-pointer">
                    <input type="checkbox" name="colors[]" value="<?= $color['_id'] ?>" class="ml-2 h-4 w-4 text-blue-600 rounded">
                    <span><?= htmlspecialchars($color['name']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- فیلتر سایز -->
        <div class="accordion mb-4 border rounded-lg overflow-hidden">
            <button type="button" class="accordion-button flex justify-between items-center w-full p-4 bg-gray-100 hover:bg-gray-200 text-right">
                <span class="font-semibold">سایزها</span>
                <i class="fas fa-chevron-left text-sm"></i>
            </button>
            <div class="accordion-content bg-white p-4 border-t">
                <?php foreach ($sizes as $size): ?>
                <label class="flex items-center mb-2 cursor-pointer">
                    <input type="checkbox" name="sizes[]" value="<?= $size['_id'] ?>" class="ml-2 h-4 w-4 text-blue-600 rounded">
                    <span><?= htmlspecialchars($size['name']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- فیلتر دسته‌بندی -->
        <div class="accordion mb-4 border rounded-lg overflow-hidden">
            <button type="button" class="accordion-button flex justify-between items-center w-full p-4 bg-gray-100 hover:bg-gray-200 text-right">
                <span class="font-semibold">دسته‌بندی‌ها</span>
                <i class="fas fa-chevron-left text-sm"></i>
            </button>
            <div class="accordion-content bg-white p-4 border-t">
                <?php foreach ($categories as $category): ?>
                <label class="flex items-center mb-2 cursor-pointer">
                    <input type="checkbox" name="categories[]" value="<?= htmlspecialchars($category['name']) ?>" class="ml-2 h-4 w-4 text-blue-600 rounded">
                    <span><?= htmlspecialchars($category['name']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- فیلتر ماهیت -->
        <div class="accordion mb-4 border rounded-lg overflow-hidden">
            <button type="button" class="accordion-button flex justify-between items-center w-full p-4 bg-gray-100 hover:bg-gray-200 text-right">
                <span class="font-semibold">جنسیت</span>
                <i class="fas fa-chevron-left text-sm"></i>
            </button>
            <div class="accordion-content bg-white p-4 border-t">
                <?php foreach ($natures as $nature): ?>
                <label class="flex items-center mb-2 cursor-pointer">
                    <input type="checkbox" name="natures[]" value="<?= htmlspecialchars($nature['name']) ?>" class="ml-2 h-4 w-4 text-blue-600 rounded">
                    <span><?= htmlspecialchars($nature['name']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </form>
</div>