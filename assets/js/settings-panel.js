(function () {
    'use strict';
    if (window.WebiliaWPSettingsInitialized) return;
    window.WebiliaWPSettingsInitialized = true;
    const runtime = window.WebiliaUI.settingsPanel.create({
        customizer: !!(window.wp && window.wp.customize),
        labels: window.webiliaWPSettingsI18n || {},
        setColor(input, value) {
            const $ = window.jQuery;
            if (!$ || !$.fn.wpColorPicker || !$(input).data('wpColorPicker')) return false;
            $(input).wpColorPicker('color', value);
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            return true;
        },
        selectMedia(config, done) {
            const frame = wp.media(config);
            frame.on('select', function () {
                const selection = frame.state().get('selection').first();
                if (selection) done(selection.toJSON());
            });
            frame.open();
        },
        sortable(list, update) {
            window.jQuery(list).sortable({ axis: 'y', handle: '.wui-sp-repeater-handle', update });
        }
    });
    const { app, Utils, webiliaSettingsToastRoot } = runtime;
    const ColorPickersModule = (function () {
        function initColorPickers(context) {
            if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.wpColorPicker) return;

            const $ = window.jQuery;
            const scope = context ? $(context) : $(document);
            const brushLabel = 'Select color';

            function dispatchNativeColorEvents(input) {
                if (!input) return;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }

            function setColorPickerIcon(container) {
                if (!container || !container.length) return;
                let label = brushLabel;

                const text = container.find('.wp-color-result-text');
                if (!text.length || text.find('.wui-sp-color-icon').length) return;

                const existing = text.text().trim();
                if (existing) label = existing;

                text.empty();
                text.append('<i class="fa-solid fa-paintbrush wui-sp-color-icon" aria-hidden="true"></i>');
                text.append('<span class="screen-reader-text">' + label + '</span>');
            }

            function resolveColorOriginValue($input) {
                const origin =
                    $input.attr('data-wui-sp-color-origin') ||
                    $input.closest('.wui-sp-field').attr('data-wui-sp-color-origin');
                if (!origin) return null;

                const originField = document.querySelector('.wui-sp-field[data-field-id="' + Utils.cssEscape(origin) + '"]');
                if (!originField) return null;

                const originInput = originField.querySelector('input.wui-sp-colorpicker, input.wui-sp-colorpicker-rgba');
                return originInput ? (originInput.value || '') : null;
            }

            scope.find('.wui-sp-colorpicker, .wui-sp-colorpicker-rgba').each(function () {
                const $input = $(this);
                if ($input.data('wpColorPicker')) return;

                const options = {
                    change: function (event, ui) {
                        if (ui && ui.color) {
                            $input.val(ui.color.toString());
                        }
                        dispatchNativeColorEvents($input.get(0));
                    },
                    clear: function () {
                        $input.val('');
                        dispatchNativeColorEvents($input.get(0));
                    }
                };

                const defaultColor = $input.attr('data-default');
                if (defaultColor !== undefined && defaultColor !== '') options.defaultColor = defaultColor;
                if ($input.hasClass('wui-sp-colorpicker-rgba')) options.alpha = true;

                $input.wpColorPicker(options);
                setColorPickerIcon($input.closest('.wp-picker-container'));

                const container = $input.closest('.wp-picker-container');
                if (container && !container.data('wui-sp-origin-default')) {
                    container.data('wui-sp-origin-default', true);
                    container.on('click', '.wp-picker-default', function (e) {
                        e.preventDefault();
                        const originValue = resolveColorOriginValue($input);
                        if (originValue !== null) {
                            $input.wpColorPicker('color', originValue);
                            dispatchNativeColorEvents($input.get(0));
                            return;
                        }
                        if (defaultColor !== undefined && defaultColor !== '') {
                            $input.wpColorPicker('color', defaultColor);
                            dispatchNativeColorEvents($input.get(0));
                            return;
                        }
                        $input.wpColorPicker('color', '');
                        $input.val('').trigger('change');
                        dispatchNativeColorEvents($input.get(0));
                    });
                }
                if (container && !container.find('.wui-sp-color-default').length) {
                    const button = $('<button type="button" class="button wui-sp-color-default">Default</button>');
                    container.find('.wp-picker-clear').before(button);

                    button.on('click', function (e) {
                        e.preventDefault();
                        const originValue = resolveColorOriginValue($input);
                        if (originValue !== null) {
                            $input.wpColorPicker('color', originValue);
                            dispatchNativeColorEvents($input.get(0));
                            return;
                        }
                        if (defaultColor !== undefined && defaultColor !== '') {
                            $input.wpColorPicker('color', defaultColor);
                            dispatchNativeColorEvents($input.get(0));
                            return;
                        }
                        $input.wpColorPicker('color', '');
                        $input.val('').trigger('change');
                        dispatchNativeColorEvents($input.get(0));
                    });
                }
            });
        }

        return {
            name: 'ColorPickersModule',
            init(context) {
                initColorPickers(context);
            },
            initPickers(context) {
                initColorPickers(context);
            }
        };
    })();

    const SortablesModule = (function () {
        function initSortables(context) {
            if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.sortable) return;

            const $ = window.jQuery;
            const scope = context ? $(context) : $(document);

            scope.find('.wui-sp-sortable').each(function () {
                const $list = $(this);
                if ($list.data('wui-sp-sortable')) return;
                $list.data('wui-sp-sortable', true);
                $list.sortable({ axis: 'y' });
            });
        }

        return {
            name: 'SortablesModule',
            init(context) {
                initSortables(context);
            }
        };
    })();

    const MediaModule = (function () {
        let bound = false;

        function initMediaFields(app) {
            if (!window.wp || !window.wp.media) return;
            if (bound) return;
            bound = true;

            function updateRemoveState(group) {
                if (!group) return;
                const urlInput = group.querySelector('.wui-sp-media-url');
                const idInput = group.querySelector('.wui-sp-media-id');
                const remove = group.querySelector('.wui-sp-media-remove');
                if (!remove) return;

                let hasValue = false;
                if (urlInput && urlInput.value) hasValue = true;
                if (idInput && idInput.value) hasValue = true;

                remove.classList.toggle('is-hidden', !hasValue);
            }

            document.addEventListener('click', function (e) {
                const button = e.target.closest('.wui-sp-media-select');
                if (!button) return;

                e.preventDefault();

                const group = button.closest('.wui-sp-media') || button.closest('.wui-sp-field-group');
                if (!group) return;

                const urlInput = group.querySelector('.wui-sp-media-url');
                const idInput = group.querySelector('.wui-sp-media-id');
                if (!urlInput && !idInput) return;

                const previewRow = group.querySelector('.wui-sp-media-input') || button.closest('.wui-sp-field-row');
                let preview = group.querySelector('.wui-sp-media-preview');

                const frame = wp.media({
                    title: 'Select Media',
                    button: { text: 'Use this media' },
                    multiple: false
                });

                frame.on('select', function () {
                    const selection = frame.state().get('selection').first();
                    if (!selection) return;

                    const data = selection.toJSON();
                    if (urlInput) {
                        urlInput.value = data.url || '';
                        urlInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    if (idInput) {
                        idInput.value = data.id || '';
                        idInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    updateRemoveState(group);

                    if (!preview && previewRow) {
                        preview = document.createElement('div');
                        preview.className = 'wui-sp-field-row wui-sp-media-preview';
                        previewRow.parentNode.insertBefore(preview, previewRow);
                    }

                    if (preview) {
                        let url = data.url || '';
                        const isImage = data.type === 'image';
                        if (isImage && data.sizes) {
                            const size = data.sizes.thumbnail || data.sizes.medium || data.sizes.full;
                            if (size && size.url) url = size.url;
                        }

                        if (isImage && url) {
                            preview.innerHTML = '<img class="wui-sp-media-preview-image" src="' + url + '" alt="">';
                        } else if (url) {
                            preview.innerHTML = '<a class="wui-sp-media-preview" href="' + url + '" target="_blank">' + url + '</a>';
                        }
                    }
                });

                frame.open();
            });

            document.addEventListener('click', function (e) {
                const remove = e.target.closest('.wui-sp-media-remove');
                if (!remove) return;

                e.preventDefault();

                const group = remove.closest('.wui-sp-media') || remove.closest('.wui-sp-field-group');
                if (!group) return;

                const urlInput = group.querySelector('.wui-sp-media-url');
                const idInput = group.querySelector('.wui-sp-media-id');
                const preview = group.querySelector('.wui-sp-media-preview');

                if (urlInput) {
                    urlInput.value = '';
                    urlInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
                if (idInput) {
                    idInput.value = '';
                    idInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
                if (preview) preview.innerHTML = '';

                updateRemoveState(group);
            });

            document.addEventListener('input', function (e) {
                if (!e.target) return;
                if (!e.target.classList.contains('wui-sp-media-url') && !e.target.classList.contains('wui-sp-media-id')) return;
                const group = e.target.closest('.wui-sp-media') || e.target.closest('.wui-sp-field-group');
                updateRemoveState(group);
            });

            document.addEventListener('change', function (e) {
                if (!e.target) return;
                if (!e.target.classList.contains('wui-sp-media-url') && !e.target.classList.contains('wui-sp-media-id')) return;
                const group = e.target.closest('.wui-sp-media') || e.target.closest('.wui-sp-field-group');
                updateRemoveState(group);
            });
        }

        return {
            name: 'MediaModule',
            init(context, app) {
                if (context !== document) return;
                initMediaFields(app);
            }
        };
    })();

    const GalleryModule = (function () {
        let bound = false;

        function initGalleryFields(app) {
            if (!window.wp || !window.wp.media) return;
            if (bound) return;
            bound = true;

            function getIds(list) {
                return Array.prototype.map
                .call(list.querySelectorAll('li'), function (item) {
                    return item.getAttribute('data-id');
                })
                .filter(function (value) {
                    return value;
                });
            }

            function updateInput(wrapper) {
                const input = wrapper.querySelector('.wui-sp-gallery-input');
                const list = wrapper.querySelector('.wui-sp-gallery-items');
                if (!input || !list) return;
                input.value = getIds(list).join(',');
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }

            function updateEmptyState(wrapper) {
                const empty = wrapper.querySelector('.wui-sp-gallery-empty');
                const list = wrapper.querySelector('.wui-sp-gallery-items');
                if (!empty || !list) return;
                empty.style.display = list.querySelectorAll('li').length ? 'none' : 'block';
            }

            function buildItem(id, url) {
                const li = document.createElement('li');
                li.setAttribute('data-id', id);

                if (url) {
                    const img = document.createElement('img');
                    img.setAttribute('src', url);
                    img.setAttribute('alt', '');
                    li.appendChild(img);
                } else {
                    const missing = document.createElement('span');
                    missing.className = 'wui-sp-gallery-missing';
                    missing.textContent = 'Missing';
                    li.appendChild(missing);
                }

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'wui-sp-gallery-remove';
                button.setAttribute('aria-label', 'Remove image');
                button.setAttribute('title', 'Remove');

                const icon = document.createElement('span');
                icon.className = 'dashicons dashicons-no-alt';
                icon.setAttribute('aria-hidden', 'true');
                button.appendChild(icon);
                li.appendChild(button);

                return li;
            }

            // initial state (panel-only)
            Utils.qsa('.wui-sp-gallery', document).forEach((wrapper) => {
                updateEmptyState(wrapper);
            });

            document.addEventListener('click', function (e) {
                const select = e.target.closest('.wui-sp-gallery-select');
                if (!select) return;

                const wrapper = select.closest('.wui-sp-gallery');
                if (!wrapper) return;

                const list = wrapper.querySelector('.wui-sp-gallery-items');
                if (!list) return;

                const currentIds = getIds(list);

                const frame = wp.media({
                    title: 'Select Images',
                    button: { text: 'Use images' },
                    multiple: true
                });

                frame.on('select', function () {
                    const selection = frame.state().get('selection');
                    if (!selection) return;

                    selection.each(function (attachment) {
                        const data = attachment.toJSON();
                        const id = String(data.id || '');
                        if (!id || currentIds.indexOf(id) !== -1) return;

                        let url = '';
                        if (data.sizes && data.sizes.thumbnail && data.sizes.thumbnail.url) {
                            url = data.sizes.thumbnail.url;
                        } else if (data.url) {
                            url = data.url;
                        }

                        list.appendChild(buildItem(id, url));
                        currentIds.push(id);
                    });

                    updateInput(wrapper);
                    updateEmptyState(wrapper);
                });

                frame.open();
            });

            document.addEventListener('click', function (e) {
                const remove = e.target.closest('.wui-sp-gallery-remove');
                if (!remove) return;

                const wrapper = remove.closest('.wui-sp-gallery');
                const item = remove.closest('li');
                if (item) item.remove();

                if (wrapper) {
                    updateInput(wrapper);
                    updateEmptyState(wrapper);
                }
            });

            document.addEventListener('click', function (e) {
                const clear = e.target.closest('.wui-sp-gallery-clear');
                if (!clear) return;

                const wrapper = clear.closest('.wui-sp-gallery');
                if (!wrapper) return;

                const list = wrapper.querySelector('.wui-sp-gallery-items');
                if (list) list.innerHTML = '';

                updateInput(wrapper);
                updateEmptyState(wrapper);
            });
        }

        return {
            name: 'GalleryModule',
            init(context, app) {
                if (context !== document) return;
                initGalleryFields(app);
            }
        };
    })();

    const AjaxActionsModule = (function () {
        let bound = false;
        let actionToast = null;

        function getActiveSectionId() {
            const active = document.querySelector('.wui-sp-panel-section.is-active');
            return active ? active.dataset.section || '' : '';
        }

        function getActiveSection() {
            return document.querySelector('.wui-sp-panel-section.is-active');
        }

        function updateSectionHeading() {
            const heading = document.querySelector('.wui-sp-panel-section-title');
            if (!heading) return;

            const activeSection = getActiveSection();
            heading.textContent = activeSection ? (activeSection.getAttribute('data-section-title') || '') : '';
        }

        function clearInlineActionMessage(form) {
            if (!form) return;

            const target = form.querySelector('.wui-sp-panel-actions-message');
            if (!target) return;

            target.textContent = '';
            target.classList.remove('is-error');
        }

        function getToastPosition() {
            const isRtl = document.documentElement && document.documentElement.getAttribute('dir') === 'rtl';

            return isRtl ? 'bottom-left' : 'bottom-right';
        }

        function getToastType(type, isError) {
            switch (type) {
                case 'lsd-in-progress': return 'loading';
                case 'lsd-error': return 'danger';
                case 'lsd-warning': return 'warning';
                case 'lsd-info': return 'info';
                case 'lsd-success': return 'success';
                default: return isError ? 'danger' : 'success';
            }
        }

        function removeActionToast() {
            if (!actionToast || typeof actionToast.dismiss !== 'function') return;

            actionToast.dismiss();
            actionToast = null;
        }

        function setActionMessage(form, message, isError, options) {
            const config = options || {};
            const text = message || '';

            clearInlineActionMessage(form);

            if (!text) {
                if (config.clearToast) removeActionToast();
                return;
            }

            if (!window.WebiliaUI || !window.WebiliaUI.toast) {
                const target = form ? form.querySelector('.wui-sp-panel-actions-message') : null;
                if (!target) return;

                target.textContent = text;
                target.classList.toggle('is-error', !!isError);
                return;
            }

            const type = getToastType(config.type, isError);
            const hideTime = typeof config.hideTime === 'number' ? config.hideTime : 5000;
            const progress = config.progress !== false && hideTime > 0;
            if (config.update && actionToast && typeof actionToast.update === 'function') {
                actionToast.update(text, { tone: type, durationMs: hideTime, progress: progress });

                return;
            }

            if (config.replace !== false) removeActionToast();

            actionToast = window.WebiliaUI.toast.show(text, {
                root: webiliaSettingsToastRoot(),
                tone: type,
                position: config.position || getToastPosition(),
                durationMs: hideTime,
                progress: progress,
                closeLabel: window.webiliaWPSettingsI18n.closeLabel
            });
        }

        function toggleActions(form, disabled) {
            Array.prototype.forEach.call(form.querySelectorAll('.wui-sp-panel-actions button'), function (button) {
                button.disabled = !!disabled;
            });
        }

        function WebiliaButtonLoader(button) {
            this.button = button;
            this.originalHtml = button ? button.innerHTML : '';

            this.start = function (loadingText) {
                if (!this.button) return;
                const text = loadingText || this.button.getAttribute('data-loading-text') || this.button.textContent || 'Loading...';
                this.button.innerHTML =
                    '<span class="wui-sp-button-label">' +
                    text +
                    '</span> <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>';
                this.button.setAttribute('disabled', 'disabled');
                this.button.classList.add('is-loading');
            };

            this.stop = function () {
                if (!this.button) return;
                this.button.innerHTML = this.originalHtml;
                this.button.removeAttribute('disabled');
                this.button.classList.remove('is-loading');
            };
        }

        function serializePanelForm(form) {
            const $ = window.jQuery;
            if (!form || !$.fn || !$.fn.serialize) return '';

            if (form.tagName && form.tagName.toLowerCase() === 'form') {
                return $(form).serialize();
            }
            return $(form).find(':input').serialize();
        }

        async function ajaxAction(form, action, extraData, confirmMessage, triggerButton) {
            if (!window.jQuery || typeof window.ajaxurl === 'undefined') return;
            if (confirmMessage) {
                const approved = await window.WebiliaUI.modal.confirm({
                    title: window.webiliaWPSettingsI18n.resetTitle,
                    message: confirmMessage,
                    confirmLabel: window.webiliaWPSettingsI18n.reset,
                    cancelLabel: window.webiliaWPSettingsI18n.cancel,
                    closeLabel: window.webiliaWPSettingsI18n.dialogCloseLabel,
                    danger: true
                });
                if (!approved) return;
            }

            const $ = window.jQuery;
            const loader = triggerButton ? new WebiliaButtonLoader(triggerButton) : null;
            if (loader) loader.start();

            const optName = form.getAttribute('data-wui-sp-opt-name') || '';
            const nonce = form.getAttribute('data-wui-sp-nonce') || '';
            const section = typeof getActiveSectionId === 'function' ? getActiveSectionId() : '';

            const importSelector = '[name="' + optName + '[import_export][import]"]';
            const importField = form.querySelector(importSelector);

            let importB64 = '';
            let originalImport = '';

            if (importField && typeof importField.value === 'string' && importField.value.trim() !== '') {
                originalImport = importField.value;
                importB64 = btoa(unescape(encodeURIComponent(originalImport)));
                importField.value = '';
            }

            const payload = {
                action: action,
                nonce: nonce,
                opt_name: optName,
                section: section,
                data: serializePanelForm(form)
            };

            if (importField) importField.value = originalImport;

            if (importB64) payload.import_b64 = importB64;

            if (extraData) Object.keys(extraData).forEach(function (key) {payload[key] = extraData[key];});

            toggleActions(form, true);
            setActionMessage(form, window.webiliaWPSettingsI18n.saving, false, {
                type: 'lsd-in-progress',
                hideTime: 0,
                progress: false
            });

            $.post(window.ajaxurl, payload)
            .done(function (response) {
                if (response && response.success) {
                    setActionMessage(
                        form,
                        response.data && response.data.message ? response.data.message : window.webiliaWPSettingsI18n.done,
                        false,
                        {
                            type: 'lsd-success',
                            hideTime: 5000,
                            progress: true
                        }
                    );

                    if (importB64 || action !== 'webilia_wp_settings_save')
                    {
                        window.location.reload();
                        return;
                    }
                } else {
                    setActionMessage(
                        form,
                        response && response.data && response.data.message ? response.data.message : window.webiliaWPSettingsI18n.error,
                        true,
                        {
                            type: 'lsd-error',
                            hideTime: 7000,
                            progress: true
                        }
                    );
                }
            })
            .fail(function () {
                setActionMessage(form, window.webiliaWPSettingsI18n.error, true, {
                    type: 'lsd-error',
                    hideTime: 7000,
                    progress: true
                });
            })

            .always(function () {
                if (loader) loader.stop();
                toggleActions(form, false);
            });
        }

        function initAjaxActions(app) {
            if (app.isCustomizer) return;
            if (bound) return;
            bound = true;

            const panelForm = document.querySelector('.wui-sp-panel-form');
            if (!panelForm) return;

            updateSectionHeading();
            window.addEventListener('hashchange', updateSectionHeading);
            document.addEventListener('wui-sp:section-change', updateSectionHeading);

            panelForm.addEventListener('click', function (e) {
                const saveBtn = e.target.closest('.wui-sp-action-save');
                if (saveBtn)
                {
                    e.preventDefault();
                    ajaxAction(panelForm, 'webilia_wp_settings_save', null, null, saveBtn);
                    return;
                }

                const resetSection = e.target.closest('.wui-sp-action-reset-section-icon');
                if (resetSection)
                {
                    e.preventDefault();

                    ajaxAction(panelForm, 'webilia_wp_settings_reset_section', null, window.webiliaWPSettingsI18n.resetSection, resetSection);
                    return;
                }

                const resetAll = e.target.closest('.wui-sp-action-reset-all');
                if (resetAll) {
                    e.preventDefault();
                    ajaxAction(panelForm, 'webilia_wp_settings_reset_all', null, window.webiliaWPSettingsI18n.resetAll, resetAll);
                }
            });
        }

        return {
            name: 'AjaxActionsModule',
            init(context, app) {
                if (context !== document) return;
                initAjaxActions(app);
            }
        };
    })();

    const CustomizerModule = (function () {
        let bound = false;

        function initCustomizerBindings(app) {
            if (!window.wp || !window.wp.customize || !window.jQuery) return;
            if (bound) return;
            bound = true;

            function parseKeys(name, baseName) {
                if (!name || !baseName) return null;
                if (name.indexOf(baseName) !== 0) return null;

                const remainder = name.slice(baseName.length);
                const keys = [];
                remainder.replace(/\[([^\]]*)\]/g, function (match, key) {
                    keys.push(key);
                    return match;
                });

                return keys;
            }

            function assignValue(obj, keys, value) {
                if (!keys || !keys.length) return value;

                const key = keys[0];
                if (key === '') {
                    const arr = Array.isArray(obj) ? obj : [];
                    if (keys.length === 1) {
                        arr.push(value);
                        return arr;
                    }
                    const next = assignValue({}, keys.slice(1), value);
                    arr.push(next);
                    return arr;
                }

                const out = obj && typeof obj === 'object' && !Array.isArray(obj) ? obj : {};
                if (keys.length === 1) {
                    if (Object.prototype.hasOwnProperty.call(out, key)) {
                        if (!Array.isArray(out[key])) out[key] = [out[key]];
                        out[key].push(value);
                    } else {
                        out[key] = value;
                    }
                    return out;
                }

                out[key] = assignValue(out[key], keys.slice(1), value);
                return out;
            }

            function serializeControl(control) {
                const container = control.container;
                if (!container || !container.length) return null;

                const baseName = control.params && control.params.field_base_name ? control.params.field_base_name : '';
                if (!baseName) return null;

                const inputs = container.find('input, select, textarea').filter(function () {
                    const name = this.name || '';
                    return name.indexOf(baseName) === 0;
                });

                if (!inputs.length) return null;

                const checkedCheckboxes = {};
                const checkboxNames = {};

                inputs.filter('input[type="checkbox"]').each(function () {
                    const name = this.name || '';
                    if (!name) return;
                    checkboxNames[name] = true;
                    if (this.checked) checkedCheckboxes[name] = true;
                });

                const pairs = [];
                const checkboxInputs = inputs.filter('input[type="checkbox"]');
                inputs.each(function () {
                    const input = this;
                    const name = input.name || '';
                    if (!name) return;

                    const type = (input.type || '').toLowerCase();
                    if (type === 'hidden' && checkboxNames[name] && checkedCheckboxes[name]) return;
                    if ((type === 'checkbox' || type === 'radio') && !input.checked) return;

                    if (type === 'select-multiple' || ((input.tagName || '').toLowerCase() === 'select' && input.multiple)) {
                        Array.prototype.forEach.call(input.selectedOptions, function (option) {
                            pairs.push({ name: name, value: option.value });
                        });
                        return;
                    }

                    pairs.push({ name: name, value: input.value });
                });

                let result = null;
                let hasAny = false;

                pairs.forEach(function (pair) {
                    const keys = parseKeys(pair.name, baseName);
                    if (keys === null) return;

                    if (!keys.length) {
                        result = pair.value;
                        hasAny = true;
                        return;
                    }

                    result = assignValue(result, keys, pair.value);
                    hasAny = true;
                });

                if (!hasAny && checkboxInputs.length) {
                    let hasBaseNameCheckbox = false;
                    let hasArrayName = false;
                    let hasKeyedName = false;

                    checkboxInputs.each(function () {
                        const name = this.name || '';
                        if (!name) return;

                        if (name === baseName) {
                            hasBaseNameCheckbox = true;
                            return;
                        }

                        const keys = parseKeys(name, baseName);
                        if (!keys) return;

                        if (keys.length === 1 && keys[0] === '') {
                            hasArrayName = true;
                            return;
                        }

                        if (keys.length) hasKeyedName = true;
                    });

                    if (hasBaseNameCheckbox) return false;
                    if (hasArrayName) return [];
                    if (hasKeyedName) return {};
                }

                if (!hasAny) return null;
                return result;
            }

            wp.customize.controlConstructor.webilia_wp_settings_panel = wp.customize.Control.extend({
                ready: function () {
                    const control = this;
                    const container = control.container;
                    if (!container || !container.length) return;

                    let refreshTimer = null;
                    function scheduleRefresh() {
                        if (!wp.customize.previewer) return;
                        if (refreshTimer) window.clearTimeout(refreshTimer);
                        refreshTimer = window.setTimeout(function ()
                        {
                            wp.customize.previewer.refresh();
                        }, 250);
                    }

                    function updateSetting() {
                        const value = serializeControl(control);
                        if (value === null) return;

                        const setting = control.setting || wp.customize.instance(control.id) || wp.customize.value(control.id);
                        if (!setting) return;

                        setting.set(value);
                        scheduleRefresh();
                    }

                    container.on('input change', 'input, select, textarea', function () {
                        updateSetting();
                    });

                    updateSetting();

                    // Single shared initializer pipeline for this control container
                    app.initContext(container[0]);
                }
            });

            wp.customize.bind('ready', function () {
                app.initContext(document);
            });
        }

        return {
            name: 'CustomizerModule',
            init(context, app)
            {
                if (context !== document) return;
                initCustomizerBindings(app);
            }
        };
    })();

    const CustomizerStructuredModule = (function () {
        let bound = false;
        let refreshTimer = null;

        function scheduleRefresh() {
            if (!window.wp || !window.wp.customize || !window.wp.customize.previewer) return;
            if (refreshTimer) window.clearTimeout(refreshTimer);
            refreshTimer = window.setTimeout(function () {
                window.wp.customize.previewer.refresh();
            }, 250);
        }

        function buildBorderValue(field) {
            const value = {};
            const inputs = field.querySelectorAll('input, select, textarea');
            if (!inputs.length) return null;

            inputs.forEach((input) => {
                if (!input || input.disabled) return;
                const name = input.name || '';
                const match = name.match(/\[([^\]]+)\]$/);
                if (!match || !match[1]) return;

                const type = (input.type || '').toLowerCase();
                if ((type === 'checkbox' || type === 'radio') && !input.checked) return;

                value[match[1]] = input.value;
            });

            return Object.keys(value).length ? value : null;
        }

        function updateCustomizerSetting(field, nextValue) {
            if (!field || !nextValue) return;
            if (!window.wp || !window.wp.customize) return;

            const settingId = field.getAttribute('data-customizer-setting-id') || '';
            if (!settingId) return;

            const setting = window.wp.customize.instance(settingId) || window.wp.customize.value(settingId);
            if (!setting) return;

            setting.set(nextValue);
            scheduleRefresh();
        }

        function handleChange(e) {
            const target = e.target;
            if (!target) return;

            const field = target.closest('.wui-sp-field.wui-sp-field-type-border');
            if (!field) return;

            if (field.querySelector('[data-customize-setting-link]')) return;

            const value = buildBorderValue(field);
            updateCustomizerSetting(field, value);
        }

        return {
            name: 'CustomizerStructuredModule',
            init(context, app) {
                if (context !== document) return;
                if (!app.isCustomizer) return;
                if (bound) return;
                bound = true;

                Utils.on(document, 'input', handleChange);
                Utils.on(document, 'change', handleChange);
            }
        };
    })();
    app.register(ColorPickersModule);
    app.register(SortablesModule);
    app.register(MediaModule);
    app.register(GalleryModule);
    app.register(AjaxActionsModule);
    app.register(CustomizerModule);
    app.register(CustomizerStructuredModule);
    app.init();
})();
