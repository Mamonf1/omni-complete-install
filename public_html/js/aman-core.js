(function () {
  const base = window.location.origin + window.location.pathname.replace(/index\.html$/, '');

  function apiRequest(route, payload = {}, method = 'POST') {
    const token = localStorage.getItem('omni_token') || '';
    const headers = { 'Content-Type': 'application/json' };
    if (token) headers.Authorization = 'Bearer ' + token;

    return fetch(base + 'api.php?route=' + route, {
      method,
      headers,
      body: method === 'GET' ? undefined : JSON.stringify(payload)
    }).then(r => r.json());
  }

  window.OmniCore = {
    api: apiRequest,
    setToken(token) {
      localStorage.setItem('omni_token', token);
    },
    getToken() {
      return localStorage.getItem('omni_token') || '';
    },
    setUser(user) {
      localStorage.setItem('omni_user', JSON.stringify(user));
    },
    getUser() {
      try {
        return JSON.parse(localStorage.getItem('omni_user') || 'null');
      } catch (e) {
        return null;
      }
    },
    logout() {
      localStorage.removeItem('omni_token');
      localStorage.removeItem('omni_user');
      window.location.reload();
    }
  };
})();
