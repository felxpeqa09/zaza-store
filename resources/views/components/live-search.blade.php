<details class="zaza-search" x-data="liveSearch" @click.outside="$el.open = false"
    @keydown.escape.stop="$el.open = false; $refs.trigger.focus()"
    @toggle="if ($el.open) $nextTick(() => $refs.input.focus())">
    <summary class="zaza-icon-button" aria-label="Abrir búsqueda" x-ref="trigger">
        <i class="bi bi-search" aria-hidden="true"></i>
    </summary>
    <form class="zaza-search-panel" role="search" method="get" action="{{ route('search') }}" data-search-url="{{ route('search') }}">
        <label class="visually-hidden" for="navbar-search">Buscar productos</label>
        <div class="d-flex gap-2">
            <input class="form-control" type="search" id="navbar-search" name="q" placeholder="Buscar productos"
                maxlength="100" autocomplete="off" x-ref="input" @input="change($event.target.value)" required>
            <button class="btn btn-light zaza-button" type="submit">Buscar</button>
        </div>
        <p class="small mt-3 mb-0" role="status" x-show="message" x-text="message" x-cloak></p>
        <ul class="list-unstyled mb-0" x-show="results.length" x-cloak>
            <template x-for="result in results" :key="result.url">
                <li><a class="zaza-search-result" :href="result.url">
                    <img :src="result.image" alt="" width="40" height="48">
                    <span x-text="result.name"></span>
                    <small x-text="result.price"></small>
                </a></li>
            </template>
        </ul>
    </form>
</details>
