'use strict';

// ===== الحالة العامة =====
const state = {
  products: [],
  exchangeRate: 15000,
  currencyMode: 'BOTH',
  currentCategory: 'ALL',
  searchQuery: '',
  cart: [],
  wishlistIds: new Set(),
  likedIds: new Set(),
  storeVersion: 0,
  bannerImg: '',
  whatsapp: '963996311804',
  currentModalProduct: null,
  currentImageIndex: 0,
  currentMarketer: null,
  audioCtx: null,
  productsLoaded: false,
};

Object.defineProperty(window, 'currentModalProduct', {
  get() { return state.currentModalProduct; },
  set(v) { state.currentModalProduct = v; },
  configurable: true
});

const LS_THEME_KEY = 'fw_theme_mode';
const LS_MARKETER_KEY = 'fw_marketer_profile';
const LS_REF_KEY = 'fw_referred_by';
const LS_BUYER_KEY = 'fw_buyer_info';

// ===== أدوات =====
function qs(id){ return document.getElementById(id); }
function fmt(n){ return (Math.round(n*100)/100).toLocaleString('en-US'); }
function escapeHtml(s){ if(s==null) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }
function sanitizeImgUrl(u){ if(!u) return ''; const s=String(u).trim(); if(/^(https?:|data:image\/|\/|\.\/)/.test(s)) return s; return ''; }

function playUiSound(type='tap'){
  try{
    const AC=window.AudioContext||window.webkitAudioContext; if(!AC) return;
    if(!state.audioCtx) state.audioCtx=new AC();
    if(state.audioCtx.state==='suspended') state.audioCtx.resume();
    const t=state.audioCtx.currentTime;
    const o=state.audioCtx.createOscillator(), g=state.audioCtx.createGain();
    o.connect(g); g.connect(state.audioCtx.destination);
    o.type=(type==='cart'||type==='success')?'triangle':'sine';
    o.frequency.setValueAtTime(type==='like'?520:620,t);
    o.frequency.exponentialRampToValueAtTime(type==='tap'?320:880,t+0.08);
    g.gain.setValueAtTime(0.12,t); g.gain.exponentialRampToValueAtTime(0.001,t+0.09);
    o.start(t); o.stop(t+0.095);
  }catch(e){}
}
window.playUiSound=playUiSound;

function showToast(msg,type='success'){
  const c=qs('toastContainer'); if(!c) return;
  const t=document.createElement('div');
  t.className=`toast-anim px-3.5 py-2.5 rounded-xl shadow-2xl text-[11px] font-bold border flex items-center gap-1.5 pointer-events-auto text-center justify-center w-full ${type==='success'?'bg-neutral-900/95 text-yellow-300 border-yellow-500/50':'bg-neutral-900/95 text-red-400 border-red-500/50'}`;
  t.innerHTML=`<span>${type==='success'?'✨':'⚠️'}</span><span>${escapeHtml(msg)}</span>`;
  c.appendChild(t); setTimeout(()=>t.remove(),3200);
}
window.showToast=showToast;

function toggleModal(id,show){ const el=qs(id); if(!el) return; el.classList.toggle('hidden',!show); el.classList.toggle('flex',show); }

// ===== API =====
async function api(path, opts={}){
  const r = await fetch(path, { headers:{'Content-Type':'application/json', ...(opts.headers||{})}, ...opts });
  const data = await r.json().catch(()=>({}));
  if(!r.ok) throw new Error(data.error || `HTTP ${r.status}`);
  return data;
}
async function apiPost(path, body){ return api(path,{method:'POST', body:JSON.stringify(body||{})}); }
async function apiPatch(path, body){ return api(path,{method:'PATCH', body:JSON.stringify(body||{})}); }
async function apiDelete(path){ return api(path,{method:'DELETE'}); }

// ===== التحميل الأولي =====
async function loadSettings(){
  try{
    const s = await api('/api/public/settings.php');
    state.exchangeRate = s.exchange_rate;
    state.storeVersion = s.store_version;
    state.bannerImg = s.banner_img;
    state.whatsapp = s.whatsapp;
    applyBanner();
    updateRateLabels();
  }catch(e){ console.warn('settings load failed', e); }
}

async function loadProducts(){
  try{
    const r = await api('/api/public/products.php');
    state.products = r.products;
    state.likedIds = new Set(r.products.filter(p=>p.isLiked).map(p=>p.id));
    state.wishlistIds = new Set(r.products.filter(p=>p.isWished).map(p=>p.id));
    state.productsLoaded = true;
    renderProducts();
    updateWishlistBadge();
    updateCountersInPlace();
    checkDeepLinkedModel();
  }catch(e){ console.warn('products load failed', e); }
}

async function loadCart(){
  try{
    const r = await api('/api/public/cart.php');
    state.cart = r.items;
    updateCartBadges();
    if(!qs('cartModal').classList.contains('hidden')) openCart();
  }catch(e){ console.warn('cart load failed', e); }
}

// ===== Polling خفيف =====
async function pollStore(){
  try{
    const s = await api('/api/public/settings.php');
    if (s.store_version !== state.storeVersion){
      state.storeVersion = s.store_version;
      state.exchangeRate = s.exchange_rate;
      state.bannerImg = s.banner_img;
      applyBanner();
      updateRateLabels();
      await loadProducts();
    }
  }catch(e){}
}

// ===== Banner + Labels =====
function applyBanner(){
  const el = qs('bannerSection');
  if (el && state.bannerImg){
    el.style.backgroundImage = `linear-gradient(to bottom, rgba(6,6,6,0.75), rgba(6,6,6,0.92)), url('${sanitizeImgUrl(state.bannerImg)}')`;
  }
}
function updateRateLabels(){
  const r = state.exchangeRate.toLocaleString('en-US');
  if(qs('headerRateDesktop')) qs('headerRateDesktop').innerText = r;
}

// ===== التصنيفات =====
function filterCategory(cat){ state.currentCategory=cat; renderProducts(); }
function setCurrencyMode(m){ state.currencyMode=m; renderProducts(); }
function handleSearch(v){ state.searchQuery=v.trim().toLowerCase(); qs('clearSearchBtn').classList.toggle('hidden',!state.searchQuery); renderProducts(); }
function clearSearch(){ qs('searchInput').value=''; handleSearch(''); }
window.filterCategory=filterCategory;
window.setCurrencyMode=setCurrencyMode;
window.handleSearch=handleSearch;
window.clearSearch=clearSearch;

// ===== عرض المنتجات =====
function getPieceWeightGrams(p){ return p.piece_weight || (p.category==='ولادي'?220:p.category==='ب.ب'?150:350); }

function getStockBadgeInfo(p){
  if(p.is_clearance) return {text:'⚡ تصفية سريعة',className:'stock-badge-low'};
  if(p.stock_status==='LOW') return {text:'⚠️ كمية محدودة',className:'stock-badge-low'};
  if(p.stock_status==='PREORDER') return {text:'🔥 تثبيت حجز',className:'stock-badge-preorder'};
  if(p.stock_status==='SOLD_OUT') return {text:'❌ نفدت الكمية',className:'stock-badge-soldout'};
  return null;
}

function getColorHex(name){
  const n=name.trim();
  const m={'أسود':'#111','أبيض':'#fff','أحمر':'#dc2626','كحلي':'#1e3a8a','أزرق':'#2563eb','أخضر':'#16a34a','بيج':'#d6d3d1','رمادي':'#6b7280','بني':'#78350f','زهري':'#ec4899','وردي':'#f472b6','أصفر':'#eab308','موف':'#9333ea','خمري':'#7f1d1d','سماوي':'#38bdf8','ذهبي':'#D4AF37'};
  for(const k in m) if(n.includes(k)) return m[k];
  return 'linear-gradient(135deg,#fde047,#ec4899,#3b82f6)';
}

function renderColorPills(colors, compact=false){
  if(!colors) return '';
  return colors.split(/[،,-]/).map(c=>c.trim()).filter(Boolean).map(c=>{
    const bg=getColorHex(c);
    const style=bg.startsWith('linear')?`background:${bg}`:`background:${bg}`;
    return compact
      ? `<span class="inline-flex items-center gap-1 bg-black/70 border border-gray-700 px-1.5 py-0.5 rounded-full text-[8.5px] text-gray-200"><span class="w-2 h-2 rounded-full border border-white/40" style="${style}"></span>${escapeHtml(c)}</span>`
      : `<span class="inline-flex items-center gap-1.5 bg-neutral-900 text-yellow-200 border border-yellow-500/30 px-2.5 py-0.5 rounded-full text-[10px] font-bold"><span class="w-3 h-3 rounded-full border border-white/50" style="${style}"></span>${escapeHtml(c)}</span>`;
  }).join('');
}

function getRatingLabel(p){
  const c=p.rating_count||0, s=p.rating_sum||0;
  if(c<=0||s<=0) return '—';
  return Math.min(5,Math.max(1,s/c)).toFixed(1);
}

function formatPublishDate(p){
  const ts = p.created_at ? new Date(p.created_at.replace(' ','T')).getTime() : Date.now();
  const diff = Math.max(0, Date.now()-ts);
  const m=Math.floor(diff/60000), h=Math.floor(diff/3600000), d=Math.floor(diff/86400000);
  if(m<2) return '🕒 نُشر للتو';
  if(m<60) return `🕒 نُشر منذ ${m} دقيقة`;
  if(h<24) return `🕒 نُشر منذ ${h} ساعات`;
  if(d<=7) return `🗓️ نُشر منذ ${d} أيام`;
  return `📅 ${new Date(ts).toLocaleDateString('ar-SY')}`;
}

function formatPrice(p){
  const usd = Math.round(p.price_usd*100)/100;
  const syp = Math.round(usd*state.exchangeRate).toLocaleString('en-US');
  if(p.has_discount && p.discount_percent>0){
    const oldUsd = Math.round(usd/(1-p.discount_percent/100)*100)/100;
    const oldSyp = Math.round(oldUsd*state.exchangeRate).toLocaleString('en-US');
    if(state.currencyMode==='USD') return `<div class="flex items-center gap-1.5"><span class="text-gray-400 line-through text-[10px]">$${oldUsd}</span><span class="text-red-500 font-bold text-xs">$${usd}</span></div>`;
    if(state.currencyMode==='SYP') return `<div class="flex items-center gap-1.5"><span class="text-gray-400 line-through text-[10px]">${oldSyp}</span><span class="text-red-500 font-bold text-[11px]">${syp} ل.س</span></div>`;
    return `<div class="flex flex-col leading-tight"><div class="flex items-center gap-1.5"><span class="text-gray-400 line-through text-[9px]">$${oldUsd}</span><span class="text-red-500 font-bold text-xs">$${usd}</span></div><span class="text-red-500 font-bold text-[9px]">${syp} ل.س</span></div>`;
  }
  if(state.currencyMode==='USD') return `<span class="text-yellow-400 font-bold text-xs">$${usd} <span class="text-[9px] text-gray-400">/قطعة</span></span>`;
  if(state.currencyMode==='SYP') return `<span class="text-emerald-400 font-bold text-[11px]">${syp} ل.س</span>`;
  return `<div class="flex flex-col leading-tight"><span class="text-yellow-400 font-bold text-xs">$${usd} <span class="text-[8px] text-gray-400">/قطعة</span></span><span class="text-emerald-400 font-bold text-[9px]">${syp} ل.س</span></div>`;
}

function getDiscountBadge(p){ return p.has_discount && p.discount_percent>0 ? `حسم ${p.discount_percent}% 🔥` : (p.has_discount?'حسم خاص 🔥':''); }

function renderProducts(){
  updateRateLabels();
  document.querySelectorAll('.cat-btn').forEach(b=>{
    const c=b.getAttribute('data-cat'), active=(c===state.currentCategory);
    if(c==='CLEARANCE'){
      b.className=`cat-btn flex-shrink-0 font-bold px-3 py-1 rounded-lg text-[11px] ${active?'bg-red-600 text-white shadow':'bg-red-950/80 text-red-300 border border-red-500/50'}`;
    } else {
      b.className=`cat-btn flex-shrink-0 font-bold px-3 py-1 rounded-lg text-[11px] ${active?'gold-gradient-bg shadow':'glossy-box text-gray-300 hover:border-yellow-400'}`;
    }
  });

  const grid=qs('productsGrid'); if(!grid) return;
  let list=[...state.products];
  if(state.currentCategory==='TREND') list.sort((a,b)=>(b.views+b.likes*3)-(a.views+a.likes*3));
  else if(state.currentCategory==='CLEARANCE') list=list.filter(p=>p.is_clearance||p.has_discount||(p.fomo_left>0&&p.fomo_left<=3)||p.stock_status==='LOW');
  else if(state.currentCategory!=='ALL') list=list.filter(p=>p.category===state.currentCategory);
  if(state.searchQuery) list=list.filter(p=>
    (p.name&&p.name.toLowerCase().includes(state.searchQuery))||
    (p.category&&p.category.toLowerCase().includes(state.searchQuery))||
    (p.pack&&p.pack.toLowerCase().includes(state.searchQuery))||
    (p.colors&&p.colors.toLowerCase().includes(state.searchQuery))
  );

  if(list.length===0){ grid.innerHTML='<div class="col-span-full text-center py-10 text-gray-400 text-xs glass-panel rounded-2xl">لا توجد موديلات مطابقة.</div>'; return; }

  grid.innerHTML=list.map(p=>{
    const liked = state.likedIds.has(p.id);
    const badge = getStockBadgeInfo(p);
    const disc = getDiscountBadge(p);
    return `<div class="card-luxury rounded-2xl overflow-hidden flex flex-col">
      <div class="relative h-44 sm:h-56 overflow-hidden bg-neutral-950 cursor-pointer" onclick="playUiSound('tap'); openProductModal(${p.id})">
        <img src="${sanitizeImgUrl(p.img)}" alt="${escapeHtml(p.name)}" loading="lazy" class="w-full h-full object-cover hover:scale-105 transition duration-500"/>
        <div class="watermark-logo"><span class="text-[8.5px] font-cinzel font-bold text-yellow-400">FASHION WORLD</span></div>
        <span class="absolute top-2 right-2 gold-gradient-bg font-bold text-[9px] px-2 py-0.5 rounded-full shadow z-20">${escapeHtml(p.category)}</span>
        ${p.extras.length?`<span class="absolute top-2 left-2 bg-black/75 text-yellow-300 border border-yellow-500/30 text-[8.5px] font-bold px-1.5 py-0.5 rounded-md z-20">📸 +${p.extras.length}</span>`:''}
        ${disc?`<span class="absolute top-7 right-2 badge-glass discount-flash text-white font-black">${disc}</span>`:''}
        ${badge?`<span class="absolute ${disc?'top-12':'top-7'} right-2 badge-glass ${badge.className} discount-flash font-bold text-white">${escapeHtml(badge.text)}</span>`:''}
        <div class="absolute bottom-1.5 right-1.5 left-1.5 flex justify-between items-center bg-black/85 backdrop-blur-sm px-2 py-1 rounded-lg text-[8px] sm:text-[9.5px] border border-yellow-500/20 z-20">
          <span id="card-views-${p.id}" class="text-sky-300 font-bold">👁️ ${p.views}</span>
          <span id="card-rating-${p.id}" class="text-yellow-400 font-bold">⭐ ${getRatingLabel(p)} (${(p.comments||[]).length}💬)</span>
          <span class="text-emerald-400 font-bold">سيرية:${p.min_qty}</span>
        </div>
      </div>
      <div class="p-2.5 sm:p-3 flex-1 flex flex-col justify-between space-y-1.5">
        <div>
          <h3 class="font-bold text-white text-[11px] sm:text-xs cursor-pointer hover:text-yellow-400 line-clamp-1" onclick="playUiSound('tap'); openProductModal(${p.id})">${escapeHtml(p.name)}</h3>
          <p class="text-[9px] text-gray-400 font-semibold mb-0.5">${formatPublishDate(p)}</p>
          <p class="text-[10px] text-gray-300 line-clamp-1">📐 ${escapeHtml(p.pack||'')}</p>
          ${p.colors?`<div class="flex items-center gap-1 flex-wrap mt-1">${renderColorPills(p.colors,true)}</div>`:''}
        </div>
        <div class="flex items-center justify-between border-t border-gray-800/80 pt-1">
          ${formatPrice(p)}
          <button type="button" id="card-like-btn-${p.id}" onclick="toggleLike(event,${p.id})" class="${liked?'bg-red-900/40 border border-red-500 text-red-300':'bg-neutral-800 text-gray-300 border border-gray-700'} text-[10px] px-2 py-0.5 rounded-lg font-bold">${liked?'❤️':'🤍'} ${p.likes}</button>
        </div>
        <button type="button" onclick="playUiSound('tap'); openProductModal(${p.id})" class="w-full gold-gradient-bg font-bold py-1.5 px-2 rounded-lg text-[10px] shadow">🛒 إضافة للسلة</button>
      </div>
    </div>`;
  }).join('');
}

function updateCountersInPlace(){
  state.products.forEach(p=>{
    const v=qs(`card-views-${p.id}`), r=qs(`card-rating-${p.id}`), l=qs(`card-like-btn-${p.id}`);
    if(v) v.innerText=`👁️ ${p.views}`;
    if(r) r.innerText=`⭐ ${getRatingLabel(p)} (${(p.comments||[]).length}💬)`;
    if(l){ const liked=state.likedIds.has(p.id); l.className=`${liked?'bg-red-900/40 border border-red-500 text-red-300':'bg-neutral-800 text-gray-300 border border-gray-700'} text-[10px] px-2 py-0.5 rounded-lg font-bold`; l.innerHTML=`${liked?'❤️':'🤍'} ${p.likes}`; }
  });
}

// ===== مودال المنتج =====
async function openProductModal(id){
  // const p=state.products.find(x=>x.id===id); if(!p) return;
  // state.currentModalProduct=p; state.currentImageIndex=0;
  
  const p=state.products.find(x=>x.id===id); if(!p) return;
  state.currentModalProduct=p;
  window.currentModalProduct=p;
  state.currentImageIndex=0;

  if(!p.isViewed){
    p.isViewed=true; p.views++;
    apiPost('/api/public/interactions.php?action=view',{product_id:id}).catch(()=>{});
    updateCountersInPlace();
  }

  qs('modalTitle').innerText=p.name;
  qs('modalPublishTime').innerText=formatPublishDate(p);
  updateModalImageDisplay();
  qs('modalCategory').innerText=`قسم: ${p.category}`;
  updateModalHeaderSocial(p);
  updateModalWishlistBtn(p);
  qs('modalPack').innerText=`📐 القياسات والتفاصيل: ${p.pack||''}`;

  const colorsWrapper=qs('modalColorCircles');
  if(p.colors && p.colors.trim()){ qs('modalColorsContainer').innerHTML=renderColorPills(p.colors,false); colorsWrapper.classList.remove('hidden'); }
  else colorsWrapper.classList.add('hidden');

  qs('modalWeightInfo').innerText=`⚖️ وزن القطعة: ${getPieceWeightGrams(p)} غرام`;
  const fomoEl=qs('modalFomoBanner');
  const fomoParts=[];
  if(p.is_clearance) fomoParts.push('⚡ عرض تصفية سريعة');
  if(p.fomo_booked) fomoParts.push(`🔥 ${p.fomo_booked}`);
  if(p.fomo_left>0) fomoParts.push(`⚠️ متبقي ${p.fomo_left} سيريات`);
  else if(p.stock_status==='LOW') fomoParts.push('⚠️ كمية محدودة');
  if(fomoParts.length){ fomoEl.innerText=fomoParts.join(' | '); fomoEl.classList.remove('hidden'); }
  else fomoEl.classList.add('hidden');

  const descEl=qs('modalAdminDesc');
  if(p.admin_desc){ descEl.innerText=`📝 القماش والوصف: ${p.admin_desc}`; descEl.classList.remove('hidden'); }
  else descEl.classList.add('hidden');

  const retail=Math.round(p.price_usd*1.4*100)/100;
  qs('modalRetailSuggestion').innerText=`💡 سعر الجملة: $${p.price_usd} | المقترح للمفرق: $${retail} | ربحك المتوقع: $${Math.round((retail-p.price_usd)*100)/100}`;

  const b=qs('modalDiscountBadge');
  if(p.has_discount){ b.innerText=getDiscountBadge(p); b.classList.remove('hidden'); } else b.classList.add('hidden');

  const sb=qs('modalStockBadge'); const si=getStockBadgeInfo(p);
  if(si){ sb.innerText=si.text; sb.className=`badge-glass ${si.className} discount-flash font-bold text-white`; sb.classList.remove('hidden'); }
  else sb.classList.add('hidden');

  qs('modalAddToCartBtn').innerText=p.stock_status==='SOLD_OUT'?'🔥 حجز مسبق':'🛒 إضافة إلى السلة';
  qs('modalMinNotice').innerText=`حجم السيرية: ${p.min_qty} قطع`;
  qs('modalQtyInput').value=p.min_qty;
  qs('modalQtyInput').min=p.min_qty;
  qs('modalPriceContainer').innerHTML=formatPrice(p);

  calculateModalTotal();
  renderModalStars(p);
  renderComments(p);

  const allImgs=[p.img,...p.extras].map(sanitizeImgUrl).filter(Boolean);
  qs('modalThumbs').innerHTML=allImgs.map((src,idx)=>
    `<img src="${src}" onclick="playUiSound('tap'); state.currentImageIndex=${idx}; updateModalImageDisplay();" class="w-12 h-12 object-cover rounded-lg border-2 ${idx===state.currentImageIndex?'border-yellow-400':'border-yellow-500/20'} cursor-pointer flex-shrink-0"/>`
  ).join('');

  toggleModal('productModal',true);
}
window.openProductModal=openProductModal;

function updateModalImageDisplay(){
  const p=state.currentModalProduct; if(!p) return;
  const imgs=[p.img,...p.extras].map(sanitizeImgUrl).filter(Boolean);
  if(!imgs.length) return;
  if(state.currentImageIndex>=imgs.length) state.currentImageIndex=0;
  qs('modalMainImg').src=imgs[state.currentImageIndex];
}
window.updateModalImageDisplay=updateModalImageDisplay;

function nextImage(e){ if(e) e.stopPropagation(); const p=state.currentModalProduct; if(!p) return; const n=[p.img,...p.extras].length; if(n<=1) return; state.currentImageIndex=(state.currentImageIndex+1)%n; updateModalImageDisplay(); playUiSound('tap'); }
function prevImage(e){ if(e) e.stopPropagation(); const p=state.currentModalProduct; if(!p) return; const n=[p.img,...p.extras].length; if(n<=1) return; state.currentImageIndex=(state.currentImageIndex-1+n)%n; updateModalImageDisplay(); playUiSound('tap'); }
window.nextImage=nextImage; window.prevImage=prevImage;

function closeProductModal(){ toggleModal('productModal',false); state.currentModalProduct=null; window.currentModalProduct=null;}
window.closeProductModal=closeProductModal;

function updateModalHeaderSocial(p){
  qs('modalViews').innerText=`👁️ ${p.views}`;
  const btn=qs('modalLikeBtn'), liked=state.likedIds.has(p.id);
  btn.className=`${liked?'bg-red-900/40 border border-red-500 text-red-300':'bg-neutral-800 text-gray-300 border border-gray-700'} px-2 py-0.5 rounded-md text-[10px] font-bold`;
  btn.innerHTML=`${liked?'❤️ معجب':'🤍 إعجاب'} (${p.likes})`;
}

function renderModalStars(p){
  const my=p.myRating||0; const c=p.rating_count||0, s=p.rating_sum||0;
  const active = my>0 ? my : (c>0?Math.min(5,Math.max(1,Math.round(s/c))):0);
  let html='';
  for(let i=1;i<=5;i++) html+=`<span onclick="rateProduct(${p.id},${i})" class="${i<=active?'text-yellow-400':'text-gray-600'} text-base select-none">★</span>`;
  html+=`<span class="text-[10px] text-gray-300 mr-1 font-bold">${c>0?`${(s/c).toFixed(1)}/5 (${c})`:'—'}</span>`;
  qs('modalStarsContainer').innerHTML=html;
}

async function rateProduct(pid,stars){
  try{
    playUiSound('like');
    const r=await apiPost('/api/public/interactions.php?action=rate',{product_id:pid,stars});
    const p=state.products.find(x=>x.id===pid); if(p){ p.rating_sum=r.rating_sum; p.rating_count=r.rating_count; p.myRating=stars; }
    renderModalStars(p); updateCountersInPlace(); showToast('شكراً لتقييمك! ⭐');
  }catch(e){ showToast(e.message,'error'); }
}
window.rateProduct=rateProduct;

function renderComments(p){
  const list=qs('commentsList'); const comments=p.comments||[];
  if(!comments.length){ list.innerHTML='<p class="text-gray-400 text-[10px]">لا توجد ملاحظات بعد.</p>'; return; }
  list.innerHTML=comments.map(c=>`<div class="bg-black/60 p-1.5 rounded-lg border border-yellow-500/15 text-[10px]"><span class="text-yellow-400 font-bold">${escapeHtml(c.author)}:</span> <span class="text-gray-200">${escapeHtml(c.body)}</span></div>`).join('');
}

async function addComment(){
  const p=state.currentModalProduct; if(!p) return;
  const author=qs('commentAuthor').value.trim()||'تاجر معتمد';
  const text=qs('commentText').value.trim();
  if(!text) return alert('اكتب ملاحظتك أولاً');
  try{
    playUiSound('cart');
    const r=await apiPost('/api/public/comments.php',{product_id:p.id,author,text});
    p.comments=p.comments||[]; p.comments.push({id:r.id,author,body:text});
    qs('commentText').value=''; renderComments(p); updateCountersInPlace(); showToast('تم إرسال الملاحظة 💬');
  }catch(e){ showToast(e.message,'error'); }
}
window.addComment=addComment;

function adjustModalQtyByHalf(dir){
  const p=state.currentModalProduct; if(!p) return;
  const step=Math.max(1,Math.round(p.min_qty/2));
  const inp=qs('modalQtyInput');
  let c=parseInt(inp.value)||p.min_qty;
  c+=dir*step;
  if(c<p.min_qty) c=p.min_qty;
  inp.value=c; calculateModalTotal();
}
window.adjustModalQtyByHalf=adjustModalQtyByHalf;

function calculateModalTotal(){
  const p=state.currentModalProduct; if(!p) return;
  const qty=Math.max(p.min_qty,parseInt(qs('modalQtyInput').value)||p.min_qty);
  qs('modalQtyInput').value=qty;
  const usd=Math.round(p.price_usd*qty*100)/100;
  const syp=Math.round(usd*state.exchangeRate).toLocaleString('en-US');
  qs('modalSubtotal').innerText=`$${usd} (${syp} ل.س)`;
  qs('modalPackAndWeight').innerText=`${(qty/p.min_qty).toFixed(1)} سيرية | ~${((qty*getPieceWeightGrams(p))/1000).toFixed(2)} كغ`;
}
window.calculateModalTotal=calculateModalTotal;

// ===== سلة =====
async function addCustomQtyToCart(){
  const p=state.currentModalProduct; if(!p) return;
  const qty=Math.max(p.min_qty,parseInt(qs('modalQtyInput').value)||p.min_qty);
  try{
    playUiSound('cart');
    await apiPost('/api/public/cart.php',{product_id:p.id,qty});
    await loadCart();
    closeProductModal(); openCart(); showToast('تمت الإضافة إلى السلة 🛒');
  }catch(e){ showToast(e.message,'error'); }
}
window.addCustomQtyToCart=addCustomQtyToCart;

async function removeFromCart(id){
  try{ await apiDelete(`/api/public/cart.php?product_id=${id}`); await loadCart(); openCart(); }catch(e){ showToast(e.message,'error'); }
}
window.removeFromCart=removeFromCart;

async function adjustCartItemQty(id, delta){
  const item=state.cart.find(c=>c.id===id); if(!item) return;
  const p=state.products.find(x=>x.id===id);
  const min=p?p.min_qty:1;
  const qty=Math.max(0, item.qty+delta);
  try{ await apiPatch(`/api/public/cart.php?product_id=${id}`,{product_id:id,qty}); await loadCart(); openCart(); }catch(e){ showToast(e.message,'error'); }
}
window.adjustCartItemQty=adjustCartItemQty;

async function clearEntireCart(){
  if(!confirm('تفريغ السلة بالكامل؟')) return;
  try{ await apiDelete('/api/public/cart.php'); await loadCart(); openCart(); showToast('تم التفريغ','error'); }catch(e){ showToast(e.message,'error'); }
}
window.clearEntireCart=clearEntireCart;

function getCartMetrics(){
  let pieces=0, packs=0, grams=0, raw=0;
  state.cart.forEach(c=>{
    const qty=Number(c.qty)||0, min=Number(c.min_qty)||10;
    pieces+=qty; packs+=qty/min; grams+=qty*getPieceWeightGrams(c); raw+=c.price_usd*qty;
  });
  raw=Math.round(raw*100)/100;
  let disc=0, total=raw;
  if(raw>=400){ disc=Math.round(raw*0.02*100)/100; total=Math.round((raw-disc)*100)/100; }
  return {pieces, packs:packs.toFixed(1), weight: pieces>0?((grams+500)/1000).toFixed(2):'0', raw, disc, total};
}

function updateCartBadges(){
  const m=getCartMetrics();
  if(qs('cartCount')) qs('cartCount').innerText=m.pieces;
  const bar=qs('floatingMiniCart'); if(!bar) return;
  if(m.pieces<=0){ bar.classList.add('hidden'); return; }
  bar.classList.remove('hidden');
  qs('floatingCartBadge').innerText=m.pieces;
  qs('floatingCartTotal').innerText=`$${m.total}`;
  qs('floatingCartMeta').innerText=`${m.packs} سيرية (~${m.weight} كغ)`;
}

function loadBuyerInfo(){
  try{
    const info=JSON.parse(localStorage.getItem(LS_BUYER_KEY)||'null'); if(!info) return;
    if(qs('buyerName')) qs('buyerName').value=info.name||'';
    if(qs('buyerAddress')) qs('buyerAddress').value=info.address||'';
    if(qs('buyerPhone')) qs('buyerPhone').value=info.phone||'';
    if(qs('buyerNotes')) qs('buyerNotes').value=info.notes||'';
  }catch(e){}
}
function saveBuyerInfo(){
  try{ localStorage.setItem(LS_BUYER_KEY, JSON.stringify({
    name:qs('buyerName')?.value||'', address:qs('buyerAddress')?.value||'',
    phone:qs('buyerPhone')?.value||'', notes:qs('buyerNotes')?.value||''
  })); }catch(e){}
}
window.saveBuyerInfoToStorage=saveBuyerInfo;

function openCart(){
  loadBuyerInfo();
  const m=getCartMetrics();
  qs('cartSummaryPieces').innerText=`${m.pieces} قطعة`;
  qs('cartSummaryPacks').innerText=`${m.packs} سيرية`;
  qs('cartSummaryWeight').innerText=`~${m.weight} كغ`;
  qs('tierDiscountNotice').classList.toggle('hidden', m.raw<400);

  const cont=qs('cartItems');
  if(!state.cart.length){ cont.innerHTML='<p class="text-center text-gray-500 py-6">السلة فارغة</p>'; qs('cartTotal').innerText='$0'; }
  else {
    cont.innerHTML=state.cart.map(c=>{
      const sub=Math.round(c.price_usd*c.qty*100)/100;
      const min=c.min_qty||10;
      return `<div class="flex justify-between items-center glossy-box p-2 rounded-xl gap-2">
        <div class="flex-1 min-w-0"><p class="font-bold text-white text-xs truncate">${escapeHtml(c.name)}</p>
        <p class="text-[10px] text-yellow-400">$${c.price_usd} × ${c.qty} (${(c.qty/min).toFixed(1)} سيرية)</p></div>
        <div class="flex items-center gap-1">
          <button onclick="adjustCartItemQty(${c.id},-${Math.max(1,Math.round(min/2))})" class="bg-neutral-800 text-white w-6 h-6 rounded font-bold">-</button>
          <span class="text-white font-bold text-xs px-1">${c.qty}</span>
          <button onclick="adjustCartItemQty(${c.id},${Math.max(1,Math.round(min/2))})" class="bg-neutral-800 text-white w-6 h-6 rounded font-bold">+</button>
        </div>
        <span class="text-emerald-400 font-bold text-xs">$${sub}</span>
        <button onclick="removeFromCart(${c.id})" class="text-red-400 text-[10px] font-bold px-1.5 py-1 bg-red-950/40 rounded">حذف</button>
      </div>`;
    }).join('');
    qs('cartTotal').innerText=`$${m.total} (${Math.round(m.total*state.exchangeRate).toLocaleString('en-US')} ل.س)`;
  }
  toggleModal('cartModal',true);
}
window.openCart=openCart;
function closeCart(){ toggleModal('cartModal',false); } window.closeCart=closeCart;

// ===== مشاركة =====
function shareLink(pid){
  const base=`${location.origin}${location.pathname}?model=${pid}`;
  if(!state.currentMarketer) return base;
  const ref=state.currentMarketer.code.substring(0,4).toUpperCase();
  return `${base}&ref=${ref}`;
}

function shareProductWhatsApp(){
  const p=state.currentModalProduct; if(!p) return;
  const t=`✨ترند الموسم 2026🔮\n\n🌟 *${p.name}*\n🧵 *القماش:* ${p.admin_desc||'قماش نخب أول'}\n📐 *القياسات:* ${p.pack}\n🎨 *الألوان:* ${p.colors||'متعددة'}\n📦 *السيرية:* ${p.min_qty}\n💰 *السعر:* $${p.price_usd}\n\n🔗 ${shareLink(p.id)}\n\n🏢 FASHION WORLD - دمشق الحريقة`;
  window.open(`https://wa.me/?text=${encodeURIComponent(t)}`,'_blank');
}
function shareProductFacebook(){
  const p=state.currentModalProduct; if(!p) return;
  window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareLink(p.id))}`,'_blank','width=600,height=400');
}
function reserveProductWhatsApp(){
  const p=state.currentModalProduct; if(!p) return;
  const t=`🔒 *طلب حجز موديل*\n👗 ${p.name}\n🔗 ${shareLink(p.id)}`;
  window.open(`https://wa.me/${state.whatsapp}?text=${encodeURIComponent(t)}`,'_blank');
}
function downloadModalProductImage(){
  const p=state.currentModalProduct; if(!p) return;
  const a=document.createElement('a'); a.href=p.img; a.download=`FW-${p.id}.jpg`; a.click();
  showToast('بدأ التحميل');
}
function copyTrendWhatsAppText(){
  const p=state.currentModalProduct; if(!p) return;
  const t=`✨ترند الموسم\n\n🌟 *${p.name}*\n📐 ${p.pack}\n🎨 ${p.colors||'متعددة'}\n💰 $${p.price_usd}\n\n🔗 ${shareLink(p.id)}`;
  try{ navigator.clipboard.writeText(t); showToast('تم النسخ 🔮'); }catch(e){}
}
window.shareProductWhatsApp=shareProductWhatsApp;
window.shareProductFacebook=shareProductFacebook;
window.reserveProductWhatsApp=reserveProductWhatsApp;
window.downloadModalProductImage=downloadModalProductImage;
window.copyTrendWhatsAppText=copyTrendWhatsAppText;

// ===== Like / Wishlist =====
// async function toggleLike(e,pid){
//   if(e) e.stopPropagation();
//   try{
//     playUiSound('like');
//     const r=await apiPost('/api/public/interactions.php?action=like',{product_id:pid});
//     const p=state.products.find(x=>x.id===pid);
//     if(p){ p.likes=r.likes; if(r.liked) state.likedIds.add(pid); else state.likedIds.delete(pid); }
//     updateCountersInPlace();
//     if(state.currentModalProduct?.id===pid) updateModalHeaderSocial(p);
//   }catch(e){ showToast(e.message,'error'); }
// }

async function toggleLike(e,pid){
  if(e) e.stopPropagation();
  if (!pid || pid <= 0) return;
  try{
    playUiSound('like');
    const r = await apiPost('/api/public/interactions.php?action=like',{product_id:pid});
    const p=state.products.find(x=>x.id===pid);
    if(p){ p.likes=r.likes; if(r.liked) state.likedIds.add(pid); else state.likedIds.delete(pid); }
    updateCountersInPlace();
    if(state.currentModalProduct?.id===pid) updateModalHeaderSocial(p);
  }catch(e){ showToast(e.message,'error'); }
}

window.toggleLikeProduct=toggleLike;

// async function toggleWishlistModalItem(pid){
//   try{
//     playUiSound('like');
//     const r=await apiPost('/api/public/interactions.php?action=wishlist',{product_id:pid});
//     if(r.wished) state.wishlistIds.add(pid); else state.wishlistIds.delete(pid);
//     updateWishlistBadge();
//     if(state.currentModalProduct?.id===pid) updateModalWishlistBtn(state.currentModalProduct);
//   }catch(e){ showToast(e.message,'error'); }
// }

async function toggleWishlistModalItem(pid){
  if (!pid || pid <= 0) {
    console.warn('[app] toggleWishlist called with invalid pid:', pid);
    return;
  }
  try{
    playUiSound('like');
    const r = await apiPost('/api/public/interactions.php?action=wishlist',{product_id:pid});
    if(r.wished) state.wishlistIds.add(pid); else state.wishlistIds.delete(pid);
    updateWishlistBadge();
    if(state.currentModalProduct?.id===pid) updateModalWishlistBtn(state.currentModalProduct);
  }catch(e){ showToast(e.message,'error'); }
}

window.toggleWishlistModalItem=toggleWishlistModalItem;

async function removeFromWishlistAndRefresh(pid) {
  if (!pid || pid <= 0) return;
  await toggleWishlistModalItem(pid);   // ★ انتظر انتهاء الحذف
  openWishlistModal();                  // ★ ثم أعد رسم القائمة
}
window.removeFromWishlistAndRefresh = removeFromWishlistAndRefresh;

function updateWishlistBadge(){ if(qs('wishlistCount')) qs('wishlistCount').innerText=state.wishlistIds.size; }
function updateModalWishlistBtn(p){
  const b=qs('modalWishlistBtn'); if(!b) return;
  const w=state.wishlistIds.has(p.id);
  b.className=`px-2 py-0.5 rounded-md text-[10px] font-bold ${w?'bg-pink-900/50 text-pink-300 border border-pink-500':'bg-neutral-800 text-pink-300'}`;
  b.innerText=w?'❤️ مفضل':'🤍 مفضلة';
}

function openWishlistModal(){
  const cont=qs('wishlistContainer'); if(!cont) return;
  const list=state.products.filter(p=>state.wishlistIds.has(p.id));
  cont.innerHTML = list.length===0
    ? '<p class="text-center text-gray-400 py-6">المفضلة فارغة</p>'
    : list.map(p=>`<div class="flex justify-between items-center glossy-box p-2 rounded-xl gap-2">
        <img src="${sanitizeImgUrl(p.img)}" class="w-10 h-10 object-cover rounded-lg"/>
        <div class="flex-1 min-w-0"><p class="font-bold text-white text-xs truncate">${escapeHtml(p.name)}</p>
        <p class="text-[10px] text-yellow-400">$${p.price_usd} | سيرية: ${p.min_qty}</p></div>
        <button onclick="openProductModal(${p.id}); closeWishlistModal();" class="gold-gradient-bg font-bold px-2 py-1 rounded text-[10px]">عرض</button>
        <button onclick="removeFromWishlistAndRefresh(${p.id})" class="bg-red-950/60 text-red-400 px-2 py-1 rounded text-[10px]">حذف</button>
        </div>`).join('');
  toggleModal('wishlistModal',true);
}
window.openWishlistModal=openWishlistModal;
function closeWishlistModal(){ toggleModal('wishlistModal',false); }
window.closeWishlistModal=closeWishlistModal;

// ===== صورة مكبرة =====
function openZoomModal(src){
  const m=qs('imageZoomModal'), i=qs('zoomModalImg'); if(!m||!i) return;
  i.src=sanitizeImgUrl(src); i.classList.remove('zoomed-img');
  m.classList.remove('hidden'); m.classList.add('flex');
}
function closeZoomModal(){ const m=qs('imageZoomModal'); if(m){m.classList.add('hidden');m.classList.remove('flex');} }
function toggleDoubleZoom(el){ el.classList.toggle('zoomed-img'); }
function nextZoomImage(e){ if(e)e.stopPropagation(); const p=state.currentModalProduct; if(!p) return; const imgs=[p.img,...p.extras]; if(imgs.length<=1)return; state.currentImageIndex=(state.currentImageIndex+1)%imgs.length; qs('zoomModalImg').src=imgs[state.currentImageIndex]; playUiSound('tap'); }
function prevZoomImage(e){ if(e)e.stopPropagation(); const p=state.currentModalProduct; if(!p) return; const imgs=[p.img,...p.extras]; if(imgs.length<=1)return; state.currentImageIndex=(state.currentImageIndex-1+imgs.length)%imgs.length; qs('zoomModalImg').src=imgs[state.currentImageIndex]; playUiSound('tap'); }
window.openZoomModal=openZoomModal; window.closeZoomModal=closeZoomModal;
window.toggleDoubleZoom=toggleDoubleZoom; window.nextZoomImage=nextZoomImage; window.prevZoomImage=prevZoomImage;

// ===== VIP =====
function openVipModal(){ toggleModal('vipModal',true); }
function closeVipModal(){ toggleModal('vipModal',false); }
function sendVipRequestWhatsApp(e){
  e.preventDefault();
  const n=qs('vipMerchantName').value.trim(), c=qs('vipCat').value, q=qs('vipQty').value, ph=qs('vipPhone').value.trim(), d=qs('vipDetails').value.trim();
  const t=`👑 *طلب تصنيع VIP*\n👤 ${n}\n👗 ${c}\n📦 ${q} قطعة\n📞 ${ph}\n📝 ${d}`;
  closeVipModal(); window.open(`https://wa.me/${state.whatsapp}?text=${encodeURIComponent(t)}`,'_blank');
}
window.openVipModal=openVipModal; window.closeVipModal=closeVipModal; window.sendVipRequestWhatsApp=sendVipRequestWhatsApp;

// ===== About =====
function openAboutModal(){ toggleModal('aboutModal',true); }
function closeAboutModal(){ toggleModal('aboutModal',false); }
window.openAboutModal=openAboutModal; window.closeAboutModal=closeAboutModal;

// ===== الشريك =====
//function openAffiliateModal(){
//  if(state.currentMarketer){ renderMarketerDashboard(); toggleModal('affiliateModal',true); }
//  else { toggleModal('partnerAuthModal',true); }
//}

async function openAffiliateModal(){
  if (state.currentMarketer) {
    // اعرض المودال فورًا مع بيانات localStorage (تجربة أسرع)
    if (qs('partnerWelcomeName')) qs('partnerWelcomeName').innerText =
      `👤 الشريك: ${state.currentMarketer.name || ''}`;
    if (qs('myAffiliateCode')) qs('myAffiliateCode').innerText =
      state.currentMarketer.code || '';
    if (qs('myShortRefCode')) qs('myShortRefCode').innerText =
      state.currentMarketer.short_code || '';
    if (qs('affiliateLinkInput')) qs('affiliateLinkInput').value =
      `${location.origin}${location.pathname}?ref=${state.currentMarketer.short_code || ''}`;
    toggleModal('affiliateModal', true);

    // ثم اجلب البيانات الحقيقية من السيرفر (مبيعات، أرباح، سجل)
    await refreshDashboard();
  } else {
    toggleModal('partnerAuthModal', true);
  }
}

function closeAffiliateModal(){ toggleModal('affiliateModal',false); }
function closePartnerAuthModal(){ toggleModal('partnerAuthModal',false); }
async function verifyPartnerPasscode(e){
  e.preventDefault();
  const code=qs('partnerPasscodeInput').value.trim().toUpperCase();
  if(!/^[A-Z0-9]{8}$/.test(code)) return alert('كود 8 خانات مطلوب');
  const btn=qs('partnerAuthSubmitBtn'); btn.innerText='جاري التحقق... ⏳'; btn.disabled=true;
  try{
    const r=await apiPost('/api/public/partner.php?action=login',{code});
    state.currentMarketer=r.marketer;
    localStorage.setItem(LS_MARKETER_KEY,JSON.stringify(r.marketer));
    closePartnerAuthModal(); playUiSound('success');
    showToast(`أهلاً ${r.marketer.name}`);
    await refreshDashboard(); toggleModal('affiliateModal',true);
  }catch(err){ alert('كود غير صحيح'); }
  finally{ btn.innerText='دخول إلى حسابي 🔓'; btn.disabled=false; }
}
//async function refreshDashboard(){
//  try{
//    const d=await api('/api/public/partner.php?action=dashboard');
//    renderMarketerDashboard(d);
//  }catch(e){}
//}

async function refreshDashboard(){
  try {
    const d = await api('/api/public/partner.php?action=dashboard');
    renderMarketerDashboard(d);
    return true;
  } catch (e) {
    // جلسة منتهية → امسح الحالة واطلب دخولاً جديداً
    if (String(e.message).includes('Unauthorized') || String(e.message).includes('401')) {
      state.currentMarketer = null;
      localStorage.removeItem(LS_MARKETER_KEY);
      toggleModal('affiliateModal', false);
      toggleModal('partnerAuthModal', true);
      showToast('انتهت الجلسة، يرجى الدخول مجددًا', 'error');
    }
    return false;
  }
}

function logoutPartnerAccount(){
  fetch('/api/public/partner.php?action=logout',{method:'POST'}).finally(()=>{
    state.currentMarketer=null; localStorage.removeItem(LS_MARKETER_KEY);
    closeAffiliateModal(); showToast('تم الخروج');
  });
}
function renderMarketerDashboard(d){
  if(!d) return;
  const sales=d.total_sales, comm=d.confirmed_commission;
  qs('partnerWelcomeName').innerText=`👤 الشريك: ${d.marketer.name}`;
  qs('myAffiliateCode').innerText=d.marketer.code;
  qs('myShortRefCode').innerText=d.marketer.short_code;
  qs('affiliateLinkInput').value=`${location.origin}${location.pathname}?ref=${d.marketer.short_code}`;
  qs('dashTotalSales').innerText=`$${sales.toFixed(2)}`;
  qs('dashConfirmedComm').innerText=`$${comm.toFixed(2)}`;
  const rank = sales>=5000?{t:'💎 ماسي (5%)',p:100}:sales>=2000?{t:'🥇 ذهبي (4%)',p:Math.round((sales-2000)/30)}:{t:'🥉 برونزي (3%)',p:Math.min(100,Math.round(sales/20))};
  qs('partnerRankBadge').innerText=rank.t;
  qs('partnerRankProgress').style.width=rank.p+'%';
  qs('dashboardStampBadge').innerText=`ختم: ${d.marketer.short_code}`;
  const log=qs('dashOrdersLog');
  log.innerHTML = d.orders.length===0
    ? '<p class="text-gray-400 text-center py-2">لا توجد عمولات بعد</p>'
    : d.orders.map(o=>`<div class="bg-black/85 p-2 rounded border border-gray-800 flex justify-between items-center"><span>📅 ${o.order_date} | $${o.total_usd}</span><span class="text-emerald-400 font-bold">+$${o.commission_usd}</span></div>`).join('');
}
function copyAffiliateLink(){
  const el=qs('affiliateLinkInput'); el.select();
  try{ navigator.clipboard.writeText(el.value); showToast('تم النسخ 📋'); }catch(e){}
}
function requestWithdrawalWhatsApp(){
  if(!state.currentMarketer) return;
  const r=qs('payoutReceiverName').value.trim(), c=qs('payoutCity').value.trim(), a=qs('payoutAccountInfo').value.trim();
  if(!r||!c||!a) return alert('أكمل بيانات السحب');
  const t=`💳 *طلب صرف أرباح*\n👤 ${state.currentMarketer.name}\n🔑 ${state.currentMarketer.code}\n💰 $${qs('dashConfirmedComm').innerText.replace('$','')}\n📥 ${r}\n📍 ${c}\n📱 ${a}`;
  window.open(`https://wa.me/${state.whatsapp}?text=${encodeURIComponent(t)}`,'_blank');
}
window.openAffiliateModal=openAffiliateModal; window.closeAffiliateModal=closeAffiliateModal;
window.closePartnerAuthModal=closePartnerAuthModal; window.verifyPartnerPasscode=verifyPartnerPasscode;
window.logoutPartnerAccount=logoutPartnerAccount; window.copyAffiliateLink=copyAffiliateLink;
window.requestWithdrawalWhatsApp=requestWithdrawalWhatsApp;

// ===== Theme =====
function applySavedTheme(){
  if(localStorage.getItem(LS_THEME_KEY)==='LIGHT'){ qs('mainBody').classList.add('light-mode'); qs('themeToggleBtn').innerText='☀️'; }
}
function toggleThemeMode(){
  const body=qs('mainBody'); const light=!body.classList.contains('light-mode');
  body.classList.toggle('light-mode',light);
  localStorage.setItem(LS_THEME_KEY, light?'LIGHT':'DARK');
  qs('themeToggleBtn').innerText=light?'☀️':'🌙';
}
window.toggleThemeMode=toggleThemeMode;

function toggleToolsDrawer(){ qs('userToolsDrawer')?.classList.toggle('hidden'); }
window.toggleToolsDrawer=toggleToolsDrawer;

// ===== Deep link + Ref =====
function checkDeepLinkedModel(){
  if(state.productsLoaded===false) return;
  try{
    const params=new URLSearchParams(location.search);
    const model=params.get('model'); const ref=params.get('ref');
    if(ref) localStorage.setItem(LS_REF_KEY,ref.trim().toUpperCase().substring(0,4));
    if(model){
      const p=state.products.find(x=>String(x.id)===String(model));
      if(p){ setTimeout(()=>openProductModal(p.id),200); history.replaceState({},'',location.pathname); }
    }
  }catch(e){}
}

function initFromStorage(){
  try{
    const m=JSON.parse(localStorage.getItem(LS_MARKETER_KEY)||'null');
    if(m&&m.code) state.currentMarketer=m;
  }catch(e){}
}

// ===== فاتورة / طلب =====
function buildOrderPayload(){
  const name=qs('buyerName').value.trim(), addr=qs('buyerAddress').value.trim(), phone=qs('buyerPhone').value.trim(), notes=qs('buyerNotes').value.trim();
  if(!name||!addr||!phone) throw new Error('أكمل الاسم والعنوان والهاتف');
  const ref=localStorage.getItem(LS_REF_KEY)||'';
  const items=state.cart.map(c=>({product_id:c.id,qty:c.qty}));
  return {buyer_name:name,buyer_phone:phone,buyer_address:addr,buyer_notes:notes,referred_by:ref,items};
}

async function printWholesaleInvoice(){
  if(!state.cart.length) return alert('السلة فارغة');
  const m=getCartMetrics();
  const payload=buildOrderPayload();
  let orderId=null;
  try{ const r=await apiPost('/api/public/orders.php',payload); orderId=r.order_id; }catch(e){ return alert(e.message); }
  const rowsHtml=state.cart.map((c,i)=>`<tr><td>${i+1}</td><td style="text-align:right;font-weight:bold">${escapeHtml(c.name)}</td><td>${c.category}</td><td>${c.qty}</td><td>$${c.price_usd}</td><td>$${Math.round(c.price_usd*c.qty*100)/100}</td></tr>`).join('');
  const totalSyp=Math.round(m.total*state.exchangeRate).toLocaleString('en-US');
  const win=window.open('','_blank'); if(!win) return;
  win.document.write(`<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>فاتورة ${orderId}</title><link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet"><style>body{font-family:Cairo,sans-serif;padding:20px}.box{max-width:800px;margin:auto;border:2px solid #D4AF37;border-radius:14px;padding:20px}table{width:100%;border-collapse:collapse;margin:14px 0}th,td{border:1px solid #ccc;padding:8px;text-align:center}th{background:#111;color:#D4AF37}.tot{background:#111;color:#fff;padding:12px;border-radius:8px;display:flex;justify-content:space-between}</style></head><body><div class="box"><button onclick="window.print()" style="background:#D4AF37;border:none;padding:8px 16px;border-radius:6px;font-weight:bold;cursor:pointer">🖨️ طباعة</button><h2 style="color:#9A6F1A">FASHION WORLD</h2><p>فاتورة #${orderId} - ${new Date().toLocaleDateString('ar-SY')}</p><p><b>العميل:</b> ${escapeHtml(payload.buyer_name)} | ${escapeHtml(payload.buyer_phone)}<br>${escapeHtml(payload.buyer_address)}</p><table><thead><tr><th>#</th><th>الموديل</th><th>القسم</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead><tbody>${rowsHtml}</tbody></table><div class="tot"><span>الإجمالي</span><span>$${m.total} (${totalSyp} ل.س)</span></div></div><script>setTimeout(()=>window.print(),400);<\/script></body></html>`);
  win.document.close();
  await loadCart(); openCart();
}
window.printWholesaleInvoice=printWholesaleInvoice;

async function sendOrderWhatsApp(){
  if(!state.cart.length) return alert('السلة فارغة');
  let payload; try{ payload=buildOrderPayload(); }catch(e){ return alert(e.message); }
  saveBuyerInfo();
  try{
    const r=await apiPost('/api/public/orders.php',payload);
    const m=getCartMetrics();
    const lines=state.cart.map((c,i)=>`🔹 ${i+1}. *${c.name}* (${c.qty} - $${Math.round(c.price_usd*c.qty*100)/100})`).join('\n');
    const totalSyp=Math.round(m.total*state.exchangeRate).toLocaleString('en-US');
    const refLine=payload.referred_by?`\n🔖 مرجع: ${payload.referred_by}`:'';
    const msg=`✨ *طلبية جملة* (رقم ${r.order_id})\n👤 ${payload.buyer_name}\n📍 ${payload.buyer_address}\n📞 ${payload.buyer_phone}\n${payload.buyer_notes?'📝 '+payload.buyer_notes+'\n':''}━━━━━━\n${lines}\n━━━━━━\n📦 ${m.pieces} قطعة (${m.packs} سيرية)\n⚖️ ~${m.weight} كغ\n💰 *$${m.total}* (${totalSyp} ل.س)${refLine}`;
    await api('/api/admin/orders.php',{method:'PATCH',headers:{'X-CSRF-Token':''},body:JSON.stringify({id:r.order_id,status:'PENDING',whatsapp_sent:1})}).catch(()=>{});
    window.open(`https://wa.me/${state.whatsapp}?text=${encodeURIComponent(msg)}`,'_blank');
    await loadCart(); openCart();
    showToast('تم تسجيل الطلب وإرساله ✅');
  }catch(e){ showToast(e.message,'error'); }
}
window.sendOrderWhatsApp=sendOrderWhatsApp;

// ===== إقلاع =====
async function boot(){
  applySavedTheme();
  initFromStorage();
  await loadSettings();
  await loadProducts();
  await loadCart();
  // إذا كان هناك شريك محفوظ، تحقق أن جلسته ما زالت صالحة
  if (state.currentMarketer) {
    refreshDashboard();
  }
  setInterval(pollStore, 30000);
}

boot();
