import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Global helper: Format integer with thousand separator (titik per 3 digit, tanpa ,00)
window.formatRupiah = function(val) {
    if (val === undefined || val === null || val === '') return '';
    let digits = String(val).replace(/\D/g, '');
    if (!digits) return '';
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
};

// Global helper: Unformat (hapus titik pemisah ribuan)
window.unformatRupiah = function(val) {
    if (val === undefined || val === null || val === '') return 0;
    let clean = String(val).replace(/\./g, '').trim();
    return parseInt(clean, 10) || 0;
};

// Helper: Terapkan format ribuan ke input sambil menjaga posisi kursor
window.applyMaskWithCursor = function(input) {
    let cursor = input.selectionStart;
    let oldVal = input.value;
    let oldLen = oldVal.length;
    let newVal = window.formatRupiah(oldVal);
    input.value = newVal;
    let newLen = newVal.length;
    let newPos = cursor + (newLen - oldLen);
    if (newPos >= 0 && newPos <= newLen) {
        input.setSelectionRange(newPos, newPos);
    }
};

// Alpine Directive: x-currency="property"
Alpine.directive('currency', (el, { expression }, { evaluateLater, effect }) => {
    let get = evaluateLater(expression);
    let set = evaluateLater(`${expression} = $event`);

    el.type = 'text';
    el.inputMode = 'numeric';
    el.classList.add('format-currency');

    // Effect: sync display saat data Alpine berubah dari luar
    effect(() => {
        get(val => {
            let formatted = window.formatRupiah(val);
            if (el.value.replace(/\D/g, '') !== String(val ?? '').replace(/\D/g, '')) {
                el.value = formatted;
            }
        });
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

