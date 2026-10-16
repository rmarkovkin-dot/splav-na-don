/**
 * ИИ-консультант «Аня» — виджет для всего сайта rostur.expert
 * v4.3 · человеческая печать + история между страницами + автолиды + streaming
 *       + mobile: окно следует за клавиатурой (visualViewport), быстрые чипы
 */
(function () {
    'use strict';

    var EXCLUDED_PATHS = ['/camping/', '/fishing-volgograd-oblast/', '/auto-trip-volgograd/'];
    var path = window.location.pathname;
    for (var i = 0; i < EXCLUDED_PATHS.length; i++) {
        if (path.indexOf(EXCLUDED_PATHS[i]) === 0) return;
    }

    var CONFIG = {
        proxyUrl: 'https://rostur.expert/dify-proxy.php',
        sendUrl: 'https://rostur.expert/send.php',
        stateKey: 'anya_state_v4',
        historyKey: 'anya_history_v4',
        uidKey: 'anya_uid_v4',
        leadDebounceMs: 5 * 60 * 1000
    };

    var state = { conversationId: null, isOpen: false };
    var history = [];
    try { state = Object.assign(state, JSON.parse(localStorage.getItem(CONFIG.stateKey) || '{}')); } catch (e) {}
    try { var h0 = JSON.parse(localStorage.getItem(CONFIG.historyKey) || '[]'); history = Array.isArray(h0) ? h0 : []; } catch (e) { history = []; }
    function saveState() { try { localStorage.setItem(CONFIG.stateKey, JSON.stringify(state)); } catch (e) {} }
    function saveHistory() { try { localStorage.setItem(CONFIG.historyKey, JSON.stringify(history.slice(-40))); } catch (e) {} }

    var userId = '';
    try {
        userId = localStorage.getItem(CONFIG.uidKey) || '';
        if (!userId) { userId = 'web-' + Math.random().toString(36).substring(2, 11); localStorage.setItem(CONFIG.uidKey, userId); }
    } catch (e) { userId = 'web-' + Math.random().toString(36).substring(2, 11); }

    var css = `
.anya-widget{position:fixed;bottom:24px;right:24px;z-index:9999;font-family:'Manrope',system-ui,-apple-system,sans-serif}
.anya-btn{width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#1e40af);border:none;cursor:pointer;box-shadow:0 8px 24px rgba(30,64,175,.4);display:flex;align-items:center;justify-content:center;position:relative;transition:transform .2s}
.anya-btn:hover{transform:scale(1.06)}
.anya-btn svg{width:28px;height:28px;fill:#fff}
.anya-btn::after{content:'';position:absolute;inset:-6px;border-radius:50%;border:2px solid rgba(37,99,235,.5);animation:anya-pulse 2s infinite}
@keyframes anya-pulse{0%{transform:scale(.9);opacity:1}100%{transform:scale(1.35);opacity:0}}
.anya-dot{position:absolute;top:2px;right:2px;width:12px;height:12px;border-radius:50%;background:#22c55e;border:2px solid #fff}
.anya-window{position:fixed;bottom:96px;right:24px;width:380px;max-width:calc(100vw - 32px);height:560px;max-height:calc(100vh - 120px);background:#fff;border-radius:16px;box-shadow:0 24px 64px rgba(8,47,73,.28);display:none;flex-direction:column;overflow:hidden}
.anya-window.open{display:flex}
.anya-head{background:linear-gradient(135deg,#2563eb,#1e40af);color:#fff;padding:14px 16px;display:flex;align-items:center;gap:10px;flex-shrink:0}
.anya-ava{width:40px;height:40px;border-radius:50%;border:2px solid rgba(255,255,255,.6);object-fit:cover}
.anya-ava-ai{background:linear-gradient(135deg,#8b5cf6,#2563eb);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;color:#fff;position:relative}
.anya-ai-badge{position:absolute;bottom:-4px;right:-6px;background:#fbbf24;color:#0f172a;font-size:8px;font-weight:800;border-radius:6px;padding:1px 4px;letter-spacing:.05em}
.anya-disc{padding:6px 12px;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:10.5px;color:#64748b;line-height:1.4;flex-shrink:0}
.anya-hname{font-size:15px;font-weight:800}
.anya-hstatus{font-size:12px;opacity:.85;display:flex;align-items:center;gap:5px}
.anya-hstatus i{width:7px;height:7px;border-radius:50%;background:#4ade80;display:inline-block}
.anya-close{margin-left:auto;background:rgba(255,255,255,.15);border:none;color:#fff;width:28px;height:28px;border-radius:50%;cursor:pointer;font-size:14px;line-height:1}
.anya-msgs{flex:1;overflow-y:auto;padding:14px;background:#f1f5f9;display:flex;flex-direction:column;gap:10px;-webkit-overflow-scrolling:touch}
.anya-msg{display:flex;gap:8px;max-width:88%}
.anya-msg.user{align-self:flex-end;flex-direction:row-reverse}
.anya-ma{width:28px;height:28px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff}
.anya-ma.bot{background:#8b5cf6}
.anya-ma.user{background:#94a3b8}
.anya-bubble{padding:10px 12px;border-radius:12px;font-size:13.5px;line-height:1.5;color:#0f172a;background:#fff;box-shadow:0 1px 3px rgba(8,47,73,.1);word-wrap:break-word}
.anya-msg.user .anya-bubble{background:#2563eb;color:#fff}
.anya-bubble a{color:#1d4ed8;text-decoration:underline}
.anya-msg.user .anya-bubble a{color:#dbeafe}
.anya-caret{display:inline-block;width:2px;height:1.05em;background:#94a3b8;margin-left:2px;vertical-align:text-bottom;animation:anya-caret .8s steps(2,start) infinite}
@keyframes anya-caret{0%,100%{opacity:1}50%{opacity:0}}
.anya-typing{display:flex;gap:4px;padding:12px}
.anya-typing i{width:7px;height:7px;border-radius:50%;background:#94a3b8;animation:anya-tb 1.2s infinite}
.anya-typing i:nth-child(2){animation-delay:.2s}.anya-typing i:nth-child(3){animation-delay:.4s}
@keyframes anya-tb{0%,60%,100%{transform:translateY(0);opacity:.5}30%{transform:translateY(-5px);opacity:1}}
.anya-chips{display:none;gap:6px;padding:8px 10px;background:#f8fafc;border-top:1px solid #e2e8f0;overflow-x:auto;flex-shrink:0}
.anya-chip{flex:0 0 auto;border:1px solid #bfdbfe;background:#fff;color:#1d4ed8;font-size:12px;font-weight:600;padding:6px 11px;border-radius:999px;cursor:pointer;white-space:nowrap;font-family:inherit}
.anya-chip:active{background:#eff6ff}
.anya-form{display:flex;gap:8px;padding:10px;background:#fff;border-top:1px solid #e2e8f0;flex-shrink:0}
.anya-input{flex:1;border:1.5px solid #cbd5e1;border-radius:999px;padding:10px 14px;font-size:14px;font-family:inherit;outline:none}
.anya-input:focus{border-color:#2563eb}
.anya-send{width:42px;height:42px;border-radius:50%;background:#1e40af;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.anya-send svg{width:18px;height:18px;fill:#fff}
.anya-send:disabled,.anya-input:disabled{opacity:.5;cursor:not-allowed}
@media (max-width:640px){
.anya-widget{bottom:calc(88px + env(safe-area-inset-bottom,0px))!important;right:12px!important}
.anya-window{position:fixed;bottom:0;left:0;right:0;width:100%;max-width:100%;height:100vh;height:100dvh;max-height:100vh;max-height:100dvh;border-radius:0;margin:0}
}`;

    var style = document.createElement('style');
    style.textContent = css;
    document.head.appendChild(style);

    var widget = document.createElement('div');
    widget.className = 'anya-widget';
    widget.innerHTML = `
    <button class="anya-btn" id="anyaBtn" aria-label="Открыть чат с ИИ-консультантом Аней">
      <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
      <span class="anya-dot"></span>
    </button>
    <div class="anya-window" id="anyaWin" role="dialog" aria-label="Чат с Аней">
      <div class="anya-head">
        <div class="anya-ava anya-ava-ai" aria-hidden="true">✦<span class="anya-ai-badge">AI</span></div>
        <div>
          <div class="anya-hname">Аня</div>
          <div class="anya-hstatus"><i></i> ИИ-консультант · онлайн 24/7</div>
        </div>
        <button class="anya-close" id="anyaClose" aria-label="Закрыть">✕</button>
      </div>
      <div class="anya-msgs" id="anyaMsgs"></div>
      <div class="anya-chips" id="anyaChips"></div>
      <div class="anya-disc">Аня — ИИ-консультант и иногда может ошибаться. Важные решения подтверждает человек по телефону.</div>
      <form class="anya-form" id="anyaForm">
        <input class="anya-input" id="anyaInput" type="text" placeholder="Напишите сообщение..." autocomplete="on" />
        <button class="anya-send" id="anyaSend" type="submit" aria-label="Отправить">
          <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
        </button>
      </form>
    </div>`;
    document.body.appendChild(widget);

    var btn = widget.querySelector('#anyaBtn');
    var win = widget.querySelector('#anyaWin');
    var closeBtn = widget.querySelector('#anyaClose');
    var msgs = widget.querySelector('#anyaMsgs');
    var chipsBox = widget.querySelector('#anyaChips');
    var form = widget.querySelector('#anyaForm');
    var input = widget.querySelector('#anyaInput');
    var sendBtn = widget.querySelector('#anyaSend');

    var GREETING = 'Здравствуйте! Я Аня — ИИ-консультант сайта РосТур Эксперт. Обучена на опыте 8 сезонов нашей команды: подскажу цены, свободные даты, что взять с собой и как добраться. Отвечаю мгновенно и круглосуточно 🙂 Если вопрос требует человека — передам его Алексею (плоты) или Сергею (кемпинг).';

    ['💰 Цены и даты', '🎒 Что взять с собой', '📍 Как добраться'].forEach(function (t) {
        var b = document.createElement('button');
        b.type = 'button'; b.className = 'anya-chip'; b.textContent = t;
        b.addEventListener('click', function () { send(t); });
        chipsBox.appendChild(b);
    });

    function escapeHtml(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
    function linkify(s) { return escapeHtml(s).replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>'); }
    function bubbleHtml(text) { return linkify(text).replace(/\n/g, '<br>'); }
    function nearBottom() { return msgs.scrollHeight - msgs.scrollTop - msgs.clientHeight < 90; }
    function scrollBottom(smooth) { msgs.scrollTo({ top: msgs.scrollHeight, behavior: smooth ? 'smooth' : 'auto' }); }

    function appendMessage(role, text) {
        var msg = document.createElement('div');
        msg.className = 'anya-msg ' + (role === 'user' ? 'user' : 'bot');
        var av = document.createElement('div');
        av.className = 'anya-ma ' + (role === 'user' ? 'user' : 'bot');
        av.textContent = role === 'user' ? 'Я' : 'А';
        var b = document.createElement('div');
        b.className = 'anya-bubble';
        b.innerHTML = bubbleHtml(text);
        msg.appendChild(av); msg.appendChild(b);
        msgs.appendChild(msg);
        scrollBottom(true);
        return b;
    }

    function pushHistory(role, text) { history.push({ r: role, t: text }); saveHistory(); }

    function renderHistory() {
        msgs.innerHTML = '';
        if (!history.length) { appendMessage('bot', GREETING); return; }
        history.forEach(function (it) { appendMessage(it.r === 'user' ? 'user' : 'bot', it.t); });
    }

    var typingEl = null;
    function showTyping() {
        if (typingEl) return;
        typingEl = document.createElement('div');
        typingEl.className = 'anya-msg bot';
        typingEl.innerHTML = '<div class="anya-ma bot">А</div><div class="anya-bubble anya-typing"><i></i><i></i><i></i></div>';
        msgs.appendChild(typingEl);
        scrollBottom(true);
    }
    function hideTyping() { if (typingEl) { typingEl.remove(); typingEl = null; } }

    /* ── имитация человеческой печати ── */
    var typer = { target: '', shown: 0, bubble: null, timer: null, streaming: true, waitUntil: 0, introShown: false, onDone: null };
    function caretHtml() { return '<span class="anya-caret"></span>'; }
    function beginReveal(onDone) {
        typer.target = ''; typer.shown = 0; typer.streaming = true; typer.introShown = false;
        typer.waitUntil = Date.now() + 450 + Math.random() * 650;
        typer.onDone = onDone || null;
        typer.bubble = appendMessage('bot', '');
        typer.bubble.innerHTML = caretHtml();
        if (!typer.timer) typer.timer = setInterval(revealTick, 30);
    }
    function revealTick() {
        var now = Date.now();
        if (now < typer.waitUntil) return;
        if (!typer.introShown) { typer.introShown = true; hideTyping(); }
        if (typer.shown >= typer.target.length) {
            if (!typer.streaming) finishReveal();
            return;
        }
        var len = typer.target.length;
        var add = 1 + (len > 800 ? 1 : 0) + (len > 1600 ? 1 : 0) + (Math.random() < 0.35 ? 1 : 0);
        var next = Math.min(typer.shown + add, typer.target.length);
        typer.shown = next;
        var ch = typer.target.charAt(next - 1);
        var pause = 0;
        if (ch === ',' || ch === ';' || ch === ':') pause = 100 + Math.random() * 120;
        else if (ch === '.' || ch === '!' || ch === '?') pause = 160 + Math.random() * 200;
        else if (ch === '\n') pause = (typer.target.charAt(next - 2) === '\n') ? 320 + Math.random() * 280 : 140 + Math.random() * 140;
        if (pause) typer.waitUntil = now + pause;
        typer.bubble.innerHTML = escapeHtml(typer.target.slice(0, typer.shown)).replace(/\n/g, '<br>') + caretHtml();
        if (nearBottom()) scrollBottom(false);
    }
    function finishReveal() {
        clearInterval(typer.timer); typer.timer = null;
        var full = typer.target;
        typer.bubble.innerHTML = bubbleHtml(full);
        scrollBottom(true);
        var cb = typer.onDone; typer.onDone = null; typer.bubble = null;
        if (cb) cb(full);
    }

    /* ── автолиды ── */
    var lastLead = { key: '', time: 0 };
    var pendingPhone = null, pendingName = null;
    function extractPhone(t) {
      var cands = t.match(/\+?\d(?:[\d\s\-\(\)]{8,18}\d)?/g) || [];
      for (var i = 0; i < cands.length; i++) {
        var c = cands[i].trim();
        var d = c.replace(/\D/g, '');
        if (d.length === 11 && (d.charAt(0) === '8' || d.charAt(0) === '7')) return '+7' + d.slice(1);
        if (d.length === 10 && d.charAt(0) === '9') return '+7' + d;
        if (c.charAt(0) === '+' && d.length >= 10 && d.length <= 15) return '+' + d;
      }
      return null;
    }
    var STOP_NAMES = /^(Здравствуйте|Здравствуй|Добрый|Добрая|Привет|Хочу|Хотим|Планируем|Можно|Сколько|Когда|Есть|Нужна|Нужно|Подскажите|Скажите|Посоветуйте|Расскажите|Июнь|Июля|Июне|Май|Мае|Август|Августе|Сентябрь|Октябрь|Компания|Семья|Группа|Коллектив|Друзья|Едем|Приедем|Выезжаем|Спасибо|Вопрос|Прошу|Запишите|Забронировать|Позвоните|Перезвоните|Телефон|Адрес)/i;
    function extractName(t) {
      var m = t.match(/(?:меня зовут|моё имя|мое имя|имя|звать)\s*:?\s*([А-ЯЁA-Z][а-яёa-z]{1,20})/i);
      if (m) return m[1];
      m = t.match(/^([А-ЯЁ][а-яё]{2,20})\s*(?:,|\.|!|\+?\d)/);
      if (m && !STOP_NAMES.test(m[1])) return m[1];
      return '';
    }
    function botName(t) { var m = t.match(/Записал(?:а|и)?\s*:?\s*([А-ЯЁ][а-яё]{2,20})/); return m ? m[1] : ''; }
    function botConfirms(t) { return /(записал|передам|перезвон|администратор|оформим|бронь|принял|свяж)/i.test(t); }
    function sendLead(name, phone, comment) {
        var body = new URLSearchParams();
        body.append('name', name || 'Гость из чата');
        body.append('phone', phone);
        body.append('source', 'anya-chat');
        body.append('page', location.href);
        body.append('comment', comment || '');
        fetch(CONFIG.sendUrl, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() }).catch(function () {});
    }

    /* ── отправка сообщения ── */
    function send(text) {
        text = (text || '').trim();
        if (!text) return;
        appendMessage('user', text);
        pushHistory('user', text);
        pendingPhone = extractPhone(text);
        pendingName = extractName(text);
        input.value = '';
        input.disabled = true; sendBtn.disabled = true;
        showTyping();
        var payload = { endpoint: 'chat-messages', inputs: {}, query: text, response_mode: 'streaming', user: userId };
        if (state.conversationId) payload.conversation_id = state.conversationId;
        fetch(CONFIG.proxyUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
            .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); })
            .then(function (raw) {
                var full = '';
                var lines = raw.split('\n');
                for (var j = 0; j < lines.length; j++) {
                    var line = lines[j].trim();
                    if (line.indexOf('data:') !== 0) continue;
                    var pstr = line.slice(5).trim();
                    if (!pstr || pstr === '[DONE]') continue;
                    var ev; try { ev = JSON.parse(pstr); } catch (e) { continue; }
                    if (ev.event === 'message' && typeof ev.answer === 'string') {
                        full += ev.answer;
                        if (!typer.timer && !typer.bubble) beginReveal(onFinalize);
                        typer.target += ev.answer;
                    } else if (ev.event === 'conversation_start' || ev.event === 'message_end') {
                        if (ev.conversation_id) { state.conversationId = ev.conversation_id; saveState(); }
                        if (ev.event === 'message_end' && typer.timer) typer.streaming = false;
                    }
                }
                if (!full) {
                    hideTyping();
                    if (typer.timer) { clearInterval(typer.timer); typer.timer = null; typer.bubble = null; }
                    appendMessage('bot', 'Не удалось соединиться с сервером. Попробуйте позже.');
                    input.disabled = false; sendBtn.disabled = false;
                }
            })
            .catch(function () {
                hideTyping();
                if (typer.timer) { clearInterval(typer.timer); typer.timer = null; typer.bubble = null; }
                appendMessage('bot', 'Не удалось соединиться с сервером. Попробуйте позже.');
                input.disabled = false; sendBtn.disabled = false;
            });
    }

    function onFinalize(full) {
        pushHistory('bot', full);
        if (pendingPhone && botConfirms(full)) {
            var now = Date.now();
            if (pendingPhone !== lastLead.key || now - lastLead.time > CONFIG.leadDebounceMs) {
                lastLead = { key: pendingPhone, time: now };
                sendLead(pendingName || botName(full), pendingPhone, full.slice(0, 300));
            }
        }
        input.disabled = false; sendBtn.disabled = false;
        if (state.isOpen && window.innerWidth > 640) input.focus();
    }

    /* ── mobile: окно следует за клавиатурой ── */
    function fitMobile() {
        var mobile = window.innerWidth <= 640;
        if (mobile && state.isOpen && window.visualViewport) {
            var h = Math.floor(window.visualViewport.height);
            win.style.height = h + 'px';
            win.style.maxHeight = h + 'px';
        } else {
            win.style.height = '';
            win.style.maxHeight = '';
        }
    }
    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', function () { fitMobile(); scrollBottom(false); });
        window.visualViewport.addEventListener('scroll', function () { fitMobile(); });
    }
    window.addEventListener('resize', fitMobile);

    function toggle(open) {
        state.isOpen = open;
        win.classList.toggle('open', open);
        if (open) { fitMobile(); setTimeout(function () { scrollBottom(false); }, 50); }
        saveState();
    }

    btn.addEventListener('click', function () { if(!state.isOpen&&window.ym){try{ym(91421476,'reachGoal','anya_open',{page:location.pathname});}catch(e){}} toggle(!state.isOpen); });
    closeBtn.addEventListener('click', function () { toggle(false); });
    form.addEventListener('submit', function (e) { e.preventDefault(); send(input.value); });

    window.AnyaWidget = {
        open: function () { toggle(true); },
        close: function () { toggle(false); },
        prefill: function (t) { toggle(true); input.value = t; input.focus(); },
        clearHistory: function () { history = []; saveHistory(); renderHistory(); }
    };

    renderHistory();
    console.log('[Anya] widget v4.3 loaded on ' + location.pathname + ' · history: ' + history.length + ' msgs · conversationId: ' + (state.conversationId || 'new'));
})();