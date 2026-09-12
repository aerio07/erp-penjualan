/**
 * Global Currency & Number Masking Engine (Option B - Thousand Dot + Comma Decimal)
 * FlashSuite Pro ERP
 */

// =========================================================================
// 1. CURRENCY & INTEGER ENGINE (Separator Titik, Khusus Angka Bulat / Rupiah)
// =========================================================================

export function formatRupiah(val) {
    if (val === undefined || val === null || val === '') return '';
    let str = String(val).trim();

    if (typeof val === 'number') {
        str = String(Math.round(val));
    } else if (/^\d+\.\d{1,2}$/.test(str)) {
        str = String(Math.round(parseFloat(str)));
    } else if (/,\d{1,2}$/.test(str)) {
        str = str.replace(/,\d{1,2}$/, '');
    }

    let digits = str.replace(/\D/g, '').replace(/^0+(?=\d)/, '');
    if (!digits) return '';
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

export function unformatRupiah(val) {
    if (val === undefined || val === null || val === '') return 0;
    if (typeof val === 'number') return Math.round(val);
    let str = String(val).trim();
    if (/^\d+\.\d{1,2}$/.test(str)) {
        return Math.round(parseFloat(str)) || 0;
    }
    let clean = str.replace(/\./g, '').trim();
    return parseInt(clean, 10) || 0;
}

export function applyMaskWithCursor(input) {
    let oldVal = input.value;
    let oldCursor = input.selectionStart;

    let digitsBefore = oldVal.slice(0, oldCursor).replace(/\D/g, '').length;

    let digits = oldVal.replace(/\D/g, '').replace(/^0+(?=\d)/, '');
    let newVal = digits ? digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
    input.value = newVal;

    if (!newVal) {
        input.setSelectionRange(0, 0);
        return;
    }

    let newCursor = newVal.length;
    if (digitsBefore === 0) {
        newCursor = 0;
    } else {
        let digitCount = 0;
        for (let i = 0; i < newVal.length; i++) {
            if (/\d/.test(newVal[i])) {
                digitCount++;
            }
            if (digitCount === digitsBefore) {
                newCursor = i + 1;
                break;
            }
        }
    }

    input.setSelectionRange(newCursor, newCursor);
}

// =========================================================================
// 2. DECIMAL & NUMBER ENGINE (Separator Ribuan Titik + Koma Desimal, cm / kg)
// =========================================================================

export function formatDecimal(val) {
    if (val === undefined || val === null || val === '') return '';
    let str = String(val).trim();

    // Dari database dengan titik desimal (misal "1500.50" -> "1.500,5" atau "1.25" -> "1,25" atau "30.00" -> "30")
    if (/^\d+\.\d+$/.test(str)) {
        let parts = str.split('.');
        let intPart = parts[0].replace(/^0+(?=\d)/, '');
        let decPart = parts[1].replace(/0+$/, ''); // Buang nol trailing DB
        let formattedInt = intPart ? intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '0';
        return decPart ? `${formattedInt},${decPart}` : formattedInt;
    }

    // Jika memiliki koma desimal
    let commaIndex = str.indexOf(',');
    if (commaIndex !== -1) {
        let intPart = str.slice(0, commaIndex).replace(/\D/g, '').replace(/^0+(?=\d)/, '');
        let decPart = str.slice(commaIndex + 1).replace(/\D/g, '');
        let formattedInt = intPart ? intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '0';
        return `${formattedInt},${decPart}`;
    }

    // Jika tanpa koma, format sebagai angka integer ribuan biasa
    let digits = str.replace(/\D/g, '').replace(/^0+(?=\d)/, '');
    if (!digits) return '';
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

export function unformatDecimal(val) {
    if (val === undefined || val === null || val === '') return '';
    let str = String(val).trim();
    if (!str) return '';

    // Hapus titik pemisah ribuan
    let clean = str.replace(/\./g, '');
    // Ganti koma ke titik desimal standar database / PHP
    clean = clean.replace(',', '.');
    // Bersihkan titik gantung di akhir jika ada
    clean = clean.replace(/\.$/, '');

    return clean;
}

export function applyDecimalMaskWithCursor(input) {
    let oldVal = input.value;
    let oldCursor = input.selectionStart;

    let commaIndex = oldVal.indexOf(',');
    let hasComma = commaIndex !== -1;

    let digitsBefore = 0;
    let isCursorInDecimal = false;
    let decDigitsBefore = 0;

    if (hasComma && oldCursor > commaIndex) {
        isCursorInDecimal = true;
        decDigitsBefore = oldVal.slice(commaIndex + 1, oldCursor).replace(/\D/g, '').length;
    } else {
        let textBefore = hasComma ? oldVal.slice(0, Math.min(oldCursor, commaIndex)) : oldVal.slice(0, oldCursor);
        digitsBefore = textBefore.replace(/\D/g, '').length;
    }

    let newVal = formatDecimal(oldVal);
    // Pertahankan koma jika user baru saja mengetik koma di akhir (misal "1.500,")
    if (oldVal.endsWith(',') && !newVal.includes(',')) {
        newVal += ',';
    }
    input.value = newVal;

    if (!newVal) {
        input.setSelectionRange(0, 0);
        return;
    }

    let newCommaIndex = newVal.indexOf(',');
    let newCursor = newVal.length;

    if (isCursorInDecimal && newCommaIndex !== -1) {
        newCursor = Math.min(newVal.length, newCommaIndex + 1 + decDigitsBefore);
    } else {
        if (digitsBefore === 0) {
            newCursor = 0;
        } else {
            let intPart = newCommaIndex !== -1 ? newVal.slice(0, newCommaIndex) : newVal;
            let digitCount = 0;
            for (let i = 0; i < intPart.length; i++) {
                if (/\d/.test(intPart[i])) {
                    digitCount++;
                }
                if (digitCount === digitsBefore) {
                    newCursor = i + 1;
                    break;
                }
            }
        }
    }

    input.setSelectionRange(newCursor, newCursor);
}

// =========================================================================
// 3. GLOBAL EXPORTS & SELECTORS
// =========================================================================

if (typeof window !== 'undefined') {
    window.formatRupiah = formatRupiah;
    window.unformatRupiah = unformatRupiah;
    window.applyMaskWithCursor = applyMaskWithCursor;
    window.formatDecimal = formatDecimal;
    window.unformatDecimal = unformatDecimal;
    window.applyDecimalMaskWithCursor = applyDecimalMaskWithCursor;
}

export const currencySelector = [
    'input[name="purchase_price"]',
    'input[name="sell_price"]',
    'input[name="price_online"]',
    'input[name="price_offline"]',
    'input[name="credit_limit"]',
    'input[name="discount_amount"]',
    'input[name="amount"]',
    'input[name="sale_price"]',
    'input[name="acquisition_cost"]',
    'input[name="salvage_value"]',
    'input[name="unit_cost"]',
    '.format-currency',
    '[data-type="currency"]',
    '[data-format="currency"]'
].join(',');

export const integerSelector = [
    'input[name="min_stock"]',
    '.format-integer',
    '[data-type="integer"]',
    '[data-format="integer"]'
].join(',');

export const decimalSelector = [
    'input[name="length_cm"]',
    'input[name="width_cm"]',
    'input[name="height_cm"]',
    'input[name="weight_kg"]',
    '.format-decimal',
    '.format-number',
    '[data-type="decimal"]',
    '[data-type="number"]',
    '[data-format="decimal"]',
    '[data-format="number"]'
].join(',');

// Helper untuk auto-select seluruh teks saat elemen difokuskan (agar user tidak perlu repot hapus nol lama)
function bindFocusSelect(input) {
    input.addEventListener('focus', function() {
        this.select();
    });
    input.addEventListener('mouseup', function(e) {
        if (this.dataset.justFocused) {
            delete this.dataset.justFocused;
            e.preventDefault();
        }
    });
    input.addEventListener('mousedown', function() {
        if (document.activeElement !== this) {
            this.dataset.justFocused = 'true';
        }
    });
}

// =========================================================================
// 4. MASK INITIALIZERS
// =========================================================================

export function initCurrencyMask(input) {
    if (!input || input.dataset.maskInitialized) return;
    if (input.hasAttribute('x-currency')) return;

    input.dataset.maskInitialized = 'true';
    if (input.type === 'number') {
        input.type = 'text';
    }
    input.inputMode = 'numeric';
    input.classList.add('format-currency');

    if (input.value) {
        let raw = input.value.trim();
        if (/^\d+\.\d{1,2}$/.test(raw)) {
            input.value = String(Math.round(parseFloat(raw)));
        }
        input.value = formatRupiah(input.value);
    }

    bindFocusSelect(input);

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && this.selectionStart === this.selectionEnd) {
            let pos = this.selectionStart;
            if (pos > 0 && this.value[pos - 1] === '.') {
                e.preventDefault();
                this.setSelectionRange(pos - 1, pos - 1);
            }
        }
    });

    input.addEventListener('input', function() {
        applyMaskWithCursor(this);
    });
}

export function initIntegerMask(input) {
    if (!input || input.dataset.maskInitialized) return;
    input.dataset.maskInitialized = 'true';
    if (input.type === 'number') {
        input.type = 'text';
    }
    input.inputMode = 'numeric';
    input.classList.add('format-integer');

    if (input.value) {
        let raw = input.value.trim();
        if (/^\d+\.\d{1,2}$/.test(raw)) {
            input.value = String(Math.round(parseFloat(raw)));
        }
        input.value = formatRupiah(input.value);
    }

    bindFocusSelect(input);

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && this.selectionStart === this.selectionEnd) {
            let pos = this.selectionStart;
            if (pos > 0 && this.value[pos - 1] === '.') {
                e.preventDefault();
                this.setSelectionRange(pos - 1, pos - 1);
            }
        }
    });

    input.addEventListener('input', function() {
        applyMaskWithCursor(this);
    });
}

export function initDecimalMask(input) {
    if (!input || input.dataset.maskInitialized) return;
    input.dataset.maskInitialized = 'true';
    if (input.type === 'number') {
        input.type = 'text';
    }
    input.inputMode = 'decimal';
    input.classList.add('format-decimal');

    if (input.value) {
        input.value = formatDecimal(input.value);
    }

    bindFocusSelect(input);

    input.addEventListener('keydown', function(e) {
        // Jika menekan titik (.) atau koma (,), gunakan koma desimal tunggal
        if (e.key === '.' || e.key === ',') {
            e.preventDefault();
            if (!this.value.includes(',')) {
                let start = this.selectionStart;
                let end = this.selectionEnd;
                this.value = this.value.slice(0, start) + ',' + this.value.slice(end);
                this.setSelectionRange(start + 1, start + 1);
                applyDecimalMaskWithCursor(this);
                this.dispatchEvent(new Event('input', { bubbles: true }));
            }
        } else if (e.key === 'Backspace' && this.selectionStart === this.selectionEnd) {
            let pos = this.selectionStart;
            if (pos > 0 && this.value[pos - 1] === '.') {
                e.preventDefault();
                this.setSelectionRange(pos - 1, pos - 1);
            }
        }
    });

    input.addEventListener('input', function() {
        applyDecimalMaskWithCursor(this);
    });
}

// =========================================================================
// 5. ENGINE SETUP (DOM OBSERVER & FORM SUBMIT)
// =========================================================================

export function setupCurrencyEngine() {
    function runInit() {
        document.querySelectorAll(currencySelector).forEach(initCurrencyMask);
        document.querySelectorAll(integerSelector).forEach(initIntegerMask);
        document.querySelectorAll(decimalSelector).forEach(initDecimalMask);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runInit);
    } else {
        runInit();
    }

    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) {
                    if (node.matches) {
                        if (node.matches(currencySelector)) initCurrencyMask(node);
                        if (node.matches(integerSelector)) initIntegerMask(node);
                        if (node.matches(decimalSelector)) initDecimalMask(node);
                    }
                    if (node.querySelectorAll) {
                        node.querySelectorAll(currencySelector).forEach(initCurrencyMask);
                        node.querySelectorAll(integerSelector).forEach(initIntegerMask);
                        node.querySelectorAll(decimalSelector).forEach(initDecimalMask);
                    }
                }
            });
        });
    });
    observer.observe(document.body, { childList: true, subtree: true });

    // Unmask saat form submit (Capture Phase) agar backend menerima integer dan desimal bersih
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form || !form.querySelectorAll) return;

        // 1. Currency & Integer inputs -> kirim integer bersih
        const intInputs = form.querySelectorAll(currencySelector + ', ' + integerSelector);
        intInputs.forEach(function(input) {
            if (input.value) {
                input.dataset.maskedValue = input.value;
                input.value = unformatRupiah(input.value);
            }
        });

        // 2. Decimal inputs -> kirim format float desimal standar (misal "1.500,5" -> "1500.5")
        const decInputs = form.querySelectorAll(decimalSelector);
        decInputs.forEach(function(input) {
            if (input.value) {
                input.dataset.maskedValue = input.value;
                input.value = unformatDecimal(input.value);
            }
        });

        setTimeout(function() {
            intInputs.forEach(function(input) {
                if (input.dataset.maskedValue) {
                    input.value = input.dataset.maskedValue;
                    delete input.dataset.maskedValue;
                }
            });
            decInputs.forEach(function(input) {
                if (input.dataset.maskedValue) {
                    input.value = input.dataset.maskedValue;
                    delete input.dataset.maskedValue;
                }
            });
        }, 100);
    }, true);
}
