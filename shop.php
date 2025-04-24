<?php
require_once 'vendor/autoload.php';
use MongoDB\Client;
?>

<!DOCTYPE html>
<html lang="fa" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>صفحه اصلی فروشگاه</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css">
    <style>
        @font-face {
            font-family: 'hamishe';
            src: url('fonts/Digi Hamishe Bold.ttf') format('woff2');
            font-display: swap;
        }

        @font-face {
            font-family: 'Vazir';
            src: url('fonts/vazir.woff2') format('woff2');
            font-display: swap;
        }

        body {
            font-family: 'Vazir', sans-serif;
            background-color: coral;
        }

        main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 180px 5px 120px;
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
            will-change: transform;
            transition: transform 0.3s ease;
        }

        .product-image {
            width: 100%;
            aspect-ratio: 1 / 1;
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
            transform: translateZ(0);
            backface-visibility: hidden;
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
            padding: 10px;
        }

        .highlights-swiper {
            max-width: 800px;
            padding: 0px 10px;
            min-height: 100px;
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
            min-width: 65px;
            min-height: 65px;
        }

        .highlight-item-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }

        .highlight-prev,
        .highlight-next {
            color: rgb(0, 0, 0);
            background: white;
            width: 25px;
            height: 25px;
            font-size: 10rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .highlight-prev::after,
        .highlight-next::after {
            font-size: 1rem;
            font-weight: bolder;
        }

        .highlight-prev:hover,
        .highlight-next:hover {
            color: #374151;
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
        <div class="swiper highlights-swiper">
            <div class="swiper-wrapper">
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <div class="swiper-slide" style="width: auto;">
                        <div class="highlight-item-wrapper">
                            <div class="no-select highlight-item" onclick="filterProducts('cat<?=$i?>')">
                                <img src="highlights/jean.png" alt="highlight <?=$i?>" class="w-full h-full rounded-full object-cover">
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
        <?php
        $client = new Client("mongodb://localhost:27017");
        $db = $client->store;
        $products = $db->products->find(['status' => 'موجود'], ['limit' => 20]);
        foreach ($products as $product) {
            $thumb = $product['thumbnail'] ?? ($product['media'][0]['url'] ?? 'placeholder.jpg');
            echo "<a href='product_detail.php?id={$product['_id']}' class='product-item'>";
            echo "<div class='product-image'><img src='$thumb' alt='{$product['productName']}' loading='lazy' width='300' height='300'></div>";
            echo "<p>{$product['productName']}</p>";
            echo "</a>";
        }
        ?>
    </main>

    <?php include 'footer.php'; ?>

    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <script>
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
    </script>
</body>
</html>