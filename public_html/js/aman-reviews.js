(function () {
  function renderReviews(productId = null) {
    const product = productId ? 'المنتج المحدد' : 'هذا المنتج';
    return `
      <div class="panel" style="margin-top:18px;">
        <h3>التقييمات</h3>
        <div class="summary-box">
          <strong>${product}</strong>
          <p class="muted">لا توجد تقييمات بعد، كن أول من يضيف رأيه.</p>
        </div>
      </div>
    `;
  }

  window.AmanReviews = { renderReviews };
})();
