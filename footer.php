<!DOCTYPE html>
<html lang="fa" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .no-select {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        body {
            font-family: 'Vazir', sans-serif;
           
            
        }

        /* استایل‌های سایدبار جدید */
        #filterSidebar {
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
            z-index: 1000;
            max-height: 100vh;
            display: flex;
            flex-direction: column;
            direction: rtl!important;
        }

        #filterSidebar.open {
            transform: translateX(0);
        }

        #filterOverlay {
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
        }

        #filterOverlay.open {
            opacity: 1;
            visibility: visible;
        }

        #filterSidebarContent {
            flex: 1;
            overflow-y: auto;
            scrollbar-width: none;
            padding-bottom: 80px; /* فضای کافی برای فوتر */
        }

        #filterSidebarContent::-webkit-scrollbar {
            display: none;
        }

        .filter-footer {
            position: sticky;
            bottom: 0;
            background: white;
            border-top: 1px solid #e5e7eb;
            padding: 16px;
            z-index: 1001;
        }

        /* استایل‌های آکاردئون */
        .accordion-content {
            display: none;
        }

        .accordion-content.open {
            display: block;
        }

        /* رسپانسیو */
        @media (max-width: 900px) {
            #filterSidebar {
                width: 100%;
            }
        }

        @media (min-width: 901px) {
            #filterSidebar {
                width: 320px;
            }
        }
        footer{
            direction: ltr;
            z-index: 2;
        }
    </style>
</head>
<body class="bg-gray-100">
    <footer class="fixed inset-x-0 bottom-0 p-2 flex justify-around items-center bg-white shadow-md">
        <div class="flex flex-col items-center cursor-pointer" onclick="window.location.href='shop.php'">
            <svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9.75L12 3l9 6.75M4.5 10.5V21h15V10.5"/>
            </svg>
            <span class="text-xs text-gray-600">خانه</span>
        </div>
        <div class="flex flex-col items-center cursor-pointer" id="filterBtn">
            <svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M5 8h14M7 12h10m2 4H5"/>
            </svg>
            <span class="text-xs text-gray-600">فیلتر</span>
        </div>
        <div class="flex flex-col items-center cursor-pointer" onclick="openPopup('search')">
            <svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.85-4.15a7 7 0 1 0-14 0 7 7 0 0 0 14 0z"/>
            </svg>
            <span class="text-xs text-gray-600">جستجو</span>
        </div>
    </footer>

    <!-- Overlay -->
    <div id="filterOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-10"></div>

    <!-- سایدبار فیلتر -->
    <div id="filterSidebar" class="fixed top-0 right-0 h-full bg-white shadow-lg">
        <div class="p-4 border-b flex justify-between items-center">
            <h2 class="text-xl font-bold">فیلتر محصولات</h2>
            <button id="closeSidebar" class="text-red-500 font-bold text-lg">×</button>
        </div>
        <div class="p-4" id="filterSidebarContent"></div>
        <div class="filter-footer">
            <button id="applyFilters" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg">
                اعمال فیلترها
            </button>
        </div>
    </div>

    <!-- پاپ‌آپ جستجو -->
    <div id="searchPopup" class="fixed inset-0 bg-black bg-opacity-70 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg w-full max-w-md mx-4">
            <div class="p-4 border-b flex justify-between items-center">
                <h2 class="text-xl font-bold">جستجو</h2>
                <button onclick="document.getElementById('searchPopup').classList.add('hidden')" class="text-red-500 font-bold text-lg p-2">×</button>
            </div>
            <div class="p-4">
                <input type="text" placeholder="جستجوی محصولات..." class="w-full p-2 border rounded">
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filterBtn = document.getElementById('filterBtn');
            const filterSidebar = document.getElementById('filterSidebar');
            const closeSidebar = document.getElementById('closeSidebar');
            const applyFilters = document.getElementById('applyFilters');
            const filterSidebarContent = document.getElementById('filterSidebarContent');
            const filterOverlay = document.getElementById('filterOverlay');

            // تابع باز کردن سایدبار
            function openFilterSidebar() {
                fetch('filter.php')
                    .then(response => {
                        if (!response.ok) throw new Error('خطا در لود filter.php');
                        return response.text();
                    })
                    .then(data => {
                        filterSidebarContent.innerHTML = data;
                        filterSidebar.classList.add('open');
                        filterOverlay.classList.add('open');
                        initAccordions();
                    })
                    .catch(error => console.error('خطا در لود سایدبار:', error));
            }

            // تابع بستن سایدبار
            function closeFilterSidebar() {
                filterSidebar.classList.remove('open');
                filterOverlay.classList.remove('open');
            }

            // رویدادهای کلیک
            filterBtn.addEventListener('click', openFilterSidebar);
            closeSidebar.addEventListener('click', closeFilterSidebar);
            filterOverlay.addEventListener('click', closeFilterSidebar);

            // اعمال فیلترها
            applyFilters.addEventListener('click', () => {
                const form = filterSidebarContent.querySelector('form');
                if (form) {
                    form.submit();
                }
            });

            // مقداردهی اولیه آکاردئون‌ها
            function initAccordions() {
                const accordionButtons = document.querySelectorAll('.accordion-button');
                
                accordionButtons.forEach(button => {
                    button.addEventListener('click', () => {
                        const content = button.nextElementSibling;
                        content.classList.toggle('open');
                        
                        // تغییر آیکون
                        const icon = button.querySelector('i');
                        if (icon) {
                            if (content.classList.contains('open')) {
                                icon.classList.replace('fa-chevron-left', 'fa-chevron-down');
                            } else {
                                icon.classList.replace('fa-chevron-down', 'fa-chevron-left');
                            }
                        }
                    });
                });
            }

            // تابع باز کردن پاپ‌آپ
            function openPopup(type) {
                document.getElementById(type + 'Popup').classList.remove('hidden');
            }
        });
    </script>
</body>
</html>