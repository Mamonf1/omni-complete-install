(function () {
  function renderReviews(productId) {
    const root = document.getElementById('reviews-root');
    if (!root) return;
    root.innerHTML = `
      <div class="review-box">
        <h3>التقييمات</h3>
        <p>لا توجد تقييمات لهذا المنتج بعد.</p>
      </div>
    `;
  }

  window.AmanReviews = { renderReviews };
})();
