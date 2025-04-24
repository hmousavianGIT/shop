<?php
require_once 'vendor/autoload.php';
use MongoDB\Client;

$client = new Client("mongodb://localhost:27017");
$db = $client->store;

$productId = $_GET['id'] ?? null;
$product = null;

if ($productId) {
    try {
        $product = $db->products->findOne(['_id' => new MongoDB\BSON\ObjectId($productId)]);
    } catch (Exception $e) {
        echo "خطا در دریافت اطلاعات محصول!";
        exit;
    }
}

if (!$product) {
    echo "محصول یافت نشد!";
    exit;
}

$variants = iterator_to_array($product['variants'] ?? []);
$colors = $db->colors->find([]);
$sizes = $db->sizes->find([]);
$attributes = $product['attributes'] ?? [];

$colorMap = [];
foreach ($colors as $color) {
    $colorMap[(string)$color['_id']] = $color['name'];
}

$sizeMap = [];
foreach ($sizes as $size) {
    $sizeMap[(string)$size['_id']] = $size['name'];
}

$variantStructure = [];
foreach ($variants as $variant) {
    if (!isset($variant['color']) || !isset($variant['size'])) {
        continue;
    }
    
    $colorId = (string)$variant['color'];
    $sizeId = (string)$variant['size'];
    
    if (!isset($colorMap[$colorId]) || !isset($sizeMap[$sizeId])) {
        continue;
    }
    
    $variantStructure[$colorId][$sizeId] = [
        'price' => isset($variant['price']) ? (int)$variant['price'] : 0,
        'discount' => isset($variant['discount']) ? (int)$variant['discount'] : 0
    ];
}

$firstColorId = null;
$firstSizeId = null;
$firstPrice = null;
$firstDiscount = null;

if (!empty($variantStructure)) {
    $firstColorId = array_key_first($variantStructure);
    if ($firstColorId) {
        $firstSizeId = array_key_first($variantStructure[$firstColorId]);
        if ($firstSizeId) {
            $firstPrice = $variantStructure[$firstColorId][$firstSizeId]['price'];
            $firstDiscount = $variantStructure[$firstColorId][$firstSizeId]['discount'];
        }
    }
}

function displayPrice($price, $discount) {
    if ($discount > 0) {
        $discountedPrice = $price * (1 - $discount / 100);
        return '<span class="line-through text-gray-500">' . number_format($price) . '</span>' .
               '<span class="text-red-600">' . number_format($discountedPrice) . ' تومان</span>';
    } else {
        return number_format($price) . ' تومان';
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ELBISENO | Products</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/ css/all.min.css">
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

    @font-face {
        font-family: 'Cinema Font';
        src: url('fonts/Cinema Font.ttf') format('truetype');
        font-display: swap;
    }

    @font-face {
        font-family: 'Lalezar';
        src: url('fonts/Digi Lalezar Plus.ttf') format('woff2');
        font-display: swap;
    }

    @font-face {
        font-family: 'mahboubeh';
        src: url('fonts/mahboubeh_mehravar.woff') format('woff2');
        font-display: swap;
    }

    body {
        font-family: 'Vazir', sans-serif;
        background-color: coral;
    }

    .no-select {
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }

    #logotext {
        font-family: 'Cinema Font', sans-serif;
    }

    .productname {
        font-family: 'Lalezar', sans-serif;
        color: rgb(0, 0, 0);
    }

    .techDetails {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
    }

    .boxTech {
        width: 49%;
        box-sizing: border-box;
    }

    .details {
        font-family: 'Vazir', sans-serif;
    }

    .control_container {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .row {
        display: flex;
        gap: 1px;
        align-items: center;
    }

    .col-1,
    .col-2 {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .item {
        padding-top: 5px;
        text-align: center;
    }

    .control-sub-items {
        display: flex;
        gap: 2px;
        margin-top: 5px;
        justify-content: center;
    }

    .control-sub-item {
        flex: 1;
        text-align: center;
    }

    .product-container {
        display: flex;
        align-items: center;
    }

    .leftside {
        width: 60%;
    }

    @media (max-width: 900px) {
        .row {
            flex-direction: column;
            align-items: stretch;
            align-content: space-between;
        }

        .col-1,
        .col-2 {
            flex: 1 1 100%;
        }

        .control-sub-items {
            flex-direction: column;
            justify-content: center;
        }

        .leftside {
            width: 100%;
        }

        .row:first-child {
            flex-direction: row;
        }

        .control-sub-items {
            flex-direction: row;
        }

        .control-sub-item {
            flex: 1;
        }
    }

    .priceNum {
        padding-top: 0.7rem;
        padding-bottom: 0.7rem;
    }

    .sectionUi {
        margin: 0;
        padding-right: 1.5rem;
        list-style: none;
    }

    .check-list {
        margin: 0;
        padding-left: 1.2rem;
    }

    .check-list li {
        position: relative;
        padding-right: 1rem;
        margin-bottom: 0.75rem;
        text-align: right;
    }

    .check-list li:before {
        content: '';
        display: block;
        position: absolute;
        right: 0;
        top: 35%;
        transform: translateY(-50%) rotate(45deg);
        width: 6px;
        height: 12px;
        border-right: 2px solid #00a8a8;
        border-bottom: 2px solid #00a8a8;
    }

    .pplsay {
        counter-reset: index;
        padding: 0;
        max-height: 200px;
        overflow-y: scroll;
        line-height: 30px;
    }

    .pplsay li {
        counter-increment: index;
        display: flex;
        flex-direction: column;
        padding: 25px 0;
        box-sizing: border-box;
        font-size: 1.2rem;
        max-width: 50%;
        color: rgb(49, 49, 49);
        font-family: 'lalezar';
    }

    .pplsay li::before {
        content: counters(index, ".", decimal-leading-zero);
        font-size: 2.5rem;
        text-align: right;
        font-weight: bold;
        min-width: 50px;
        padding: 20px;
        color: orangered;
        font-feature-settings: "tnum";
        font-variant-numeric: tabular-nums;
        align-self: flex-start;
        font-family: "Big Shoulders Stencil", sans-serif;
    }

    .pplsay li+.pplsay li {
        border-top: 1px solid rgba(255, 255, 255, 0.2);
    }

    @media (max-width: 900px) {
        .pplsay li {
            max-width: 100%;
            flex-direction: column;
        }
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 5px;
    }

    .product-container {
        display: flex;
        flex-direction: row;
        gap: 10px;
    }

    .gallery {
        flex: 1;
        max-width: 40%;
        position: relative;
    }

    @media (max-width: 800px) {
        .product-container {
            flex-direction: column;
        }

        .gallery {
            max-width: 100%;
        }
    }

    .swiper {
        aspect-ratio: 1 / 1;
        width: 100%;
        position: relative;
        overflow: hidden;
    }

    .swiper-slide {
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .swiper-slide img,
    .swiper-slide video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.2s ease;
        user-select: none;
        pointer-events: auto;
    }

    .swiper-button-next,
    .swiper-button-prev {
        color: #fff;
        background-color: rgba(0, 0, 0, 0.5);
        padding: 10px;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        z-index: 10;
    }

    .swiper-button-next::after,
    .swiper-button-prev::after {
        font-size: 15px;
    }

    .related-products-swiper {
        overflow: hidden;
        padding: 0 10px;
    }

    .related-products-swiper .swiper-wrapper {
        padding: 10px 0;
    }

    .related-products-prev,
    .related-products-next {
        background-color: rgba(255, 255, 255, 0.8);
        width: 30px;
        height: 30px;
        border-radius: 50%;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        color: #333;
        top: 70%;
        transform: translateY(-50%);
    }

    .related-products-prev {
        right: 5px;
        left: auto;
    }

    .related-products-next {
        left: 5px;
        right: auto;
    }

    .related-products-prev::after,
    .related-products-next::after {
        font-size: 16px;
        font-weight: bold;
    }

    .related-products-prev.swiper-button-disabled,
    .related-products-next.swiper-button-disabled {
        opacity: 0;
    }

    
    </style>
</head>

<body class="bg-gray-100">
    <?php include 'header.php'; ?>

    <main class="mt-5 p-2">
        <div class="container">
            <div class="flex justify-between mb-2">
                <div class="flex flex-row" style="margin-top: -1px;">
                    <img src="img/logo.jpg" alt="لوگو" class="w-12 h-12 object-cover rounded-full mr-1">
                    <div class="flex flex-col items-start text-right" style="margin-top: -1px;">
                        <h2 class="mr-1 text-xl font-bold productname">
                            <?php echo htmlspecialchars($product['productName']); ?>
                        </h2>
                        <img src="img/tl1.png" alt="متن لوگو" class="mr-0.5 w-auto h-5">
                    </div>
                </div>
                <div class="flex flex-row items-end" style="margin-top: -1px;">
                    <img src="img/meter.png" alt="لوگو"
                        class="cursor-pointer w-9 h-9 object-cover rounded-full ml-4 hover:bg-blue-300">
                    <img src="img/telegram.png" alt="لوگو"
                        class="cursor-pointer w-9 h-9 object-cover rounded-full ml-1 hover:bg-blue-300">
                </div>
            </div>
            <div class="flex flex-col">
                <div class="product-container">
                    <div class="gallery">
                        <div class="swiper mySwiper">
                            <div class="swiper-wrapper">
                                <?php foreach ($product['media'] as $media): ?>
                                <div class="swiper-slide">
                                    <?php if ($media['type'] === 'image'): ?>
                                    <div class="zoom-container">
                                        <img src="<?php echo htmlspecialchars($media['url']); ?>" alt="تصویر محصول"
                                            class="zoom-image" >
                                    </div>
                                    <?php elseif ($media['type'] === 'video'): ?>
                                    <video controls>
                                        <source src="<?php echo htmlspecialchars($media['url']); ?>" type="video/mp4">
                                    </video>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                        </div>
                    </div>
                    <div class="leftside">
                        <div class="details">
                            <div class="bg-white p-4 rounded">
                                <h3 class="font-bold"><?php echo htmlspecialchars($product['productName']); ?></h3>
                                <p class="no-select text-right text-justify hyphens-auto text-sm">
                                    <?php echo htmlspecialchars($product['description']); ?>
                                </p>
                            </div>
                            <div class="bg-white p-4 mt-2 mb-2 rounded">
                                <h3 class="font-bold pb-2">ویژگی های محصول</h3>
                                <div class="sectionUi">
                                    <?php if (!empty($attributes)): ?>
                                    <ul class="check-list">
                                        <?php foreach ($attributes as $attribute): ?>
                                        <?php if (!empty($attribute['value'])): ?>
                                        <li>
                                            <p class="no-select text-right text-justify hyphens-auto text-sm">
                                                <?php echo htmlspecialchars($attribute['value']); ?>
                                            </p>
                                        </li>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php else: ?>
                                    <p class="text-gray-500 text-sm">ویژگی‌ای برای این محصول ثبت نشده است</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="control_container">
                            <div class="row">
                                <div class="col-1">
                                    <div class="item">
                                        <select class="w-full p-2 border rounded" name="color" id="colorSelect">
                                            <?php foreach ($variantStructure as $colorId => $sizes): ?>
                                            <?php if (isset($colorMap[$colorId])): ?>
                                            <option value="<?php echo htmlspecialchars($colorId); ?>"
                                                <?php echo ($colorId === $firstColorId) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($colorMap[$colorId]); ?>
                                            </option>
                                            <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-2">
                                    <div class="item">
                                        <select class="w-full p-2 border rounded" name="size" id="sizeSelect">
                                            <?php if ($firstColorId && isset($variantStructure[$firstColorId])): ?>
                                            <?php foreach ($variantStructure[$firstColorId] as $sizeId => $variant): ?>
                                            <?php if (isset($sizeMap[$sizeId])): ?>
                                            <option value="<?php echo htmlspecialchars($sizeId); ?>"
                                                <?php echo ($sizeId === $firstSizeId) ? 'selected' : ''; ?>
                                                data-price="<?php echo htmlspecialchars($variant['price']); ?>"
                                                data-discount="<?php echo htmlspecialchars($variant['discount']); ?>">
                                                <?php echo htmlspecialchars($sizeMap[$sizeId]); ?>
                                            </option>
                                            <?php endif; ?>
                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-1">
                                    <div class="item">
                                        <div class="control-sub-items">
                                            <div class="control-sub-item">
                                                <div
                                                    class="no-select mr-4 ml-4 flex flex-row items-center border rounded py-2 mr-1 bg-red-500 hover:bg-red-600">
                                                    <div id="decrease"
                                                        class="no-select cursor-pointer flex-1 text-white text-xl">−
                                                    </div>
                                                    <div id="quantity" class="no-select flex-2 text-lg text-white">1
                                                    </div>
                                                    <div id="increase"
                                                        class="no-select cursor-pointer flex-1 text-white text-xl">+
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="control-sub-item">
                                                <div id="addToBasketButton"
                                                    class="mr-4 ml-4 no-select flex justify-center items-center border rounded px-2 py-2.5 mr-1 bg-blue-500 text-white cursor-pointer hover:bg-blue-600">
                                                    اضافه به سبد
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-2">
                                    <div class="item">
                                        <div class="no-select control-sub-items mr-3 ml-3 rounded bg-yellow-200">
                                            <p id="priceDisplay" class="priceNum text-lg font-bold md:text-base">
                                                <?php 
                                                if (!empty($variants)) {
                                                    echo displayPrice($firstPrice, $firstDiscount);
                                                } else {
                                                    echo "هیچ تنوعی برای این محصول موجود نیست.";
                                                }
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="bg-white p-4 mt-2 rounded">
                        <h3 class="border-b-2 pb2 font-bold">نظرات کاربران</h3>
                        <div id="comments">
                            <?php foreach ($db->comments->find(['productId' => $productId]) as $comment): ?>
                            <p><strong><?php echo htmlspecialchars($comment['user']); ?>:</strong>
                                <?php echo htmlspecialchars($comment['text']); ?></p>
                            <?php endforeach; ?>
                        </div>
                        <ul class="pplsay text-base text-justify hyphens-auto">
                            <li>لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک
                                است
                                <span style="text-align: left;font-family:'vazir'">1400/25/5</span>
                            </li>
                            <li>لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ
                                <span style="text-align: left;font-family:'vazir'">1400/25/5</span>
                            </li>
                            <li>لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ</li>
                            <li>لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک
                                است</li>
                            <li>لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک
                                است</li>
                            <li>لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک
                                است چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است</li>
                        </ul>
                        <div class="bg-white p-1">
                            <div class="container flex space-x-2">
                                <input type="text" id="commentInput" placeholder="نظر خود را بنویسید..."
                                    class="flex-1 p-2 border rounded focus:outline-none focus:ring-2 ml-2 focus:ring-blue-500">
                                <div onclick="showCommentModal()"
                                    class="no-select flex justify-center items-center border rounded px-2 py-2.5 mr-1 bg-green-700 text-white cursor-pointer hover:bg-green-800">
                                    ثبت نظر
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- شروع محصلات مرتبط -->
        <div class="mt-2 mb-4 pb-10 relative">
            <div class="bg-white p-4 mt-4 mb-4 rounded text-center font-bold">محصولات مرتبط</div>
            <div class="swiper related-products-swiper relative">
                <div class="swiper-wrapper">
                    <?php 
            // مقدار پیش‌فرض برای $relatedProducts
            $relatedProducts = [];
            
            $minCommonCategories = 1;
            $pipeline = [
                [
                    '$match' => [
                        'categories' => ['$in' => $product['categories']],
                        '_id' => ['$ne' => $product['_id']],
                        'natures' => ['$in' => (array)$product['natures']]
                    ]
                ],
                [
                    '$addFields' => [
                        'commonCategories' => [
                            '$size' => [
                                '$setIntersection' => ['$categories', $product['categories']]
                            ]
                        ]
                    ]
                ],
                [
                    '$match' => [
                        'commonCategories' => ['$gte' => $minCommonCategories]
                    ]
                ],
                [
                    '$sort' => ['commonCategories' => -1]
                ],
                [
                    '$limit' => 12
                ]
            ];
            
            try {
                $cursor = $db->products->aggregate($pipeline);
                $relatedProducts = iterator_to_array($cursor);
            } catch (MongoDB\Driver\Exception\Exception $e) {
                // به جای die، خطا را لاگ می‌کنیم و ادامه می‌دهیم
                error_log("خطا در دریافت محصولات مرتبط: " . $e->getMessage());
            }
            
            // بررسی وجود محصولات مرتبط قبل از نمایش
            if (!empty($relatedProducts)):
                foreach ($relatedProducts as $related): ?>
                    <div class="swiper-slide">
                        <div class="aspect-square bg-gray-200 rounded-lg overflow-hidden">
                            <a href="product_detail.php?id=<?php echo $related['_id']; ?>">
                                <img src="<?php echo htmlspecialchars($related['thumbnail'] ?? 'placeholder.jpg'); ?>"
                                    alt="محصول مرتبط" class="w-full h-full object-cover rounded-lg">
                            </a>
                        </div>
                    </div>
                    <?php endforeach; 
            else: ?>
                    <div class="text-center py-4">محصول مرتبطی یافت نشد</div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($relatedProducts)): ?>
                <div class="swiper-button-prev related-products-prev"></div>
                <div class="swiper-button-next related-products-next"></div>
                <?php endif; ?>
            </div>
        </div>

        <style>
        .related-products-swiper {
            overflow: hidden;
            padding: 10px 10px;
            height: 150px;
            min-height: 150px;
            display: flex;
        }

        .related-products-swiper .swiper-wrapper {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            height: auto;
            padding: 5px 0;
        }

        .related-products-swiper .swiper-slide {
            width: 140px !important;
            height: 140px !important;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px !important;
        }

        .aspect-square {
            aspect-ratio: 1 / 1;
            width: 100%;
            height: 100%;
        }

        .related-products-prev,
        .related-products-next {
            background-color: rgba(255, 255, 255, 0.8);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            color: #333;
            top: 50%;
            transform: translateY(-50%);
        }

        .related-products-prev {
            left: 10px;
        }

        .related-products-next {
            right: 10px;
        }

        .related-products-prev::after,
        .related-products-next::after {
            font-size: 16px;
            font-weight: bold;
        }

        .related-products-prev.swiper-button-disabled,
        .related-products-next.swiper-button-disabled {
            opacity: 0;
            pointer-events: none;
        }

        @media (min-width: 440px) {
            .related-products-swiper .swiper-slide {
                width: 140px !important;
                height: 140px !important;
            }
        }

        @media (min-width: 768px) {
            .related-products-swiper .swiper-slide {
                width: 140px !important;
                height: 140px !important;
            }
        }

        @media (min-width: 1024px) {
            .related-products-swiper .swiper-slide {
                width: 160px !important;
                height: 160px !important;
            }
        }
        </style>
    </main>

    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/swiper-bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const swiper = new Swiper('.mySwiper', {
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });

        const relatedSwiper = new Swiper('.related-products-swiper', {
            slidesPerView: 'auto',
            spaceBetween: 10,
            freeMode: true,
            grabCursor: true,
            navigation: {
                nextEl: '.related-products-next',
                prevEl: '.related-products-prev',
            },
            breakpoints: {
                440: {
                    slidesPerView: 3,
                    spaceBetween: 10
                },
                768: {
                    slidesPerView: 5,
                    spaceBetween: 10
                },
                1024: {
                    slidesPerView: 10,
                    spaceBetween: 10
                }
            }
        });

        const quantityElement = document.getElementById("quantity");
        const decreaseBtn = document.getElementById("decrease");
        const increaseBtn = document.getElementById("increase");

        let quantity = parseInt(quantityElement.textContent);

        decreaseBtn.addEventListener("click", () => {
            if (quantity > 1) {
                quantity--;
                quantityElement.textContent = quantity;
            }
        });

        increaseBtn.addEventListener("click", () => {
            quantity++;
            quantityElement.textContent = quantity;
        });

        document.getElementById('addToBasketButton').addEventListener('click', function() {
            alert('محصول به سبد خرید اضافه شد!');
        });

        // Zoom functionality
        const zoomContainers = document.querySelectorAll('.zoom-container');
        zoomContainers.forEach(container => {
            const img = container.querySelector('.zoom-image');
            let isZooming = false;
            let scale = 1;
            let pinchStartDistance = 0;
            let originX = 0;
            let originY = 0;

            // Mouse hover zoom
            container.addEventListener('mousemove', (e) => {
                if (!isZooming) {
                    isZooming = true;
                    scale = 3;
                    img.style.transform = `scale(${scale})`;
                }

                const rect = container.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const percentX = (x / rect.width) * 100;
                const percentY = (y / rect.height) * 100;

                img.style.transformOrigin = `${percentX}% ${percentY}%`;
            });

            container.addEventListener('mouseleave', () => {
                isZooming = false;
                scale = 1;
                img.style.transform = `scale(${scale})`;
                img.style.transformOrigin = 'center center';
            });

            // Touch pinch zoommmmmmmmmmmm
            



            

            
        });
    });

    $(document).ready(function() {
        const variantStructure = <?php echo json_encode($variantStructure); ?>;
        const sizeMap = <?php echo json_encode($sizeMap); ?>;

        function populateSizes(colorId) {
            const sizeSelect = $('#sizeSelect');
            sizeSelect.empty();

            if (colorId && variantStructure[colorId]) {
                $.each(variantStructure[colorId], function(sizeId, variant) {
                    if (sizeMap[sizeId]) {
                        const option = $('<option></option>')
                            .val(sizeId)
                            .text(sizeMap[sizeId])
                            .attr('data-price', variant.price)
                            .attr('data-discount', variant.discount);
                        sizeSelect.append(option);
                    }
                });

                if (sizeSelect.children().length > 0) {
                    sizeSelect.children().first().prop('selected', true);
                    updatePrice();
                }
            }
        }

        function updatePrice() {
            const selectedOption = $('#sizeSelect option:selected');
            const price = parseInt(selectedOption.data('price')) || 0;
            const discount = parseInt(selectedOption.data('discount')) || 0;

            let priceHtml;
            if (discount > 0) {
                const discountedPrice = Math.round(price * (1 - discount / 100));
                priceHtml = `
                        <span class="text

-sm line-through text-gray-500">${price.toLocaleString('fa-IR')}</span>
                        <span class="text-red-600 mr-2">${discountedPrice.toLocaleString('fa-IR')} تومان</span>
                        <span class="text-green-600 text-sm">(${discount}% تخفیف)</span>
                    `;
            } else {
                priceHtml = `<span class="text-green-800">${price.toLocaleString('fa-IR')} تومان</span>`;
            }

            $('#priceDisplay').html(priceHtml);
        }

        $('#colorSelect').on('change', function() {
            populateSizes($(this).val());
        });

        $('#sizeSelect').on('change', updatePrice);

        populateSizes($('#colorSelect').val());
    });

    let isLoggedIn = false;

    function addToBasket() {
        if (!isLoggedIn) showLoginModal();
        else {
            const quantity = document.getElementById('quantity').value;
            console.log('محصول به سبد خرید اضافه شد با تعداد:', quantity);
        }
    }

    function showCommentModal() {
        document.getElementById('comment-modal')?.classList.remove('hidden');
    }

    function closeCommentModal() {
        document.getElementById('comment-modal')?.classList.add('hidden');
    }

    function showLoginModal() {
        document.getElementById('login-modal')?.classList.remove('hidden');
    }

    function closeLoginModal() {
        document.getElementById('login-modal')?.classList.add('hidden');
    }

    function submitComment() {
        const commentText = document.getElementById('comment-text')?.value;
        if (commentText && isLoggedIn) {
            console.log('کامنت ارسال شد:', commentText);
            closeCommentModal();
        } else {
            alert('لطفاً وارد شوید یا متن کامنت را وارد کنید.');
        }
    }

    function login() {
        const username = document.getElementById('username')?.value;
        const password = document.getElementById('password')?.value;
        console.log('ورود با:', username, password);
        closeLoginModal();
        isLoggedIn = true;
    }
    </script>
</body>

</html>