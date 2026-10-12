(function () {
  if (window.WebiliaWPFeedback) return;
  window.WebiliaWPFeedback = true;
  const dialogs = new Map();
  function init() {
    document.querySelectorAll('[data-webilia-feedback]').forEach(wrapper => {
      const config = JSON.parse(wrapper.dataset.webiliaFeedback);
      const modal = wrapper.querySelector('[data-wui-modal]');
      let targetUrl = '';
      const deactivate = () => { if (targetUrl) window.location.assign(targetUrl); };
      WebiliaUI.deactivation.init(modal, {
        ...config, skip: deactivate, complete: deactivate,
        submit: async ({ reason, details }) => {
          const controller = new AbortController();
          const timer = setTimeout(() => controller.abort(), 15000);
          try {
            const response = await fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', signal: controller.signal,
              body: new URLSearchParams({ action: 'webilia_feedback', action_type: 'submit_feedback', basename: config.basename,
                nonce: config.nonce, reason_key: reason, details }) });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.data?.message || config.error);
          } catch (error) { throw new Error(config.error); }
          finally { clearTimeout(timer); }
        },
      });
      dialogs.set(config.basename, { open(url) { targetUrl = url; WebiliaUI.modal.open(modal); } });
    });
  }
  document.addEventListener('click', event => {
    const link = event.target.closest('a[href]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    const url = new URL(link.href, document.baseURI);
    if (url.origin !== window.location.origin || url.searchParams.get('action') !== 'deactivate') return;
    const dialog = dialogs.get(url.searchParams.get('plugin'));
    if (!dialog) return;
    event.preventDefault(); dialog.open(link.href);
  });
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
  else init();
})();
