import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import test from 'node:test';
import { JSDOM, VirtualConsole } from 'jsdom';
const root = resolve(import.meta.dirname, '../..');

test('feedback adapter registers once, targets full basename, and submits only through its callback', async () => {
  const dom = new JSDOM('<div data-webilia-feedback><div data-wui-modal></div></div><a id="target" href="?action=deactivate&plugin=wibee%2Fwibee.php">Deactivate</a><a id="unrelated" href="?action=deactivate&plugin=wibee-pro%2Fwibee.php">Other</a>', { url: 'https://site.test/wp-admin/plugins.php', runScripts: 'outside-only', virtualConsole: new VirtualConsole() });
  const w = dom.window, d = w.document; let options, initialized = 0, opened = 0; const requests = [];
  d.querySelector('[data-webilia-feedback]').dataset.webiliaFeedback = JSON.stringify({ basename: 'wibee/wibee.php', ajaxUrl: '/ajax', nonce: 'nonce', error: 'Offline' });
  w.WebiliaUI = { deactivation: { init: (modal, config) => { options = config; initialized++; } }, modal: { open: () => { opened++; } } };
  w.fetch = async (url, config) => { requests.push(config); return { ok: true, json: async () => ({ success: true }) }; };
  const source = await readFile(resolve(root, 'assets/js/deactivation.js'), 'utf8'); w.eval(source); w.eval(source); d.dispatchEvent(new w.Event('DOMContentLoaded'));
  assert.equal(initialized, 1);
  const click = () => new w.MouseEvent('click', { bubbles: true, cancelable: true });
  const own = click(); d.getElementById('target').dispatchEvent(own); assert.equal(own.defaultPrevented, true); assert.equal(opened, 1); assert.equal(requests.length, 0);
  const other = click(); d.getElementById('unrelated').dispatchEvent(other); assert.equal(other.defaultPrevented, false);
  const modified = new w.MouseEvent('click', { bubbles: true, cancelable: true, ctrlKey: true }); d.getElementById('target').dispatchEvent(modified); assert.equal(modified.defaultPrevented, false);
  await options.submit({ reason: 'other', details: 'Comment' });
  assert.equal(requests.length, 1); assert.equal(requests[0].body.get('action'), 'webilia_feedback'); assert.equal(requests[0].body.get('basename'), 'wibee/wibee.php'); assert.equal(requests[0].body.get('details'), 'Comment');
  w.fetch = async () => { throw new Error('network'); };
  await assert.rejects(options.submit({ reason: 'other', details: '' }), /Offline/);
});

test('announcement adapter passes server snapshot and distinguishes read/dismiss actions', async () => {
  const dom = new JSDOM('<div data-webilia-announcements><div data-wui-modal></div></div>', { url: 'https://site.test', runScripts: 'outside-only' });
  const w = dom.window, d = w.document; let options, initialized = 0; const requests = [];
  d.querySelector('[data-webilia-announcements]').dataset.webiliaAnnouncements = JSON.stringify({ consumer: 'one', ajaxUrl: '/ajax', nonce: 'nonce', scope: 'site-1', items: [], unavailable: false, labels: { error: 'Unavailable' } });
  w.WebiliaUI = { announcements: { init: (modal, config) => { options = config; initialized++; } } };
  w.fetch = async (url, config) => { requests.push(config); return { ok: true, json: async () => ({ success: true, data: { items: [], unavailable: false } }) }; };
  const source = await readFile(resolve(root, 'assets/js/announcements.js'), 'utf8'); w.eval(source); w.eval(source); d.dispatchEvent(new w.Event('DOMContentLoaded')); assert.equal(initialized, 1);
  assert.equal(options.scope, 'site-1'); assert.equal(requests.length, 0);
  await options.load(); await options.dismiss('remote:1');
  assert.equal(requests[0].body.get('action'), 'webilia_announcements_read'); assert.equal(requests[1].body.get('action'), 'webilia_announcements_dismiss'); assert.equal(requests[1].body.get('id'), 'remote:1');
  w.fetch = async () => ({ ok: false, json: async () => ({ success: false }) });
  await assert.rejects(options.load(), /Unavailable/);
});
