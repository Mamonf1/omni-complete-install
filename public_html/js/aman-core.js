(function () {
  const storage = {
    token: 'omni_token',
    user: 'omni_user',
    cart: 'omni_cart'
  };

  function api(route, payload = {}, method = 'POST') {
    const token = localStorage.getItem(storage.token) || '';
    const headers = { 'Content-Type': 'application/json' };
    if (token) headers.Authorization = 'Bearer ' + token;

    const options = {
      method,
      headers
    };

    if (method !== 'GET') {
      options.body = JSON.stringify(payload);
    }

    return fetch('api.php?route=' + route, options).then(async (res) => {
      const text = await res.text();
      try {
        return JSON.parse(text);
      } catch (e) {
        return { success: false, error: text || 'حدث خطأ في الخادم' };
      }
    });
  }

  function getUser() {
    try {
      return JSON.parse(localStorage.getItem(storage.user) || 'null');
    } catch (e) {
      return null;
    }
  }

  function setUser(user) {
    localStorage.setItem(storage.user, JSON.stringify(user));
  }

  function getToken() {
    return localStorage.getItem(storage.token) || '';
  }

  function setToken(token) {
    localStorage.setItem(storage.token, token);
  }

  function getCart() {
    try {
      return JSON.parse(localStorage.getItem(storage.cart) || '[]');
    } catch (e) {
      return [];
    }
  }

  function saveCart(cart) {
    localStorage.setItem(storage.cart, JSON.stringify(cart));
  }

  window.OmniCore = {
    api,
    getUser,
    setUser,
    getToken,
    setToken,
    getCart,
    saveCart
  };
})();
