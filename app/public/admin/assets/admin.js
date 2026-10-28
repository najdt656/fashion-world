'use strict';

const CSRF = window.FW_CSRF;
// const state = { products: [], currentEdit: null };
const state = { products: [], currentEdit: null, deletedExtras: new Set() };

// ===== tools  =====
function qs(id){ return document.getElementById(id); }

function toggleModal(id, show) {
  const el = qs(id);
  if (!el) return;
  el.classList.toggle('hidden', !show);
  el.classList.toggle('flex', show);
}

function esc(s){
  return s==null ? '' : String(s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

const STOCK_LABELS = {
  AVAILABLE: { ar: 'متوفر',        emoji: '✅', cls: 'text-emerald-400' },
  LOW:       { ar: 'كمية محدودة',  emoji: '⚠️', cls: 'text-amber-400'  },
  PREORDER:  { ar: 'تثبيت حجز',    emoji: '🔥', cls: 'text-purple-400' },
  SOLD_OUT:  { ar: 'نفدت الكمية',  emoji: '❌', cls: 'text-gray-400'   },
};

function stockLabel(value, withEmoji = true) {
  const info = STOCK_LABELS[value] || { ar: value || '—', emoji: '' };
  return withEmoji ? `${info.emoji} ${info.ar}`.trim() : info.ar;
}

// =====  =====
async function api(path, opts = {}) {
  const headers = { 'X-CSRF-Token': CSRF, ...(opts.headers || {}) };
  const r = await fetch(path, {
    credentials: 'same-origin',
    ...opts,
    headers,
  });
  const d = await r.json().catch(() => ({}));
  if (!r.ok) throw new Error(d.error || `HTTP ${r.status}`);
  return d;
}

// =====================================================
// Authentication
// =====================================================
qs('loginForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const btn = qs('loginBtn');
  const err = qs('loginErr');
  btn.disabled = true; btn.innerText = '...';
  err.classList.add('hidden');
  try {
    await api('/api/admin/auth.php?action=login', {
      method: 'POST',
      body: JSON.stringify({
        email: qs('loginEmail').value.trim(),
        password: qs('loginPass').value,
      }),
    });
    location.reload();
  } catch (ex) {
    err.innerText = ex.message;
    err.classList.remove('hidden');
  } finally {
    btn.disabled = false; btn.innerText = 'دخول 🔑';
  }
});

async function logout(){
  try { await api('/api/admin/auth.php?action=logout', { method: 'POST' }); }
  finally { location.reload(); }
}
window.logout = logout;

// =====================================================
// Tabs
// =====================================================
document.querySelectorAll('.tab-btn').forEach((b) => {
  b.addEventListener('click', () => {
    const t = b.dataset.tab;
    document.querySelectorAll('.tab-btn').forEach((x) =>
      x.className = 'tab-btn glossy-box text-yellow-300 px-3 py-1.5 rounded-lg text-xs'
    );
    b.className = 'tab-btn gold-gradient-bg font-bold px-3 py-1.5 rounded-lg text-xs';
    document.querySelectorAll('.tab-content').forEach((c) => c.classList.add('hidden'));
    qs('tab-' + t)?.classList.remove('hidden');
    if (t === 'products')  loadProducts();
    if (t === 'orders')    loadOrders();
    if (t === 'comments')  loadComments();
    if (t === 'marketers') loadMarketers();
    if (t === 'settings')  loadSettings();
  });
});

// =====================================================
// products
// =====================================================
async function loadProducts() {
  try {
    const d = await api('/api/admin/products.php');
    state.products = d.products || [];
    if (state.products.length === 0) {
      qs('productsList').innerHTML =
        '<p class="text-gray-400 text-sm text-center py-6">لا توجد منتجات. اضغط "منتج جديد" للبدء.</p>';
      return;
    }
    qs('productsList').innerHTML = state.products.map((p) => `
      <div class="glass-panel p-3 rounded-xl flex justify-between items-center gap-2 text-sm">
        <div class="flex items-center gap-2 min-w-0">
          ${p.main_image
            ? `<img src="${esc(p.main_image)}" class="w-12 h-12 object-cover rounded"/>`
            : `<div class="w-12 h-12 bg-neutral-800 rounded flex items-center justify-center text-xl">📦</div>`}
          <div class="min-w-0">
            <p class="font-bold text-white truncate">${esc(p.name)}</p>
            <p class="text-[10px] text-yellow-400">
              ${esc(p.category)} • $${p.price_usd} • سيرية ${p.min_qty}
            </p>
            <p class="text-[10px] ${STOCK_LABELS[p.stock_status]?.cls || 'text-gray-400'}">
              ${stockLabel(p.stock_status)}
            </p>
          </div>
        </div>
        <div class="flex gap-1">
          <button onclick="showProductForm(${p.id})" class="bg-blue-700 text-white px-2 py-1 rounded text-xs" title="تعديل">✏️</button>
          <button onclick="deleteProduct(${p.id})" class="bg-red-700 text-white px-2 py-1 rounded text-xs" title="حذف">🗑️</button>
        </div>
      </div>
    `).join('');
  } catch (e) {
    qs('productsList').innerHTML = `<p class="text-red-400">${e.message}</p>`;
  }
}
window.loadProducts = loadProducts;

async function showProductForm(id = null) {
  let p = null;
  if (id) {
    try {
      const d = await api('/api/admin/products.php?id=' + id);
      p = d.product;
    } catch (e) {
      alert('فشل تحميل المنتج: ' + e.message);
      return;
    }
  }
  state.currentEdit = p;
  state.deletedExtras = new Set();

  const titleEl = qs('productFormTitle');
  if (titleEl) titleEl.innerText = id ? '✏️ تعديل المنتج' : '➕ نشر منتج جديد';

  const mainImg = p && p.main_image ? p.main_image : '';
  const extras = p && Array.isArray(p.extras) ? p.extras : [];

  qs('productForm').innerHTML = `
    <form onsubmit="saveProduct(event)" class="space-y-2 text-sm">
      <input id="fName" required class="w-full bg-black border border-gray-700 rounded px-3 py-2 text-white"
             placeholder="اسم الموديل" value="${p ? esc(p.name) : ''}"/>
      <div class="grid grid-cols-2 gap-2">
        <select id="fCat" class="bg-black border border-gray-700 rounded px-2 py-2 text-white">
          ${['نسواني','رجالي','ولادي','ب.ب'].map((c) =>
            `<option value="${c}" ${p && p.category === c ? 'selected' : ''}>${c}</option>`
          ).join('')}
        </select>
        <input id="fPrice" required type="number" step="0.01" min="0.01"
               class="bg-black border border-gray-700 rounded px-3 py-2 text-white"
               placeholder="السعر $" value="${p ? p.price_usd : ''}"/>
        <input id="fMinQty" required type="number" min="1"
               class="bg-black border border-gray-700 rounded px-3 py-2 text-white"
               placeholder="حجم السيرية" value="${p ? p.min_qty : 10}"/>
        <input id="fPack" required class="bg-black border border-gray-700 rounded px-3 py-2 text-white"
               placeholder="القياسات (M/L/XL)" value="${p ? esc(p.pack || '') : ''}"/>
        <input id="fColors" class="bg-black border border-gray-700 rounded px-3 py-2 text-white"
               placeholder="الألوان (افصل بفاصلة)" value="${p ? esc(p.colors || '') : ''}"/>
        <input id="fWeight" type="number" min="50" max="3000"
               class="bg-black border border-gray-700 rounded px-3 py-2 text-white"
               placeholder="الوزن/غرام" value="${p ? p.piece_weight : 350}"/>
        <select id="fStock" class="bg-black border border-gray-700 rounded px-2 py-2 text-white">
          ${[
            ['AVAILABLE', '✅ متوفر بالمستودع'],
            ['LOW',       '⚠️ كمية محدودة'],
            ['PREORDER',  '🔥 تثبيت حجز مسبق'],
            ['SOLD_OUT',  '❌ نفدت الكمية']
          ].map(([value, label]) =>
            `<option value="${value}" ${p && p.stock_status === value ? 'selected' : ''}>${label}</option>`
          ).join('')}
        </select>
        <input id="fFomoLeft" type="number" min="0"
               class="bg-black border border-gray-700 rounded px-3 py-2 text-white"
               placeholder="سيريات متبقية" value="${p ? p.fomo_left : 0}"/>
      </div>
      <input id="fFomoBooked" class="w-full bg-black border border-gray-700 rounded px-3 py-2 text-white"
             placeholder="عبارة التشويق (اختياري)" value="${p ? esc(p.fomo_booked || '') : ''}"/>
      <textarea id="fDesc" rows="2"
                class="w-full bg-black border border-gray-700 rounded px-3 py-2 text-white"
                placeholder="الوصف / نوع القماش">${p ? esc(p.admin_desc || '') : ''}</textarea>
      <div class="flex gap-3 items-center text-xs">
        <label><input type="checkbox" id="fDisc" ${p && p.has_discount ? 'checked' : ''}/> حسم</label>
        <input id="fDiscPct" type="number" min="0" max="90"
               value="${p ? p.discount_percent : 0}"
               class="w-16 bg-black border border-gray-700 rounded px-2 py-1 text-white"/>
        <label><input type="checkbox" id="fClear" ${p && p.is_clearance ? 'checked' : ''}/> تصفية</label>
      </div>

      <!-- -->
      <div class="border-t border-gray-800 pt-3 mt-2">
        <p class="text-yellow-400 font-bold text-xs mb-2">📷 إدارة الصور</p>

        <!-- Mine Image  -->
        <div class="mb-3">
          <label class="block text-xs text-yellow-300 mb-1">الصورة الرئيسية:</label>
          <div class="flex items-start gap-2">
            <div id="mainImgPreview" class="w-20 h-20 rounded-lg bg-neutral-800 border border-gray-700 overflow-hidden flex items-center justify-center flex-shrink-0">
              ${mainImg
                ? `<img src="${esc(mainImg)}" class="w-full h-full object-cover"/>`
                : `<span class="text-2xl">📷</span>`}
            </div>
            <div class="flex-1 min-w-0">
              <input id="fImg" type="file" accept="image/*" class="w-full text-gray-300 text-xs"/>
              <p class="text-[10px] text-gray-500 mt-1">
                ${mainImg ? 'اختر صورة جديدة للاستبدال (اترك فارغًا للإبقاء على الحالية)' : 'مطلوبة عند الإنشاء'}
              </p>
            </div>
          </div>
        </div>

        <!-- More Images -->
        <div>
          <label class="block text-xs text-yellow-300 mb-1">
            الصور الإضافية
            <span class="text-gray-500 font-normal">(<span id="extrasCount">${extras.length}</span>/4)</span>
          </label>
          <div id="extrasGrid" class="grid grid-cols-4 gap-2 mb-2">
            ${extras.map((ex) => `
              <div class="relative group" data-path="${esc(ex)}">
                <img src="${esc(ex)}" class="w-full aspect-square object-cover rounded-lg border border-gray-700"/>
                <button type="button" onclick="removeExtraImage(this)"
                        class="absolute -top-1 -right-1 bg-red-600 hover:bg-red-500 text-white w-5 h-5 rounded-full text-xs font-bold shadow flex items-center justify-center"
                        title="حذف هذه الصورة">✕</button>
              </div>
            `).join('')}
          </div>
          <input id="fExtras" type="file" accept="image/*" multiple class="w-full text-gray-300 text-xs"/>
          <p class="text-[10px] text-gray-500 mt-1">
            يمكنك إضافة حتى 4 صور. المتبقي: <span id="extrasRemaining">${Math.max(0, 4 - extras.length)}</span>
          </p>
        </div>
      </div>

      <div class="flex gap-2 pt-2 sticky bottom-0 bg-neutral-950/95 backdrop-blur py-2 -mx-4 px-4 sm:-mx-5 sm:px-5">
        <button type="submit" class="flex-1 gold-gradient-bg font-bold py-2.5 rounded-xl text-sm">
          ${p ? 'تحديث ✅' : 'حفظ ونشر ✅'}
        </button>
        <button type="button" onclick="closeProductFormModal()"
                class="bg-neutral-800 text-white px-4 rounded-xl text-sm font-bold">إلغاء</button>
      </div>
    </form>`;

  const mainFile = qs('fImg');
  if (mainFile) {
    mainFile.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = (ev) => {
        const prev = qs('mainImgPreview');
        if (prev) prev.innerHTML = `<img src="${ev.target.result}" class="w-full h-full object-cover"/>`;
      };
      reader.readAsDataURL(file);
    });
  }

  toggleModal('productFormModal', true);
  document.body.style.overflow = 'hidden';
}
window.showProductForm = showProductForm;


function removeExtraImage(btn) {
  const wrap = btn.closest('[data-path]');
  if (!wrap) return;
  const path = wrap.getAttribute('data-path');
  if (path) {
    state.deletedExtras.add(path);
    console.log('[admin] marked for deletion:', path);
  }
  wrap.remove();
  // تحديث العدادات
  const grid = qs('extrasGrid');
  const count = grid ? grid.children.length : 0;
  if (qs('extrasCount')) qs('extrasCount').innerText = count;
  if (qs('extrasRemaining')) qs('extrasRemaining').innerText = Math.max(0, 4 - count);
}
window.removeExtraImage = removeExtraImage;

function closeProductFormModal() {
  toggleModal('productFormModal', false);
  document.body.style.overflow = '';
  state.deletedExtras = new Set();
}
window.closeProductFormModal = closeProductFormModal;


async function saveProduct(e) {
  e.preventDefault();

  const fd = new FormData();
  fd.append('name',              qs('fName').value.trim());
  fd.append('category',          qs('fCat').value);
  fd.append('price_usd',         qs('fPrice').value);
  fd.append('min_qty',           qs('fMinQty').value);
  fd.append('pack',              qs('fPack').value.trim());
  fd.append('colors',            qs('fColors').value.trim());
  fd.append('piece_weight',      qs('fWeight').value || 350);
  fd.append('stock_status',      qs('fStock').value);
  fd.append('fomo_left',         qs('fFomoLeft').value || 0);
  fd.append('fomo_booked',       qs('fFomoBooked').value.trim());
  fd.append('admin_desc',        qs('fDesc').value.trim());
  fd.append('has_discount',      qs('fDisc').checked ? '1' : '0');
  fd.append('discount_percent',  qs('fDiscPct').value || 0);
  fd.append('is_clearance',      qs('fClear').checked ? '1' : '0');

  if (qs('fImg').files[0]) fd.append('main_image', qs('fImg').files[0]);

  if (state.deletedExtras && state.deletedExtras.size > 0) {
    state.deletedExtras.forEach((path) => fd.append('delete_extras[]', path));
  }

  const extrasEl = qs('fExtras');
  if (extrasEl && extrasEl.files.length) {
    for (let i = 0; i < Math.min(extrasEl.files.length, 4); i++) {
      fd.append('extras[]', extrasEl.files[i]);
    }
  }

  const id = state.currentEdit ? state.currentEdit.id : null;
  const url = id ? '/api/admin/products.php?id=' + id : '/api/admin/products.php';

  if (id) fd.append('_method', 'PUT');

  try {
    const r = await api(url, { method: 'POST', body: fd });
    closeProductFormModal();
    await loadProducts();
    alert(id ? '✅ تم التحديث بنجاح' : `✅ تم حفظ المنتج (رقم ${r.id})`);
  } catch (err) {
    alert('❌ فشل الحفظ:\n' + err.message);
  }
}
window.saveProduct = saveProduct;

async function deleteProduct(id) {
  if (!confirm('حذف المنتج نهائيًا؟')) return;
  try {
    await api('/api/admin/products.php?id=' + id, { method: 'DELETE' });
    await loadProducts();
  } catch (e) {
    alert('فشل الحذف: ' + e.message);
  }
}
window.deleteProduct = deleteProduct;

// =====================================================
// Orders
// =====================================================
async function loadOrders() {
  try {
    const d = await api('/api/admin/orders.php');
    qs('ordersList').innerHTML = d.orders.length === 0
      ? '<p class="text-gray-400 text-sm">لا توجد طلبات</p>'
      : d.orders.map((o) => `
        <div class="glass-panel p-3 rounded-xl text-sm space-y-1">
          <div class="flex justify-between items-center">
            <span class="font-bold text-white">#${o.id} — ${esc(o.buyer_name)}</span>
            <select onchange="updateOrderStatus(${o.id}, this.value)"
                    class="bg-black border border-gray-700 rounded px-2 py-0.5 text-yellow-300 text-xs">
              ${['PENDING','CONFIRMED','SHIPPED','CANCELLED'].map((s) =>
                `<option ${o.status === s ? 'selected' : ''}>${s}</option>`
              ).join('')}
            </select>
          </div>
          <p class="text-xs text-gray-300">📞 ${esc(o.buyer_phone)} | 📍 ${esc(o.buyer_address)}</p>
          <p class="text-xs text-yellow-400 font-bold">
            $${o.total_usd} • ${o.total_pieces} قطعة • ~${o.total_weight || 0} كغ
          </p>
          <p class="text-[10px] text-gray-500">
            ${o.created_at}${o.whatsapp_sent ? ' • ✅ واتساب' : ''}${o.referred_by ? ' • ref:' + o.referred_by : ''}
          </p>
        </div>
      `).join('');
  } catch (e) {
    qs('ordersList').innerHTML = `<p class="text-red-400">${e.message}</p>`;
  }
}

async function updateOrderStatus(id, status) {
  try {
    await api('/api/admin/orders.php', {
      method: 'PATCH',
      body: JSON.stringify({ id, status }),
    });
  } catch (e) {
    alert(e.message);
  }
}
window.updateOrderStatus = updateOrderStatus;

// =====================================================
// Comments
// =====================================================
async function loadComments() {
  try {
    const d = await api('/api/admin/comments.php');
    qs('commentsList').innerHTML = d.comments.length === 0
      ? '<p class="text-gray-400 text-sm">لا توجد تعليقات</p>'
      : d.comments.map((c) => `
        <div class="glass-panel p-3 rounded-xl text-sm flex justify-between gap-2">
          <div class="min-w-0">
            <p class="text-white font-bold">
              ${esc(c.author)}
              <span class="text-[10px] text-gray-400">في #${c.product_id} (${esc(c.product_name)})</span>
            </p>
            <p class="text-gray-300 text-xs break-words">${esc(c.body)}</p>
          </div>
          <button onclick="deleteComment(${c.id})"
                  class="bg-red-800 text-white px-2 py-1 rounded text-xs self-start">🗑️</button>
        </div>
      `).join('');
  } catch (e) {
    qs('commentsList').innerHTML = `<p class="text-red-400">${e.message}</p>`;
  }
}

async function deleteComment(id) {
  if (!confirm('حذف التعليق؟')) return;
  try {
    await api('/api/admin/comments.php?id=' + id, { method: 'DELETE' });
    loadComments();
  } catch (e) { alert(e.message); }
}
window.deleteComment = deleteComment;

// =====================================================
// Marketers
// =====================================================
async function loadMarketers() {
  try {
    const d = await api('/api/admin/marketers.php');
    qs('marketersList').innerHTML = d.marketers.length === 0
      ? '<p class="text-gray-400 text-sm">لا يوجد شركاء</p>'
      : d.marketers.map((m) => `
        <div class="glass-panel p-3 rounded-xl text-sm space-y-2">
          <div class="flex justify-between items-start">
            <div>
              <p class="font-bold text-white">${esc(m.name)}</p>
              <p class="text-[10px] text-yellow-400">🔑 ${m.code} | ${m.short_code}</p>
              <p class="text-[10px] text-sky-300">$${m.total_sales} مبيعات | $${m.confirmed_commission} أرباح</p>
            </div>
            <button onclick="deleteMarketer(${m.id})"
                    class="bg-red-800 text-white px-2 py-1 rounded text-xs">🗑️</button>
          </div>
          <div class="flex gap-1 items-center">
            <input id="mkUsd-${m.id}" type="number" value="100"
                   class="w-20 bg-black border border-gray-700 rounded px-2 py-1 text-white text-xs"/>
            <select id="mkRate-${m.id}"
                    class="bg-black border border-gray-700 rounded px-1 py-1 text-emerald-400 text-xs">
              <option value="0.03">3%</option>
              <option value="0.04">4%</option>
              <option value="0.05">5%</option>
            </select>
            <button onclick="addOrder(${m.id})"
                    class="bg-emerald-700 text-white px-2 py-1 rounded text-xs">+ رصيد</button>
          </div>
        </div>
      `).join('');
  } catch (e) {
    qs('marketersList').innerHTML = `<p class="text-red-400">${e.message}</p>`;
  }
}

async function createMarketer() {
  const name = qs('mkName').value.trim();
  if (!name) return alert('اسم مطلوب');
  try {
    const r = await api('/api/admin/marketers.php', {
      method: 'POST',
      body: JSON.stringify({ name }),
    });
    alert('✅ كود الشريك:\n' + r.code + '\n\nانسخه الآن — لن يظهر بهذه السهولة مجددًا.');
    qs('mkName').value = '';
    loadMarketers();
  } catch (e) { alert(e.message); }
}
window.createMarketer = createMarketer;

async function deleteMarketer(id) {
  if (!confirm('حذف الشريك وكل عمولاته؟')) return;
  try {
    await api('/api/admin/marketers.php?id=' + id, { method: 'DELETE' });
    loadMarketers();
  } catch (e) { alert(e.message); }
}
window.deleteMarketer = deleteMarketer;

async function addOrder(id) {
  const usd = parseFloat(qs('mkUsd-' + id).value);
  const rate = parseFloat(qs('mkRate-' + id).value);
  try {
    await api('/api/admin/marketers.php?action=add-order', {
      method: 'POST',
      body: JSON.stringify({ marketer_id: id, total_usd: usd, rate }),
    });
    loadMarketers();
  } catch (e) { alert(e.message); }
}
window.addOrder = addOrder;

// =====================================================
// Settings
// =====================================================
async function loadSettings() {
  try {
    const s = await api('/api/public/settings.php');
    qs('setRate').value = s.exchange_rate;
    const img = qs('setBannerPreview');
    if (s.banner_img) {
      img.src = s.banner_img;
      img.classList.remove('hidden');
    }
  } catch (e) { /*  */ }
}

async function saveSettings() {
  try {
    await api('/api/admin/settings.php', {
      method: 'POST',
      body: JSON.stringify({ exchange_rate: parseInt(qs('setRate').value) }),
    });
    const f = qs('setBannerFile').files[0];
    if (f) {
      const fd = new FormData();
      fd.append('file', f);
      // ✅ لا نمرر headers — دالة api تضبطها تلقائيًا
      const r = await api('/api/admin/upload.php', { method: 'POST', body: fd });
      await api('/api/admin/settings.php', {
        method: 'POST',
        body: JSON.stringify({ banner_img: r.url }),
      });
    }
    alert('✅ تم حفظ الإعدادات');
    loadSettings();
  } catch (e) { alert('فشل الحفظ: ' + e.message); }
}
window.saveSettings = saveSettings;

// إغلاق النافذة بـ ESC
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    const modal = qs('productFormModal');
    if (modal && !modal.classList.contains('hidden')) {
      closeProductFormModal();
    }
  }
});

if (!qs('adminView')?.classList.contains('hidden')) {
  loadProducts();
}
