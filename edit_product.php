<?php
require_once 'vendor/autoload.php';
use MongoDB\Client;

try {
    $client = new Client("mongodb://localhost:27017");
    $db = $client->store;

    // بررسی وجود ID محصول
    if (!isset($_GET['id'])) {
        throw new Exception("شناسه محصول مشخص نشده است!");
    }

    $productId = new MongoDB\BSON\ObjectId($_GET['id']);
    $product = $db->products->findOne(['_id' => $productId]);

    if (!$product) {
        throw new Exception("محصول یافت نشد!");
    }

    // توابع کمکی
    function convertToArray($data) {
        if ($data instanceof Traversable) {
            return iterator_to_array($data);
        } elseif (is_array($data)) {
            return $data;
        }
        return [];
    }

    // تبدیل محصول به آرایه
    $productArray = convertToArray($product);
    
    // دریافت لیست‌های مرجع از دیتابیس با ID
    $allnatures = $db->natures->find([], ['projection' => ['name' => 1]])->toArray();
    $allCategories = $db->categories->find([], ['projection' => ['name' => 1]])->toArray();
    $allSizes = $db->sizes->find([], ['projection' => ['_id' => 1, 'name' => 1]])->toArray();
    $allColors = $db->colors->find([], ['projection' => ['_id' => 1, 'name' => 1]])->toArray();
    $allTags = $db->tags->find([], ['projection' => ['name' => 1]])->toArray();

    // ایجاد جداول تبدیل ID به نام
    $sizeIdToName = [];
    foreach ($allSizes as $size) {
        $sizeIdToName[(string)$size['_id']] = $size['name'];
    }

    $colorIdToName = [];
    foreach ($allColors as $color) {
        $colorIdToName[(string)$color['_id']] = $color['name'];
    }

    // تبدیل داده‌های محصول برای نمایش
    $natures = convertToArray($product['natures'] ?? []);
    $categories = convertToArray($product['categories'] ?? []);
    $tags = convertToArray($product['tags'] ?? []);
    
    // تبدیل variants (رنگ و سایز از ID به نام)
    $variants = [];
    if (!empty($product['variants'])) {
        foreach ($product['variants'] as $variant) {
            $variantArray = convertToArray($variant);
            $variants[] = [
                'color' => $colorIdToName[(string)$variantArray['color']] ?? $variantArray['color'],
                'size' => $sizeIdToName[(string)$variantArray['size']] ?? $variantArray['size'],
                'quantity' => $variantArray['quantity'] ?? 0,
                'price' => $variantArray['price'] ?? 0,
                'discount' => $variantArray['discount'] ?? 0
            ];
        }
    }

    // پردازش ویژگی‌ها
    $attributes = convertToArray($product['attributes'] ?? []);
    $attributesValues = array_column($attributes, 'value');
    $attributesString = implode(', ', $attributesValues);

    // پردازش مدیا
    $media = array_map('convertToArray', convertToArray($product['media'] ?? []));
    $thumbnail = $productArray['thumbnail'] ?? ($media[0]['url'] ?? '');

    // پردازش ارسال فرم (ذخیره تغییرات)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $updateData = [
            'productName' => $_POST['productName'],
            'natures' => array_keys(array_filter($_POST['natures'] ?? [])),
            'categories' => array_keys(array_filter($_POST['categories'] ?? [])),
            'tags' => array_filter(array_map('trim', explode(',', $_POST['tags']))),
            'description' => $_POST['description'],
            'status' => $_POST['status'],
            'media' => $media,
            'thumbnail' => $thumbnail,
            'updatedAt' => new MongoDB\BSON\UTCDateTime(time() * 1000),
            'attributes' => []
        ];

        // پردازش ویژگی‌ها
        if (!empty($_POST['attributes'])) {
            $attributesInput = json_decode($_POST['attributes'], true);
            foreach ($attributesInput as $attr) {
                if (!empty($attr['value'])) {
                    $updateData['attributes'][] = ['value' => trim($attr['value'])];
                }
            }
        }

        // پردازش تنوع‌ها (تبدیل نام به ID)
        // پردازش تنوع‌ها
$variantsToSave = [];
if (!empty($_POST['variants'])) {
    foreach ($_POST['variants'] as $variant) {
        // پردازش رنگ (تبدیل از فرمت JSON اگر لازم باشد)
        $colorInput = $variant['color'] ?? '';
        $colorName = $colorInput;
        
        // اگر رنگ به صورت JSON است (مثل [{"value":"سبز"}])
        if (strpos($colorInput, 'value') !== false) {
            $colorArray = json_decode($colorInput, true);
            $colorName = $colorArray[0]['value'] ?? $colorInput;
        }
        
        $sizeName = $variant['size'] ?? '';
        
        if (!empty($colorName) && !empty($sizeName)) {
            // یافتن یا ایجاد رنگ
            $color = $db->colors->findOne(['name' => $colorName]);
            if (!$color) {
                $colorId = $db->colors->insertOne(['name' => $colorName])->getInsertedId();
            } else {
                $colorId = $color['_id'];
            }
            
            // یافتن یا ایجاد سایز
            $size = $db->sizes->findOne(['name' => $sizeName]);
            if (!$size) {
                $sizeId = $db->sizes->insertOne(['name' => $sizeName])->getInsertedId();
            } else {
                $sizeId = $size['_id'];
            }
            
            $variantsToSave[] = [
                'color' => $colorId,
                'size' => $sizeId,
                'quantity' => (int)($variant['quantity'] ?? 0),
                'price' => (int)($variant['price'] ?? 0),
                'discount' => (int)($variant['discount'] ?? 0)
            ];
        }
    }
}
$updateData['variants'] = $variantsToSave;

        // پردازش حذف مدیا
        $deleteMedia = isset($_POST['delete_media']) ? array_map('intval', $_POST['delete_media']) : [];
        if (!empty($deleteMedia)) {
            $media = array_values(array_filter($media, function($_, $index) use ($deleteMedia) {
                return !in_array($index, $deleteMedia);
            }, ARRAY_FILTER_USE_BOTH));
            
            // به روزرسانی thumbnail در صورت نیاز
            $thumbnailUrls = array_column($media, 'url');
            if (!in_array($thumbnail, $thumbnailUrls)) {
                $thumbnail = $thumbnailUrls[0] ?? '';
            }
        }

        // پردازش آپلود مدیاهای جدید
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (!empty($_FILES['media']['name'][0])) {
            foreach ($_FILES['media']['name'] as $index => $name) {
                if ($_FILES['media']['error'][$index] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $tmpName = $_FILES['media']['tmp_name'][$index];
                $fileName = time() . '_' . $index . '_' . basename($name);
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($tmpName, $filePath)) {
                    $media[] = ['type' => 'image', 'url' => $filePath];
                }
            }
        }

        // پردازش thumbnail
        if (isset($_POST['thumbnail'])) {
            $selectedIndex = (int)$_POST['thumbnail'];
            if (isset($media[$selectedIndex])) {
                $thumbnail = $media[$selectedIndex]['url'];
            }
        } elseif (isset($_POST['new_thumbnail'])) {
            $newThumbIndex = str_replace('new_', '', $_POST['new_thumbnail']);
            if (isset($_FILES['media']['name'][$newThumbIndex])) {
                $fileName = time() . '_' . $newThumbIndex . '_' . basename($_FILES['media']['name'][$newThumbIndex]);
                $thumbnail = $uploadDir . $fileName;
            }
        }

        if (empty($thumbnail) && !empty($media)) {
            $thumbnail = $media[0]['url'];
        }

        $updateData['media'] = $media;
        $updateData['thumbnail'] = $thumbnail;

        // ذخیره تغییرات در دیتابیس
        $result = $db->products->updateOne(['_id' => $productId], ['$set' => $updateData]);
        if ($result->getModifiedCount() > 0) {
            header("Location: manage_products.php");
            exit;
        } else {
            throw new Exception("هیچ تغییری اعمال نشد یا خطایی رخ داد.");
        }
    }
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px;'>خطا: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
?>


<!DOCTYPE html>
<html dir="rtl" lang="fa">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ویرایش محصول</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.min.js"></script>
    <style>
    .thumbnail-preview {
        width: 100px;
        height: 100px;
        object-fit: cover;
    }

    .media-item {
        position: relative;
        margin: 5px;
        transition: opacity 0.3s;
    }

    .media-delete {
        position: absolute;
        top: 2px;
        right: 2px;
        background: red;
        color: white;
        border: none;
        padding: 2px 5px;
        cursor: pointer;
    }

    .variant-row {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
    }

    .variant-input {
        flex: 1;
        padding: 5px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .preview-item {
        position: relative;
        display: inline-block;
        margin: 5px;
    }

    .preview-remove {
        position: absolute;
        top: 2px;
        right: 2px;
        background: red;
        color: white;
        border: none;
        padding: 2px 5px;
        cursor: pointer;
    }

    .hidden {
        display: none;
    }


    .tagify {
        @apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus: outline-none focus:ring-indigo-500 focus:border-indigo-500;
    }

    .tagify__tag {
        @apply bg-indigo-100 text-indigo-800 rounded-md;
    }

    .tagify__tag__removeBtn {
        @apply text-indigo-500 hover: text-indigo-700;
    }

    .tagify__input {
        @apply text-gray-700;
    }

    .upload-container {
        border: 2px dashed #ccc;
        padding: 20px;
        text-align: center;
        margin-bottom: 20px;
    }

    .preview-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin: 20px 0;
    }

    .preview-item {
        width: 120px;
        height: 120px;
        position: relative;
        border: 1px solid #ddd;
        border-radius: 4px;
        overflow: hidden;
    }

    .preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .preview-item .file-info {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 5px;
        font-size: 12px;
    }

    .remove-btn {
        position: absolute;
        top: 0;
        right: 0;
        background: red;
        color: white;
        border: none;
        border-radius: 0 0 0 4px;
        cursor: pointer;
        padding: 2px 5px;
        font-size: 12px;
    }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">ویرایش محصول:
            <?php echo htmlspecialchars($productArray['productName']); ?></h1>

        <form method="POST" action="" enctype="multipart/form-data" class="space-y-6" id="productForm">
            <div>
                <label class="block font-semibold">نام محصول:</label>
                <input type="text" name="productName"
                    value="<?php echo htmlspecialchars($productArray['productName']); ?>" required
                    class="w-full p-2 border rounded">
            </div>
            <div>
                <label class="block font-semibold">ماهیت محصول:</label>
                <div class="checkbox-group">
                    <?php foreach ($allnatures as $cat): ?>
                    <label class="flex items-center">
                        <input type="checkbox" name="natures[<?php echo htmlspecialchars($cat['name'] ?? ''); ?>]"
                            value="<?php echo htmlspecialchars($cat['name'] ?? ''); ?>"
                            <?php echo in_array($cat['name'] ?? '', $natures) ? 'checked' : ''; ?>>
                        <span class="ml-2"><?php echo htmlspecialchars($cat['name'] ?? ''); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <label class="block font-semibold">کتگوری‌ها:</label>
                <div class="checkbox-group">
                    <?php foreach ($allCategories as $cat): ?>
                    <label class="flex items-center">
                        <input type="checkbox" name="categories[<?php echo htmlspecialchars($cat['name'] ?? ''); ?>]"
                            value="<?php echo htmlspecialchars($cat['name'] ?? ''); ?>"
                            <?php echo in_array($cat['name'] ?? '', $categories) ? 'checked' : ''; ?>>
                        <span class="ml-2"><?php echo htmlspecialchars($cat['name'] ?? ''); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <label class="block font-semibold">توضیحات:</label>
                <textarea name="description"
                    class="w-full p-2 border rounded"><?php echo htmlspecialchars($productArray['description'] ?? ''); ?></textarea>
            </div>
            <div>
                <label class="block font-semibold">وضعیت:</label>
                <select name="status" required class="w-full p-2 border rounded">
                    <option value="موجود"
                        <?php echo ($productArray['status'] ?? 'موجود') === 'موجود' ? 'selected' : ''; ?>>موجود</option>
                    <option value="ناموجود"
                        <?php echo ($productArray['status'] ?? 'موجود') === 'ناموجود' ? 'selected' : ''; ?>>ناموجود
                    </option>
                    <option value="در حال تولید"
                        <?php echo ($productArray['status'] ?? 'موجود') === 'در حال تولید' ? 'selected' : ''; ?>>در حال
                        تولید</option>
                </select>
            </div>
            <div>
                <label class="block font-semibold">تنوع‌ها:</label>
                <div id="variantsContainer">
                    <?php foreach ($variants as $index => $variant): ?>
                    <div class="variant-row" data-index="<?php echo $index; ?>">
                        <input type="text" name="variants[<?php echo $index; ?>][color]"
                            value="<?php echo htmlspecialchars($variant['color'] ?? ''); ?>" placeholder="رنگ"
                            class="variant-input" id="colorInput<?php echo $index; ?>">
                        <select name="variants[<?php echo $index; ?>][size]" class="variant-input">
                            <?php if (!empty($allSizes)) {
                                    foreach ($allSizes as $size):
                                        $sizeName = $size['name'] ?? '';
                                        $variantSize = $variant['size'] ?? '';
                                ?>
                            <option value="<?php echo htmlspecialchars($sizeName); ?>"
                                <?php echo $variantSize === $sizeName ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sizeName); ?>
                            </option>
                            <?php endforeach;
                                } else {
                                    echo '<option value="">هیچ سایزی یافت نشد</option>';
                                } ?>
                        </select>
                        <input type="number" name="variants[<?php echo $index; ?>][quantity]"
                            value="<?php echo htmlspecialchars($variant['quantity'] ?? ''); ?>" placeholder="تعداد"
                            class="variant-input">
                        <input type="number" name="variants[<?php echo $index; ?>][price]"
                            value="<?php echo htmlspecialchars($variant['price'] ?? ''); ?>" placeholder="قیمت"
                            class="variant-input">
                        <input type="number" name="variants[<?php echo $index; ?>][discount]"
                            value="<?php echo htmlspecialchars($variant['discount'] ?? ''); ?>" placeholder="تخفیف"
                            class="variant-input">
                        <button type="button" class="bg-red-500 text-white px-2 py-1 rounded"
                            onclick="removeVariant(this)">حذف</button>
                    </div>
                    <script>
                    new Tagify(document.getElementById('colorInput<?php echo $index; ?>'), {
                        whitelist: <?php echo json_encode(array_column($allColors, 'name'), JSON_UNESCAPED_UNICODE); ?>,
                        dropdown: {
                            enabled: 1,
                            maxItems: 10
                        }
                    });
                    </script>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="addVariant" class="bg-green-600 text-white px-4 py-2 rounded mt-2">تنوع
                    جدید</button>
            </div>
            <div>
                <label class="block font-semibold">تگ‌ها:</label>
                <input type="text" name="tags" id="tagInput"
                    value="<?php echo htmlspecialchars(implode(',', array_filter($tags, 'is_string'))); ?>"
                    class="w-full p-2 border rounded">
                <script>
                new Tagify(document.getElementById('tagInput'), {
                    whitelist: <?php echo json_encode(array_column($allTags, 'name'), JSON_UNESCAPED_UNICODE); ?>,
                    dropdown: {
                        enabled: 1,
                        maxItems: 10
                    },
                    originalInputValueFormat: values => values.map(item => item.value).join(','),
                    onChange: (e) => {
                        console.log('تگ‌ها تغییر کردند:', e.detail.value);
                    }
                });
                </script>
            </div>


            <!-- بعد از بخش تگ‌ها و قبل از بخش مدیا -->
            <div class="mt-6">
                <label class="block font-semibold mb-2">ویژگی‌های محصول:</label>
                <input name="productAttributes" id="attributesInput" class="w-full p-2 border rounded"
                    value="<?php echo htmlspecialchars($attributesString); ?>">
            </div>


            <!-- بخش جدید مدیا -->
            <!-- بخش مدیا (عکس‌ها) -->
            <div>
                <label class="block font-semibold">عکس‌های فعلی:</label>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($media as $index => $mediaItem): ?>
                    <div class="media-item">
                        <img src="<?php echo htmlspecialchars($mediaItem['url']); ?>" class="thumbnail-preview"
                            alt="عکس محصول">
                        <div class="flex items-center gap-2 mt-1">
                            <button type="button" class="media-delete"
                                onclick="this.nextElementSibling.nextElementSibling.checked = true; this.parentElement.parentElement.style.opacity = '0.5';">حذف</button>
                            <label class="flex items-center gap-1 cursor-pointer">
                                <input type="radio" name="thumbnail" value="<?php echo $index; ?>"
                                    <?php echo ($mediaItem['url'] === $thumbnail) ? 'checked' : ''; ?>
                                    onchange="uncheckNewThumbnails()">
                                کاور
                            </label>
                            <input type="checkbox" name="delete_media[]" value="<?php echo $index; ?>" class="hidden">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <label class="block font-semibold">اضافه کردن عکس جدید:</label>
                <div class="upload-container">
                    <input type="file" id="fileInput" name="media[]" accept="image/*" multiple style="display: none;">
                    <button type="button" onclick="document.getElementById('fileInput').click()"
                        class="bg-blue-500 text-white px-4 py-2 rounded">انتخاب عکس‌ها</button>
                    <p class="mt-2">یا فایل‌ها را اینجا رها کنید (چند فایل قابل انتخاب است)</p>

                    <div id="previewContainer" class="preview-container"></div>
                </div>
            </div>

            <!-- پایان بخش جدید مدیا -->
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">ذخیره
                تغییرات</button>
            <a href="manage_products.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">لغو</a>
        </form>
    </div>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. مدیریت ویژگی‌های محصول (Attributes)
    const attributesInput = document.getElementById('attributesInput');
    const tagifyAttributes = new Tagify(attributesInput, {
        delimiters: ',|\n',
        pattern: null,
        dropdown: { enabled: 0 },
        originalInputValueFormat: values => values.map(item => item.value).join(',')
    });

    // اضافه کردن ویژگی‌ها به فرم قبل از ارسال
    document.getElementById('productForm').addEventListener('submit', function(e) {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'attributes';
        hiddenInput.value = JSON.stringify(tagifyAttributes.value);
        this.appendChild(hiddenInput);
    });

    // 2. مدیریت تنوع‌های محصول (Variants)
    document.getElementById('addVariant').addEventListener('click', addNewVariant);
    
    // اضافه کردن تنوع جدید
    function addNewVariant() {
        const index = document.querySelectorAll('.variant-row').length;
        const container = document.getElementById('variantsContainer');
        const row = document.createElement('div');
        row.className = 'variant-row';
        row.dataset.index = index;
        row.innerHTML = `
            <input type="text" name="variants[${index}][color]" placeholder="رنگ" 
                   class="variant-input" id="colorInput${index}">
            <select name="variants[${index}][size]" class="variant-input">
                ${generateSizeOptions()}
            </select>
            <input type="number" name="variants[${index}][quantity]" placeholder="تعداد" class="variant-input">
            <input type="number" name="variants[${index}][price]" placeholder="قیمت" class="variant-input">
            <input type="number" name="variants[${index}][discount]" placeholder="تخفیف" class="variant-input">
            <button type="button" class="bg-red-500 text-white px-2 py-1 rounded" 
                    onclick="removeVariant(this)">حذف</button>
        `;
        container.appendChild(row);
        
        // راه‌اندازی Tagify برای رنگ‌ها
        new Tagify(document.getElementById(`colorInput${index}`), {
            whitelist: <?php echo json_encode(array_column($allColors, 'name'), JSON_UNESCAPED_UNICODE); ?>,
            dropdown: { enabled: 1, maxItems: 3 },
            maxTags: 1,
            originalInputValueFormat: values => values[0]?.value || values // تغییر کلیدی برای حل مشکل JSON
        });
    }

    // تولید گزینه‌های سایز
    function generateSizeOptions() {
        const sizes = <?php echo json_encode(array_column($allSizes, 'name'), JSON_UNESCAPED_UNICODE); ?>;
        if (sizes.length === 0) return '<option value="">هیچ سایزی یافت نشد</option>';
        
        return sizes.map(size => 
            `<option value="${size}">${size}</option>`
        ).join('');
    }

    // 3. مدیریت آپلود و پیش‌نمایش تصاویر
    let selectedFiles = [];
    const fileInput = document.getElementById('fileInput');
    const uploadContainer = document.querySelector('.upload-container');

    fileInput.addEventListener('change', handleFileSelect);
    
    // Drag and Drop
    uploadContainer.addEventListener('dragover', e => {
        e.preventDefault();
        uploadContainer.style.borderColor = '#4CAF50';
    });
    
    uploadContainer.addEventListener('dragleave', () => {
        uploadContainer.style.borderColor = '#ccc';
    });
    
    uploadContainer.addEventListener('drop', e => {
        e.preventDefault();
        uploadContainer.style.borderColor = '#ccc';
        handleFileSelect({ target: { files: e.dataTransfer.files } });
    });

    function handleFileSelect(event) {
        const files = event.target.files;
        for (let i = 0; i < files.length; i++) {
            if (!selectedFiles.some(f => 
                f.name === files[i].name && 
                f.size === files[i].size &&
                f.lastModified === files[i].lastModified
            )) {
                selectedFiles.push(files[i]);
            }
        }
        updateFileInput();
        updatePreview();
    }

    function updateFileInput() {
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
    }

    function updatePreview() {
        const previewContainer = document.getElementById('previewContainer');
        previewContainer.innerHTML = '';

        selectedFiles.forEach((file, i) => {
            if (!file.type.startsWith('image/')) return;

            const previewItem = document.createElement('div');
            previewItem.className = 'preview-item';

            const img = document.createElement('img');
            const reader = new FileReader();

            reader.onload = function(e) {
                img.src = e.target.result;

                const fileInfo = document.createElement('div');
                fileInfo.className = 'file-info';
                fileInfo.textContent = `${file.name} (${formatFileSize(file.size)})`;

                const removeBtn = document.createElement('button');
                removeBtn.className = 'remove-btn';
                removeBtn.innerHTML = '×';
                removeBtn.onclick = (e) => {
                    e.preventDefault();
                    removeFile(i);
                };

                const thumbnailRadio = document.createElement('input');
                thumbnailRadio.type = 'radio';
                thumbnailRadio.name = 'new_thumbnail';
                thumbnailRadio.value = `new_${i}`;
                thumbnailRadio.id = `new_thumb_${i}`;
                thumbnailRadio.onchange = uncheckExistingThumbnails;

                const thumbnailLabel = document.createElement('label');
                thumbnailLabel.htmlFor = `new_thumb_${i}`;
                thumbnailLabel.textContent = ' کاور';
                thumbnailLabel.style.color = 'white';
                thumbnailLabel.style.marginRight = '5px';

                previewItem.append(img, fileInfo, removeBtn, thumbnailRadio, thumbnailLabel);
                previewContainer.appendChild(previewItem);
            };

            reader.readAsDataURL(file);
        });
    }

    function removeFile(index) {
        selectedFiles.splice(index, 1);
        updateFileInput();
        updatePreview();
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 بایت';
        const k = 1024;
        const sizes = ['بایت', 'کیلوبایت', 'مگابایت'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i))).toFixed(2) + ' ' + sizes[i];
    }

    // 4. مدیریت thumbnail
    function uncheckExistingThumbnails() {
        document.querySelectorAll('input[name="thumbnail"]').forEach(radio => {
            radio.checked = false;
        });
    }

    function uncheckNewThumbnails() {
        document.querySelectorAll('input[name="new_thumbnail"]').forEach(radio => {
            radio.checked = false;
        });
    }
});

// تابع عمومی برای حذف تنوع
function removeVariant(button) {
    button.parentElement.remove();
}
</script>
</body>

</html>