/* Regression checks without the live MySQL database, bank transfers or Push Service. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const vm = require('node:vm');
const net = require('node:net');
const { spawn } = require('node:child_process');

const root = __dirname;
const results = [];
async function test(name, run) {
    try { await run(); results.push({ name, passed: true }); }
    catch (error) { results.push({ name, passed: false, error: error.message }); }
}
function element() {
    const classes = new Set();
    return {
        textContent: '', innerHTML: '', className: '', style: {}, hidden: false,
        classList: { add: x => classes.add(x), remove: x => classes.delete(x), contains: x => classes.has(x) },
        setAttribute(name, value) { this[name] = value; }, getAttribute(name) { return this[name] || null; },
        addEventListener() {},
    };
}
function paymentSandbox() {
    const nodes = new Map();
    const copied = [];
    let modalOpens = 0;
    const document = {
        getElementById(id) { if (!nodes.has(id)) nodes.set(id, element()); return nodes.get(id); },
        querySelectorAll: () => [], addEventListener() {}, documentElement: element(),
    };
    const sandbox = vm.createContext({
        document, window: {}, navigator: { clipboard: { writeText: async x => { copied.push(x); } } },
        Intl, URLSearchParams, Date, console,
        Swal: { fire: async () => ({}) },
        bootstrap: { Modal: { getOrCreateInstance: () => ({ show: () => { modalOpens++; } }) } },
    });
    vm.runInContext(fs.readFileSync(path.join(root, 'assets/js/payment.js'), 'utf8'), sandbox);
    return { sandbox, document, nodes, copied, opens: () => modalOpens, run: code => vm.runInContext(code, sandbox) };
}
function workerSandbox(fetchImpl, options = {}) {
    const handlers = {};
    const stored = new Map();
    const cached = new Map(Object.entries(options.cached || {}));
    const deleted = [];
    const context = vm.createContext({
        URL, Request, Response, console: { warn() {} },
        self: { addEventListener: (name, cb) => { handlers[name] = cb; }, skipWaiting: async () => {}, clients: { claim: async () => {} } },
        fetch: fetchImpl,
        caches: {
            keys: async () => options.cacheNames || [], delete: async name => { deleted.push(name); return true; },
            match: async req => cached.get(typeof req === 'string' ? req : req.url),
            open: async () => ({
                add: async () => {},
                put: async (req, response) => {
                    if (options.failPut) throw new Error('Quota exceeded');
                    stored.set(req.url, await response.text());
                },
            }),
        },
        clients: {},
    });
    vm.runInContext(fs.readFileSync(path.join(root, 'sw.js'), 'utf8'), context);
    function event(request) {
        const waits = [];
        const e = { request, response: null, waitUntil: promise => waits.push(Promise.resolve(promise)), respondWith: promise => { e.response = Promise.resolve(promise); } };
        e.finish = async () => { for (let i = 0; i < waits.length; i++) await waits[i]; };
        return e;
    }
    return { context, handlers, stored, deleted, event };
}
function request(url, extra = {}) {
    return { url: `https://example.test/GODMINTON/${url}`, method: 'GET', mode: 'navigate', destination: 'document', headers: new Headers({ accept: 'text/html' }), ...extra };
}

async function paymentTests() {
    for (const status of [null, 'pending', 'paid', 'expired']) {
        await test(`payment CTA state ${status || 'new'}`, () => {
            const h = paymentSandbox();
            h.run(`activePayment = ${status ? JSON.stringify({ status }) : 'null'}; updateCtaLabels();`);
            assert.match(h.document.getElementById('btnPayDesktopText').innerHTML, status === 'pending' ? /Tiếp tục/ : /Thanh Toán/);
            assert.equal(h.document.getElementById('btnPayMobile').classList.contains('pulse-cta'), status === 'pending');
        });
    }
    for (const method of ['bank', 'vnpay', 'momo', 'zalopay']) {
        await test(`server payment details and QR for ${method}`, () => {
            const h = paymentSandbox();
            h.run(`booking.method = '${method}'; activePayment = {status:'pending',payment_code:'GDM009876',amount:18900,qr_url:'https://example.test/qr',bank:{name:'TEST BANK',account_number:'DEMO-ACCOUNT',account_name:'DEMO'},simulate_available:false}; openPaymentGatewayModal();`);
            assert.equal(h.document.getElementById('gwModalRef').textContent, 'GDM009876');
            assert.equal(h.document.getElementById('gwModalAccValue').textContent, 'DEMO-ACCOUNT');
            assert.equal(h.document.getElementById('gwModalQrImg').src, 'https://example.test/qr');
            assert.equal(h.document.getElementById('devSimulateWrap').style.display, 'none');
            assert.equal(h.opens(), 1);
        });
    }
    await test('no placeholder payment modal before server creates payment', () => {
        const h = paymentSandbox(); h.run('openPaymentGatewayModal();'); assert.equal(h.opens(), 0);
    });
    await test('copy transfer reference matches visible server code', async () => {
        const h = paymentSandbox();
        h.document.getElementById('gwModalRef').textContent = 'GDM009876';
        h.run("booking._transferRef = 'OLD-REFERENCE'; copyTransferRef();");
        await new Promise(resolve => setImmediate(resolve));
        assert.deepEqual(h.copied, ['GDM009876']);
    });
    for (const [base, membership, expectedTotal, expectedDeposit] of [
        [100000, 0, 100000, 50000], [40000, 10, 37800, 18900], [100001, 10, 94501, 47251], [100000, 100, 0, 0],
    ]) {
        await test(`payment totals base=${base}, membership=${membership}`, () => {
            const h = paymentSandbox();
            h.run(`booking.pricePerHour=${base};booking.duration=1;window.__GODMINTON_USER__={membership:{tier:'gold',discount_percent:${membership}}};calcPrices();`);
            assert.equal(h.run('booking._total'), expectedTotal);
            assert.equal(h.run('booking._deposit'), expectedDeposit);
        });
    }
}

async function workerTests() {
    await test('activate removes only obsolete GODMINTON caches', async () => {
        const names = ['another-app-cache', 'godminton-static-old', 'godminton-runtime-old'];
        const h = workerSandbox(async () => new Response('ok'), { cacheNames: names });
        names.push(vm.runInContext('CACHE_STATIC', h.context), vm.runInContext('CACHE_RUNTIME', h.context));
        const e = h.event(); h.handlers.activate(e); await e.finish();
        assert.deepEqual(h.deleted.sort(), ['godminton-runtime-old', 'godminton-static-old']);
    });
    for (const url of ['api/slots.php', 'api/hold.php', 'api/auth/me.php', 'payment.php', 'booking.php', 'admin-courts.php', 'auth.php']) {
        await test(`network-only route ${url}`, () => {
            const h = workerSandbox(() => { throw new Error('Must not intercept'); });
            const e = h.event(request(url)); h.handlers.fetch(e); assert.equal(e.response, null);
        });
    }
    await test('POST requests are never cached', () => {
        const h = workerSandbox(() => {}); const e = h.event(request('index.php', { method: 'POST' }));
        h.handlers.fetch(e); assert.equal(e.response, null);
    });
    await test('online navigation returns fresh response and persists cache', async () => {
        const h = workerSandbox(async () => new Response('fresh'));
        const req = request('index.php'); const e = h.event(req); h.handlers.fetch(e);
        assert.equal(await (await e.response).text(), 'fresh'); await e.finish(); assert.equal(h.stored.get(req.url), 'fresh');
    });
    for (const cacheControl of ['no-store', 'private, max-age=0']) {
        await test(`do not cache ${cacheControl} responses`, async () => {
            const h = workerSandbox(async () => new Response('private', { headers: { 'Cache-Control': cacheControl } }));
            const e = h.event(request('profile.php')); h.handlers.fetch(e); await e.response; await e.finish(); assert.equal(h.stored.size, 0);
        });
    }
    await test('cache write failure does not break a network response', async () => {
        const h = workerSandbox(async () => new Response('fresh'), { failPut: true });
        const e = h.event(request('index.php')); h.handlers.fetch(e);
        assert.equal(await (await e.response).text(), 'fresh'); await e.finish();
    });
    await test('offline navigation falls back to saved page', async () => {
        const req = request('index.php');
        const h = workerSandbox(async () => { throw new Error('offline'); }, { cached: { [req.url]: new Response('saved') } });
        const e = h.event(req); h.handlers.fetch(e); assert.equal(await (await e.response).text(), 'saved');
    });
    await test('offline navigation falls back to offline.php', async () => {
        const h = workerSandbox(async () => { throw new Error('offline'); }, { cached: { 'offline.php': new Response('offline shell') } });
        const e = h.event(request('courts.php')); h.handlers.fetch(e); assert.equal(await (await e.response).text(), 'offline shell');
    });
    await test('static cache hit is returned while update remains alive', async () => {
        const req = request('assets/js/app.js', { mode: 'cors', destination: 'script', headers: new Headers() });
        let resolveNetwork;
        const h = workerSandbox(() => new Promise(resolve => { resolveNetwork = resolve; }), { cached: { [req.url]: new Response('old') } });
        const e = h.event(req); h.handlers.fetch(e); assert.equal(await (await e.response).text(), 'old');
        resolveNetwork(new Response('new')); await e.finish(); assert.equal(h.stored.get(req.url), 'new');
    });
    await test('offline missing static asset returns Response.error rather than null', async () => {
        const h = workerSandbox(async () => { throw new Error('offline'); });
        const e = h.event(request('assets/js/missing.js', { mode: 'cors', destination: 'script', headers: new Headers() }));
        h.handlers.fetch(e); const response = await e.response; await e.finish();
        assert.ok(response instanceof Response); assert.equal(response.type, 'error');
    });
}

async function apiTests() {
    const fixture = fs.mkdtempSync(path.join(os.tmpdir(), 'godminton-final-test-'));
    const write = (file, text) => { const full = path.join(fixture, file); fs.mkdirSync(path.dirname(full), { recursive: true }); fs.writeFileSync(full, text); };
    for (const name of ['subscribe.php', 'send.php']) write(`api/push/${name}`, fs.readFileSync(path.join(root, 'api/push', name)));
    write('config/db.php', `<?php
    $pdo = new PDO('sqlite:' . __DIR__ . '/../test.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE IF NOT EXISTS push_subscriptions (endpoint TEXT PRIMARY KEY, user_id INTEGER)');
    if (!$pdo->query('SELECT COUNT(*) FROM sqlite_master WHERE name="seed_marker"')->fetchColumn()) {
      $pdo->exec("INSERT INTO push_subscriptions VALUES ('https://fcm.googleapis.com/test-endpoint',7)");
      $pdo->exec('CREATE TABLE seed_marker(id INTEGER)');
    }
    `);
    write('includes/auth-session.php', `<?php
    function god_auth_start(): void { $_SESSION = ['csrf_token'=>'test-csrf-token']; }
    function god_auth_user(PDO $pdo): ?array { return ['id'=>7]; }
    `);
    write('includes/web-push.php', `<?php
    function god_push_ensure_schema(PDO $pdo): void {}
    function god_push_endpoint_is_allowed(string $endpoint): bool { return str_starts_with($endpoint,'https://fcm.googleapis.com/'); }
    function god_push_dispatch_due_reminders(PDO $pdo, ?int $userId): array { return ['sent'=>0,'failed'=>0,'upcoming_count'=>0,'test_only'=>true]; }
    `);
    const port = await new Promise((resolve, reject) => {
        const server = net.createServer(); server.on('error', reject); server.listen(0, '127.0.0.1', () => {
            const number = server.address().port; server.close(() => resolve(number));
        });
    });
    const php = process.env.PHP_BINARY || 'php';
    const logPath = path.join(fixture, 'php-test.log');
    const logFd = fs.openSync(logPath, 'a');
    const child = spawn(php, ['-S', `127.0.0.1:${port}`, '-t', fixture], { stdio: ['ignore', logFd, logFd], windowsHide: true });
    let processError; child.once('error', error => { processError = error; });
    const endpoint = `http://127.0.0.1:${port}/api/push/`;
    const send = (name, body, headers = {}, method = 'POST') => fetch(endpoint + name, { method, headers: { 'Content-Type': 'application/json', ...headers }, ...(body == null ? {} : { body: typeof body === 'string' ? body : JSON.stringify(body) }) });
    try {
        let ready = false;
        for (let i=0;i<40;i++) {
            if (processError) throw processError;
            try { const response = await fetch(endpoint + 'subscribe.php'); ready = response.ok; } catch {}
            if (ready) break;
            await new Promise(resolve => setTimeout(resolve, 50));
        }
        assert.ok(ready, 'isolated PHP test server did not start: ' + fs.readFileSync(logPath, 'utf8'));
        for (const name of ['subscribe.php', 'send.php']) {
            for (const [label, headers] of [['missing',{}],['empty',{'X-GODMINTON-CSRF':''}],['wrong',{'X-GODMINTON-CSRF':'wrong'}]]) {
                await test(`${name} rejects ${label} CSRF before processing JSON`, async () => {
                    const res = await send(name, '{malformed-json', headers);
                    assert.equal(res.status, 403); assert.equal((await res.json()).status, 'error');
                });
            }
            await test(`${name} rejects unsupported HTTP method`, async () => { assert.equal((await send(name, null, {}, 'PUT')).status, 405); });
        }
        await test('missing token does not delete the subscription', async () => {
            await send('subscribe.php', {action:'unsubscribe',endpoint:'https://fcm.googleapis.com/test-endpoint'});
            const data = await (await fetch(endpoint+'subscribe.php')).json(); assert.equal(data.data.count, 1);
        });
        const headers = {'X-GODMINTON-CSRF':'test-csrf-token'};
        await test('valid CSRF reaches JSON validation', async () => { assert.equal((await send('subscribe.php','{malformed-json',headers)).status,400); });
        await test('valid CSRF reaches endpoint validation', async () => { assert.equal((await send('subscribe.php',{endpoint:'https://example.test/push'},headers)).status,422); });
        await test('valid CSRF permits unsubscribe of the test user', async () => {
            const res=await send('subscribe.php',{action:'unsubscribe',endpoint:'https://fcm.googleapis.com/test-endpoint'},headers);
            assert.equal(res.status,200); assert.equal((await res.json()).data.unsubscribed,true);
            const data=await (await fetch(endpoint+'subscribe.php')).json(); assert.equal(data.data.count,0);
        });
        await test('valid CSRF permits reminder dispatcher without external delivery', async () => {
            const res=await send('send.php',{action:'reminder'},headers);
            assert.equal(res.status,200); assert.equal((await res.json()).data.test_only,true);
        });
    } finally {
        child.kill();
        await Promise.race([new Promise(resolve => child.once('exit', resolve)), new Promise(resolve => setTimeout(resolve,1000))]);
        fs.closeSync(logFd);
        const safePrefix=path.resolve(os.tmpdir())+path.sep+'godminton-final-test-';
        if (path.resolve(fixture).startsWith(safePrefix)) fs.rmSync(fixture,{recursive:true,force:true,maxRetries:3,retryDelay:100});
    }
}

(async () => {
    await paymentTests(); await workerTests(); await apiTests();
    const failed=results.filter(x=>!x.passed);
    for (const item of results) console.log(`${item.passed?'PASS':'FAIL'} ${item.name}${item.error?': '+item.error:''}`);
    console.log(JSON.stringify({passed:results.length-failed.length,failed:failed.length,total:results.length}));
    if (process.env.GODMINTON_TEST_REPORT) fs.writeFileSync(process.env.GODMINTON_TEST_REPORT,JSON.stringify(results,null,2));
    process.exitCode=failed.length?1:0;
})().catch(error=>{console.error(error);process.exitCode=1;});
