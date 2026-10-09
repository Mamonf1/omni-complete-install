(function () {
  function renderChat() {
    return `
      <div class="panel" style="margin-top:20px;">
        <h3>الدعم الفوري</h3>
        <div class="chat-body" style="max-height:300px;">
          <div class="bubble bot">مرحباً! كيف يمكنني مساعدتك؟</div>
          <div class="bubble user">أرغب في معرفة منتج معين.</div>
        </div>
      </div>
    `;
  }

  window.AmanChat = { renderChat };
})();
