export function liveSearch() {
    let timer;
    let pending;

    return {
        results: [],
        message: '',
        change(value) {
            clearTimeout(timer);
            pending?.abort();
            this.results = [];
            this.message = '';

            const term = value.trim();
            if (!term) return;

            timer = setTimeout(() => this.search(term), 250);
        },
        async search(term) {
            const request = new AbortController();
            pending = request;
            this.message = 'Buscando…';

            try {
                const url = new URL(this.$refs.input.form.dataset.searchUrl, window.location.origin);
                url.searchParams.set('q', term);
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    signal: request.signal,
                });
                if (!response.ok) throw new Error('Search failed');
                const { results } = await response.json();

                if (request.signal.aborted) return;
                this.results = results;
                this.message = results.length ? '' : 'No encontramos productos.';
            } catch (error) {
                if (!request.signal.aborted) this.message = 'La búsqueda en vivo no está disponible. Pulsa Buscar para continuar.';
            }
        },
    };
}

export function productSelection(images, colors, variants) {
    return {
        images,
        colors,
        variants,
        image: images[0],
        color: colors.find(option => variants.some(variant => variant.color === option.id && variant.stock > 0))?.id
            ?? colors[0]?.id ?? null,
        selected: null,
        quantity: 1,
        init() {
            this.image = this.colors.find(option => option.id === this.color)?.image ?? this.images[0];
            this.chooseSize(this.sizes.find(option => option.stock > 0) ?? null);
        },
        get sizes() {
            return this.variants.filter(option => option.color === this.color);
        },
        chooseColor(option) {
            this.color = option.id;
            this.image = option.image || this.images[0];
            this.chooseSize(this.sizes.find(size => size.stock > 0) ?? null);
        },
        chooseSize(option) {
            this.selected = option;
            this.quantity = 1;
        },
        limitQuantity() {
            const quantity = Math.floor(Number(this.quantity)) || 1;
            this.quantity = Math.max(1, Math.min(quantity, this.selected?.stock ?? 1));
        },
    };
}
