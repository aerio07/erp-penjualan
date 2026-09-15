import './bootstrap';
import Alpine from 'alpinejs';
import { formatRupiah, unformatRupiah, applyMaskWithCursor, setupCurrencyEngine } from './currency';

window.Alpine = Alpine;
window.formatRupiah = formatRupiah;
window.unformatRupiah = unformatRupiah;
window.applyMaskWithCursor = applyMaskWithCursor;

// Alpine Directive: x-currency="property"
Alpine.directive('currency', (el, { expression }, { evaluateLater, effect }) => {
    let get = evaluateLater(expression);
    let set = evaluateLater(`${expression} = $event`);

    el.type = 'text';
    el.inputMode = 'numeric';
    el.classList.add('format-currency');

    // Auto-select seluruh teks saat fokus (memudahkan edit angka tanpa repot hapus 0 manual)
    el.addEventListener('focus', function() {
        this.select();
    });
    el.addEventListener('mouseup', function(e) {
        if (this.dataset.justFocused) {
            delete this.dataset.justFocused;
            e.preventDefault();
        }
    });
    el.addEventListener('mousedown', function() {
        if (document.activeElement !== this) {
            this.dataset.justFocused = 'true';
        }
    });

    // Effect: sync display saat data Alpine berubah dari luar
    effect(() => {
        get(val => {
            let formatted = window.formatRupiah(val);
            if (window.unformatRupiah(el.value) !== window.unformatRupiah(val)) {
                el.value = formatted;
            }
        });
    });

    // Handle Backspace jika kursor tepat berada di sebelah kanan tanda titik pemisah
    el.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && el.selectionStart === el.selectionEnd) {
            let pos = el.selectionStart;
            if (pos > 0 && el.value[pos - 1] === '.') {
                e.preventDefault();
                el.setSelectionRange(pos - 1, pos - 1);
            }
        }
    });

    // Event: saat user mengetik, format tampilan dan kirim raw number ke Alpine data
    el.addEventListener('input', () => {
        window.applyMaskWithCursor(el);
        let rawNum = window.unformatRupiah(el.value);
        set(() => {}, { scope: { $event: rawNum } });
        el.dispatchEvent(new CustomEvent('change', { bubbles: true }));
    });
});

Alpine.start();

// Initialize currency engine for standard HTML inputs across the app
setupCurrencyEngine();
