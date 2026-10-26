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
