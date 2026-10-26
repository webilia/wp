import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import test from 'node:test';
import { JSDOM } from 'jsdom';
import jquery from 'jquery';
const root = resolve(import.meta.dirname, '../..');
const ui = process.env.WEBILIA_UI_PATH || resolve(root, '../webilia-ui');

test('WP demo adapter isolates transport and never mutates retry payloads', async () => {
    const dom = new JSDOM('<div class="js-wui-di-page"></div><div class="js-wdi-page"></div>', {runScripts:'outside-only'});
    const w = dom.window;
    const requests = [];
    const $ = jquery(w);
    $.ajax = config => { requests.push(config); return $.Deferred().resolve({}).promise(); };
    w.jQuery = $;
    let adapter;
    w.WebiliaUI = {demoImport:{init(root, options) { adapter = options; assert.equal(root.className,'js-wui-di-page'); }}};
    w.eval(await readFile(resolve(root,'assets/js/demo-import.js'),'utf8'));
    const payload = {action:'import_demo_data',security:'nonce',selected:0};
    adapter.request({data:payload}); adapter.request({data:payload});
    assert.equal(payload.action,'import_demo_data');
    assert.equal(requests[0].data.action,'webilia_wp_demo_import_demo_data');
    assert.equal(requests[1].data.action,'webilia_wp_demo_import_demo_data');
    dom.window.close();
});

test('WP settings adapter initializes once and serializes the selected option owner', async () => {
    const dom = new JSDOM('<div class="webilia-panel"><form><input name="legacy[title]" value="Legacy"></form></div><div class="wui wui-sp-panel"><section class="wui-sp-panel-section is-active" data-section="general"></section><form class="wui-sp-panel-form" data-wui-sp-opt-name="theme_b" data-wui-sp-nonce="nonce"><input name="theme_b[title]" value="Saved"><div class="wui-sp-panel-actions"><button type="button" class="wui-sp-action-save">Save</button></div><span class="wui-sp-panel-actions-message"></span></form></div>', {runScripts:'outside-only',url:'https://site.test/'});
    const w = dom.window;
    w.matchMedia = () => ({matches:false});
    w.requestAnimationFrame = callback => callback();
    w.scrollTo = () => {};
    const $ = jquery(w); w.jQuery=$; w.ajaxurl='/wp-admin/admin-ajax.php';
    const requests=[];
    $.post=(url,payload) => { requests.push({url,payload}); return $.Deferred().resolve({success:true,data:{message:'Saved'}}).promise(); };
    w.webiliaWPSettingsI18n={saving:'Saving',done:'Done',error:'Error'};
    w.eval(await readFile(resolve(ui,'dist/js/settings-panel.js'),'utf8'));
    const adapter=await readFile(resolve(root,'assets/js/settings-panel.js'),'utf8');
    w.eval(adapter); w.eval(adapter);
    w.document.querySelector('.wui-sp-action-save').click();
    await new Promise(resolve => setTimeout(resolve,0));
    assert.equal(requests.length,1);
    assert.equal(requests[0].payload.action,'webilia_wp_settings_save');
    assert.equal(requests[0].payload.opt_name,'theme_b');
    assert.equal(requests[0].payload.data,'theme_b%5Btitle%5D=Saved');
    assert.equal(w.document.querySelector('[name="legacy[title]"]').value,'Legacy');
    dom.window.close();
});


test('native WP media picker supplies single and multiple selections to shared controls without duplicate dialogs', async () => {
    const dom = new JSDOM('<div class="wui-sp-media"><input class="wui-sp-media-url"><input class="wui-sp-media-id"><div class="wui-sp-media-input"><button class="wui-sp-media-select">Select</button></div></div><div class="wui-sp-gallery"><ul class="wui-sp-gallery-items"></ul><input class="wui-sp-gallery-input"><p class="wui-sp-gallery-empty">Empty</p><button class="wui-sp-gallery-select">Add</button></div>', {runScripts:'outside-only',url:'https://site.test/'});
    const w = dom.window;
    w.matchMedia = () => ({matches:false});
    w.requestAnimationFrame = callback => callback();
    const $ = jquery(w); w.jQuery = $;
    const picks = [];
    w.wp = { media(config) {
        picks.push(config);
        const data = config.multiple ? [{id:2,url:'https://files.test/two.png'},{id:3,url:'https://files.test/three.png'}] : [{id:1,type:'image',url:'https://files.test/one.png'}];
        let selected;
        return { on(event, callback) { selected=callback; }, open() { selected(); }, state() { return {get() { return {toJSON:()=>data,first:()=>({toJSON:()=>data[0]})}; }}; } };
    }};
    w.webiliaWPSettingsI18n = {};
    w.eval(await readFile(resolve(ui,'dist/js/settings-panel.js'),'utf8'));
    w.eval(await readFile(resolve(root,'assets/js/settings-panel.js'),'utf8'));
    w.document.querySelector('.wui-sp-media-select').click();
    assert.equal(picks.length,1);
    assert.equal(w.document.querySelector('.wui-sp-media-id').value,'1');
    w.document.querySelector('.wui-sp-gallery-select').click();
    assert.equal(picks.length,2);
    assert.equal(picks[1].multiple,true);
    assert.equal(w.document.querySelector('.wui-sp-gallery-input').value,'2,3');
    w.document.querySelector('.wui-sp-gallery-select').click();
    assert.equal(picks.length,3);
    assert.equal(w.document.querySelector('.wui-sp-gallery-items').children.length,2);
    await new Promise(resolve => setTimeout(resolve,0));
    w.close();
});
