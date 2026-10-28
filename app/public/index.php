<?php require __DIR__ . '/../src/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover"/>
<title>FASHION WORLD STORE| عالم الأزياء لتجارة الألبسة بالجملة</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Cinzel:wght@700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/styles.css">
</head>
<body class="min-h-screen flex flex-col pb-24" id="mainBody">
<div id="toastContainer" class="fixed top-16 left-1/2 -translate-x-1/2 z-[140] flex flex-col items-center gap-2 pointer-events-none px-3 w-full max-w-xs"></div>

<!-- Image zoom-->
<div id="imageZoomModal" class="fixed inset-0 bg-black/95 hidden items-center justify-center z-[125] p-2 select-none" onclick="closeZoomModal()">
  <button type="button" onclick="closeZoomModal()" class="fixed top-4 right-4 z-[130] bg-neutral-800 text-white w-9 h-9 rounded-full font-bold text-sm border border-yellow-500/30">✕</button>
  <button type="button" onclick="prevZoomImage(event)" class="absolute left-3 top-1/2 -translate-y-1/2 z-[130] bg-black/70 hover:bg-black text-yellow-300 px-3 py-2 rounded-full text-sm font-bold border border-yellow-500/30">❮</button>
  <button type="button" onclick="nextZoomImage(event)" class="absolute right-3 top-1/2 -translate-y-1/2 z-[130] bg-black/70 hover:bg-black text-yellow-300 px-3 py-2 rounded-full text-sm font-bold border border-yellow-500/30">❯</button>
  <div class="fixed bottom-5 left-1/2 -translate-x-1/2 z-[130] bg-black/80 text-yellow-300 text-[11px] font-bold px-3.5 py-1.5 rounded-full border border-yellow-500/30 pointer-events-none">🔍 اضغط للتكبير</div>
  <div class="w-full h-full flex items-center justify-center overflow-auto" onclick="event.stopPropagation()">
    <img id="zoomModalImg" src="" onclick="toggleDoubleZoom(this)" class="max-w-full max-h-[92vh] object-contain transition-transform duration-300 cursor-zoom-in rounded-lg"/>
  </div>
</div>

<!-- Header -->
<header class="glass-panel sticky top-0 z-50 border-b border-yellow-500/20 w-full">
  <div class="max-w-7xl mx-auto px-2.5 sm:px-6 py-2 flex items-center justify-between gap-2">
    <div class="flex items-center gap-2 cursor-pointer select-none flex-shrink-0" onclick="logoSecretClick()">
      <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl border border-yellow-500/40 bg-black flex items-center justify-center shadow flex-shrink-0">
        <svg viewBox="0 0 64 64" class="w-5 h-5"><path fill="#D4AF37" d="M25 8 L28 18 L32 14 L36 18 L39 8 L42 22 C42 28 38 32 39 39 L47 56 L17 56 L25 39 C26 32 22 28 22 22 Z"/></svg>
      </div>
      <div>
        <h1 class="font-cinzel text-sm sm:text-lg font-black gold-gradient-text tracking-wide whitespace-nowrap">FASHION WORLD</h1>
        <p class="text-[8.5px] sm:text-[10px] text-gray-400 font-semibold whitespace-nowrap">تجارة الألبسة بالجملة - دمشق</p>
      </div>
    </div>
    <div class="flex items-center gap-1.5 flex-shrink-0">
      <div id="adminDropdownWrapper" class="hidden">
        <button type="button" onclick="playUiSound('tap'); toggleAdminMenu()" class="gold-gradient-bg font-black px-2.5 py-1.5 rounded-xl text-[10px] sm:text-xs flex items-center gap-1 shadow">👑 <span class="hidden sm:inline">الإدارة</span></button>
      </div>
      <button type="button" onclick="playUiSound('tap'); openWishlistModal()" class="glossy-box text-pink-400 font-bold px-2.5 py-1.5 rounded-xl text-[10px] flex items-center gap-1" title="المفضلة">❤️ <span id="wishlistCount" class="bg-black px-1.5 rounded-full text-[9px] text-white">0</span></button>
      <button type="button" onclick="playUiSound('tap'); openCart()" class="gold-gradient-bg font-bold px-2.5 py-1.5 rounded-xl text-[10px] sm:text-xs flex items-center gap-1 shadow"><span>🛒</span><span class="hidden xs:inline">السلة</span><span id="cartCount" class="bg-black text-yellow-400 px-1.5 rounded-full text-[9px] font-bold">0</span></button>
      <button type="button" onclick="playUiSound('tap'); toggleToolsDrawer()" class="glossy-box text-yellow-400 px-2.5 py-1.5 rounded-xl text-[10px] sm:text-xs font-bold flex items-center gap-1 border border-yellow-500/30">⚙️ <span class="hidden sm:inline">الأدوات</span></button>
    </div>
  </div>

  <!-- tools drawer -->
  <div id="userToolsDrawer" class="hidden border-t border-yellow-500/20 bg-neutral-950/95 px-3 py-2">
    <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-2 text-xs">
      <div class="flex items-center gap-2 flex-wrap">
        <button type="button" onclick="playUiSound('tap'); toggleThemeMode()" class="glossy-box text-yellow-400 px-3 py-1 rounded-xl text-[10px] font-bold flex items-center gap-1.5"><span id="themeToggleBtn">🌙</span><span>المظهر</span></button>
        <div class="flex items-center gap-1 glossy-box px-2.5 py-1 rounded-xl text-[10px]">
          <span class="text-gray-300 font-bold">العملة:</span>
          <select id="currencySelector" onchange="playUiSound('tap'); setCurrencyMode(this.value)" class="bg-transparent text-yellow-400 font-bold focus:outline-none">
            <option value="BOTH">💵 $ + ل.س</option>
            <option value="USD">💵 دولار ($)</option>
            <option value="SYP">🇸🇾 ليرة (ل.س)</option>
          </select>
        </div>
        <button type="button" onclick="openAboutModal(); toggleToolsDrawer();" class="glossy-box text-yellow-400 px-3 py-1 rounded-xl text-[10px] font-bold flex items-center gap-1">ℹ️ من نحن وتواصل معنا</button>
      </div>
      <button type="button" onclick="toggleToolsDrawer()" class="bg-neutral-800 text-gray-300 px-2.5 py-1 rounded-lg text-[10px] font-bold">إغلاق ✕</button>
    </div>
  </div>

<!-- banner -->
<section class="max-w-7xl mx-auto px-2.5 sm:px-6 pt-2.5 w-full">
  <div id="bannerSection" class="relative overflow-hidden rounded-2xl border border-yellow-500/25 bg-cover bg-center py-6 sm:py-10 px-3 sm:px-6 shadow-lg">
    <div class="max-w-2xl mx-auto text-center relative z-10 space-y-2.5">
      <span class="inline-block px-2.5 py-0.5 rounded-full bg-black/75 border border-yellow-400/30 text-yellow-300 text-[9px] sm:text-xs font-bold">✨ المنصة الاحترافية لتجار الجملة والشركاء المعتمدين ✨</span>
      <h2 class="text-lg sm:text-3xl font-black text-white">تشكيلة الموسم الحصرية بأسعار <span class="gold-gradient-text">الجملة</span></h2>
      <p class="text-gray-300 text-[10px] sm:text-xs max-w-lg mx-auto">تصفح الكتالوج الرسمي لشركة <strong class="text-yellow-400">Fashion World</strong>، </p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2 text-[10px] sm:text-xs max-w-md mx-auto">
        <button type="button" onclick="openAffiliateModal()" class="bg-emerald-700 text-white font-bold py-2 px-3 rounded-xl border border-emerald-500/30 flex items-center justify-center gap-1 shadow">🤝 بوابة شركاء النجاح (3% - 5%)</button>
        <button type="button" onclick="openVipModal()" class="bg-purple-800 text-yellow-200 font-bold py-2 px-3 rounded-xl border border-purple-400/30 flex items-center justify-center gap-1 shadow">👑 تصنيع الكميات الخاصة (VIP)</button>
      </div>
    </div>
  </div>
</section>

<!-- filter and search bar -->
<section class="max-w-7xl mx-auto px-2.5 sm:px-6 pt-2.5 w-full space-y-1.5">
  <div onclick="filterCategory('CLEARANCE')" class="cursor-pointer bg-gradient-to-r from-red-950/90 via-red-900/80 to-amber-950/90 border border-red-500/40 rounded-xl px-3 py-1.5 flex items-center justify-between text-[10px] sm:text-xs shadow">
    <div class="flex items-center gap-1.5 truncate font-bold text-red-200">
      <span class="bg-red-600 text-white px-2 py-0.5 rounded-md text-[9px] font-black discount-flash">تصفية سريعة</span>
      <span class="truncate">قسم العروض الخاصة والحسومات الكبرى على الموديلات!</span>
    </div>
    <span class="text-yellow-300 font-bold whitespace-nowrap mr-1">عرض الكل ⬅️</span>
  </div>

  <div class="glass-panel rounded-xl p-1.5 flex items-center border border-yellow-500/20">
    <div class="relative w-full flex items-center">
      <span class="absolute right-3 text-yellow-400 text-xs pointer-events-none">🔍</span>
      <input id="searchInput" type="text" oninput="handleSearch(this.value)" placeholder="ابحث برقم الكود، اسم الموديل، اللون، أو القسم..." class="w-full bg-black/70 border border-yellow-500/20 rounded-lg pr-8 pl-7 py-2 text-xs text-white focus:outline-none"/>
      <button type="button" id="clearSearchBtn" onclick="clearSearch()" class="hidden absolute left-2 text-gray-300 text-xs bg-neutral-800 px-2 py-0.5 rounded">✕</button>
    </div>
  </div>

  <div class="glass-panel rounded-xl p-1.5 flex items-center gap-1.5 overflow-x-auto no-scrollbar border border-yellow-500/20" id="categoryTabs">
    <button type="button" onclick="filterCategory('ALL')" data-cat="ALL" class="cat-btn flex-shrink-0 gold-gradient-bg font-bold px-3 py-1 rounded-lg text-[11px]">✨ كافة الأقسام</button>
    <button type="button" onclick="filterCategory('CLEARANCE')" data-cat="CLEARANCE" class="cat-btn flex-shrink-0 bg-red-950/80 text-red-300 border border-red-500/50 font-bold px-3 py-1 rounded-lg text-[11px]">⚡ تصفية سريعة وعروض</button>
    <button type="button" onclick="filterCategory('نسواني')" data-cat="نسواني" class="cat-btn flex-shrink-0 glossy-box text-pink-200 font-bold px-3 py-1 rounded-lg text-[11px]">👗 قسم النسواني</button>
    <button type="button" onclick="filterCategory('رجالي')" data-cat="رجالي" class="cat-btn flex-shrink-0 glossy-box text-sky-200 font-bold px-3 py-1 rounded-lg text-[11px]">👔 قسم الرجالي</button>
    <button type="button" onclick="filterCategory('ولادي')" data-cat="ولادي" class="cat-btn flex-shrink-0 glossy-box text-emerald-200 font-bold px-3 py-1 rounded-lg text-[11px]">🧒 قسم الولادي</button>
    <button type="button" onclick="filterCategory('ب.ب')" data-cat="ب.ب" class="cat-btn flex-shrink-0 glossy-box text-purple-200 font-bold px-3 py-1 rounded-lg text-[11px]">👶 قسم الأطفال الرضع</button>
    <button type="button" onclick="filterCategory('TREND')" data-cat="TREND" class="cat-btn flex-shrink-0 glossy-box text-amber-300 font-bold px-3 py-1 rounded-lg text-[11px]">🔥 الأكثر طلباً</button>
  </div>
</section>

<!-- Products Grid -->
<main class="max-w-7xl mx-auto px-2.5 sm:px-6 py-3 flex-1 w-full">
  <div id="productsGrid" class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5 sm:gap-4"></div>
</main>

<!-- Product Modal -->
<div id="productModal" class="fixed inset-0 bg-black/90 hidden items-end sm:items-center justify-center z-50 p-0 sm:p-4">
  <div class="glass-panel rounded-t-3xl sm:rounded-3xl w-full max-w-3xl max-h-[93vh] p-3.5 sm:p-5 flex flex-col shadow-2xl overflow-hidden">
    <div class="flex justify-between items-center border-b border-gray-800 pb-2 mb-2">
      <div class="min-w-0 pr-1">
        <h3 id="modalTitle" class="font-bold text-yellow-400 text-xs sm:text-base truncate">تفاصيل الموديل</h3>
        <p id="modalPublishTime" class="text-[9.5px] text-gray-400 font-semibold"></p>
      </div>
      <button type="button" onclick="closeProductModal()" class="bg-neutral-800 text-gray-300 w-7 h-7 rounded-full text-xs flex-shrink-0">✕</button>
    </div>
    <div class="overflow-y-auto flex-1 grid grid-cols-1 md:grid-cols-2 gap-3.5 pl-1">
      <div class="space-y-2 flex flex-col">
        <div class="relative h-64 sm:h-80 rounded-2xl overflow-hidden bg-neutral-950 border border-yellow-500/25 flex items-center justify-center cursor-zoom-in group" onclick="openZoomModal(document.getElementById('modalMainImg').src)">
          <img id="modalMainImg" src="" class="w-full h-full object-contain transition duration-300"/>
          <div class="watermark-logo"><span class="text-[9px] font-cinzel font-bold text-yellow-400 drop-shadow-md">FASHION WORLD</span></div>
          <div class="absolute top-2 right-2 flex flex-col items-end gap-1 z-20">
            <span id="modalDiscountBadge" class="hidden badge-glass discount-flash text-white font-black"></span>
            <span id="modalStockBadge" class="hidden badge-glass font-bold text-white"></span>
          </div>
          <button type="button" onclick="prevImage(event)" class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/60 hover:bg-black text-yellow-300 px-2.5 py-1.5 rounded-full text-xs font-bold border border-yellow-500/30 z-20">❮</button>
          <button type="button" onclick="nextImage(event)" class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/60 hover:bg-black text-yellow-300 px-2.5 py-1.5 rounded-full text-xs font-bold border border-yellow-500/30 z-20">❯</button>
          <span class="absolute bottom-2 right-2 bg-black/75 backdrop-blur-sm text-yellow-300 text-[8.5px] font-bold px-2 py-0.5 rounded-full border border-yellow-500/25 pointer-events-none z-20">🔍 اضغط للتكبير</span>
        </div>
        <div id="modalThumbs" class="flex gap-1.5 overflow-x-auto pb-1 no-scrollbar"></div>
        <div class="grid grid-cols-2 gap-1.5">
          <button type="button" onclick="downloadModalProductImage()" class="bg-purple-900/45 text-purple-200 border border-purple-500/45 font-bold py-1.5 rounded-xl text-[10px] flex items-center justify-center gap-1 shadow">📥 1. تحميل الصورة</button>
          <button type="button" onclick="copyTrendWhatsAppText()" class="bg-pink-900/45 text-pink-200 border border-pink-500/45 font-bold py-1.5 rounded-xl text-[10px] flex items-center justify-center gap-1 shadow">🔮 2. نسخ نص الترند</button>
        </div>
        <div class="grid grid-cols-3 gap-1">
          <button type="button" onclick="shareProductWhatsApp()" class="bg-emerald-900/35 text-emerald-300 border border-emerald-500/35 font-bold py-1.5 rounded-xl text-[9.5px]">📲 نشر واتساب</button>
          <button type="button" onclick="shareProductFacebook()" class="bg-blue-900/35 text-blue-300 border border-blue-500/35 font-bold py-1.5 rounded-xl text-[9.5px]">📘 فيسبوك</button>
          <button type="button" onclick="reserveProductWhatsApp()" class="bg-amber-600/30 text-amber-300 border border-amber-500/35 font-bold py-1.5 rounded-xl text-[9.5px]">🔒 حجز</button>
        </div>
      </div>

      <div class="space-y-2 flex flex-col justify-between text-xs">
        <div class="space-y-2 glossy-box p-3 rounded-2xl">
          <div class="flex justify-between items-center">
            <span id="modalCategory" class="bg-yellow-500/10 text-yellow-300 border border-yellow-500/25 px-2 py-0.5 rounded-md font-bold text-[10px]"></span>
            <div class="flex items-center gap-1.5">
              <span id="modalViews" class="text-[10px] text-sky-300 font-bold bg-sky-950/40 px-2 py-0.5 rounded-md border border-sky-500/20">👁️ 0</span>
              <button type="button" id="modalLikeBtn" onclick="toggleLikeProduct(event, window.currentModalProduct ? window.currentModalProduct.id : 0)" class="px-2 py-0.5 rounded-md text-[10px] font-bold transition"></button>
              <button type="button" onclick="toggleWishlistModalItem(window.currentModalProduct ? window.currentModalProduct.id : 0)" id="modalWishlistBtn" class="px-2 py-0.5 rounded-md text-[10px] font-bold transition bg-neutral-800 text-pink-300">❤️ المفضلة</button>
            </div>
          </div>
          <div id="modalFomoBanner" class="hidden fomo-strip px-2.5 py-1.5 rounded-xl text-[10px] text-amber-200 font-bold flex items-center justify-between"></div>
          <p id="modalPack" class="text-gray-200 font-bold text-[11px]"></p>
          <div id="modalColorCircles" class="hidden flex items-center gap-1.5 pt-1 flex-wrap">
            <span class="text-[10px] text-yellow-300 font-bold">🎨 الألوان:</span>
            <div id="modalColorsContainer" class="flex items-center gap-1 flex-wrap"></div>
          </div>
          <p id="modalWeightInfo" class="text-[10px] text-amber-300 font-bold"></p>
          <div id="modalAdminDesc" class="hidden text-[10px] text-gray-300 bg-neutral-900/80 p-2 rounded-xl border border-yellow-500/20 leading-relaxed whitespace-pre-line"></div>
          <div id="modalRetailSuggestion" class="text-[10px] text-emerald-300 font-bold bg-emerald-950/30 p-2 rounded-xl border border-emerald-500/30 leading-relaxed"></div>
          <div class="flex items-center justify-between pt-1 border-t border-gray-800">
            <span class="text-yellow-300 font-bold text-[11px]">تقييم المنتج:</span>
            <div id="modalStarsContainer" class="flex items-center gap-0.5 text-base cursor-pointer"></div>
          </div>
          <div id="modalPriceContainer" class="pt-1 border-t border-gray-800 flex items-center justify-between"></div>
          <div class="pt-2 border-t border-gray-800 space-y-1.5 bg-black/50 p-2 rounded-xl border border-yellow-500/20">
            <div class="flex justify-between items-center">
              <span class="text-yellow-300 font-bold text-[10px]">تحديد الكمية:</span>
              <span id="modalMinNotice" class="text-[9px] bg-yellow-500/10 text-yellow-300 px-1.5 py-0.5 rounded border border-yellow-500/25 font-bold">حجم السيرية: 10</span>
            </div>
            <div class="flex items-center justify-center gap-1.5">
              <button type="button" onclick="adjustModalQtyByHalf(-1)" class="glossy-box text-yellow-300 w-10 h-7 rounded-lg font-bold text-[10px]">-نصف</button>
              <input id="modalQtyInput" type="number" min="1" value="10" oninput="calculateModalTotal()" class="w-16 bg-black border border-yellow-500/40 text-center text-yellow-400 font-bold rounded-lg py-1 text-xs"/>
              <button type="button" onclick="adjustModalQtyByHalf(1)" class="glossy-box text-yellow-300 w-10 h-7 rounded-lg font-bold text-[10px]">+نصف</button>
            </div>
            <div class="flex justify-between items-center pt-1 text-gray-300 font-bold text-[11px]">
              <span>الإجمالي الفرعي:</span>
              <span id="modalSubtotal" class="text-emerald-400 font-bold">$0</span>
            </div>
            <div class="flex justify-between items-center text-[10px] text-sky-300 font-bold">
              <span>📦 السيريات والوزن:</span>
              <span id="modalPackAndWeight">1 سيرية | ~3.5 كغ</span>
            </div>
          </div>
        </div>

        <div class="glossy-box p-2.5 rounded-2xl space-y-1.5 text-xs">
          <p class="font-bold text-yellow-400 text-[10px]">💬 ملاحظات:</p>
          <div id="commentsList" class="space-y-1 max-h-24 overflow-y-auto pr-1"></div>
          <div class="flex gap-1 pt-1">
            <input id="commentAuthor" class="w-1/3 bg-black border border-yellow-500/25 rounded-lg px-2 py-1 text-white text-[10px]" placeholder="اسم التاجر..."/>
            <input id="commentText" class="flex-1 bg-black border border-yellow-500/25 rounded-lg px-2 py-1 text-white text-[10px]" placeholder="اكتب ملاحظتك..."/>
            <button type="button" onclick="addComment()" class="gold-gradient-bg font-bold px-2.5 py-1 rounded-lg text-[10px]">إرسال</button>
          </div>
        </div>
      </div>
    </div>
    <div class="flex gap-2 pt-2 border-t border-gray-800 mt-2">
      <button type="button" id="modalAddToCartBtn" onclick="addCustomQtyToCart()" class="flex-1 gold-gradient-bg font-bold py-2 rounded-xl text-xs">🛒 إضافة إلى السلة</button>
      <button type="button" onclick="closeProductModal()" class="bg-neutral-800 text-white px-4 py-2 rounded-xl text-xs font-bold">إغلاق</button>
    </div>
  </div>
</div>

<!-- favorit window -->
<div id="wishlistModal" class="fixed inset-0 bg-black/90 hidden items-center justify-center z-50 p-3">
  <div class="glass-panel rounded-3xl w-full max-w-lg p-4 space-y-3 text-xs border border-pink-500/30">
    <div class="flex justify-between items-center border-b border-gray-800 pb-2">
      <h3 class="font-bold text-pink-400 text-sm">❤️ قائمة الموديلات المفضلة</h3>
      <button type="button" onclick="closeWishlistModal()" class="bg-neutral-800 text-gray-300 w-7 h-7 rounded-full font-bold">✕</button>
    </div>
    <div id="wishlistContainer" class="max-h-72 overflow-y-auto space-y-2 pr-1"></div>
    <button type="button" onclick="closeWishlistModal()" class="w-full bg-neutral-800 text-white font-bold py-2 rounded-xl">إغلاق</button>
  </div>
</div>

<!-- VIP -->
<div id="vipModal" class="fixed inset-0 bg-black/90 hidden items-end sm:items-center justify-center z-50 p-0 sm:p-4">
  <div class="glass-panel rounded-t-3xl sm:rounded-3xl w-full max-w-lg p-4 sm:p-5 flex flex-col space-y-3 text-xs border border-purple-500/40">
    <div class="flex justify-between items-center border-b border-gray-800 pb-2">
      <h3 class="font-bold text-yellow-400 text-sm"> تصنيع الكميات الخاصة (VIP)</h3>
      <button type="button" onclick="closeVipModal()" class="bg-neutral-800 text-gray-300 w-7 h-7 rounded-full">✕</button>
    </div>
    <form onsubmit="sendVipRequestWhatsApp(event)" class="space-y-2.5">
      <input id="vipMerchantName" required class="w-full bg-black border border-gray-700 rounded-xl px-3 py-2 text-white" placeholder="👤 اسم التاجر / الشركة والمحافظة..."/>
      <div class="grid grid-cols-2 gap-2">
        <select id="vipCat" class="bg-black border border-gray-700 rounded-xl px-2.5 py-2 text-white">
          <option value="نسواني">نسواني</option>
          <option value="رجالي">رجالي</option>
          <option value="ولادي">ولادي</option>
          <option value="ب.ب">ب.ب</option>
          <option value="تشكيلة متنوعة">تشكيلة متنوعة</option>
        </select>
        <input id="vipQty" type="number" min="50" value="100" required class="w-full bg-black border border-gray-700 rounded-xl px-3 py-2 text-yellow-400 font-bold" placeholder="الكمية بالقطع"/>
      </div>
      <input id="vipPhone" type="tel" required dir="ltr" class="w-full bg-black border border-gray-700 rounded-xl px-3 py-2 text-right text-white" placeholder="📞 رقم الهاتف / واتساب للتواصل..."/>
      <textarea id="vipDetails" rows="3" required class="w-full bg-black border border-gray-700 rounded-xl px-3 py-2 text-white resize-none" placeholder="📝 مواصفات الموديل أو القماش المطلوب..."></textarea>
      <button type="submit" class="w-full gold-gradient-bg font-bold py-2.5 rounded-xl">📲 إرسال طلب التصنيع لإدارة المعمل</button>
    </form>
  </div>
</div>

<!-- Partner Authentication Modal -->
<div id="partnerAuthModal" class="fixed inset-0 bg-black/90 hidden items-center justify-center z-50 p-3">
  <div class="glass-panel rounded-3xl w-full max-w-md p-5 space-y-3.5 text-xs border border-emerald-500/40 shadow-2xl">
    <div class="flex justify-between items-center border-b border-gray-800 pb-2">
      <h3 class="font-bold text-yellow-400 text-sm">🤝 بوابة شركاء النجاح المعتمدين</h3>
      <button type="button" onclick="closePartnerAuthModal()" class="bg-neutral-800 text-gray-300 w-7 h-7 rounded-full font-bold">✕</button>
    </div>
    <div class="bg-emerald-950/30 border border-emerald-500/30 rounded-2xl p-3.5 text-gray-200 leading-relaxed text-center">
      <p class="font-bold text-amber-200 text-xs">"أدخل كود الشريك السري الخاص بك (8 خانات) للدخول إلى حسابك ورصد أرباحك التصاعدية (3% - 4% - 5%)."</p>
    </div>
    <form onsubmit="verifyPartnerPasscode(event)" class="space-y-2.5 pt-1">
      <label class="block text-yellow-400 font-bold text-[11px]">🔑 كود الشريك (8 خانات):</label>
      <input id="partnerPasscodeInput" type="text" required dir="ltr" class="w-full bg-black border border-yellow-500/40 rounded-xl px-3 py-2 text-center text-white font-mono text-base tracking-widest uppercase" placeholder="مثال: A7K9M2X4" maxlength="8"/>
      <div class="flex gap-2 pt-1">
        <button id="partnerAuthSubmitBtn" type="submit" class="flex-1 gold-gradient-bg font-bold py-2.5 rounded-xl text-xs">دخول إلى حسابي 🔓</button>
        <button type="button" onclick="closePartnerAuthModal()" class="bg-neutral-800 text-white px-4 py-2.5 rounded-xl font-bold text-xs">إغلاق</button>
      </div>
    </form>
  </div>
</div>

<!-- Affiliate Modal -->
<div id="affiliateModal" class="fixed inset-0 bg-black/90 hidden items-end sm:items-center justify-center z-50 p-0 sm:p-4">
  <div class="glass-panel rounded-t-3xl sm:rounded-3xl w-full max-w-xl max-h-[92vh] p-4 sm:p-5 flex flex-col overflow-hidden border border-yellow-500/40 text-xs">
    <div class="flex justify-between items-center border-b border-gray-800 pb-2.5 mb-2.5">
      <div>
        <h3 class="font-bold text-yellow-400 text-sm">💡 حساب الشريك المعتمد وعمولاتك</h3>
        <p id="partnerWelcomeName" class="text-[10px] text-emerald-400 font-bold"></p>
      </div>
      <div class="flex items-center gap-1.5">
        <button type="button" onclick="logoutPartnerAccount()" class="bg-red-900/50 text-red-300 border border-red-500/30 px-2.5 py-1 rounded-lg text-[10px] font-bold">تبديل الحساب 🔒</button>
        <button type="button" onclick="closeAffiliateModal()" class="bg-neutral-800 text-gray-300 w-7 h-7 rounded-full">✕</button>
      </div>
    </div>
    <div class="overflow-y-auto flex-1 space-y-2.5 pl-1">
      <div class="glossy-box p-2.5 rounded-xl border border-yellow-500/30 space-y-2">
        <div class="flex justify-between items-center">
          <span class="font-bold text-yellow-400 text-[11px]">🏆 مستواك ونسبة عمولتك الحالية:</span>
          <span id="partnerRankBadge" class="bg-amber-700/40 text-amber-200 border border-amber-400/40 px-2.5 py-0.5 rounded-full font-bold text-[10px]">🥉 شريك برونزي (3%)</span>
        </div>
        <div class="grid grid-cols-3 gap-1.5 text-center text-[9.5px]">
          <div class="bg-black/60 p-1.5 rounded-lg border border-amber-700/40">
            <strong class="text-amber-400 block">🥉 برونزي</strong>
            <span class="text-gray-400">حتى $2,000</span>
            <span class="text-emerald-400 block font-black text-[11px]">نسبة 3%</span>
          </div>
          <div class="bg-black/60 p-1.5 rounded-lg border border-yellow-500/40">
            <strong class="text-yellow-300 block">🥇 ذهبي</strong>
            <span class="text-gray-400">فوق $2,000</span>
            <span class="text-emerald-400 block font-black text-[11px]">نسبة 4%</span>
          </div>
          <div class="bg-black/60 p-1.5 rounded-lg border border-sky-400/40">
            <strong class="text-sky-300 block">💎 ماسي</strong>
            <span class="text-gray-400">فوق $5,000</span>
            <span class="text-emerald-400 block font-black text-[11px]">نسبة 5%</span>
          </div>
        </div>
        <div class="w-full bg-black rounded-full h-2 overflow-hidden border border-gray-800">
          <div id="partnerRankProgress" class="gold-gradient-bg h-full transition-all duration-500" style="width: 10%"></div>
        </div>
        <p id="partnerNextRankHint" class="text-[9.5px] text-gray-300 text-center font-semibold">حقق مبيعات مؤكدة لرفع نسبة عمولتك تلقائياً!</p>
      </div>

      <div class="glossy-box p-2.5 rounded-xl space-y-2">
        <p class="font-bold text-yellow-400 text-[10px]">🔑 كود الدخول السري والرابط المختصر الآمن:</p>
        <div class="bg-black/80 p-2 rounded-lg border border-gray-800 space-y-1.5">
          <div class="flex justify-between items-center text-[10px]">
            <span class="text-gray-400">كود الدخول السري:</span>
            <strong id="myAffiliateCode" class="text-yellow-400 font-mono text-sm"></strong>
          </div>
          <div class="flex justify-between items-center text-[10px]">
            <span class="text-gray-400">الختم المختصر في الرابط:</span>
            <strong id="myShortRefCode" class="text-emerald-400 font-mono text-xs"></strong>
          </div>
          <div class="flex gap-1 pt-0.5">
            <input id="affiliateLinkInput" readonly class="w-full bg-neutral-950 border border-gray-800 rounded px-2 py-1 text-emerald-400 text-[10px]" dir="ltr"/>
            <button type="button" onclick="copyAffiliateLink()" class="bg-emerald-700 text-white font-bold px-2.5 py-1 rounded text-[10px] flex-shrink-0">نسخ الرابط 📋</button>
          </div>
        </div>
      </div>

      <div class="glossy-box p-2.5 rounded-xl space-y-1.5 border border-emerald-500/30">
        <div class="flex justify-between items-center">
          <span class="font-bold text-emerald-400 text-[10px]">📊 سجل أرباحك وعمولاتك المعتمدة:</span>
          <span id="dashboardStampBadge" class="bg-black text-yellow-400 px-2 py-0.5 rounded font-mono text-[10px]">---</span>
        </div>
        <div class="grid grid-cols-2 gap-1.5 text-center">
          <div class="bg-sky-950/20 border border-sky-500/25 p-1.5 rounded-lg">
            <span class="text-[9px] text-sky-300 block">📦 إجمالي المبيعات المعتمدة</span>
            <strong id="dashTotalSales" class="text-sky-400 font-bold">$0.00</strong>
          </div>
          <div class="bg-emerald-950/20 border border-emerald-500/25 p-1.5 rounded-lg">
            <span class="text-[9px] text-emerald-300 block">✨ صافي أرباحك المتاحة</span>
            <strong id="dashConfirmedComm" class="text-emerald-400 font-bold">$0.00</strong>
          </div>
        </div>
        <div id="dashOrdersLog" class="max-h-28 overflow-y-auto space-y-1 text-[10px] text-gray-300 bg-black/50 p-1.5 rounded-lg"></div>
      </div>

      <div class="glossy-box p-3 rounded-xl space-y-2 border border-yellow-500/30 bg-neutral-900/90">
        <p class="font-bold text-yellow-400 text-[11px]">💳 طلب سحب الأرباح المتاحة:</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-1.5">
          <input id="payoutReceiverName" class="bg-black border border-gray-700 rounded-lg px-2.5 py-1.5 text-white text-[10px]" placeholder="اسم المستلم الثلاثي..."/>
          <input id="payoutCity" class="bg-black border border-gray-700 rounded-lg px-2.5 py-1.5 text-white text-[10px]" placeholder="المحافظة ورقم الهاتف..."/>
          <input id="payoutAccountInfo" class="bg-black border border-gray-700 rounded-lg px-2.5 py-1.5 text-white text-[10px]" placeholder="رقم الشام كاش أو بيانات الحوالة..."/>
        </div>
        <button type="button" onclick="requestWithdrawalWhatsApp()" class="w-full gold-gradient-bg font-bold py-2 rounded-xl text-[11px]">📲 إرسال طلب السحب للإدارة عبر واتساب</button>
      </div>
    </div>
    <div class="flex gap-2 pt-2.5 border-t border-gray-800 mt-2">
      <button type="button" onclick="closeAffiliateModal()" class="w-full bg-neutral-800 text-white px-4 py-2 rounded-xl font-bold">إغلاق</button>
    </div>
  </div>
</div>

<!-- about -->
<div id="aboutModal" class="fixed inset-0 bg-black/85 hidden items-center justify-center z-50 p-3">
  <div class="glass-panel rounded-3xl w-full max-w-md p-5 space-y-3.5 text-xs text-gray-200 border border-yellow-500/30">
    <div class="flex justify-between items-center border-b border-gray-800 pb-2">
      <h3 class="font-bold text-yellow-400 text-sm">✨ من نحن - Fashion World</h3>
      <button type="button" onclick="closeAboutModal()" class="bg-neutral-800 text-gray-300 w-7 h-7 rounded-full font-bold">✕</button>
    </div>
    <p class="font-bold text-white leading-relaxed text-center">المعقل الرئيسي لتجارة وتصنيع الألبسة بالجملة (نسواني، رجالي، ولادي، أطفال) في قلب دمشق - الحريقة. نضمن لتجارنا أعلى جودة وأفضل أسعار الجملة مع شحن آمن لكافة المحافظات.</p>
    <div class="space-y-2 pt-1">
      <p class="text-yellow-400 font-bold text-center text-[11px]">📱 تواصل معنا عبر منصاتنا الرسمية:</p>
      <div class="grid grid-cols-2 gap-2">
        <a href="https://wa.me/963996311804?text=✨%20*استفسار%20تجاري*" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center gap-2 bg-emerald-900/40 hover:bg-emerald-800/60 border border-emerald-500/40 text-emerald-300 font-bold py-2.5 px-3 rounded-xl transition">
          <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824z"/></svg>
          <span>واتساب</span>
        </a>
        <a href="https://www.facebook.com/profile.php?id=100094030465332" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center gap-2 bg-blue-900/40 hover:bg-blue-800/60 border border-blue-500/40 text-blue-300 font-bold py-2.5 px-3 rounded-xl transition">
          <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
          <span>فيسبوك</span>
        </a>
        <a href="https://www.instagram.com/fashion.world_na?dlrf=cDB4aDlyZ3h4eWQ2" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center gap-2 bg-pink-900/40 hover:bg-pink-800/60 border border-pink-500/40 text-pink-300 font-bold py-2.5 px-3 rounded-xl transition">
          <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
          <span>إنستغرام</span>
        </a>
        <a href="https://t.me/mi2cS3jE8qA1Y2Zk" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center gap-2 bg-sky-900/40 hover:bg-sky-800/60 border border-sky-500/40 text-sky-300 font-bold py-2.5 px-3 rounded-xl transition">
          <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
          <span>تيليجرام</span>
        </a>
      </div>
    </div>
    <button type="button" onclick="closeAboutModal()" class="w-full bg-neutral-800 text-white font-bold py-2 rounded-xl mt-2">إغلاق النافذة</button>
  </div>
</div>

<!--  -->
<div id="floatingMiniCart" class="fixed bottom-3 left-1/2 -translate-x-1/2 z-40 w-[95%] max-w-3xl hidden">
  <div class="glass-panel rounded-2xl p-2.5 sm:px-4 border border-yellow-500/40 shadow-2xl flex items-center justify-between gap-2 bg-neutral-950/95">
    <div class="flex items-center gap-2.5 min-w-0 cursor-pointer" onclick="playUiSound('tap'); openCart()">
      <div class="relative gold-gradient-bg w-10 h-10 rounded-xl flex items-center justify-center text-base font-black flex-shrink-0">
        🛒<span id="floatingCartBadge" class="absolute -top-1.5 -right-1.5 bg-red-600 text-white text-[9px] font-black px-1.5 py-0.5 rounded-full border border-black">0</span>
      </div>
      <div class="min-w-0">
        <div class="flex items-center gap-1.5 text-xs font-bold text-white truncate">
          <span id="floatingCartTotal" class="text-yellow-400 font-black">$0</span>
          <span class="text-gray-500">|</span>
          <span id="floatingCartMeta" class="text-[10px] text-sky-300 truncate">0 سيرية (~0 كغ)</span>
        </div>
        <p class="text-[9.5px] text-emerald-400 font-semibold truncate">أضف موديلات بقيمة $400 للحصول على حسم جملة 2% فوراً</p>
      </div>
    </div>
    <button type="button" onclick="playUiSound('tap'); openCart()" class="gold-gradient-bg font-black px-3 py-2 rounded-xl text-[10px] sm:text-xs shadow flex-shrink-0">عرض السلة ⬅️</button>
  </div>
</div>

<!--   -->
<div id="cartModal" class="fixed inset-0 bg-black/80 hidden justify-end z-50" onclick="closeCart()">
  <div class="glass-panel w-full max-w-md h-full p-4 flex flex-col justify-between overflow-hidden text-xs border-r border-yellow-500/30 drawer-anim" onclick="event.stopPropagation()">
    <div class="flex justify-between items-center border-b border-gray-800 pb-2.5">
      <div class="flex items-center gap-2">
        <h3 class="font-bold text-yellow-400 text-sm">🛒 درج طلبية الجملة الذكي</h3>
        <button type="button" onclick="clearEntireCart()" class="text-[10px] text-red-400 bg-red-950/40 px-2 py-0.5 rounded border border-red-500/30 font-bold">تفريغ</button>
      </div>
      <button type="button" onclick="closeCart()" class="bg-neutral-800 text-gray-300 w-7 h-7 rounded-full font-bold">✕</button>
    </div>
    <div id="cartItems" class="overflow-y-auto flex-1 space-y-2 my-2.5 pr-1"></div>
    <div class="border-t border-gray-800 pt-2.5 space-y-2">
      <div class="glossy-box p-2 rounded-xl border border-sky-500/30 space-y-1">
        <p class="text-[10px] text-sky-300 font-bold">📦 حاسبة الطرد والشحن:</p>
        <div class="grid grid-cols-3 gap-1.5 text-center text-[10px]">
          <div class="bg-black/70 py-1 rounded border border-gray-800"><span class="text-gray-400 block">إجمالي القطع</span><strong id="cartSummaryPieces" class="text-white font-bold">0 قطعة</strong></div>
          <div class="bg-black/70 py-1 rounded border border-gray-800"><span class="text-gray-400 block">عدد السيريات</span><strong id="cartSummaryPacks" class="text-yellow-400 font-bold">0 سيرية</strong></div>
          <div class="bg-black/70 py-1 rounded border border-gray-800"><span class="text-gray-400 block">الوزن التقريبي</span><strong id="cartSummaryWeight" class="text-emerald-400 font-bold">0 كغ</strong></div>
        </div>
      </div>
      <div class="bg-emerald-950/40 p-2 rounded-xl border border-emerald-500/40 text-[10px] text-emerald-300 font-bold flex flex-col gap-0.5">
        <span>🎉 عند بلوغ فاتورتك 400$ فأكثر، يتم حسم 2% تلقائياً!</span>
        <span id="tierDiscountNotice" class="hidden text-yellow-300">✨ تم تطبيق حسم الجملة (2%)!</span>
      </div>
      <div class="flex justify-between font-bold bg-black/60 p-2 rounded-xl border border-yellow-500/25">
        <span>الإجمالي النهائي الصافي:</span>
        <span id="cartTotal" class="text-yellow-400">$0</span>
      </div>
      <div class="space-y-1.5">
        <input id="buyerName" oninput="saveBuyerInfoToStorage()" class="w-full bg-black border border-gray-700 rounded-xl px-3 py-1.5 text-white" placeholder="👤 اسم العميل / المحل التجاري (إلزامي)..."/>
        <input id="buyerAddress" oninput="saveBuyerInfoToStorage()" class="w-full bg-black border border-gray-700 rounded-xl px-3 py-1.5 text-white" placeholder="📍 المحافظة ومكتب الشحن (إلزامي)..."/>
        <input id="buyerPhone" type="tel" dir="ltr" oninput="saveBuyerInfoToStorage()" class="w-full bg-black border border-gray-700 rounded-xl px-3 py-1.5 text-right text-white" placeholder="📞 الهاتف / واتساب (إلزامي)..."/>
        <textarea id="buyerNotes" rows="1" oninput="saveBuyerInfoToStorage()" class="w-full bg-black border border-gray-700 rounded-xl px-3 py-1.5 text-white resize-none" placeholder="📝 ملاحظات إضافية..."></textarea>
      </div>
      <div class="grid grid-cols-2 gap-1.5 pt-0.5">
        <button type="button" onclick="sendOrderWhatsApp()" class="bg-emerald-600 text-white font-bold py-2.5 rounded-xl">📲 إرسال عبر واتساب</button>
        <button type="button" onclick="printWholesaleInvoice()" class="gold-gradient-bg font-bold py-2.5 rounded-xl">🖨️ طباعة فاتورة PDF</button>
      </div>
    </div>
  </div>
</div>

<footer class="bg-black/95 border-t border-yellow-500/20 py-4 px-4 text-center text-[11px] text-gray-400 mt-4 space-y-2.5">
  <div class="flex flex-wrap items-center justify-center gap-2">
    <a href="https://wa.me/963996311804?text=✨%20*استفسار%20تجاري*" target="_blank" rel="noopener noreferrer" class="bg-emerald-950/60 text-emerald-300 border border-emerald-500/30 px-3 py-1 rounded-lg font-bold flex items-center gap-1.5">
      <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824z"/></svg>
      <span>واتساب</span>
    </a>
    <a href="https://www.facebook.com/profile.php?id=100094030465332" target="_blank" rel="noopener noreferrer" class="bg-blue-950/60 text-blue-300 border border-blue-500/30 px-3 py-1 rounded-lg font-bold flex items-center gap-1.5">
      <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
      <span>فيسبوك</span>
    </a>
    <a href="https://www.instagram.com/fashion.world_na?dlrf=cDB4aDlyZ3h4eWQ2" target="_blank" rel="noopener noreferrer" class="bg-pink-950/60 text-pink-300 border border-pink-500/30 px-3 py-1 rounded-lg font-bold flex items-center gap-1.5">
      <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
      <span>إنستغرام</span>
    </a>
    <a href="https://t.me/mi2cS3jE8qA1Y2Zk" target="_blank" rel="noopener noreferrer" class="bg-sky-950/60 text-sky-300 border border-sky-500/30 px-3 py-1 rounded-lg font-bold flex items-center gap-1.5">
      <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
      <span>تيليجرام</span>
    </a>
  </div>
  <p class="font-cinzel font-bold gold-gradient-text m-0">FASHION WORLD - DAMASCUS AL-HARIQA</p>
</footer>

<?php $appJsVer = @filemtime(__DIR__ . '/assets/app.js') ?: time(); ?>
<script src="/assets/app.js?v=<?= $appJsVer ?>"></script>
</body>
</html>
