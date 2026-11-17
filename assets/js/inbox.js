// Unified Inbox (Phase 1): seluruh data dari PHP (dummy), status/pesan hanya di memori browser (simulasi, hilang saat reload).
(function () {
    var D = window.INBOX_DATA;
    var convs = D.conversations, channels = {}, agents = D.agents;
    D.channels.forEach(function (c) { channels[c.key] = c; });
    var state = { filter: 'all', status: 'all', q: D.q || '', sel: null, noteMode: false };
    var $ = function (id) { return document.getElementById(id); };
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function now() { var d = new Date(); return ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2); }
    function icon(key, sm) { var c = channels[key]; return '<span class="ch-ico' + (sm ? ' sm' : '') + '" style="background:' + c.color + '"><i class="' + c.icon + '"></i></span>'; }
    function initials(n) { return n.split(' ').slice(0, 2).map(function (w) { return w[0]; }).join('').toUpperCase(); }
    function lastMsg(c) { var m = c.messages[c.messages.length - 1]; return m ? m.text : ''; }
    function find(id) { return convs.filter(function (c) { return c.id === id; })[0]; }

    function visible() {
        var q = state.q.toLowerCase();
        return convs.filter(function (c) {
            if (state.filter === 'online' && channels[c.channel].group !== 'online') return false;
            if (state.filter === 'offline' && channels[c.channel].group !== 'offline') return false;
            if (state.filter !== 'all' && state.filter !== 'online' && state.filter !== 'offline' && c.channel !== state.filter) return false;
            if (state.status !== 'all' && c.status !== state.status) return false;
            if (q) {
                var hay = (c.customer + ' ' + c.info.phone + ' ' + c.info.email + ' ' + c.info.company + ' ' + c.messages.map(function (m) { return m.text; }).join(' ')).toLowerCase();
                if (hay.indexOf(q) === -1) return false;
            }
            return true;
        });
    }

    function renderList() {
        var list = visible();
        $('convCount').textContent = list.length + ' percakapan';
        $('convList').innerHTML = list.length ? list.map(function (c) {
            return '<div class="conv ' + (c.unread ? 'unread ' : '') + (c.id === state.sel ? 'active' : '') + '" data-id="' + c.id + '">'
                + '<div style="position:relative"><span class="avatar" style="background:#8aa0d6">' + esc(initials(c.customer)) + '</span>'
                + '<span style="position:absolute;right:-4px;bottom:-4px">' + icon(c.channel, true) + '</span></div>'
                + '<div class="conv-body"><div class="conv-top"><span class="conv-name">' + esc(c.customer) + '</span><span class="conv-time">' + esc(c.time) + '</span></div>'
                + '<div class="conv-snip">' + esc(lastMsg(c)) + '</div>'
                + '<div class="conv-meta"><span class="st st-' + c.status + '">' + c.status + '</span><i class="fa-solid fa-flag pr-' + c.priority + '" title="Prioritas ' + c.priority + '" style="font-size:.65rem"></i>'
                + (c.unread ? '<span class="unread-badge">' + c.unread + '</span>' : '') + '</div></div></div>';
        }).join('') : '<div class="empty-state">Tidak ada percakapan yang cocok.</div>';
    }

    function opts(arr, cur) { return arr.map(function (a) { return '<option' + (a === cur ? ' selected' : '') + '>' + esc(a) + '</option>'; }).join(''); }

    function renderChat() {
        var c = find(state.sel), box = $('chatCol');
        if (!c) { box.innerHTML = '<div class="empty-state"><i class="fa-regular fa-comments fa-3x mb-3"></i><p>Pilih percakapan untuk mulai.</p></div>'; $('custCol').innerHTML = ''; return; }
        var ch = channels[c.channel], offline = ch.group === 'offline';
        box.innerHTML =
            '<div class="chat-head"><button class="icon-btn inbox-back" id="btnBack"><i class="fa-solid fa-arrow-left"></i></button>' + icon(c.channel)
            + '<div class="grow"><b>' + esc(c.customer) + '</b><br><small class="text-muted">' + esc(ch.name) + ' &middot; ' + (offline ? 'Offline (dicatat manual)' : 'Online (simulasi)') + '</small></div>'
            + '<select class="form-select form-select-sm w-auto" id="selStatus">' + opts(['New', 'Open', 'Pending', 'Resolved'], c.status) + '</select>'
            + '<select class="form-select form-select-sm w-auto" id="selPrio" title="Prioritas">' + opts(['High', 'Medium', 'Low'], c.priority) + '</select>'
            + '<select class="form-select form-select-sm w-auto" id="selAgent" title="Transfer / tugaskan staf">' + opts(agents, c.agent) + '</select>'
            + '<button class="icon-btn cust-toggle" id="btnCust" title="Info pelanggan"><i class="fa-regular fa-id-card"></i></button></div>'
            + '<div class="chat-body" id="chatBody">' + c.messages.map(function (m) {
                var cls = m.from === 'note' ? 'note' : m.from;
                return '<div class="bubble ' + cls + '">' + (m.from === 'note' ? '<i class="fa-solid fa-lock me-1"></i><b>Catatan internal:</b> ' : '') + esc(m.text) + '<small>' + esc(m.time) + '</small></div>';
            }).join('') + '</div>'
            + '<div class="chat-compose"><div class="compose-tabs"><button class="chip ' + (state.noteMode ? '' : 'active') + '" id="tabReply">Balas</button><button class="chip ' + (state.noteMode ? 'active' : '') + '" id="tabNote">Catatan Internal</button></div>'
            + '<div class="compose-row ' + (state.noteMode ? 'note-mode' : '') + '"><textarea id="msgInput" rows="2" placeholder="' + (state.noteMode ? 'Tulis catatan internal (tidak terlihat pelanggan)...' : 'Tulis balasan (simulasi, tidak dikirim ke channel nyata)...') + '"></textarea>'
            + '<button class="btn btn-primary" id="btnSend"><i class="fa-solid fa-paper-plane"></i></button></div></div>';
        var cb = $('chatBody'); cb.scrollTop = cb.scrollHeight;
        renderCustomer(c);
        $('selStatus').onchange = function () { c.status = this.value; renderList(); };
        $('selPrio').onchange = function () { c.priority = this.value; renderList(); };
        $('selAgent').onchange = function () { c.agent = this.value; c.messages.push({ from: 'note', text: 'Percakapan ditugaskan ke ' + this.value, time: now() }); renderChat(); };
        $('tabReply').onclick = function () { state.noteMode = false; renderChat(); };
        $('tabNote').onclick = function () { state.noteMode = true; renderChat(); };
        $('btnSend').onclick = send;
        $('msgInput').onkeydown = function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); } };
        var back = $('btnBack'); if (back) back.onclick = function () { $('inbox').classList.remove('chat-open'); };
        var bc = $('btnCust'); if (bc) bc.onclick = function () { $('custCol').classList.toggle('show'); };
    }

    function renderCustomer(c) {
        var i = c.info, ch = channels[c.channel];
        $('custCol').innerHTML = '<div class="cust-panel">'
            + '<div class="text-center"><span class="avatar mx-auto" style="width:64px;height:64px;font-size:1.3rem;background:#8aa0d6">' + esc(initials(c.customer)) + '</span>'
            + '<h6 style="margin:.7rem 0 0;font-size:1rem;text-transform:none;letter-spacing:0;color:inherit">' + esc(c.customer) + '</h6><small class="text-muted">' + esc(i.id) + '</small></div>'
            + '<h6>Identitas</h6><dl><dt>Telepon</dt><dd>' + esc(i.phone || '-') + '</dd><dt>Email</dt><dd>' + esc(i.email || '-') + '</dd><dt>Jenis</dt><dd>' + esc(i.type) + '</dd>'
            + (i.company ? '<dt>Perusahaan</dt><dd>' + esc(i.company) + '</dd>' : '') + '<dt>Status</dt><dd><span class="badge text-bg-primary">' + esc(i.status) + '</span></dd>'
            + '<dt>Preferensi</dt><dd>' + esc(i.pref) + '</dd><dt>Channel</dt><dd>' + esc(ch.name) + '</dd><dt>Staf</dt><dd>' + esc(c.agent) + '</dd></dl>'
            + '<h6>Riwayat Booking</h6>' + (i.bookings.length ? i.bookings.map(function (b) { return '<div class="small mb-1"><b>' + esc(b[0]) + '</b><br>' + esc(b[1]) + ' &middot; ' + esc(b[2]) + '</div>'; }).join('') : '<div class="small text-muted">Belum ada booking.</div>')
            + '<h6>Riwayat Transaksi</h6>' + (i.payments.length ? i.payments.map(function (p) { return '<div class="small mb-1"><b>' + esc(p[0]) + '</b><br>' + esc(p[1]) + ' &middot; ' + esc(p[2]) + '</div>'; }).join('') : '<div class="small text-muted">Belum ada transaksi.</div>')
            + '<h6>Catatan Pelanggan</h6><div class="small">' + esc(i.note || 'Tidak ada catatan.') + '</div></div>';
    }

    function send() {
        var c = find(state.sel), t = $('msgInput').value.trim();
        if (!c || !t) return;
        c.messages.push({ from: state.noteMode ? 'note' : 'agent', text: t, time: now() });
        if (!state.noteMode) { c.time = now(); if (c.status === 'New') c.status = 'Open'; }
        renderList(); renderChat();
    }

    function select(id) {
        state.sel = id; var c = find(id); if (c) c.unread = 0;
        $('inbox').classList.add('chat-open');
        renderList(); renderChat();
    }

    // Filter channel & status
    $('channelChips').innerHTML = ['all:Semua', 'online:Online', 'offline:Offline'].concat(D.channels.map(function (c) { return c.key + ':' + c.name; })).map(function (s) {
        var p = s.split(':'); return '<button class="chip' + (p[0] === 'all' ? ' active' : '') + '" data-f="' + p[0] + '">' + esc(p[1]) + '</button>';
    }).join('');
    $('channelChips').onclick = function (e) { var b = e.target.closest('.chip'); if (!b) return; state.filter = b.dataset.f; [].forEach.call(this.children, function (x) { x.classList.toggle('active', x === b); }); renderList(); };
    $('statusFilter').onchange = function () { state.status = this.value; renderList(); };
    $('inboxSearch').value = state.q;
    $('inboxSearch').oninput = function () { state.q = this.value; renderList(); };
    $('convList').onclick = function (e) { var el = e.target.closest('.conv'); if (el) select(parseInt(el.dataset.id, 10)); };

    renderList();
    if (D.selected && find(D.selected)) select(D.selected); else renderChat();
    if (D.selected) { /* pada mobile langsung buka chat */ } else { $('inbox').classList.remove('chat-open'); }
})();
