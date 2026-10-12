(function () {
  if (window.WebiliaWPAnnouncements) return;
  window.WebiliaWPAnnouncements = true;
  function init() {
    document.querySelectorAll('[data-webilia-announcements]').forEach(wrapper => {
      const config = JSON.parse(wrapper.dataset.webiliaAnnouncements);
      const request = async (action, id = '') => {
        const response = await fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin',
          body: new URLSearchParams({ action, consumer: config.consumer, nonce: config.nonce, id }) });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(config.labels.error);
        return result.data;
      };
      WebiliaUI.announcements.init(wrapper.querySelector('[data-wui-modal]'), {
        ...config, load: () => request('webilia_announcements_read'),
        dismiss: id => request('webilia_announcements_dismiss', id),
      });
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
  else init();
})();
