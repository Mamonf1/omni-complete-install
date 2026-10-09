(function () {
  function renderHome(state) {
    const userName = state.user ? state.user.name : 'زائر';
    const topProducts = state.products.slice(0, 3);

    return `
      <section class="hero premium-hero">
        <div class="hero-panel">
          <div class="badge">Omni Premium</div>
          <h1>مستقبل التسوق الرقمي</h1>
          <p>منصة متكاملة تجمع بين المتجر الإلكتروني والمحفظة الرقمية والدعم الفوري وإدارة الطلبات في تجربة واحدة أنيقة ومعاصرة.</p>
          <div class="hero-actions">
            <button class="primary-btn" data-view="products">تسوق الآن</button>
            <button class="secondary-btn" id="chat-toggle">الدعم المباشر</button>
          </div>
          <div class="stats-grid">
            <div class="stat-box">
              <strong>${state.products.length}</strong>
              <span>منتجات</span>
            </div>
            <div class="stat-box">
              <strong>${state.cart.length}</strong>
              <span>في السلة</span>
            </div>
            <div class="stat-box">
              <strong>${escapeHtml(userName)}</strong>
              <span>مستخدم</span>
            </div>
          </div>
        </div>

        <div class="hero-side">
          <div class="wallet-card">
            <div class="mini-title">محفظتي</div>
            <div class="wallet-row"><span>OMNI</span><strong>${state.user ? Number(state.user.balance_omni || 0).toLocaleString('ar-SY') : '0'}</strong></div>
            <div class="wallet-row"><span>SYP</span><strong>${state.user ? Number(state.user.balance_syp || 0).toLocaleString('ar-SY') : '0'}</strong></div>
          </div>

          <div class="promo-card">
            <div class="mini-title">خدماتنا</div>
            <ul>
              <li>بيع المنتجات</li>
              <li>تحويل أموال</li>
              <li>صرف العملات</li>
              <li>دعم عربي مباشر</li>
            </ul>
          </div>
        </div>
      </section>

      <section class="showcase">
        <div class="section-head">
          <h2>منتجات مميزة</h2>
          <button class="ghost-btn" data-view="products">عرض الكل</button>
        </div>
        <div class="products-grid">
          ${topProducts.map((p) => `
            <article class="product-card premium-card">
              <div class="product-icon">${p.icon || '🛍️'}</div>
              <h3>${escapeHtml(p.name)}</h3>
              <p>${escapeHtml(p.description || 'منتج عالي الجودة ومناسب لاحتياجاتك اليومية.')}</p>
              <div class="product-footer">
                <div>
                  <div class="price">${Number(p.price || 0).toLocaleString('ar-SY')} OMNI</div>
                  ${p.old_price ? `<div class="old-price">${Number(p.old_price).toLocaleString('ar-SY')} OMNI</div>` : ''}
                </div>
                <button class="primary-btn small-btn" data-product-add="${p.id}">أضف</button>
              </div>
            </article>
          `).join('')}
        </div>
      </section>
    `;
  }

  function renderProducts(state) {
    const filtered = state.filter === 'all'
      ? state.products
      : state.products.filter(p => p.category === state.filter || (state.filter === 'featured' ? p.featured : true));

    return `
      <section class="products-page">
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
            <article class="product-card premium-card">
              <div class="product-icon">${p.icon || '🛒'}</div>
              <h3>${escapeHtml(p.name)}</h3>
              <p>${escapeHtml(p.description || 'منتج مميز بجودة عالية.')}</p>
              <div class="product-footer">
                <div>
                  <div class="price">${Number(p.price || 0).toLocaleString('ar-SY')} OMNI</div>
                  ${p.old_price ? `<div class="old-price">${Number(p.old_price).toLocaleString('ar-SY')} OMNI</div>` : ''}
                </div>
                <button class="primary-btn small-btn" data-product-add="${p.id}">أضف</button>
              </div>
            </article>
          `).join('') || '<div class="empty-panel"><p>لا توجد منتجات في هذه الفئة.</p></div>'}
        </div>
      </section>
    `;
  }

  function renderCart(state) {
    const total = state.cart.reduce((sum, item) => sum + Number(item.price || 0) * Number(item.qty || 1), 0);
    return `
      <section class="cart-layout">
        <div class="panel premium-panel">
          <div class="section-head">
            <h2>سلة المشتريات</h2>
            <span class="tiny-pill">${state.cart.length} عنصر</span>
          </div>

          ${state.cart.length ? state.cart.map(item => `
            <div class="cart-item">
              <div>
                <strong>${escapeHtml(item.name)}</strong>
                <div class="muted">${Number(item.price || 0).toLocaleString('ar-SY')} OMNI</div>
              </div>
              <div class="qty-wrap">
                <div class="qty-box">
                  <button data-qty="${item.id}" data-delta="-1">−</button>
                  <span>${item.qty}</span>
                  <button data-qty="${item.id}" data-delta="1">＋</button>
                </div>
                <button class="ghost-btn small-btn" data-remove-cart="${item.id}">حذف</button>
              </div>
            </div>
          `).join('') : '<div class="empty-panel"><p>السلة فارغة الآن.</p></div>'}
        </div>

        <aside class="panel premium-panel summary-panel">
          <h3>ملخص الطلب</h3>
          <div class="summary-box">
            <div class="summary-row"><span>المجموع</span><strong>${Number(total).toLocaleString('ar-SY')} OMNI</strong></div>
          </div>
          <button class="primary-btn wide" id="submit-order">إتمام الطلب</button>
        </aside>
      </section>
    `;
  }

  function renderAuth(state) {
    const isLogin = state.authMode === 'login';
    return `
      <div class="panel premium-panel auth-panel">
        <div class="auth-tabs">
          <button class="tab ${isLogin ? 'active' : ''}" id="toggle-login">تسجيل الدخول</button>
          <button class="tab ${!isLogin ? 'active' : ''}" id="toggle-register">إنشاء حساب</button>
        </div>

        <form id="auth-form" class="form-grid">
          ${!isLogin ? `
            <div><input name="name" placeholder="الاسم الكامل" required /></div>
            <div><input name="phone" placeholder="رقم الهاتف" required /></div>
            <div><input name="email" type="email" placeholder="البريد الإلكتروني" /></div>
            <div>
              <select name="role">
                <option value="customer">عميل</option>
                <option value="merchant">تاجر</option>
              </select>
            </div>
          ` : ''}
          <div><input name="identifier" placeholder="رقم الهاتف أو البريد" required /></div>
          <div><input name="password" type="password" placeholder="كلمة المرور" required /></div>
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
