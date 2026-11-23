// Chat module — Alpine.js component. Talks to /api/chat (SSE stream proxied from OpenAI).
document.addEventListener('alpine:init', () => {
    Alpine.data('chatPage', () => ({
        messages: [],
        input: '',
        loading: false,
        error: '',
        model: window.__MODEL__ || '',

        async init() {
            try {
                const r = await fetch('/api/chat', {credentials: 'same-origin'});
                if (r.ok) {
                    const d = await r.json();
                    if (d.model) this.model = d.model;
                }
            } catch (e) { /* keep the server-rendered model name */ }
        },

        scrollBottom() {
            this.$nextTick(() => {
                const el = this.$refs.log;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        clear() {
            this.messages = [];
            this.error = '';
        },

        async send() {
            const text = this.input.trim();
            if (!text || this.loading) return;
            this.error = '';
            this.messages.push({role: 'user', content: text});
            this.input = '';
            this.loading = true;
            const placeholder = {role: 'assistant', content: ''};
            this.messages.push(placeholder);
            this.scrollBottom();

            // send the conversation without empty assistant placeholders
            const history = this.messages.filter(m => !(m.role === 'assistant' && m.content === ''));

            try {
                const r = await fetch('/api/chat', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({messages: history})
                });

                if (!r.ok) {
                    const d = await r.json().catch(() => ({}));
                    this.error = d.message || ('Request failed (' + r.status + ')');
                    this.messages.pop();
                    this.loading = false;
                    return;
                }

                const reader = r.body.getReader();
                const decoder = new TextDecoder();
                let buf = '';
                while (true) {
                    const {done, value} = await reader.read();
                    if (done) break;
                    buf += decoder.decode(value, {stream: true});
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
            } catch (e) {
                this.error = '無法連線到伺服器';
                if (!placeholder.content) this.messages.pop();
            }
            this.loading = false;
            this.scrollBottom();
        }
    }));
});
