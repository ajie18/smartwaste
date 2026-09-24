
(function () {
    let cfg = null;
    let state = {
        open: false,
        view: 'list',        // 'list' | 'thread'
        conversations: [],
        activeConversationId: null,
        activeOtherName: '',
        messages: [],
        pollTimer: null,
        subscribedConversation: null,
    };

    function api(method, path, body) {
        return fetch(cfg.apiBase + path, {
            method,
            headers: {
                'Content-Type': 'application/json',
                Authorization: `Bearer ${cfg.token}`,
            },
            body: body ? JSON.stringify(body) : undefined,
        }).then(async (res) => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`);
            return data;
        });
    }

    function injectStyles() {
        if (document.getElementById('chatWidgetStyles')) return;
        const style = document.createElement('style');
        style.id = 'chatWidgetStyles';
        style.textContent = `
        #chatWidgetBtn {
            position: fixed; bottom: 22px; right: 22px; z-index: 99998;
            width: 56px; height: 56px; border-radius: 50%;
            background: var(--gold, #22c55e); color: var(--bg, #0d0f13);
            border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 24px rgba(0,0,0,0.35); transition: transform 0.15s ease;
        }
        #chatWidgetBtn:hover { transform: scale(1.06); }
        #chatWidgetBtn svg { width: 24px; height: 24px; }
        #chatWidgetBadge {
            position: absolute; top: -4px; right: -4px; background: #ff5c5c; color: #fff;
            font-size: 0.65rem; font-weight: 700; min-width: 18px; height: 18px; border-radius: 9px;
            display: none; align-items: center; justify-content: center; padding: 0 4px;
            font-family: system-ui, sans-serif;
        }
        #chatWidgetPanel {
            position: fixed; bottom: 90px; right: 22px; z-index: 99999;
            width: min(380px, calc(100vw - 32px)); height: min(480px, calc(100vh - 140px));
            background: var(--panel, #16181f); border: 1px solid var(--border2, #26282f);
            border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.45);
            display: none; flex-direction: column; overflow: hidden;
            font-family: system-ui, -apple-system, sans-serif;
        }
        #chatWidgetPanel.open { display: flex; }
        #chatWidgetHeader {
            padding: 14px 16px; border-bottom: 1px solid var(--border2, #26282f);
            display: flex; align-items: center; gap: 8px; color: var(--text, #f2f3f5);
            font-weight: 700; font-size: 0.9rem; flex-shrink: 0;
        }
        #chatWidgetHeader .back-btn {
            background: none; border: none; color: var(--text2, #9099a8); cursor: pointer;
            display: none; padding: 2px 6px 2px 0; font-size: 1.1rem; line-height: 1;
        }
        #chatWidgetHeader .close-btn {
            margin-left: auto; background: none; border: none; color: var(--text2, #9099a8);
            cursor: pointer; font-size: 1.1rem; line-height: 1; padding: 2px 4px;
        }
        #chatWidgetBody { flex: 1; overflow-y: auto; padding: 10px; }
        .chat-conv-item {
            display: flex; flex-direction: column; gap: 2px; padding: 10px 12px; border-radius: 10px;
            cursor: pointer; margin-bottom: 4px; transition: background 0.15s;
        }
        .chat-conv-item:hover { background: var(--bg2, rgba(255,255,255,0.04)); }
        .chat-conv-name { font-weight: 600; font-size: 0.85rem; color: var(--text, #f2f3f5); display: flex; justify-content: space-between; align-items: center; }
        .chat-conv-preview { font-size: 0.76rem; color: var(--text2, #9099a8); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chat-unread-dot { background: var(--gold, #22c55e); color: var(--bg, #0d0f13); font-size: 0.62rem; font-weight: 800; min-width: 16px; height: 16px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; }
        .chat-empty { text-align: center; color: var(--text2, #9099a8); font-size: 0.8rem; padding: 40px 20px; }
        .chat-msg-row { display: flex; margin-bottom: 8px; }
        .chat-msg-row.mine { justify-content: flex-end; }
        .chat-bubble { max-width: 78%; padding: 8px 12px; border-radius: 14px; font-size: 0.82rem; line-height: 1.4; overflow-wrap: anywhere; word-break: break-word; }
        .chat-msg-row:not(.mine) .chat-bubble { background: var(--bg2, rgba(255,255,255,0.06)); color: var(--text, #f2f3f5); border-bottom-left-radius: 4px; }
        .chat-msg-row.mine .chat-bubble { background: var(--gold, #22c55e); color: var(--bg, #0d0f13); border-bottom-right-radius: 4px; }
        .chat-msg-time { font-size: 0.6rem; color: var(--text3, #5b6470); margin-top: 2px; text-align: right; }
        #chatWidgetInputRow { display: flex; align-items: flex-end; gap: 8px; padding: 10px; border-top: 1px solid var(--border2, #26282f); flex-shrink: 0; }
        #chatWidgetInput {
            flex: 1 1 auto; min-width: 0; width: 100%; min-height: 38px; max-height: 96px;
            box-sizing: border-box; border-radius: 20px; border: 1px solid var(--border2, #26282f);
            background: var(--bg, #0d0f13); color: var(--text, #f2f3f5); padding: 9px 14px;
            font: inherit; font-size: 0.83rem; line-height: 1.35; outline: none; resize: none; overflow-y: auto;
        }
        #chatWidgetSendBtn {
            background: var(--gold, #22c55e); color: var(--bg, #0d0f13); border: none; border-radius: 50%;
            width: 38px; height: 38px; flex-shrink: 0; cursor: pointer; display: flex; align-items: center; justify-content: center;
        }
        #chatWidgetSendBtn:disabled { opacity: 0.5; cursor: default; }
        @media (max-width: 420px) {
            #chatWidgetPanel { right: 12px; bottom: 82px; width: calc(100vw - 24px); }
            #chatWidgetBtn { right: 14px; bottom: 14px; }
        }
        `;
        document.head.appendChild(style);
    }

    function injectDom() {
        if (document.getElementById('chatWidgetBtn')) return;

        const btn = document.createElement('button');
        btn.id = 'chatWidgetBtn';
        btn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
            </svg>
            <span id="chatWidgetBadge">0</span>
        `;
        btn.addEventListener('click', togglePanel);
        document.body.appendChild(btn);

        const panel = document.createElement('div');
        panel.id = 'chatWidgetPanel';
        panel.innerHTML = `
            <div id="chatWidgetHeader">
                <button class="back-btn" id="chatWidgetBack">←</button>
                <span id="chatWidgetTitle">Messages</span>
                <button class="close-btn" id="chatWidgetClose">✕</button>
            </div>
            <div id="chatWidgetBody"></div>
            <div id="chatWidgetInputRow" style="display:none;">
                <textarea id="chatWidgetInput" rows="1" placeholder="Type a message…"></textarea>
                <button id="chatWidgetSendBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                </button>
            </div>
        `;
        document.body.appendChild(panel);

        document.getElementById('chatWidgetClose').addEventListener('click', () => setOpen(false));
        document.getElementById('chatWidgetBack').addEventListener('click', showList);
        document.getElementById('chatWidgetSendBtn').addEventListener('click', sendMessage);
        document.getElementById('chatWidgetInput').addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }

    function togglePanel() {
        setOpen(!state.open);
    }

    function setOpen(open) {
        state.open = open;
        document.getElementById('chatWidgetPanel').classList.toggle('open', open);
        if (open) {
            if (cfg.role === 'resident' && state.conversations.length === 1) {
                openThread(state.conversations[0].id, state.conversations[0].other_user_name);
            } else {
                showList();
            }
        } else {
            stopPolling();
        }
    }

    function updateBadge() {
        const total = state.conversations.reduce((s, c) => s + (c.unread_count || 0), 0);
        const badge = document.getElementById('chatWidgetBadge');
        if (total > 0) {
            badge.textContent = total > 9 ? '9+' : total;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    async function loadConversations() {
        try {
            state.conversations = await api('GET', '/chat/conversations');
        } catch (e) {
            state.conversations = [];
        }
        updateBadge();
        return state.conversations;
    }

    function showList() {
        state.view = 'list';
        state.activeConversationId = null;
        document.getElementById('chatWidgetBack').style.display = 'none';
        document.getElementById('chatWidgetInputRow').style.display = 'none';
        document.getElementById('chatWidgetTitle').textContent = 'Messages';
        stopPolling();

        const body = document.getElementById('chatWidgetBody');
        if (!state.conversations.length) {
            body.innerHTML = `<div class="chat-empty">${cfg.role === 'resident'
                ? "No driver assigned yet today — check back once a pickup is scheduled."
                : "No conversations yet. Residents can message you once you're on today's route."
                }</div>`;
            return;
        }

        body.innerHTML = state.conversations.map((c) => `
            <div class="chat-conv-item" data-id="${c.id}" data-name="${escapeHtml(c.other_user_name)}">
                <div class="chat-conv-name">
                    <span>${escapeHtml(c.other_user_name)}</span>
                    ${c.unread_count > 0 ? `<span class="chat-unread-dot">${c.unread_count}</span>` : ''}
                </div>
                <div class="chat-conv-preview">${c.last_message ? escapeHtml(c.last_message) : 'Say hello 👋'}</div>
            </div>
        `).join('');

        body.querySelectorAll('.chat-conv-item').forEach((el) => {
            el.addEventListener('click', () => openThread(parseInt(el.dataset.id), el.dataset.name));
        });
    }

    async function openThread(conversationId, otherName) {
        state.view = 'thread';
        state.activeConversationId = conversationId;
        state.activeOtherName = otherName;
        document.getElementById('chatWidgetBack').style.display = cfg.role === 'driver' ? 'inline-block' : 'none';
        document.getElementById('chatWidgetInputRow').style.display = 'flex';
        document.getElementById('chatWidgetTitle').textContent = otherName;
        document.getElementById('chatWidgetBody').innerHTML = '<div class="chat-empty">Loading…</div>';

        await loadMessages(conversationId);
        api('PUT', `/chat/conversations/${conversationId}/read`).catch(() => { });
        subscribeToConversation(conversationId);
        startPolling(conversationId);
    }

    async function loadMessages(conversationId) {
        try {
            const data = await api('GET', `/chat/conversations/${conversationId}/messages`);
            state.messages = data.messages || [];
            renderMessages();
        } catch (e) {
            document.getElementById('chatWidgetBody').innerHTML = '<div class="chat-empty">Could not load messages.</div>';
        }
    }

    function renderMessages() {
        const body = document.getElementById('chatWidgetBody');
        if (!state.messages.length) {
            body.innerHTML = '<div class="chat-empty">No messages yet. Say hello 👋</div>';
            return;
        }
        body.innerHTML = state.messages.map((m) => `
            <div class="chat-msg-row ${m.sender_id === cfg.currentUserId ? 'mine' : ''}">
                <div>
                    <div class="chat-bubble">${escapeHtml(m.body)}</div>
                    <div class="chat-msg-time">${fmtTime(m.created_at)}</div>
                </div>
            </div>
        `).join('');
        body.scrollTop = body.scrollHeight;
    }

    function appendIncoming(msg) {
        if (msg.conversation_id !== state.activeConversationId) return;
        if (state.messages.some((m) => m.id === msg.id)) return;
        state.messages.push(msg);
        renderMessages();
        if (state.open && state.view === 'thread') {
            api('PUT', `/chat/conversations/${msg.conversation_id}/read`).catch(() => { });
        }
    }

    async function sendMessage() {
        const input = document.getElementById('chatWidgetInput');
        const text = input.value.trim();
        if (!text || !state.activeConversationId) return;

        input.value = '';
        const sendBtn = document.getElementById('chatWidgetSendBtn');
        sendBtn.disabled = true;

        try {
            const res = await api('POST', `/chat/conversations/${state.activeConversationId}/messages`, { body: text });
            if (!state.messages.some((m) => m.id === res.data.id)) {
                state.messages.push(res.data);
                renderMessages();
            }
        } catch (e) {
            console.error('Send message failed:', e);
            input.value = text;
        } finally {
            sendBtn.disabled = false;
        }
    }

    function subscribeToConversation(conversationId) {
        if (typeof window.Echo?.private !== 'function') return;
        if (state.subscribedConversation === conversationId) return;
        state.subscribedConversation = conversationId;
        window.Echo.private(`conversation.${conversationId}`).listen('.chat-message', (data) => {
            appendIncoming(data);
        });
    }

    function startPolling(conversationId) {
        stopPolling();
        // Only needed as a fallback when Echo isn't connected — real-time
        // delivery happens instantly via subscribeToConversation above.
        if (typeof window.Echo?.private === 'function') return;
        state.pollTimer = setInterval(() => {
            if (state.open && state.activeConversationId === conversationId) {
                loadMessages(conversationId);
            }
        }, 5000);
    }

    function stopPolling() {
        if (state.pollTimer) {
            clearInterval(state.pollTimer);
            state.pollTimer = null;
        }
    }

    function listenForPings() {
        if (typeof window.Echo?.private !== 'function') return;
        const channel = cfg.role === 'driver' ? `driver.${cfg.currentUserId}` : `resident.${cfg.currentUserId}`;
        window.Echo.private(channel).listen('.chat-message', async (data) => {
            if (data.conversation_id === state.activeConversationId) return; // already handled above
            await loadConversations();
            if (state.view === 'list') showList();
        });
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function fmtTime(d) {
        if (!d) return '';
        return new Date(d).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    }

    window.initChatWidget = async function (options) {
        cfg = options;
        if (!cfg.token || !cfg.currentUserId) {
            console.warn('Chat widget: missing token/currentUserId, not initializing.');
            return;
        }
        injectStyles();
        injectDom();

        if (cfg.role === 'resident') {
            // Make sure a thread with today's driver exists so the widget
            // has something to show the moment it's opened.
            try {
                await api('POST', '/resident/chat/start');
            } catch (e) {
                // No driver assigned yet today — fine, list will just be empty.
            }
        }

        await loadConversations();
        listenForPings();
    };
})();
