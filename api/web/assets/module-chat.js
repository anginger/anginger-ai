// Chat module — Alpine.js component. Conversations + context live server-side;
// /api/chat streams the completion as SSE and persists the reply.
document.addEventListener('alpine:init', () => {
    Alpine.data('chatPage', () => ({
        conversations: [],
        q: '',
        filtered() {
            const q = (this.q || '').trim().toLowerCase();
            const list = this.items || this.conversations || [];
            if (!q) return list;
            return list.filter(row => JSON.stringify(row).toLowerCase().includes(q));
        },
        current: null,          // current conversation object
        messages: [],           // messages of the current conversation
        input: '',
        loading: false,
        loadingConvs: true,
        error: '',
        model: window.__MODEL__ || '',
        settingsOpen: false,
        settingsForm: {title: '', system_prompt: ''},
        settingsMessage: '',

        async init() {
            try {
                const r = await fetch('/api/chat', {credentials: 'same-origin'});
                if (r.ok) {
                    const d = await r.json();
                    if (d.model) this.model = d.model;
                }
            } catch (e) { /* keep the server-rendered model name */ }
            await this.refreshConversations();
        },

        async refreshConversations() {
            this.loadingConvs = true;
            try {
                const r = await fetch('/api/chat/conversations', {credentials: 'same-origin'});
                if (r.ok) this.conversations = await r.json();
            } finally {
                this.loadingConvs = false;
            }
        },

        scrollBottom() {
            this.$nextTick(() => {
                const el = this.$refs.log;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        async newConversation() {
            const r = await fetch('/api/chat/conversations', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({title: '新對話'})
            });
            if (r.status === 201) {
                const c = await r.json();
                await this.refreshConversations();
                await this.openConversation(c.uuid);
            }
        },

        async openConversation(uuid) {
            const r = await fetch('/api/chat/conversation?uuid=' + encodeURIComponent(uuid), {credentials: 'same-origin'});
            if (!r.ok) return;
            this.current = await r.json();
            this.messages = this.current.messages || [];
            this.error = '';
            this.scrollBottom();
        },

        async removeConversation(c) {
            if (!(await appConfirm('確定要刪除對話「' + c.title + '」嗎？'))) return;
            const r = await fetch('/api/chat/conversation?uuid=' + encodeURIComponent(c.uuid), {
                method: 'DELETE', credentials: 'same-origin'
            });
            if (r.status === 204) {
                if (this.current && this.current.uuid === c.uuid) {
                    this.current = null;
                    this.messages = [];
                }
                await this.refreshConversations();
            }
        },

        openSettings() {
            if (!this.current) return;
            this.settingsForm = {title: this.current.title, system_prompt: this.current.system_prompt || ''};
            this.settingsMessage = '';
            this.settingsOpen = true;
        },

        async saveSettings() {
            const r = await fetch('/api/chat/conversation?uuid=' + encodeURIComponent(this.current.uuid), {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(this.settingsForm)
            });
            if (r.status === 204) {
                this.current.title = this.settingsForm.title;
                this.current.system_prompt = this.settingsForm.system_prompt || null;
                this.settingsOpen = false;
                await this.refreshConversations();
            } else {
                const d = await r.json().catch(() => ({}));
                this.settingsMessage = d.message || '儲存失敗';
            }
        },

        async send() {
            const text = this.input.trim();
            if (!text || this.loading) return;
            this.error = '';
            // no conversation yet -> let the server create one
            if (!this.current) {
                await this.newConversation();
                if (!this.current) { this.error = '無法建立對話'; return; }
            }
            this.messages.push({role: 'user', content: text});
            this.input = '';
            this.loading = true;
            const placeholder = {role: 'assistant', content: ''};
            this.messages.push(placeholder);
            this.scrollBottom();

            try {
                const r = await fetch('/api/chat', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        conversation_uuid: this.current.uuid,
                        messages: [{role: 'user', content: text}]
                    })
                });

                if (!r.ok) {
                    const d = await r.json().catch(() => ({}));
                    this.error = d.message || ('Request failed (' + r.status + ')');
                    this.messages.pop();
                    this.loading = false;
                    return;
                }

                const convUuid = r.headers.get('x-conversation-uuid');
                if (convUuid && (!this.current || this.current.uuid !== convUuid)) {
                    await this.openConversation(convUuid);
                    this.messages.push({role: 'user', content: text}, placeholder);
                }

                const reader = r.body.getReader();
                const decoder = new TextDecoder();
                let buf = '';
                while (true) {
                    const {done, value} = await reader.read();
                    if (done) break;
                    buf += decoder.decode(value, {stream: true}).replace(/\r\n/g, '\n');
                    let i;
                    while ((i = buf.indexOf('\n\n')) !== -1) {
                        const raw = buf.slice(0, i);
                        buf = buf.slice(i + 2);
                        if (!raw.startsWith('data:')) continue;
                        const data = raw.slice(5).trim();
                        if (data === '[DONE]') continue;
                        try {
                            const j = JSON.parse(data);
                            const delta = j.choices && j.choices[0] && j.choices[0].delta && j.choices[0].delta.content;
                            if (delta) {
                                placeholder.content += delta;
                                this.scrollBottom();
                            }
                            if (j.error && j.error.message) this.error = j.error.message;
                        } catch (e) { /* ignore malformed chunk */ }
                    }
                }
                if (!placeholder.content) this.messages.pop();
                await this.refreshConversations();
            } catch (e) {
                this.error = '無法連線到伺服器';
                if (!placeholder.content) this.messages.pop();
            }
            this.loading = false;
            this.scrollBottom();
        }
    }));
});
