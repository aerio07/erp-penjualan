@props([
    'name',
    'id' => null,
    'options' => [],
    'value' => null,
    'placeholder' => '-- Pilih Opsi --',
    'searchPlaceholder' => 'Ketik untuk mencari...',
    'required' => false,
    'disabled' => false,
    'error' => null,
    'onSelect' => null,
])

@php
    $inputId = $id ?? $name;
    $currentVal = old($name, $value);
    
    // Format options uniformly into array of [value, label, subtext]
    $formattedOptions = [];
    foreach ($options as $key => $opt) {
        if (is_object($opt)) {
            $val = $opt->id ?? $opt->value ?? $key;
            $lbl = $opt->name ?? $opt->label ?? $opt->title ?? (string)$val;
            $code = $opt->code ?? $opt->sku ?? $opt->code_account ?? null;
            $sub = $code ? "({$code})" : ($opt->subtext ?? null);
            if ($code && !str_contains($lbl, $code)) {
                $lblFull = "{$lbl} ({$code})";
            } else {
                $lblFull = $lbl;
            }
        } elseif (is_array($opt)) {
            $val = $opt['id'] ?? $opt['value'] ?? $key;
            $lblFull = $opt['name'] ?? $opt['label'] ?? (string)$val;
            $sub = $opt['subtext'] ?? null;
        } else {
            $val = $key;
            $lblFull = (string)$opt;
            $sub = null;
        }
        
        $formattedOptions[] = [
            'value' => (string)$val,
            'label' => $lblFull,
            'subtext' => $sub,
        ];
    }

    $initialSelectedLabel = '';
    foreach ($formattedOptions as $fo) {
        if ((string)$fo['value'] === (string)$currentVal) {
            $initialSelectedLabel = $fo['label'];
            break;
        }
    }
@endphp

<div 
    x-data="{
        open: false,
        search: '',
        selected: @js((string)($currentVal ?? '')),
        selectedLabel: @js($initialSelectedLabel),
        options: @js($formattedOptions),
        placeholder: @js($placeholder),
        
        init() {
            if (this.selected) {
                const match = this.options.find(o => String(o.value) === String(this.selected));
                if (match) this.selectedLabel = match.label;
            }
        },

        get filteredOptions() {
            if (!this.search.trim()) return this.options;
            const q = this.search.toLowerCase();
            return this.options.filter(o => 
                o.label.toLowerCase().includes(q) || 
                (o.subtext && o.subtext.toLowerCase().includes(q))
            );
        },

        selectOption(opt) {
            this.selected = opt.value;
            this.selectedLabel = opt.label;
            this.open = false;
            this.search = '';
            
            // Clear any error styling on trigger
            if (this.$refs.trigger) {
                this.$refs.trigger.classList.remove('is-invalid');
                this.$refs.trigger.classList.remove('focused');
                this.$refs.trigger.style.borderColor = '';
                this.$refs.trigger.style.backgroundColor = '';
            }

            // Sync value to native input and trigger change
            this.$nextTick(() => {
                const hiddenInput = this.$refs.hiddenInput;
                if (hiddenInput) {
                    hiddenInput.value = opt.value;
                    hiddenInput.setCustomValidity('');
                    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
                @if($onSelect)
                    ({{ $onSelect }})(opt.value, opt);
                @endif
            });
        },

        clear() {
            this.selected = '';
            this.selectedLabel = '';
            this.search = '';
            this.$nextTick(() => {
                const hiddenInput = this.$refs.hiddenInput;
                if (hiddenInput) {
                    hiddenInput.value = '';
                    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
                @if($onSelect)
                    ({{ $onSelect }})('', null);
                @endif
            });
        }
    }"
    class="searchable-select-wrapper"
    style="position: relative; width: 100%;"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
>
    {{-- Native input for reliable HTML5 constraint validation & tooltip --}}
    <input 
        type="text" 
        name="{{ $name }}" 
        id="{{ $inputId }}" 
        x-ref="hiddenInput"
        :value="selected" 
        @if($required) required @endif
        tabindex="-1"
        aria-hidden="true"
        class="searchable-select-native-input"
        style="position: absolute; top: 0; left: 0; width: 100%; height: 38px; opacity: 0; pointer-events: none; z-index: -1;"
        @focus="$refs.trigger?.classList.add('focused')"
        @blur="$refs.trigger?.classList.remove('focused')"
    />

    {{-- Trigger Button / Field Display --}}
    <div 
        type="button"
        x-ref="trigger"
        @click="if(!{{ $disabled ? 'true' : 'false' }}) { open = !open; if(open) $nextTick(() => $refs.searchInput.focus()); }"
        class="form-control searchable-select-trigger {{ $error || $errors->has($name) ? 'is-invalid' : '' }}"
        :class="{ 'active': open }"
        style="display: flex !important; flex-wrap: nowrap !important; align-items: center !important; justify-content: space-between !important; cursor: pointer; user-select: none; background-color: #fff; height: 38px !important; min-height: 38px !important; max-height: 38px !important; border-radius: 0.5rem; padding: 0 0.75rem !important; box-sizing: border-box !important;"
    >
        <span 
            x-text="selectedLabel || placeholder" 
            :class="!selected ? 'text-slate-400' : 'text-slate-900 font-semibold'"
            style="white-space: nowrap !important; word-break: keep-all !important; overflow: hidden !important; text-overflow: ellipsis !important; flex: 1 1 auto; min-width: 0; line-height: 1.2; padding-right: 8px;"
        ></span>

        <div style="display: flex !important; align-items: center !important; gap: 6px; flex-shrink: 0; margin-left: auto;">
            <template x-if="selected && !{{ $required ? 'true' : 'false' }}">
                <i 
                    class="fa-solid fa-xmark text-slate-400 hover:text-red-500" 
                    style="font-size: 13px; padding: 2px 4px; cursor: pointer;" 
                    @click.stop="clear()"
                    title="Hapus Pilihan"
                ></i>
            </template>
            <i 
                class="fa-solid fa-chevron-down text-slate-400" 
                :style="open ? 'transform: rotate(180deg); transition: transform 0.2s;' : 'transition: transform 0.2s;'"
                style="font-size: 12px;"
            ></i>
        </div>
    </div>

    {{-- Dropdown Popup with Search Box --}}
    <div 
        x-show="open" 
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        style="position: absolute; top: calc(100% + 4px); left: 0; width: 100%; min-width: 100%; max-width: 100%; box-sizing: border-box; z-index: 1060; background: #ffffff; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; max-height: 280px; display: flex; flex-direction: column; overflow: hidden;"
        x-cloak
    >
        {{-- Search Input with Magnifying Glass Icon --}}
        <div style="padding: 8px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; position: relative;">
            <div style="position: relative; display: flex; align-items: center;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 13px; pointer-events: none; z-index: 2;"></i>
                <input 
                    type="text" 
                    x-ref="searchInput"
                    x-model="search" 
                    placeholder="{{ $searchPlaceholder }}" 
                    class="form-control searchable-select-search-input"
                    style="width: 100% !important; padding-left: 32px !important; padding-right: 28px !important; font-size: 13px !important; height: 36px !important; border-radius: 6px !important; border: 1px solid #cbd5e1 !important;"
                    @keydown.down.prevent="$refs.optionsList.firstElementChild?.focus()"
                    @click.stop
                />
                <template x-if="search">
                    <button 
                        type="button" 
                        @click="search = ''; $refs.searchInput.focus()"
                        style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 2px 4px; font-size: 12px; z-index: 2;"
                    >
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </template>
            </div>
        </div>

        {{-- Options List --}}
        <div 
            x-ref="optionsList"
            style="overflow-y: auto; max-height: 220px; padding: 4px 0;"
        >
            <template x-for="opt in filteredOptions" :key="opt.value">
                <div 
                    @click="selectOption(opt)"
                    :class="{ 'bg-blue-50 font-semibold text-blue-700': String(selected) === String(opt.value) }"
                    class="searchable-option-item"
                    style="padding: 9px 12px; cursor: pointer; font-size: 13.5px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f8fafc; transition: background-color 0.15s;"
                    onmouseover="this.style.backgroundColor='#f1f5f9'"
                    onmouseout="if(!this.classList.contains('bg-blue-50')) this.style.backgroundColor='transparent'"
                >
                    <div style="display: flex; flex-direction: column; overflow: hidden; text-overflow: ellipsis;">
                        <span x-text="opt.label" style="color: #1e293b;"></span>
                    </div>
                    <template x-if="String(selected) === String(opt.value)">
                        <i class="fa-solid fa-check text-blue-600" style="font-size: 12px; margin-left: 8px;"></i>
                    </template>
                </div>
            </template>

            {{-- Empty State --}}
            <template x-if="filteredOptions.length === 0">
                <div style="padding: 18px 12px; text-align: center; color: #94a3b8; font-size: 13px;">
                    <i class="fa-solid fa-magnifying-glass" style="font-size: 20px; display: block; margin-bottom: 6px; opacity: 0.5;"></i>
                    <span>Tidak ada data yang cocok dengan pencarian</span>
                </div>
            </template>
        </div>
    </div>

    @error($name)
        <div class="invalid-feedback" style="display: block; margin-top: 4px; font-size: 12px;">{{ $message }}</div>
    @enderror
</div>
