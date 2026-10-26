(function ($) {
    'use strict';
    const root = document.querySelector('.js-wui-di-page');
    if (!root) return;
    window.WebiliaUI.demoImport.init(root, {
        jquery: $,
        labels: window.webiliaWPImportLabels || {},
        request(config) {
            return $.ajax(Object.assign({}, config, {
                data: Object.assign({}, config.data, { action: 'webilia_wp_demo_' + config.data.action })
            }));
        }
    });
})(jQuery);
