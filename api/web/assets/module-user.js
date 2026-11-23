// User module (personnel management, admin only) — Alpine.js component.
document.addEventListener('alpine:init', () => {
    Alpine.data('userPage', () => ({
        items: window.__ITEMS__ || [],
        mode: 0,
        form: {},
        message: '',
        ts(t) { return new Date(Number(t) * 1000).toLocaleString(); },
        async refresh() {
            const r = await fetch('/api/user/all', {credentials: 'same-origin'});
            if (r.ok) this.items = await r.json();
        },
        openCreate() {
            this.mode = 1;
            this.form = {username: '', password: '', display_name: '', role: 'user'};
            this.message = '';
        },
        openEdit(u) {
            this.mode = 2;
            this.form = Object.assign({password: ''}, u);
            this.message = '';
        },
        async submit() {
            const body = new URLSearchParams(this.form);
            const create = this.mode === 1;
            const r = await fetch('/api/user', {
                method: create ? 'POST' : 'PUT',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body
            });
            if (r.status === 201 || r.status === 204) {
                this.mode = 0;
                await this.refresh();
                return;
            }
            const d = await r.json().catch(() => ({}));
            this.message = d.message || '操作失敗';
        },
        async remove(u) {
            if (!confirm('確定要刪除「' + u.username + '」嗎？')) return;
            const r = await fetch('/api/user?uuid=' + encodeURIComponent(u.uuid), {
                method: 'DELETE',
                credentials: 'same-origin'
            });
            if (r.status === 204) await this.refresh();
        }
    }));
});
