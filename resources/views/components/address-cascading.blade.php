@props([
    'name' => 'address',
    'value' => '',
    'label' => 'Alamat Lengkap',
    'required' => true,
    'placeholder' => 'Nama jalan, gedung, nomor rumah, RT/RW...',
])

@php
    $currentAddress = old($name, $value ?? '');
@endphp

<div 
    x-data="{
        provinces: [],
        regencies: [],
        districts: [],
        villages: [],

        selectedProvince: '',
        selectedProvinceName: '',
        selectedRegency: '',
        selectedRegencyName: '',
        selectedDistrict: '',
        selectedDistrictName: '',
        selectedVillage: '',
        selectedVillageName: '',
        postalCode: '',
        streetAddress: '',
        fullAddress: @js($currentAddress),
        isLoading: false,

        async init() {
            // Load provinces on start
            try {
                const res = await fetch('{{ route('regions.provinces') }}');
                const data = await res.json();
                if (data.success) {
                    this.provinces = data.data;
                }
            } catch (e) {
                console.error('Failed to load provinces', e);
            }

            // If initial address exists and has street part
            if (this.fullAddress) {
                this.streetAddress = this.fullAddress;
            }
        },

        async onProvinceChange() {
            this.selectedRegency = '';
            this.selectedRegencyName = '';
            this.selectedDistrict = '';
            this.selectedDistrictName = '';
            this.selectedVillage = '';
            this.selectedVillageName = '';
            this.postalCode = '';
            this.regencies = [];
            this.districts = [];
            this.villages = [];

            const pObj = this.provinces.find(p => p.id === this.selectedProvince);
            this.selectedProvinceName = pObj ? pObj.name : '';
            this.updateFullAddress();

            if (!this.selectedProvince) return;

            this.isLoading = true;
            try {
                const res = await fetch(`{{ url('/regions/provinces') }}/${this.selectedProvince}/regencies`);
                const data = await res.json();
                if (data.success) this.regencies = data.data;
            } catch (e) {
                console.error('Failed to load regencies', e);
            } finally {
                this.isLoading = false;
            }
        },

        async onRegencyChange() {
            this.selectedDistrict = '';
            this.selectedDistrictName = '';
            this.selectedVillage = '';
            this.selectedVillageName = '';
            this.postalCode = '';
            this.districts = [];
            this.villages = [];

            const rObj = this.regencies.find(r => r.id === this.selectedRegency);
            this.selectedRegencyName = rObj ? rObj.name : '';
            this.updateFullAddress();

            if (!this.selectedRegency) return;

            this.isLoading = true;
            try {
                const res = await fetch(`{{ url('/regions/regencies') }}/${this.selectedRegency}/districts`);
                const data = await res.json();
                if (data.success) this.districts = data.data;
            } catch (e) {
                console.error('Failed to load districts', e);
            } finally {
                this.isLoading = false;
            }
        },

        async onDistrictChange() {
            this.selectedVillage = '';
            this.selectedVillageName = '';
            this.postalCode = '';
            this.villages = [];

            const dObj = this.districts.find(d => d.id === this.selectedDistrict);
            this.selectedDistrictName = dObj ? dObj.name : '';
            this.updateFullAddress();

            if (!this.selectedDistrict) return;

            this.isLoading = true;
            try {
                const res = await fetch(`{{ url('/regions/districts') }}/${this.selectedDistrict}/villages`);
                const data = await res.json();
                if (data.success) this.villages = data.data;
            } catch (e) {
                console.error('Failed to load villages', e);
            } finally {
                this.isLoading = false;
            }
        },

        onVillageChange() {
            const vObj = this.villages.find(v => v.id === this.selectedVillage);
            if (vObj) {
                this.selectedVillageName = vObj.name;
                if (vObj.postal_code) {
                    this.postalCode = vObj.postal_code;
                }
            } else {
                this.selectedVillageName = '';
            }
            this.updateFullAddress();
        },

        updateFullAddress() {
            let parts = [];
            if (this.streetAddress.trim()) parts.push(this.streetAddress.trim());
            if (this.selectedVillageName) parts.push('Kel. ' + this.selectedVillageName);
            if (this.selectedDistrictName) parts.push('Kec. ' + this.selectedDistrictName);
            if (this.selectedRegencyName) parts.push(this.selectedRegencyName);
            if (this.selectedProvinceName) parts.push('Prov. ' + this.selectedProvinceName);
            if (this.postalCode.trim()) parts.push(this.postalCode.trim());

            if (parts.length > 0) {
                this.fullAddress = parts.join(', ');
            }
        }
    }"
    class="w-full space-y-3"
>
    {{-- Cascading Dropdowns Grid --}}
    <div class="p-3.5 bg-slate-50/80 rounded-lg border border-slate-200">
        <div class="text-xs font-bold text-[#0e1b35] uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
            <i class="fa-solid fa-map-location-dot text-primary"></i>
            <span>Pilih Wilayah Administratif</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            {{-- 1. Provinsi --}}
            <div class="form-group mb-0">
                <label class="form-label text-[11px] font-semibold text-slate-700 mb-1">
                    1. Provinsi @if($required)<span style="color:var(--danger);">*</span>@endif
                </label>
                <select 
                    x-model="selectedProvince" 
                    @change="onProvinceChange()" 
                    class="form-control text-xs" 
                    style="height: 38px !important;"
                >
                    <option value="">-- Pilih Provinsi --</option>
                    <template x-for="p in provinces" :key="p.id">
                        <option :value="p.id" x-text="p.name"></option>
                    </template>
                </select>
            </div>

            {{-- 2. Kabupaten / Kota --}}
            <div class="form-group mb-0">
                <label class="form-label text-[11px] font-semibold text-slate-700 mb-1">
                    2. Kabupaten / Kota @if($required)<span style="color:var(--danger);">*</span>@endif
                </label>
                <select 
                    x-model="selectedRegency" 
                    @change="onRegencyChange()" 
                    :disabled="!selectedProvince || regencies.length === 0"
                    class="form-control text-xs disabled:bg-slate-100 disabled:cursor-not-allowed" 
                    style="height: 38px !important;"
                >
                    <option value="">-- Pilih Kab/Kota --</option>
                    <template x-for="r in regencies" :key="r.id">
                        <option :value="r.id" x-text="r.name"></option>
                    </template>
                </select>
            </div>

            {{-- 3. Kecamatan --}}
            <div class="form-group mb-0">
                <label class="form-label text-[11px] font-semibold text-slate-700 mb-1">
                    3. Kecamatan @if($required)<span style="color:var(--danger);">*</span>@endif
                </label>
                <select 
                    x-model="selectedDistrict" 
                    @change="onDistrictChange()" 
                    :disabled="!selectedRegency || districts.length === 0"
                    class="form-control text-xs disabled:bg-slate-100 disabled:cursor-not-allowed" 
                    style="height: 38px !important;"
                >
                    <option value="">-- Pilih Kecamatan --</option>
                    <template x-for="d in districts" :key="d.id">
                        <option :value="d.id" x-text="d.name"></option>
                    </template>
                </select>
            </div>

            {{-- 4. Kelurahan / Desa & Kode Pos --}}
            <div class="form-group mb-0">
                <label class="form-label text-[11px] font-semibold text-slate-700 mb-1">
                    4. Kelurahan / Desa
                </label>
                <select 
                    x-model="selectedVillage" 
                    @change="onVillageChange()" 
                    :disabled="!selectedDistrict || villages.length === 0"
                    class="form-control text-xs disabled:bg-slate-100 disabled:cursor-not-allowed" 
                    style="height: 38px !important;"
                >
                    <option value="">-- Pilih Kelurahan --</option>
                    <template x-for="v in villages" :key="v.id">
                        <option :value="v.id" x-text="v.name + (v.postal_code ? ' (' + v.postal_code + ')' : '')"></option>
                    </template>
                </select>
            </div>
        </div>

        {{-- Kode Pos Input --}}
        <div class="mt-2.5 flex items-center gap-3">
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-600">Kode Pos:</span>
                <input 
                    type="text" 
                    x-model="postalCode" 
                    @input="updateFullAddress()" 
                    placeholder="Kode Pos" 
                    class="form-control text-xs" 
                    style="width: 110px !important; height: 32px !important; padding: 0 8px !important;"
                    maxlength="10"
                >
            </div>
            <span class="text-[11px] text-slate-400">Kode pos terisi otomatis saat kelurahan dipilih.</span>
        </div>
    </div>

    {{-- Detail Jalan & RT/RW Textarea --}}
    <div class="form-group mb-0">
        <label class="form-label font-bold text-xs uppercase tracking-wider text-[#0e1b35] mb-1.5 flex items-center justify-between">
            <span>
                {{ $label }}
                @if($required)<span style="color:var(--danger);">*</span>@endif
            </span>
            <span class="text-[11px] font-normal text-slate-500 lowercase">Jalan, No, RT/RW, Gedung</span>
        </label>
        
        <textarea 
            x-model="streetAddress" 
            @input="updateFullAddress()"
            placeholder="{{ $placeholder }}"
            class="form-control {{ $errors->has($name) ? 'is-invalid' : '' }}" 
            rows="2"
        ></textarea>

        {{-- Hidden Input carrying the full combined address string for backend compatibility --}}
        <input 
            type="hidden" 
            name="{{ $name }}" 
            :value="fullAddress || streetAddress" 
            @if($required) required @endif
        />

        @error($name)
            <div class="invalid-feedback" style="display:flex; align-items:flex-start; gap:6px; color:#b91c1c; font-size:12px; margin-top:6px; background:#fef2f2; padding:8px 10px; border-radius:6px; border:1px solid #fecaca; line-height:1.4;">
                <i class="fa-solid fa-circle-exclamation" style="margin-top:2px; flex-shrink:0;"></i>
                <span>{{ $message }}</span>
            </div>
        @enderror
    </div>
</div>
