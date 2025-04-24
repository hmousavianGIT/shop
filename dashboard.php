<?php
require_once 'vendor/autoload.php';
use MongoDB\Client;
$client = new Client("mongodb://localhost:27017");
$db = $client->store;
?>

<!DOCTYPE html>
<html dir="rtl" lang="fa">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد مدیریت</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet">
    <link href="dropdown.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.min.js"></script>
    <style>
    .thumbnail-preview {
        width: 100px;
        height: 100px;
        object-fit: cover;
    }

    @media (max-width: 640px) {
        .container {
            max-width: 100%;
        }
    }

    @media (min-width: 641px) {
        .container {
            max-width: 1200px;
        }
    }
    </style>
</head>

<body class="bg-gray-100 p-4">
    <div class="container mx-auto">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">داشبورد مدیریت</h1>

        <!-- منوی ناوبری -->
        <nav class="mb-6">
            <a href="shop.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mr-2">صفحه اصلی</a>
            <a href="manage_products.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mr-2">مدیریت محصولات</a>
            <a href="manage_items.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mr-2">مدیریت تگ‌ها و کتگوری‌ها</a>
            <a href="addons/clear_products.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">پاک کردن دیتابیس</a>
        </nav>

        <!-- فرم اضافه کردن محصول -->
        <form method="POST" action="add_product.php" enctype="multipart/form-data" class="space-y-4" id="productForm">
            <div>
                <label class="block font-semibold">نام محصول:</label>
                <input type="text" name="productName" required class="w-full p-2 border rounded">
            </div>

            <div>
                <label class="block font-semibold">ماهیت محصول:</label>
                <div class="flex flex-wrap gap-2">
                    <?php
                    $natures = $db->natures->find();
                    foreach ($natures as $cat) {
                        echo "<label><input type='checkbox' name='natures[]' value='{$cat['name']}'> {$cat['name']}</label>";
                    }
                    ?>
                </div>
            </div>
            <div>
                <label class="block font-semibold">کتگوری‌ها:</label>
                <div class="flex flex-wrap gap-2">
                    <?php
                    $categories = $db->categories->find();
                    foreach ($categories as $cat) {
                        echo "<label><input type='checkbox' name='categories[]' value='{$cat['name']}'> {$cat['name']}</label>";
                    }
                    ?>
                </div>
            </div>
            <div>
                <label class="block font-semibold">تگ‌ها:</label>
                <input type="text" name="tags" id="tagInput" class="w-full p-2 border rounded" placeholder="تگ‌ها رو وارد کنید">
            </div>
            <div>
                <label class="block font-semibold">تنوع‌ها:</label>
                <div id="variants-container"></div>
                <button type="button" onclick="addVariant()" class="bg-blue-500 text-white px-4 py-2 rounded mt-2">افزودن تنوع</button>
            </div>
            <div>
                <label class="block font-semibold">توضیحات:</label>
                <textarea name="description" required class="w-full p-2 border rounded"></textarea>
            </div>

            <!-- بخش ویژگی‌ها -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">ویژگی‌های محصول (با Enter جدا کنید)</label>
                <input name="productAttributes" class="attributes-input w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="مثال: جنس پارچه نخی، کشور سازنده ایران، وزن 250 گرم">
            </div>

            <div>
                <label class="block font-semibold">وضعیت:</label>
                <select name="status" required class="w-full p-2 border rounded">
                    <option value="موجود">موجود</option>
                    <option value="ناموجود">ناموجود</option>
                    <option value="در حال تولید">در حال تولید</option>
                </select>
            </div>
            <div>
                <label class="block font-semibold">رسانه‌ها (عکس یا ویدئو):</label>
                <input type="file" name="media[]" id="mediaInput" multiple accept="image/*,video/*" class="w-full p-2 border rounded">
                <div id="mediaPreview" class="flex flex-wrap gap-4 mt-4"></div>
            </div>
            <input type="hidden" name="thumbnail_index" id="thumbnailIndex" value="0">
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">اضافه کردن محصول</button>
        </form>
    </div>

    <script>
    // مدیریت تگ‌ها
    const tagInput = document.getElementById('tagInput');
    const tagifyTags = new Tagify(tagInput, {
        whitelist: [<?php
                $tags = $db->tags->find();
                $tagList = [];
                foreach ($tags as $tag) {
                    $tagList[] = "'{$tag['name']}'";
                }
                echo implode(',', $tagList);
            ?>],
        dropdown: {
            enabled: 1,
            maxItems: 10
        }
    });

    // مدیریت ویژگی‌ها
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.querySelector('.attributes-input');
        const tagify = new Tagify(input, {
            delimiters: ',|\n',
            pattern: null,
            dropdown: {
                enabled: 1
            },
            originalInputValueFormat: values => values.map(item => item.value).join(',')
        });

        document.querySelector('form').addEventListener('submit', function(e) {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'attributes';
            hiddenInput.value = JSON.stringify(tagify.value);
            this.appendChild(hiddenInput);
        });
    });

    let variantIndex = 0;

    function addVariant() {
        <?php
        $sizes = $db->sizes->find();
        $sizesArray = iterator_to_array($sizes);
        array_walk($sizesArray, function(&$item) {
            $item['_id'] = (string)$item['_id'];
        });
        $sizesJson = json_encode($sizesArray);

        $colors = $db->colors->find();
        $colorsArray = iterator_to_array($colors);
        array_walk($colorsArray, function(&$item) {
            $item['_id'] = (string)$item['_id'];
        });
        $colorsJson = json_encode($colorsArray);
        ?>
        const sizes = <?php echo $sizesJson; ?>;
        const colors = <?php echo $colorsJson; ?>;

        const variantDiv = document.createElement('div');
        variantDiv.className = 'variant flex gap-2 mb-2';
        variantDiv.innerHTML = `
            <input type="text" name="variants[${variantIndex}][color]" class="color-input p-2 border rounded" placeholder="رنگ">
            <select name="variants[${variantIndex}][size]" class="size-select p-2 border rounded">
                ${sizes.map(size => `
                    <option value="${size._id}">${size.name}</option>
                `).join('')}
            </select>
            <input type="number" name="variants[${variantIndex}][quantity]" placeholder="تعداد" class="p-2 border rounded">
            <input type="number" name="variants[${variantIndex}][price]" placeholder="قیمت" class="p-2 border rounded">
            <input type="number" name="variants[${variantIndex}][discount]" placeholder="درصد تخفیف" class="p-2 border rounded">
            <button type="button" onclick="removeVariant(this)" class="bg-red-500 text-white px-2 py-1 rounded">حذف</button>
        `;
        document.getElementById('variants-container').appendChild(variantDiv);

        const colorInput = variantDiv.querySelector('.color-input');
        new Tagify(colorInput, {
            whitelist: colors.map(color => ({
                value: color._id,
                name: color.name
            })),
            dropdown: {
                enabled: 1, // پیشنهادات فعال باشد
                maxItems: 10,
                classname: "tagify__dropdown",
                mapValueTo: "name", // نمایش نام در پیشنهادات
                searchKeys: ["name"] // جستجو بر اساس نام
            },
            maxTags: 1,
            enforceWhitelist: true, // فقط از لیست مجاز انتخاب شود
            templates: {
                tag: function(tagData) {
                    return `<tag title="${tagData.name}" contenteditable='false' spellcheck='false' tabIndex="-1" class="tagify__tag" ${this.getAttributes(tagData)}>
                                <x title='' class='tagify__tag__removeBtn' role='button' aria-label='remove tag'></x>
                                <div>
                                    <span class='tagify__tag-text'>${tagData.name}</span>
                                </div>
                            </tag>`;
                },
                dropdownItem: function(tagData) {
                    return `<div ${this.getAttributes(tagData)} class='tagify__dropdown__item' tabindex="0" role="option">${tagData.name}</div>`;
                }
            },
            originalInputValueFormat: values => values.map(item => item.value)[0] // فقط _id به سرور ارسال شود
        });

        variantIndex++;
    }

    function removeVariant(button) {
        button.parentElement.remove();
    }

    // پیش‌نمایش و مدیریت فایل‌ها
    const mediaInput = document.getElementById('mediaInput');
    const mediaPreview = document.getElementById('mediaPreview');
    const thumbnailIndexInput = document.getElementById('thumbnailIndex');
    let uploadedFiles = [];

    mediaInput.addEventListener('change', function() {
        const files = Array.from(this.files);
        uploadedFiles = uploadedFiles.concat(files);
        updatePreview();
        updateFileInput();
    });

    function updatePreview() {
        mediaPreview.innerHTML = '';
        uploadedFiles.forEach((file, index) => {
            const url = URL.createObjectURL(file);
            const isImage = file.type.startsWith('image');
            const div = document.createElement('div');
            div.className = 'relative';
            div.innerHTML = `
                <img src="${url}" class="thumbnail-preview">
                ${isImage ? `<input type="radio" name="thumbnail_radio" value="${index}" class="absolute top-2 left-2" ${index === 0 ? 'checked' : ''} onchange="updateThumbnailIndex(${index})">` : ''}
                <button type="button" class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded" onclick="removeFile(${index})">حذف</button>
            `;
            mediaPreview.appendChild(div);
        });
        updateThumbnailIndex(0);
    }

    function removeFile(index) {
        uploadedFiles = uploadedFiles.filter((_, i) => i !== index);
        updatePreview();
        updateFileInput();
    }

    function updateThumbnailIndex(index) {
        thumbnailIndexInput.value = index;
    }

    function updateFileInput() {
        const dataTransfer = new DataTransfer();
        uploadedFiles.forEach(file => dataTransfer.items.add(file));
        mediaInput.files = dataTransfer.files;
    }

    document.getElementById('productForm').addEventListener('submit', function(e) {
        updateFileInput();
    });
    </script>

    <style>
    .tagify {
        @apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500;
    }

    .tagify__tag {
        @apply bg-indigo-100 text-indigo-800 rounded-md;
    }

    .tagify__tag__removeBtn {
        @apply text-indigo-500 hover:text-indigo-700;
    }

    .tagify__input {
        @apply text-gray-700;
    }
    </style>
</body>

</html>