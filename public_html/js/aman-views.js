(function () {
  function renderProducts(products) {
    const container = document.getElementById('products-list');
    if (!container) return;
    if (!products || !products.length) {
      container.innerHTML = '<p>لا توجد منتجات حالياً.</p>';
      return;
    }
    container.innerHTML = products.map(p => `
      <div class="product-card">
        <h3>${p.name}</h3>
        <div class="price">${Number(p.price || 0).toLocaleString()} OMNI</div>
        <p>${p.description || ''}</p>
        <button data-product-id="${p.id}">أضف للسلة</button>
      </div>
    `).join('');
  }

  window.AmanViews = { renderProducts };
})();
