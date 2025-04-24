<?php
// شروع output buffering در ابتدای فایل
ob_start();

require_once 'vendor/autoload.php';
use MongoDB\Client;
$client = new Client("mongodb://localhost:27017");
$db = $client->store;

// پردازش تمام فرم‌ها قبل از هرگونه خروجی HTML
if (isset($_POST['add_nature'])) {
    $name = trim($_POST['nature_name']);
    if (!empty($name)) {
        $db->natures->insertOne(['name' => $name]);
        ob_end_clean();
        header("Location: manage_items.php#natures");
        exit;
    }
}

if (isset($_GET['delete_nature'])) {
    $db->natures->deleteOne(['name' => $_GET['delete_nature']]);
    ob_end_clean();
    header("Location: manage_items.php#natures");
    exit;
}

if (isset($_POST['edit_nature'])) {
    $oldName = $_POST['old_name'];
    $newName = trim($_POST['new_name']);
    if (!empty($newName)) {
        $db->natures->updateOne(['name' => $oldName], ['$set' => ['name' => $newName]]);
        ob_end_clean();
        header("Location: manage_items.php#natures");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['category_name']);
    if (!empty($name)) {
        $db->categories->insertOne(['name' => $name]);
        ob_end_clean();
        header("Location: manage_items.php#categories");
        exit;
    }
}

if (isset($_GET['delete_category'])) {
    $db->categories->deleteOne(['name' => $_GET['delete_category']]);
    ob_end_clean();
    header("Location: manage_items.php#categories");
    exit;
}

if (isset($_POST['edit_category'])) {
    $oldName = $_POST['old_name'];
    $newName = trim($_POST['new_name']);
    if (!empty($newName)) {
        $db->categories->updateOne(['name' => $oldName], ['$set' => ['name' => $newName]]);
        ob_end_clean();
        header("Location: manage_items.php#categories");
        exit;
    }
}

if (isset($_POST['add_tag'])) {
    $name = trim($_POST['tag_name']);
    if (!empty($name)) {
        $db->tags->insertOne(['name' => $name]);
        ob_end_clean();
        header("Location: manage_items.php#tags");
        exit;
    }
}

if (isset($_GET['delete_tag'])) {
    $db->tags->deleteOne(['name' => $_GET['delete_tag']]);
    ob_end_clean();
    header("Location: manage_items.php#tags");
    exit;
}

if (isset($_POST['edit_tag'])) {
    $oldName = $_POST['old_name'];
    $newName = trim($_POST['new_name']);
    if (!empty($newName)) {
        $db->tags->updateOne(['name' => $oldName], ['$set' => ['name' => $newName]]);
        ob_end_clean();
        header("Location: manage_items.php#tags");
        exit;
    }
}

if (isset($_POST['add_color'])) {
    $name = trim($_POST['color_name']);
    if (!empty($name)) {
        $db->colors->insertOne(['name' => $name]);
        ob_end_clean();
        header("Location: manage_items.php#colors");
        exit;
    }
}

if (isset($_GET['delete_color'])) {
    $db->colors->deleteOne(['name' => $_GET['delete_color']]);
    ob_end_clean();
    header("Location: manage_items.php#colors");
    exit;
}

if (isset($_POST['edit_color'])) {
    $oldName = $_POST['old_name'];
    $newName = trim($_POST['new_name']);
    if (!empty($newName)) {
        $db->colors->updateOne(['name' => $oldName], ['$set' => ['name' => $newName]]);
        ob_end_clean();
        header("Location: manage_items.php#colors");
        exit;
    }
}

if (isset($_POST['add_size'])) {
    $name = trim($_POST['size_name']);
    if (!empty($name)) {
        $db->sizes->insertOne(['name' => $name]);
        ob_end_clean();
        header("Location: manage_items.php#sizes");
        exit;
    }
}

if (isset($_GET['delete_size'])) {
    $db->sizes->deleteOne(['name' => $_GET['delete_size']]);
    ob_end_clean();
    header("Location: manage_items.php#sizes");
    exit;
}

if (isset($_POST['edit_size'])) {
    $oldName = $_POST['old_name'];
    $newName = trim($_POST['new_name']);
    if (!empty($newName)) {
        $db->sizes->updateOne(['name' => $oldName], ['$set' => ['name' => $newName]]);
        ob_end_clean();
        header("Location: manage_items.php#sizes");
        exit;
    }
}

// پایان پردازش فرم‌ها - شروع خروجی HTML
ob_end_flush();
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت آیتم‌ها</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-4">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">مدیریت آیتم‌ها</h1>

        <!-- Ù…Ù†ÙˆÛŒ Ù†Ø§ÙˆØ¨Ø±ÛŒ -->
        <nav class="mb-6">
            <a href="dashboard.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mr-2">داشبورد</a>
            <a href="shop.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mr-2">صفحه اصلی</a>
            <a href="manage_products.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mr-2">مدیریت محصولات</a>
        </nav>

        <!-- ØªØ¨â€ŒÙ‡Ø§ -->
        <div class="flex border-b mb-4">
        <button class="tab-btn px-4 py-2 border-b-2 border-transparent hover:border-green-600 focus:outline-none focus:border-green-600" data-tab="natures">ماهیت و جنسیت</button>
            <button class="tab-btn px-4 py-2 border-b-2 border-transparent hover:border-green-600 focus:outline-none focus:border-green-600" data-tab="categories">دسته بندی</button>
            <button class="tab-btn px-4 py-2 border-b-2 border-transparent hover:border-green-600 focus:outline-none focus:border-green-600" data-tab="tags">تگ ها</button>
            <button class="tab-btn px-4 py-2 border-b-2 border-transparent hover:border-green-600 focus:outline-none focus:border-green-600" data-tab="colors">رنگ ها</button>
            <button class="tab-btn px-4 py-2 border-b-2 border-transparent hover:border-green-600 focus:outline-none focus:border-green-600" data-tab="sizes">سایزها</button>
            <button class="tab-btn px-4 py-2 border-b-2 border-transparent hover:border-green-600 focus:outline-none focus:border-green-600" data-tab="all-data">همه اطلاعات</button>
        </div>

        <!-- Ù…Ø­ØªÙˆØ§ÛŒ ØªØ¨â€ŒÙ‡Ø§ -->
        

<div id="natures" class="tab-content hidden">
            <h2 class="text-xl font-semibold mb-2">ماهیت و جنسیت</h2>
            <form method="POST" class="mb-4">
                <input type="text" id="nature_input" name="nature_name" placeholder="ماهیت و جنسیت" class="p-2 border rounded">
                <button type="submit" name="add_nature" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">اضافه کن</button>
            </form>
            <table class="w-full border-collapse">
                <?php
                $natures = $db->natures->find();
                foreach ($natures as $cat) {
                    echo "<tr class='border-b'><td class='p-2'>{$cat['name']}</td>";
                    echo "<td class='p-2'><form method='POST' class='inline'><input type='hidden' name='old_name' value='{$cat['name']}'><input type='text' name='new_name' value='{$cat['name']}' class='p-1 border rounded'><button type='submit' name='edit_nature' class='bg-blue-500 text-white px-2 py-1 rounded ml-2'>ویرایش</button></form></td>";
                    echo "<td class='p-2'><a href='?delete_nature={$cat['name']}' class='bg-red-500 text-white px-2 py-1 rounded' onclick='return confirm(\"مطمعنی ؟\")'>حذف</a></td></tr>";
                }
                ?>
            </table>
        </div>
        <!-- ØªØ¨ Ú©ØªÚ¯ÙˆØ±ÛŒâ€ŒÙ‡Ø§ -->
        <div id="categories" class="tab-content hidden">
            <h2 class="text-xl font-semibold mb-2">دسته بندی</h2>
            <form method="POST" class="mb-4">
                <input type="text" id="category_input" name="category_name" placeholder="دسته بندی" class="p-2 border rounded">
                <button type="submit" name="add_category" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">اضافه کن</button>
            </form>
            <table class="w-full border-collapse">
                <?php
                $categories = $db->categories->find();
                foreach ($categories as $cat) {
                    echo "<tr class='border-b'><td class='p-2'>{$cat['name']}</td>";
                    echo "<td class='p-2'><form method='POST' class='inline'><input type='hidden' name='old_name' value='{$cat['name']}'><input type='text' name='new_name' value='{$cat['name']}' class='p-1 border rounded'><button type='submit' name='edit_category' class='bg-blue-500 text-white px-2 py-1 rounded ml-2'>ویرایش</button></form></td>";
                    echo "<td class='p-2'><a href='?delete_category={$cat['name']}' class='bg-red-500 text-white px-2 py-1 rounded' onclick='return confirm(\"مطمعنی ؟\")'>حذف</a></td></tr>";
                }
                ?>
            </table>
        </div>

        <!-- ØªØ¨ ØªÚ¯â€ŒÙ‡Ø§ -->
        <div id="tags" class="tab-content hidden">
            <h2 class="text-xl font-semibold mb-2">تگ ها</h2>
            <form method="POST" class="mb-4">
                <input type="text" id="tag_input" name="tag_name" placeholder="تگ ها" class="p-2 border rounded">
                <button type="submit" name="add_tag" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">اضافه کن</button>
            </form>
            <table class="w-full border-collapse">
                <?php
                $tags = $db->tags->find();
                foreach ($tags as $tag) {
                    echo "<tr class='border-b'><td class='p-2'>{$tag['name']}</td>";
                    echo "<td class='p-2'><form method='POST' class='inline'><input type='hidden' name='old_name' value='{$tag['name']}'><input type='text' name='new_name' value='{$tag['name']}' class='p-1 border rounded'><button type='submit' name='edit_tag' class='bg-blue-500 text-white px-2 py-1 rounded ml-2'>ویرایش</button></form></td>";
                    echo "<td class='p-2'><a href='?delete_tag={$tag['name']}' class='bg-red-500 text-white px-2 py-1 rounded' onclick='return confirm(\"مطمعنی ؟\")'>حذف</a></td></tr>";
                }
                ?>
            </table>
        </div>

        <!-- ØªØ¨ Ø±Ù†Ú¯â€ŒÙ‡Ø§ -->
        <div id="colors" class="tab-content hidden">
            <h2 class="text-xl font-semibold mb-2">رنگ ها</h2>
            <form method="POST" class="mb-4">
                <input type="text" id="color_input" name="color_name" placeholder="رنگ ها" class="p-2 border rounded">
                <button type="submit" name="add_color" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">اضافه کن</button>
            </form>
            <table class="w-full border-collapse">
                <?php
                $colors = $db->colors->find();
                foreach ($colors as $color) {
                    echo "<tr class='border-b'><td class='p-2'>{$color['name']}</td>";
                    echo "<td class='p-2'><form method='POST' class='inline'><input type='hidden' name='old_name' value='{$color['name']}'><input type='text' name='new_name' value='{$color['name']}' class='p-1 border rounded'><button type='submit' name='edit_color' class='bg-blue-500 text-white px-2 py-1 rounded ml-2'>ویرایش</button></form></td>";
                    echo "<td class='p-2'><a href='?delete_color={$color['name']}' class='bg-red-500 text-white px-2 py-1 rounded' onclick='return confirm(\"مطمعنی ؟\")'>حذف/a></td></tr>";
                }
                ?>
            </table>
        </div>

        <!-- ØªØ¨ Ø³Ø§ÛŒØ²Ù‡Ø§ -->
        <div id="sizes" class="tab-content hidden">
            <h2 class="text-xl font-semibold mb-2">سایزها</h2>
            <form method="POST" class="mb-4">
                <input type="text" id="size_input" name="size_name" placeholder="سایزها" class="p-2 border rounded">
                <button type="submit" name="add_size" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">اضافه کن</button>
            </form>
            <table class="w-full border-collapse">
                <?php
                $sizes = $db->sizes->find();
                foreach ($sizes as $size) {
                    echo "<tr class='border-b'><td class='p-2'>{$size['name']}</td>";
                    echo "<td class='p-2'><form method='POST' class='inline'><input type='hidden' name='old_name' value='{$size['name']}'><input type='text' name='new_name' value='{$size['name']}' class='p-1 border rounded'><button type='submit' name='edit_size' class='bg-blue-500 text-white px-2 py-1 rounded ml-2'>ویرایش</button></form></td>";
                    echo "<td class='p-2'><a href='?delete_size={$size['name']}' class='bg-red-500 text-white px-2 py-1 rounded' onclick='return confirm(\"مطمعنی ؟\")'>حذف</a></td></tr>";
                }
                ?>
            </table>
        </div>

        <!-- ØªØ¨ Ø¬Ø¯ÛŒØ¯ Ø¨Ø±Ø§ÛŒ Ù†Ù…Ø§ÛŒØ´ Ù‡Ù…Ù‡ Ø§Ø·Ù„Ø§Ø¹Ø§Øª -->
        <div id="all-data" class="tab-content hidden">
            <h2 class="text-xl font-semibold mb-4">همه اطلاعات</h2>


            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-2">ماهیت و جنسیت</h3>
                <table class="w-full border-collapse mb-4">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="p-2">نام</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $natures = $db->natures->find();
                        foreach ($natures as $cat) {
                            echo "<tr class='border-b'><td class='p-2'>{$cat['name']}</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>


            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-2">دسته بندی ها</h3>
                <table class="w-full border-collapse mb-4">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="p-2">نام</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $categories = $db->categories->find();
                        foreach ($categories as $cat) {
                            echo "<tr class='border-b'><td class='p-2'>{$cat['name']}</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-2">تگ ها</h3>
                <table class="w-full border-collapse mb-4">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="p-2">نام</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tags = $db->tags->find();
                        foreach ($tags as $tag) {
                            echo "<tr class='border-b'><td class='p-2'>{$tag['name']}</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-2">رنگ ها</h3>
                <table class="w-full border-collapse mb-4">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="p-2">نام</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $colors = $db->colors->find();
                        foreach ($colors as $color) {
                            echo "<tr class='border-b'><td class='p-2'>{$color['name']}</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-2">سایزها</h3>
                <table class="w-full border-collapse mb-4">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="p-2">نام</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sizes = $db->sizes->find();
                        foreach ($sizes as $size) {
                            echo "<tr class='border-b'><td class='p-2'>{$size['name']}</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const tabs = document.querySelectorAll('.tab-btn');
        const contents = document.querySelectorAll('.tab-content');

        function openTab(tabName) {
            tabs.forEach(t => t.classList.remove('border-green-600'));
            contents.forEach(c => c.classList.add('hidden'));
            const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
            const activeContent = document.getElementById(tabName);
            activeTab.classList.add('border-green-600');
            activeContent.classList.remove('hidden');
            window.location.hash = tabName;

            const input = document.getElementById(`${tabName}_input`);
            if (input) {
                input.focus();
                input.select();
            }
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', () => openTab(tab.dataset.tab));
        });

        const hash = window.location.hash.replace('#', '');
        if (hash) {
            openTab(hash);
        } else {
            openTab('categories');
        }
    </script>

</body>
</html>