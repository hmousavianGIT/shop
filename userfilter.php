<?php
require_once 'vendor/autoload.php';
use MongoDB\Client;
use MongoDB\BSON\ObjectId;

$client = new Client("mongodb://localhost:27017");
$db = $client->store;

// دریافت فیلترها از فرم ارسال‌شده
$filters = [];
if (isset($_POST['colors']) && !empty($_POST['colors'])) {
    $filters['variants.color'] = ['$in' => array_map(function($id) {
        return new ObjectId($id);
    }, $_POST['colors'])];
}
if (isset($_POST['sizes']) && !empty($_POST['sizes'])) {
    $filters['variants.size'] = ['$in' => array_map(function($id) {
        return new ObjectId($id);
    }, $_POST['sizes'])];
}
if (isset($_POST['categories']) && !empty($_POST['categories'])) {
    $filters['categories'] = ['$in' => $_POST['categories']];
}
if (isset($_POST['natures']) && !empty($_POST['natures'])) {
    $filters['natures'] = ['$in' => $_POST['natures']];
}

// جستجو در محصولات بر اساس فیلترها
$products = $db->products->find($filters);
// تبدیل کورسور به آرایه برای استفاده چندباره
$productsArray = $products->toArray();

?>

<!DOCTYPE html>
<html lang="fa" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتایج فیلتر</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css">
    <style>
    @font-face {
        font-family: 'hamishe';
        src: url('fonts/Digi Hamishe Bold.ttf') format('woff2');
    }

    @font-face {
        font-family: 'Vazir';
        src: url('fonts/vazir.woff2') format('woff2');
    }

    .no-select {
    -webkit-user-select: none; /* Safari */
    -moz-user-select: none; /* Firefox */
    -ms-user-select: none; /* IE/Edge */
    user-select: none; /* استاندارد */
}

    body {
        font-family: 'Vazir', sans-serif;
        background-color: coral;
    }

    main {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 5px;
        padding-top: 180px;
        padding-bottom: 120px;
        margin-bottom: 80px;
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        padding: 0 10px;
        margin-bottom: 100px;
    }

    .product-item {
        position: relative;
        overflow: hidden;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        background: white;
        padding: 10px;
        display: flex;
        flex-direction: column;
        align-items: center;

    }

    .product-image {
        width: 100%;
        padding-top: 100%;
        /* نسبت 1:1 برای حفظ مربع بودن */
        position: relative;
    }

    .product-image img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 6px;
    }

    .product-item p {
        font-family: 'hamishe';
        text-align: center;
        padding: 5px 0;
        margin: 0;
        background: #f9fafb;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 30px;
        width: 100%;
    }

    .container {
        max-width: 800px;
        margin: 0 auto;
        padding: 10px 10px;
        
    }

    /* استایل‌های جدید برای Swiper هایلایت‌ها */
    .highlights-swiper {
        max-width: 800px;
        padding: 0px 10px;
        
    }

    .highlight-item {
        width: 65px;
        height: 65px;
        border-radius: 50%;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border: 2px solidrgb(255, 0, 0);
    }

    .highlight-item-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
    }

    .highlight-prev, .highlight-next {
        color:rgb(0, 0, 0);
        background: white;
        width: 25px;
        height: 25px;
        font-size: 10rem; /* تغییر سایز آیکون */
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
          
    }

    .highlight-prev::after,
.highlight-next::after {
    font-size: 1rem; /* تغییر سایز آیکون فلش */
    font-weight: bolder;
}

    .highlight-prev:hover, .highlight-next:hover {
        color: #374151;
    }

    .product-image img {
    transform: translateZ(0);
    -webkit-transform: translateZ(0);
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}
.product-item {
    will-change: transform;
}


@media (min-width: 768px) {
        .container {
        max-width: 700px;
    }
    .highlights-swiper {
        max-width: 700px;
        margin: 0 auto;
        padding: 10px 40px;
    }
        .product-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }
    </style>
</head>

<body class="bg-gray-100">
    <?php include 'header_full.php'; ?>

    <div class="container">
        <!-- Swiper Highlights -->
        <div class="swiper highlights-swiper">
            <div class="swiper-wrapper">
                <?php for ($i = 1; $i <= 10; $i++): ?>
                <div class="swiper-slide" style="width: auto;">
                    <div class="highlight-item-wrapper">
                        <div class="no-select highlight-item" onclick="filterProducts('cat<?=$i?>')">
                            <img src="highlights/jean.png" class="w-full h-full rounded-full object-cover">
                        </div>
                        <span class="no-select text-xs"><?=$i?></span>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            
            <div class="swiper-button-prev highlight-prev"></div>
            <div class="swiper-button-next highlight-next"></div>
        </div>
    </div>

    <main id="productContainer" class="product-grid mt-4">
    <?php if (count($productsArray) > 0): ?>
        <?php
      
        foreach ($productsArray as $product) {
            $thumb = $product['thumbnail'] ?? ($product['media'][0]['url'] ?? 'placeholder.jpg');
            echo "<a href='product_detail.php?id=" . $product['_id'] . "' class='product-item'>";
            echo "<div class='product-image'><img src='$thumb' alt='{$product['productName']}' loading='lazy'></div>";
            echo "<p>{$product['productName']}</p>";
            echo "</a>";
        }
        ?> <?php else: ?>
            <p class="text-center text-gray-600">هیچ محصولی با فیلترهای انتخاب‌شده یافت نشد.</p>
            <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>

    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <script>
    // Initialize Highlights Swiper
    const highlightsSwiper = new Swiper('.highlights-swiper', {
        slidesPerView: 'auto',
        spaceBetween: 15,
        navigation: {
            nextEl: '.highlight-next',
            prevEl: '.highlight-prev',
        },
        freeMode: true,
        mousewheel: true,
        breakpoints: {
            640: {
                spaceBetween: 20
            }
        }
    });

    function filterProducts(category) {
        fetch('filter_products.php?category=' + encodeURIComponent(category))
            .then(response => response.text())
            .then(data => document.getElementById('productContainer').innerHTML = data)
            .catch(error => console.error('Error:', error));
    }

    let page = 1;
    let isLoading = false;
    let hasMore = true;

    async function loadMoreProducts() {
        if (isLoading || !hasMore) return;
        
        isLoading = true;
        try {
            const response = await fetch(`load_products.php?page=${page}&limit=20`);
            const data = await response.text();
            
            if (data.trim() === '') {
                hasMore = false;
                return;
            }
            
            document.getElementById('productContainer').innerHTML += data;
            page++;
        } catch (error) {
            console.error('Error:', error);
        } finally {
            isLoading = false;
        }
    }

    const debouncedScrollHandler = _.debounce(() => {
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
            loadMoreProducts();
        }
    }, 200);

    window.addEventListener('scroll', debouncedScrollHandler);

    loadMoreProducts();

    function preloadVisibleImages() {
        document.querySelectorAll('.product-image img').forEach(img => {
            const rect = img.getBoundingClientRect();
            if (rect.top < window.innerHeight + 500 && rect.bottom > -500) {
                img.src = img.getAttribute('data-src') || img.src;
            }
        });
    }

    window.addEventListener('scroll', _.debounce(preloadVisibleImages, 200));
    </script>
</body>

</html>