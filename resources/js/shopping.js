import Offcanvas from 'bootstrap/js/dist/offcanvas';

async function requestJson(url, method = 'GET', body) {
    const safe = method === 'GET';
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            ...(safe ? {} : { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }),
            ...(body ? { 'Content-Type': 'application/json' } : {}),
        },
        ...(body ? { body: JSON.stringify(body) } : {}),
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
        const fallback = response.status === 419
            ? 'La sesión cambió. Recarga la página para continuar.'
            : 'No pudimos completar la solicitud. Inténtalo de nuevo.';
        throw new Error(Object.values(payload.errors ?? {})[0]?.[0] ?? fallback);
    }
    return payload;
}

export function shoppingCart(drawer) {
    const urls = drawer.dataset;
    const panel = Offcanvas.getOrCreateInstance(drawer);
    let opener;

    drawer.addEventListener('shown.bs.offcanvas', () => {
        drawer.querySelector('[data-bs-dismiss="offcanvas"]').focus();
    });
    drawer.addEventListener('hidden.bs.offcanvas', () => opener?.focus());

    return {
        items: [],
        units: 0,
        totalLabel: '$0 COP',
        notices: [],
        error: '',
        busy: false,
        loaded: false,
        isOpen: false,
        editing: null,
        quantity: 1,

        init() {
            drawer.addEventListener('show.bs.offcanvas', () => { this.isOpen = true; });
            drawer.addEventListener('hidden.bs.offcanvas', () => { this.isOpen = false; });
            this.refresh();
        },

        open(trigger) {
            opener = trigger;
            panel.show();
            if (!this.loaded && !this.busy) this.refresh();
        },

        async refresh() {
            await this.send(urls.showUrl);
        },

        async add(id, quantity, trigger) {
            if (await this.send(urls.itemsUrl, 'POST', { variant_id: id, cantidad: quantity })) {
                this.open(trigger);
            }
        },

        edit(item) {
            this.editing = item.id;
            this.quantity = Math.min(item.quantity, item.stock);
        },

        async update(item) {
            if (await this.send(this.itemUrl(item), 'PATCH', { cantidad: this.quantity })) this.editing = null;
        },

        async remove(item) {
            if (await this.send(this.itemUrl(item), 'DELETE')) this.editing = null;
        },

        itemUrl(item) {
            return urls.itemUrl.replace('__variant__', encodeURIComponent(item.id));
        },

        async send(url, method, body) {
            if (this.busy) return false;
            this.busy = true;
            this.error = '';
            try {
                const summary = await requestJson(url, method, body);
                this.items = summary.items;
                this.units = summary.units;
                this.totalLabel = summary.totalLabel;
                this.notices = [...new Set([...this.notices, ...summary.notices])];
                this.loaded = true;
                return true;
            } catch (error) {
                this.error = error.message;
                return false;
            } finally {
                this.busy = false;
            }
        },
    };
}

export function wishlist(config) {
    let url = config?.dataset.listUrl;
    const itemUrl = config?.dataset.itemUrl;

    return {
        ids: [],
        ready: false,
        error: '',
        pending: [],
        status: '',
        isSaved(id) {
            return this.ids.includes(id);
        },

        async init() {
            if (!url) return;
            try {
                this.ids = (await requestJson(url)).ids;
                this.ready = true;
                this.error = '';
            } catch (error) {
                this.error = error.message;
            }
        },

        async toggle(id) {
            if (!this.ready || this.pending.includes(id)) return;
            this.pending.push(id);
            this.error = '';
            this.status = '';

            try {
                const target = itemUrl.replace('__product__', encodeURIComponent(id));
                const result = await requestJson(target, this.isSaved(id) ? 'DELETE' : 'PUT');
                this.ids = this.ids.filter(saved => saved !== id);
                if (result.saved) this.ids.push(id);
                this.status = result.saved ? 'Producto guardado en favoritos.' : 'Producto retirado de favoritos.';
            } catch (error) {
                this.error = error.message;
            } finally {
                this.pending = this.pending.filter(item => item !== id);
            }
        },
    };
}
