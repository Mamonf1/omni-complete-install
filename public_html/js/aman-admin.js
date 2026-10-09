(function () {
  function adminDashboard() {
    const root = document.getElementById('admin-root');
    if (!root) return;
    root.innerHTML = `
      <div class="admin-box">
        <h2>لوحة الأدمن</h2>
        <p>نظرة عامة على المستخدمين، الطلبات، والمبيعات.</p>
      </div>
    `;
  }

  window.AmanAdmin = { adminDashboard };
})();
