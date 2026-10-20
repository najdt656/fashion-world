<?php require __DIR__ . '/../../src/bootstrap.php';
$csrf = issue_csrf_token();
$isAuth = !empty($_SESSION['admin_id']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>لوحة الإدارة | FASHION WORLD</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/styles.css">
</head>
<body class="min-h-screen" id="mainBody">

<div id="loginView" class="<?= $isAuth ? 'hidden' : '' ?> min-h-screen flex items-center justify-center p-4">
  <div class="glass-panel rounded-3xl w-full max-w-sm p-6 space-y-4">
    <h1 class="text-center font-cinzel text-xl font-black gold-gradient-text">FASHION WORLD</h1>
    <p class="text-center text-yellow-400 font-bold text-sm">🔐 بوابة المصادقة الإدارية</p>
    <form id="loginForm" class="space-y-3">
      <input id="loginEmail" type="email" required class="w-full bg-black border border-gray-700 rounded-xl px-3 py-2 text-white text-sm" dir="ltr" placeholder="Email"/>
      <input id="loginPass" type="password" required class="w-full bg-black border border-gray-700 rounded-xl px-3 py-2 text-white text-sm" dir="ltr" placeholder="Password"/>
      <button id="loginBtn" type="submit" class="w-full gold-gradient-bg font-bold py-2.5 rounded-xl">دخول 👑</button>
      <p id="loginErr" class="text-red-400 text-xs text-center hidden"></p>
    </form>
    <a href="/" class="block text-center text-gray-400 text-xs">→ العودة للموقع</a>
  </div>
</div>

<div id="adminView" class="<?= $isAuth ? '' : 'hidden' ?> max-w-7xl mx-auto p-3 sm:p-6 space-y-4">
  <header class="glass-panel rounded-2xl p-3 flex justify-between items-center">
    <div>
      <h1 class="font-cinzel font-black gold-gradient-text text-lg">FASHION WORLD ADMIN</h1>
      <p class="text-[10px] text-gray-400">لوحة الإدارة المركزية</p>
    </div>
    <div class="flex gap-2">
      <a href="/" class="bg-neutral-800 text-white px-3 py-1.5 rounded-lg text-xs font-bold">← الموقع</a>
      <button onclick="logout()" class="bg-red-800 text-white px-3 py-1.5 rounded-lg text-xs font-bold">خروج 🔒</button>
    </div>
  </header>

  <nav class="glass-panel rounded-2xl p-1.5 flex flex-wrap gap-1.5">
    <button data-tab="products" class="tab-btn gold-gradient-bg font-bold px-3 py-1.5 rounded-lg text-xs">📦 المنتجات</button>
    <button data-tab="orders" class="tab-btn glossy-box text-yellow-300 px-3 py-1.5 rounded-lg text-xs">🧾 الطلبات</button>
    <button data-tab="comments" class="tab-btn glossy-box text-yellow-300 px-3 py-1.5 rounded-lg text-xs">💬 التعليقات</button>
    <button data-tab="marketers" class="tab-btn glossy-box text-yellow-300 px-3 py-1.5 rounded-lg text-xs">👥 الشركاء</button>
    <button data-tab="settings" class="tab-btn glossy-box text-yellow-300 px-3 py-1.5 rounded-lg text-xs">⚙️ الإعدادات</button>
  </nav>

  <section id="tab-products" class="tab-content">
    <div class="flex justify-between mb-3">
      <h2 class="font-bold text-yellow-400">📦 إدارة المنتجات</h2>
      <button onclick="showProductForm()" class="gold-gradient-bg font-bold px-3 py-1.5 rounded-xl text-xs">➕ منتج جديد</button>
    </div>
    <div id="productsList" class="grid gap-2"></div>
    <div id="productForm" class="hidden glass-panel rounded-2xl p-4 mt-4 space-y-2"></div>
  </section>

  <section id="tab-orders" class="tab-content hidden">
    <h2 class="font-bold text-yellow-400 mb-3">🧾 الطلبات</h2>
    <div id="ordersList" class="space-y-2"></div>
  </section>

  <section id="tab-comments" class="tab-content hidden">
    <h2 class="font-bold text-yellow-400 mb-3">💬 التعليقات</h2>
    <div id="commentsList" class="space-y-2"></div>
  </section>

  <section id="tab-marketers" class="tab-content hidden">
    <h2 class="font-bold text-yellow-400 mb-3">👥 الشركاء</h2>
    <div class="glass-panel p-3 rounded-xl flex gap-2">
      <input id="mkName" class="flex-1 bg-black border border-gray-700 rounded-lg px-3 py-2 text-white text-sm" placeholder="اسم الشريك..."/>
      <button onclick="createMarketer()" class="gold-gradient-bg font-bold px-4 py-2 rounded-lg text-xs">توليد كود ✨</button>
    </div>
    <div id="marketersList" class="space-y-2 mt-3"></div>
  </section>

  <section id="tab-settings" class="tab-content hidden">
    <h2 class="font-bold text-yellow-400 mb-3">⚙️ الإعدادات</h2>
    <div class="glass-panel rounded-2xl p-4 space-y-3 text-sm">
      <div>
        <label class="block text-yellow-300 font-bold text-xs mb-1">💱 سعر الصرف (ل.س / $)</label>
        <input id="setRate" type="number" class="w-full bg-black border border-gray-700 rounded-lg px-3 py-2 text-white"/>
      </div>
      <div>
        <label class="block text-yellow-300 font-bold text-xs mb-1">🖼️ صورة الغلاف</label>
        <input id="setBannerFile" type="file" accept="image/*" class="w-full text-gray-300 text-xs"/>
        <img id="setBannerPreview" class="mt-2 w-40 h-24 object-cover rounded-lg hidden"/>
      </div>
      <button onclick="saveSettings()" class="w-full gold-gradient-bg font-bold py-2 rounded-xl text-sm">حفظ ✅</button>
    </div>
  </section>
</div>

<script>window.FW_CSRF = <?= json_encode($csrf) ?>;</script>
<script src="/admin/assets/admin.js"></script>
</body>
</html>
