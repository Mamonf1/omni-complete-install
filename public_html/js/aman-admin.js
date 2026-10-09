(function () {
  function renderAdmin(state) {
    const user = state.user;
    if (!user || user.role !== 'admin') {
      return `
        <div class="panel" style="max-width:700px;margin:40px auto;">
          <h2>لوحة الإدارة</h2>
          <p class="muted">هذه الصفحة خاصة بالمدير فقط. سجّل الدخول بحساب مسؤول.</p>
        </div>
      `;
    }

    const stats = state.adminStats || {
      users: 0,
      orders: 0,
      products: 0,
      fund: 0
    };

    return `
      <div class="panel" style="margin-top:30px;">
        <h2>لوحة الإدارة</h2>
        <div class="products-grid">
          <div class="product-card">
            <div class="product-emoji">👥</div>
            <h3>المستخدمون</h3>
            <p class="price">${stats.users}</p>
          </div>
          <div class="product-card">
            <div class="product-emoji">🧾</div>
            <h3>الطلبات</h3>
            <p class="price">${stats.orders}</p>
          </div>
          <div class="product-card">
            <div class="product-emoji">📦</div>
            <h3>المنتجات</h3>
            <p class="price">${stats.products}</p>
          </div>
          <div class="product-card">
            <div class="product-emoji">💰</div>
            <h3>الصندوق</h3>
            <p class="price">${Number(stats.fund || 0).toLocaleString('ar-SY')} OMNI</p>
          </div>
        </div>
      </div>
    `;
  }

  window.AmanAdmin = { renderAdmin };
})();
