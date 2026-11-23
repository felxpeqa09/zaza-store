import Alpine from 'alpinejs';
import { shoppingCart, wishlist } from './shopping';
import { liveSearch, productSelection } from './catalog';
import 'bootstrap/js/dist/collapse';
import 'bootstrap-icons/font/bootstrap-icons.css';
import '@fontsource/anton/latin-400.css';
import '@fontsource/quicksand/latin-500.css';
import '@fontsource/quicksand/latin-700.css';
import '../scss/store.scss';

window.Alpine = Alpine;
Alpine.data("liveSearch", liveSearch);
Alpine.data("productSelection", productSelection);
const cartDrawer = document.querySelector("#cart-drawer");
if (cartDrawer) Alpine.store("cart", shoppingCart(cartDrawer));
Alpine.store("wishlist", wishlist(document.querySelector("#wishlist-config")));
Alpine.store('cookieConsent', {
    visible: false,
    choice: null,
    init() {
        try {
            const saved = localStorage.getItem('zaza-cookie-consent');
            this.choice = ['essential', 'external'].includes(saved) ? saved : null;
        } catch {
            // Consent remains session-only when storage is unavailable.
        }
        this.visible = this.choice === null;
    },
    save(choice) {
        this.choice = choice;
        this.visible = false;
        try {
            localStorage.setItem('zaza-cookie-consent', choice);
        } catch {
            // The notice can still close when browser storage is blocked.
        }
    },
});
Alpine.start();
