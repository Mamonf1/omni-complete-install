(function () {
  function renderHome(state) {
    const userName = state.user ? state.user.name : 'زائر';
    const topProducts = state.products.slice(0, 3);

    return `
      <section class="hero">
        <div class="hero-panel">
          <div class="eyebrow">متجر ذكي + محفظة رقمية</div>
          <h1>التجربة الرقمية الحديثة تبدأ من أومني</h1>
          <p>تسوق المنتجات، أضف إلى السلة، استلم الطلب، وادفع من المحفظة داخل نفس التطبيق مع لوحة إدارة متكاملة ودعم فوري.</p>
          <div class="hero-actions">
            <button class="primary-btn" data-view="products">تسوق الآن</button>
            <button class="secondary-btn" id="chat-toggle">الدعم الفوري</button>
          </div>
          <div class="stats">
            <div class="stat">
              <div class="num">${state.products.length}</div>
              <div class="label">منتج</div>
            </div>
            <div class="stat">
              <div class="num">${state.cart.length}</div>
              <div class="label">في السلة</div>
            </div>
            <div class="stat">
              <div class="num">${userName}</div>
              <div class="label">مستخدم</div>
            </div>
          </div>
        </div>

        <div class="hero-aside">
          <div class="wallet-box">
            <h3>محفظتي</h3>
            <div class="currency-row"><span>الرصيد OMNI</span><strong>${state.user ? Number(state.user.balance_omni || 0).toLocaleString('ar-SY') : '0'}</strong></div>
            <div class="currency-row"><span>الرصيد SYP</span><strong>${state.user ? Number(state.user.balance_syp || 0).toLocaleString('ar-SY') : '0'}</strong></div>
          </div>

          <div class="promo-box">
            <h3>خدماتنا</h3>
            <p class="muted">تسوق • تحويل • صرف • شحن • دعم</p>
            <div class="hero-actions">
              <button class="ghost-btn" data-view="products">استعراض المنتجات</button>
            </div>
          </div>
        </div>
      </section>

      <div class="section-head">
        <h2>منتجات مميزة</h2>
      </div>
      <div class="products-grid">
        ${topProducts.map((p) => `
          <article class="product-card">
            <div class="product-emoji">${p.icon || '🛍️'}</div>
            <h3>${p.name}</h3>
            <p>${p.description || 'منتج مصمم لتلبية احتياجاتك اليومية.'}</p>
            <div class="price-row">
              <div>
                <div class="price">${Number(p.price || 0).toLocaleString('ar-SY')} OMNI</div>
                ${p.old_price ? `<div class="old-price">${Number(p.old_price).toLocaleString('ar-SY')} OMNI</div>` : ''}
              </div>
              <button class="primary-btn small-btn" data-product-add="${p.id}">أضف</button>
            </div>
          </article>
        `).join('')}
      </div>
    `;
  }

  function renderProducts(state) {
    const filtered = state.filter === 'all'
      ? state.products
      : state.products.filter(p => p.category === state.filter || (state.filter === 'featured' ? p.featured : true));

    return `
      <div class="section-head">
        <h2>المتجر</h2>
        <div class="filters">
          <button class="chip ${state.filter === 'all' ? 'active' : ''}" data-filter="all">الكل</button>
          <button class="chip ${state.filter === 'featured' ? 'active' : ''}" data-filter="featured">مميز</button>
          <button class="chip ${state.filter === 'groceries' ? 'active' : ''}" data-filter="groceries">بقالة</button>
          <button class="chip ${state.filter === 'restaurants' ? 'active' : ''}" data-filter="restaurants">مطاعم</button>
          <button class="chip ${state.filter === 'drinks' ? 'active' : ''}" data-filter="drinks">مشروبات</button>
        </div>
      </div>

      <div class="products-grid">
        ${filtered.map((p) => `
          <article class="product-card">
            <div class="product-emoji">${p.icon || '🛒'}</div>
            <h3>${p.name}</h3>
            <p>${p.description || 'منتج عالي الجودة.'}</p>
            <div class="price-row">
              <div>
                <div class="price">${Number(p.price || 0).toLocaleString('ar-SY')} OMNI</div>
                ${p.old_price ? `<div class="old-price">${Number(p.old_price).toLocaleString('ar-SY')} OMNI</div>` : ''}
              </div>
              <button class="primary-btn small-btn" data-product-add="${p.id}">أضف</button>
            </div>
          </article>
        `).join('') || '<div class="card"><p>لا توجد منتجات في هذه الفئة.</p></div>'}
      </div>
    `;
  }

  function renderCart(state) {
    const total = state.cart.reduce((sum, item) => sum + Number(item.price || 0) * Number(item.qty || 1), 0);
    return `
      <div class="layout">
        <div class="panel">
          <h2>السلة</h2>
          ${state.cart.length ? state.cart.map(item => `
            <div class="cart-item">
              <div class="cart-item-meta">
                <strong>${item.name}</strong>
                <span class="muted">${Number(item.price || 0).toLocaleString('ar-SY')} OMNI</span>
              </div>
              <div class="qty-box">
                <button data-qty="${item.id}" data-delta="-1">-</button>
                <span>${item.qty}</span>
                <button data-qty="${item.id}" data-delta="1">+</button>
              </div>
              <button class="ghost-btn small-btn" data-remove-cart="${item.id}">حذف</button>
            </div>
          `).join('') : '<p class="muted">السلة فارغة الآن.</p>'}
        </div>

        <aside class="panel">
          <h3>ملخص الطلب</h3>
          <div class="summary-box">
            <div class="currency-row"><span>المجموع</span><strong>${Number(total).toLocaleString('ar-SY')} OMNI</strong></div>
          </div>
          <div class="hero-actions" style="margin-top:18px;">
            <button class="primary-btn" id="submit-order">إتمام الطلب</button>
          </div>
        </aside>
      </div>
    `;
  }

  function renderAuth(state) {
    const isLogin = state.authMode === 'login';
    return `
      <div class="panel" style="max-width:660px;margin:30px auto;">
        <div class="auth-tabs">
          <button class="tab ${isLogin ? 'active' : ''}" id="toggle-login">تسجيل الدخول</button>
          <button class="tab ${!isLogin ? 'active' : ''}" id="toggle-register">إنشاء حساب</button>
        </div>

        <form id="auth-form" class="form-grid">
          ${!isLogin ? `
            <div>
              <input name="name" placeholder="الاسم الكامل" required />
            </div>
            <div>
              <input name="phone" placeholder="رقم الهاتف" required />
            </div>
            <div>
              <input name="email" type="email" placeholder="البريد الإلكتروني" />
            </div>
            <div>
              <select name="role">
                <option value="customer">عميل</option>
                <option value="merchant">تاجر</option>
              </select>
            </div>
          ` : ''}

          <div>
            <input name="identifier" placeholder="رقم الهاتف أو البريد" required />
          </div>
          <div>
            <input name="password" type="password" placeholder="كلمة المرور" required />
          </div>
          <button class="primary-btn" type="submit">${isLogin ? 'دخول' : 'إنشاء الحساب'}</button>
        </form>
      </div>
    `;
  }

  window.AmanViews = {
    renderHome,
    renderProducts,
    renderCart,
    renderAuth
  };
})();
