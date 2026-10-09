(function () {
  function openChat() {
    const root = document.getElementById('chat-root');
    if (!root) return;
    root.innerHTML = `
      <div class="chat-box">
        <h3>الدردشة</h3>
        <div class="chat-messages">
          <p>مرحباً، كيف يمكننا مساعدتك؟</p>
        </div>
        <input type="text" placeholder="اكتب رسالتك..." />
        <button>إرسال</button>
      </div>
    `;
  }

  window.AmanChat = { openChat };
})();
