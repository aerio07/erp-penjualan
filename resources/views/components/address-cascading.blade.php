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
    x-data="addressCascading(@js($currentAddress))"
    class="w-full space-y-3"
    @click.outside="closeAll()"
    @keydown.escape.window="closeAll()"
>
    {{-- Cascading Dropdowns Grid --}}
    <div class="p-3.5 bg-slate-50/80 rounded-lg border border-slate-200">
        <div class="text-xs font-bold text-[#0e1b35] uppercase tracking-wider mb-2.5 flex items-center justify-between">
            <div class="flex items-center gap-1.5">
                <i class="fa-solid fa-map-location-dot text-primary"></i>
                <span>Pilih Wilayah Administratif</span>
            </div>
            <span class="text-[11px] font-normal text-slate-500 normal-case">Ketik untuk mencari atau scroll opsi</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            
            {{-- 1. PROVINSI (Searchable Dropdown + Required Native Validation) --}}
            <div class="form-group mb-0 relative">
                <label class="form-label text-[11px] font-semibold text-slate-700 mb-1 flex items-center justify-between">
                    <span>1. Provinsi @if($required)<span style="color:var(--danger);">*</span>@endif</span>
                    <span x-show="loadingProvince" class="text-primary text-[10px]"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
                </label>

                {{-- Native Input for HTML5 Form Validation --}}
                <input 
                    type="text" 
                    x-ref="provinceInput"
                    :value="selectedProvince" 
                    @if($required) 
                        required 
                        oninvalid="this.setCustomValidity('Silakan pilih Provinsi terlebih dahulu')"
                        oninput="this.setCustomValidity('')"
                    @endif
                    tabindex="-1"
                    aria-hidden="true"
                    style="position: absolute; top: 22px; left: 0; width: 100%; height: 38px; opacity: 0; pointer-events: none; z-index: -1;"
                />

                {{-- Trigger Button --}}
                <div 
                    type="button"
                    @click="openProvince = !openProvince; openRegency = false; openDistrict = false; openVillage = false; if(openProvince) $nextTick(() => $refs.searchProvinceInput?.focus())"
                    class="form-control"
                    :class="{ 'border-primary ring-1 ring-primary/20': openProvince }"
                    style="display: flex !important; align-items: center !important; justify-content: space-between !important; cursor: pointer; user-select: none; background-color: #fff; height: 38px !important; border-radius: 0.5rem; padding: 0 0.65rem !important; box-sizing: border-box !important;"
                >
                    <span 
                        x-text="selectedProvinceName || '-- Pilih Provinsi --'" 
                        :class="!selectedProvince ? 'text-slate-400' : 'text-slate-900 font-semibold'"
                        style="white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; font-size: 12px; line-height: 1.2;"
                    ></span>
                    
                    <div style="display: flex; align-items: center; gap: 4px; flex-shrink: 0; margin-left: 6px;">
                        <template x-if="selectedProvince">
                            <i 
                                class="fa-solid fa-xmark text-slate-400 hover:text-red-500" 
                                style="font-size: 11px; padding: 2px; cursor: pointer;" 
                                @click.stop="selectProvince(null)"
                                title="Hapus Pilihan"
                            ></i>
                        </template>
                        <i 
                            class="fa-solid fa-chevron-down text-slate-400 text-[11px]" 
                            :style="openProvince ? 'transform: rotate(180deg); transition: transform 0.2s;' : 'transition: transform 0.2s;'"
                        ></i>
                    </div>
                </div>

                {{-- Popup Menu --}}
                <div 
                    x-show="openProvince" 
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-1"
                    style="position: absolute; top: calc(100% + 4px); left: 0; width: 100%; min-width: 220px; z-index: 1060; background: #ffffff; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1); border: 1px solid #cbd5e1; max-height: 260px; display: flex; flex-direction: column; overflow: hidden;"
                    x-cloak
                >
                    {{-- Search Input Box --}}
                    <div style="padding: 6px 8px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; position: relative;">
                        <div style="position: relative; display: flex; align-items: center;">
                            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 9px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 11px; pointer-events: none;"></i>
                            <input 
                                type="text" 
                                x-ref="searchProvinceInput"
                                x-model="searchProvince" 
                                placeholder="Ketik cari provinsi..." 
                                class="form-control"
                                style="width: 100% !important; padding-left: 28px !important; padding-right: 24px !important; font-size: 12px !important; height: 32px !important; border-radius: 6px !important; border: 1px solid #cbd5e1 !important;"
                                @click.stop
                            />
                            <template x-if="searchProvince">
                                <button 
                                    type="button" 
                                    @click="searchProvince = ''; $refs.searchProvinceInput.focus()"
                                    style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 2px; font-size: 11px;"
                                >
                                    <i class="fa-solid fa-circle-xmark"></i>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Scrollable List --}}
                    <div style="overflow-y: auto; max-height: 200px; padding: 2px 0;">
                        <template x-for="p in filteredProvinces" :key="p.id">
                            <div 
                                @click="selectProvince(p)"
                                :class="{ 'bg-blue-50 font-semibold text-blue-700': selectedProvince === p.id }"
                                class="px-3 py-2 cursor-pointer text-xs flex items-center justify-between hover:bg-slate-100 transition-colors border-b border-slate-50"
                            >
                                <span x-text="p.name" style="color: #1e293b;"></span>
                                <template x-if="selectedProvince === p.id">
                                    <i class="fa-solid fa-check text-blue-600 text-[11px]"></i>
                                </template>
                            </div>
                        </template>

                        <template x-if="filteredProvinces.length === 0">
                            <div class="p-3 text-center text-slate-400 text-xs">
                                <i class="fa-solid fa-magnifying-glass mb-1 block opacity-40"></i>
                                Tidak ditemukan
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- 2. KABUPATEN / KOTA (Searchable Dropdown + Required Native Validation) --}}
            <div class="form-group mb-0 relative">
                <label class="form-label text-[11px] font-semibold text-slate-700 mb-1 flex items-center justify-between">
                    <span>2. Kabupaten / Kota @if($required)<span style="color:var(--danger);">*</span>@endif</span>
                    <span x-show="loadingRegency" class="text-primary text-[10px]"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
                </label>

                {{-- Native Input for HTML5 Form Validation --}}
                <input 
                    type="text" 
                    x-ref="regencyInput"
                    :value="selectedRegency" 
                    @if($required) 
                        required 
                        oninvalid="this.setCustomValidity('Silakan pilih Kabupaten / Kota terlebih dahulu')"
                        oninput="this.setCustomValidity('')"
                    @endif
                    tabindex="-1"
                    aria-hidden="true"
                    style="position: absolute; top: 22px; left: 0; width: 100%; height: 38px; opacity: 0; pointer-events: none; z-index: -1;"
                />

                {{-- Trigger Button --}}
                <div 
                    type="button"
                    @click="if(selectedProvince && regencies.length > 0 && !loadingRegency) { openRegency = !openRegency; openProvince = false; openDistrict = false; openVillage = false; if(openRegency) $nextTick(() => $refs.searchRegencyInput?.focus()); }"
                    class="form-control"
                    :class="{ 
                        'border-primary ring-1 ring-primary/20': openRegency,
                        'bg-slate-100/90 text-slate-400 cursor-not-allowed border-slate-200': !selectedProvince || regencies.length === 0 || loadingRegency 
                    }"
                    style="display: flex !important; align-items: center !important; justify-content: space-between !important; cursor: pointer; user-select: none; background-color: #fff; height: 38px !important; border-radius: 0.5rem; padding: 0 0.65rem !important; box-sizing: border-box !important;"
                >
                    <span 
                        x-text="loadingRegency ? 'Memuat kab/kota...' : (selectedRegencyName || '-- Pilih Kab/Kota --')" 
                        :class="!selectedRegency ? 'text-slate-400' : 'text-slate-900 font-semibold'"
                        style="white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; font-size: 12px; line-height: 1.2;"
                    ></span>
                    
                    <div style="display: flex; align-items: center; gap: 4px; flex-shrink: 0; margin-left: 6px;">
                        <template x-if="selectedRegency">
                            <i 
                                class="fa-solid fa-xmark text-slate-400 hover:text-red-500" 
                                style="font-size: 11px; padding: 2px; cursor: pointer;" 
                                @click.stop="selectRegency(null)"
                                title="Hapus Pilihan"
                            ></i>
                        </template>
                        <i 
                            class="fa-solid fa-chevron-down text-slate-400 text-[11px]" 
                            :style="openRegency ? 'transform: rotate(180deg); transition: transform 0.2s;' : 'transition: transform 0.2s;'"
                        ></i>
                    </div>
                </div>

                {{-- Popup Menu --}}
                <div 
                    x-show="openRegency" 
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-1"
                    style="position: absolute; top: calc(100% + 4px); left: 0; width: 100%; min-width: 220px; z-index: 1060; background: #ffffff; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1); border: 1px solid #cbd5e1; max-height: 260px; display: flex; flex-direction: column; overflow: hidden;"
                    x-cloak
                >
                    {{-- Search Input Box --}}
                    <div style="padding: 6px 8px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; position: relative;">
                        <div style="position: relative; display: flex; align-items: center;">
                            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 9px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 11px; pointer-events: none;"></i>
                            <input 
                                type="text" 
                                x-ref="searchRegencyInput"
                                x-model="searchRegency" 
                                placeholder="Ketik cari kab/kota..." 
                                class="form-control"
                                style="width: 100% !important; padding-left: 28px !important; padding-right: 24px !important; font-size: 12px !important; height: 32px !important; border-radius: 6px !important; border: 1px solid #cbd5e1 !important;"
                                @click.stop
                            />
                            <template x-if="searchRegency">
                                <button 
                                    type="button" 
                                    @click="searchRegency = ''; $refs.searchRegencyInput.focus()"
                                    style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 2px; font-size: 11px;"
                                >
                                    <i class="fa-solid fa-circle-xmark"></i>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Scrollable List --}}
                    <div style="overflow-y: auto; max-height: 200px; padding: 2px 0;">
                        <template x-for="r in filteredRegencies" :key="r.id">
                            <div 
                                @click="selectRegency(r)"
                                :class="{ 'bg-blue-50 font-semibold text-blue-700': selectedRegency === r.id }"
                                class="px-3 py-2 cursor-pointer text-xs flex items-center justify-between hover:bg-slate-100 transition-colors border-b border-slate-50"
                            >
                                <span x-text="r.name" style="color: #1e293b;"></span>
                                <template x-if="selectedRegency === r.id">
                                    <i class="fa-solid fa-check text-blue-600 text-[11px]"></i>
                                </template>
                            </div>
                        </template>

                        <template x-if="filteredRegencies.length === 0">
                            <div class="p-3 text-center text-slate-400 text-xs">
                                <i class="fa-solid fa-magnifying-glass mb-1 block opacity-40"></i>
                                Tidak ditemukan
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- 3. KECAMATAN (Searchable Dropdown + Required Native Validation) --}}
            <div class="form-group mb-0 relative">
                <label class="form-label text-[11px] font-semibold text-slate-700 mb-1 flex items-center justify-between">
                    <span>3. Kecamatan @if($required)<span style="color:var(--danger);">*</span>@endif</span>
                    <span x-show="loadingDistrict" class="text-primary text-[10px]"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
                </label>

                {{-- Native Input for HTML5 Form Validation --}}
                <input 
                    type="text" 
                    x-ref="districtInput"
                    :value="selectedDistrict" 
                    @if($required) 
                        required 
                        oninvalid="this.setCustomValidity('Silakan pilih Kecamatan terlebih dahulu')"
                        oninput="this.setCustomValidity('')"
                    @endif
                    tabindex="-1"
                    aria-hidden="true"
                    style="position: absolute; top: 22px; left: 0; width: 100%; height: 38px; opacity: 0; pointer-events: none; z-index: -1;"
                />

                {{-- Trigger Button --}}
                <div 
                    type="button"
                    @click="if(selectedRegency && districts.length > 0 && !loadingDistrict) { openDistrict = !openDistrict; openProvince = false; openRegency = false; openVillage = false; if(openDistrict) $nextTick(() => $refs.searchDistrictInput?.focus()); }"
                    class="form-control"
                    :class="{ 
                        'border-primary ring-1 ring-primary/20': openDistrict,
                        'bg-slate-100/90 text-slate-400 cursor-not-allowed border-slate-200': !selectedRegency || districts.length === 0 || loadingDistrict 
                    }"
                    style="display: flex !important; align-items: center !important; justify-content: space-between !important; cursor: pointer; user-select: none; background-color: #fff; height: 38px !important; border-radius: 0.5rem; padding: 0 0.65rem !important; box-sizing: border-box !important;"
                >
                    <span 
                        x-text="loadingDistrict ? 'Memuat kecamatan...' : (selectedDistrictName || '-- Pilih Kecamatan --')" 
                        :class="!selectedDistrict ? 'text-slate-400' : 'text-slate-900 font-semibold'"
                        style="white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; font-size: 12px; line-height: 1.2;"
                    ></span>
                    
                    <div style="display: flex; align-items: center; gap: 4px; flex-shrink: 0; margin-left: 6px;">
                        <template x-if="selectedDistrict">
                            <i 
                                class="fa-solid fa-xmark text-slate-400 hover:text-red-500" 
                                style="font-size: 11px; padding: 2px; cursor: pointer;" 
                                @click.stop="selectDistrict(null)"
                                title="Hapus Pilihan"
                            ></i>
                        </template>
                        <i 
                            class="fa-solid fa-chevron-down text-slate-400 text-[11px]" 
                            :style="openDistrict ? 'transform: rotate(180deg); transition: transform 0.2s;' : 'transition: transform 0.2s;'"
                        ></i>
                    </div>
                </div>

                {{-- Popup Menu --}}
                <div 
                    x-show="openDistrict" 
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-1"
                    style="position: absolute; top: calc(100% + 4px); left: 0; width: 100%; min-width: 220px; z-index: 1060; background: #ffffff; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1); border: 1px solid #cbd5e1; max-height: 260px; display: flex; flex-direction: column; overflow: hidden;"
                    x-cloak
                >
                    {{-- Search Input Box --}}
                    <div style="padding: 6px 8px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; position: relative;">
                        <div style="position: relative; display: flex; align-items: center;">
                            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 9px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 11px; pointer-events: none;"></i>
                            <input 
                                type="text" 
                                x-ref="searchDistrictInput"
                                x-model="searchDistrict" 
                                placeholder="Ketik cari kecamatan..." 
                                class="form-control"
                                style="width: 100% !important; padding-left: 28px !important; padding-right: 24px !important; font-size: 12px !important; height: 32px !important; border-radius: 6px !important; border: 1px solid #cbd5e1 !important;"
                                @click.stop
                            />
                            <template x-if="searchDistrict">
                                <button 
                                    type="button" 
                                    @click="searchDistrict = ''; $refs.searchDistrictInput.focus()"
                                    style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 2px; font-size: 11px;"
                                >
                                    <i class="fa-solid fa-circle-xmark"></i>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Scrollable List --}}
                    <div style="overflow-y: auto; max-height: 200px; padding: 2px 0;">
                        <template x-for="d in filteredDistricts" :key="d.id">
                            <div 
                                @click="selectDistrict(d)"
                                :class="{ 'bg-blue-50 font-semibold text-blue-700': selectedDistrict === d.id }"
                                class="px-3 py-2 cursor-pointer text-xs flex items-center justify-between hover:bg-slate-100 transition-colors border-b border-slate-50"
                            >
                                <span x-text="d.name" style="color: #1e293b;"></span>
                                <template x-if="selectedDistrict === d.id">
                                    <i class="fa-solid fa-check text-blue-600 text-[11px]"></i>
                                </template>
                            </div>
                        </template>

                        <template x-if="filteredDistricts.length === 0">
                            <div class="p-3 text-center text-slate-400 text-xs">
                                <i class="fa-solid fa-magnifying-glass mb-1 block opacity-40"></i>
                                Tidak ditemukan
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- 4. KELURAHAN / DESA (Searchable Dropdown) --}}
            <div class="form-group mb-0 relative">
                <label class="form-label text-[11px] font-semibold text-slate-700 mb-1 flex items-center justify-between">
                    <span>4. Kelurahan / Desa</span>
                    <span x-show="loadingVillage" class="text-primary text-[10px]"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
                </label>

                {{-- Trigger Button --}}
                <div 
                    type="button"
                    @click="if(selectedDistrict && villages.length > 0 && !loadingVillage) { openVillage = !openVillage; openProvince = false; openRegency = false; openDistrict = false; if(openVillage) $nextTick(() => $refs.searchVillageInput?.focus()); }"
                    class="form-control"
                    :class="{ 
                        'border-primary ring-1 ring-primary/20': openVillage,
                        'bg-slate-100/90 text-slate-400 cursor-not-allowed border-slate-200': !selectedDistrict || villages.length === 0 || loadingVillage 
                    }"
                    style="display: flex !important; align-items: center !important; justify-content: space-between !important; cursor: pointer; user-select: none; background-color: #fff; height: 38px !important; border-radius: 0.5rem; padding: 0 0.65rem !important; box-sizing: border-box !important;"
                >
                    <span 
                        x-text="loadingVillage ? 'Memuat kelurahan...' : (selectedVillageName ? (selectedVillageName + (postalCode ? ' (' + postalCode + ')' : '')) : '-- Pilih Kelurahan --')" 
                        :class="!selectedVillage ? 'text-slate-400' : 'text-slate-900 font-semibold'"
                        style="white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; font-size: 12px; line-height: 1.2;"
                    ></span>
                    
                    <div style="display: flex; align-items: center; gap: 4px; flex-shrink: 0; margin-left: 6px;">
                        <template x-if="selectedVillage">
                            <i 
                                class="fa-solid fa-xmark text-slate-400 hover:text-red-500" 
                                style="font-size: 11px; padding: 2px; cursor: pointer;" 
                                @click.stop="selectVillage(null)"
                                title="Hapus Pilihan"
                            ></i>
                        </template>
                        <i 
                            class="fa-solid fa-chevron-down text-slate-400 text-[11px]" 
                            :style="openVillage ? 'transform: rotate(180deg); transition: transform 0.2s;' : 'transition: transform 0.2s;'"
                        ></i>
                    </div>
                </div>

                {{-- Popup Menu --}}
                <div 
                    x-show="openVillage" 
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-1"
                    style="position: absolute; top: calc(100% + 4px); left: 0; width: 100%; min-width: 220px; z-index: 1060; background: #ffffff; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1); border: 1px solid #cbd5e1; max-height: 260px; display: flex; flex-direction: column; overflow: hidden;"
                    x-cloak
                >
                    {{-- Search Input Box --}}
                    <div style="padding: 6px 8px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; position: relative;">
                        <div style="position: relative; display: flex; align-items: center;">
                            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 9px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 11px; pointer-events: none;"></i>
                            <input 
                                type="text" 
                                x-ref="searchVillageInput"
                                x-model="searchVillage" 
                                placeholder="Ketik cari kelurahan..." 
                                class="form-control"
                                style="width: 100% !important; padding-left: 28px !important; padding-right: 24px !important; font-size: 12px !important; height: 32px !important; border-radius: 6px !important; border: 1px solid #cbd5e1 !important;"
                                @click.stop
                            />
                            <template x-if="searchVillage">
                                <button 
                                    type="button" 
                                    @click="searchVillage = ''; $refs.searchVillageInput.focus()"
                                    style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 2px; font-size: 11px;"
                                >
                                    <i class="fa-solid fa-circle-xmark"></i>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Scrollable List --}}
                    <div style="overflow-y: auto; max-height: 200px; padding: 2px 0;">
                        <template x-for="v in filteredVillages" :key="v.id">
                            <div 
                                @click="selectVillage(v)"
                                :class="{ 'bg-blue-50 font-semibold text-blue-700': selectedVillage === v.id }"
                                class="px-3 py-2 cursor-pointer text-xs flex items-center justify-between hover:bg-slate-100 transition-colors border-b border-slate-50"
                            >
                                <div>
                                    <span x-text="v.name" style="color: #1e293b;"></span>
                                    <template x-if="v.postal_code">
                                        <span class="text-[10.5px] text-slate-400 font-mono ml-1" x-text="'(' + v.postal_code + ')'"></span>
                                    </template>
                                </div>
                                <template x-if="selectedVillage === v.id">
                                    <i class="fa-solid fa-check text-blue-600 text-[11px]"></i>
                                </template>
                            </div>
                        </template>

                        <template x-if="filteredVillages.length === 0">
                            <div class="p-3 text-center text-slate-400 text-xs">
                                <i class="fa-solid fa-magnifying-glass mb-1 block opacity-40"></i>
                                Tidak ditemukan
                            </div>
                        </template>
                    </div>
                </div>
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
            <span class="text-[11px] text-slate-400">Kode pos otomatis terisi saat kelurahan dipilih atau dapat disesuaikan manual.</span>
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
            @if($required) 
                required 
                oninvalid="this.setCustomValidity('Silakan isi detail alamat jalan, nomor rumah, RT/RW')"
                oninput="this.setCustomValidity('')"
            @endif
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

<script>
(function() {
    function initAddressCascading() {
        if (window.Alpine && !window.Alpine.data('addressCascading')) {
            window.Alpine.data('addressCascading', (initialAddress = '') => ({
                provinces: [],
                regencies: [],
                districts: [],
                villages: [],

                openProvince: false,
                searchProvince: '',
                selectedProvince: '',
                selectedProvinceName: '',

                openRegency: false,
                searchRegency: '',
                selectedRegency: '',
                selectedRegencyName: '',

                openDistrict: false,
                searchDistrict: '',
                selectedDistrict: '',
                selectedDistrictName: '',

                openVillage: false,
                searchVillage: '',
                selectedVillage: '',
                selectedVillageName: '',

                postalCode: '',
                streetAddress: '',
                fullAddress: initialAddress || '',
                
                loadingProvince: false,
                loadingRegency: false,
                loadingDistrict: false,
                loadingVillage: false,

                cleanStreetAddress(str) {
                    if (!str) return '';
                    const idx = str.search(/,\s*(Kel\.|Kec\.|Prov\.)/i);
                    if (idx !== -1) {
                        return str.substring(0, idx).trim();
                    }
                    return str.trim();
                },

                parseInitialAddress(rawAddress) {
                    if (!rawAddress || !rawAddress.trim()) return;

                    let addressStr = rawAddress.trim();
                    
                    const postalMatch = addressStr.match(/,?\s*(\d{5})$/);
                    if (postalMatch) {
                        this.postalCode = postalMatch[1];
                        addressStr = addressStr.substring(0, postalMatch.index).trim();
                    }

                    const provMatch = addressStr.match(/,?\s*Prov\.\s*([^,]+)/i);
                    if (provMatch) {
                        this.selectedProvinceName = provMatch[1].trim();
                        addressStr = addressStr.substring(0, provMatch.index).trim();
                    }

                    const kecMatch = addressStr.match(/,?\s*Kec\.\s*([^,]+)/i);
                    if (kecMatch) {
                        this.selectedDistrictName = kecMatch[1].trim();
                        const regencyPart = addressStr.substring(kecMatch.index + kecMatch[0].length).trim();
                        if (regencyPart) {
                            const regName = regencyPart.replace(/^,\s*/, '').trim();
                            if (regName && !regName.toLowerCase().startsWith('prov.')) {
                                this.selectedRegencyName = regName;
                            }
                        }
                        addressStr = addressStr.substring(0, kecMatch.index).trim();
                    }

                    const kelMatch = addressStr.match(/,?\s*Kel\.\s*([^,]+)/i);
                    if (kelMatch) {
                        this.selectedVillageName = kelMatch[1].trim();
                        addressStr = addressStr.substring(0, kelMatch.index).trim();
                    }

                    this.streetAddress = this.cleanStreetAddress(addressStr);
                },

                async autoSelectRegions() {
                    if (!this.selectedProvinceName) return;
                    const pMatch = this.provinces.find(p => p.name.toLowerCase() === this.selectedProvinceName.toLowerCase());
                    if (!pMatch) return;

                    this.selectedProvince = pMatch.id;
                    this.loadingRegency = true;
                    try {
                        const res = await fetch(`{{ url('/regions/provinces') }}/${this.selectedProvince}/regencies`);
                        const data = await res.json();
                        if (data.success) {
                            this.regencies = data.data;
                            if (this.selectedRegencyName) {
                                const rMatch = this.regencies.find(r => r.name.toLowerCase() === this.selectedRegencyName.toLowerCase());
                                if (rMatch) {
                                    this.selectedRegency = rMatch.id;
                                    this.loadingDistrict = true;
                                    const resD = await fetch(`{{ url('/regions/regencies') }}/${this.selectedRegency}/districts`);
                                    const dataD = await resD.json();
                                    if (dataD.success) {
                                        this.districts = dataD.data;
                                        if (this.selectedDistrictName) {
                                            const dMatch = this.districts.find(d => d.name.toLowerCase() === this.selectedDistrictName.toLowerCase());
                                            if (dMatch) {
                                                this.selectedDistrict = dMatch.id;
                                                this.loadingVillage = true;
                                                const resV = await fetch(`{{ url('/regions/districts') }}/${this.selectedDistrict}/villages`);
                                                const dataV = await resV.json();
                                                if (dataV.success) {
                                                    this.villages = dataV.data;
                                                    if (this.selectedVillageName) {
                                                        const vMatch = this.villages.find(v => v.name.toLowerCase() === this.selectedVillageName.toLowerCase());
                                                        if (vMatch) {
                                                            this.selectedVillage = vMatch.id;
                                                            if (vMatch.postal_code) this.postalCode = vMatch.postal_code;
                                                        }
                                                    }
                                                }
                                                this.loadingVillage = false;
                                            }
                                        }
                                        this.loadingDistrict = false;
                                    }
                                }
                            }
                            this.loadingRegency = false;
                        }
                    } catch (e) {
                        console.error('Failed to auto-select cascading regions', e);
                    } finally {
                        this.loadingRegency = false;
                        this.loadingDistrict = false;
                        this.loadingVillage = false;
                    }
                },

                async init() {
                    if (this.fullAddress) {
                        this.parseInitialAddress(this.fullAddress);
                    }

                    this.loadingProvince = true;
                    try {
                        const res = await fetch('{{ route('regions.provinces') }}');
                        const data = await res.json();
                        if (data.success) {
                            this.provinces = data.data;
                            if (this.selectedProvinceName) {
                                await this.autoSelectRegions();
                            }
                        }
                    } catch (e) {
                        console.error('Failed to load provinces', e);
                    } finally {
                        this.loadingProvince = false;
                    }
                },

                get filteredProvinces() {
                    if (!this.searchProvince.trim()) return this.provinces;
                    const q = this.searchProvince.toLowerCase();
                    return this.provinces.filter(p => p.name.toLowerCase().includes(q));
                },

                get filteredRegencies() {
                    if (!this.searchRegency.trim()) return this.regencies;
                    const q = this.searchRegency.toLowerCase();
                    return this.regencies.filter(r => r.name.toLowerCase().includes(q));
                },

                get filteredDistricts() {
                    if (!this.searchDistrict.trim()) return this.districts;
                    const q = this.searchDistrict.toLowerCase();
                    return this.districts.filter(d => d.name.toLowerCase().includes(q));
                },

                get filteredVillages() {
                    if (!this.searchVillage.trim()) return this.villages;
                    const q = this.searchVillage.toLowerCase();
                    return this.villages.filter(v => 
                        v.name.toLowerCase().includes(q) || 
                        (v.postal_code && v.postal_code.includes(q))
                    );
                },

                async selectProvince(p) {
                    this.selectedProvince = p ? p.id : '';
                    this.selectedProvinceName = p ? p.name : '';
                    this.openProvince = false;
                    this.searchProvince = '';
                    if (this.$refs.provinceInput) {
                        this.$refs.provinceInput.setCustomValidity('');
                    }
                    await this.onProvinceChange();
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
                    this.updateFullAddress();

                    if (!this.selectedProvince) return;

                    this.loadingRegency = true;
                    try {
                        const res = await fetch(`{{ url('/regions/provinces') }}/${this.selectedProvince}/regencies`);
                        const data = await res.json();
                        if (data.success) this.regencies = data.data;
                    } catch (e) {
                        console.error('Failed to load regencies', e);
                    } finally {
                        this.loadingRegency = false;
                    }
                },

                async selectRegency(r) {
                    this.selectedRegency = r ? r.id : '';
                    this.selectedRegencyName = r ? r.name : '';
                    this.openRegency = false;
                    this.searchRegency = '';
                    if (this.$refs.regencyInput) {
                        this.$refs.regencyInput.setCustomValidity('');
                    }
                    await this.onRegencyChange();
                },

                async onRegencyChange() {
                    this.selectedDistrict = '';
                    this.selectedDistrictName = '';
                    this.selectedVillage = '';
                    this.selectedVillageName = '';
                    this.postalCode = '';
                    this.districts = [];
                    this.villages = [];
                    this.updateFullAddress();

                    if (!this.selectedRegency) return;

                    this.loadingDistrict = true;
                    try {
                        const res = await fetch(`{{ url('/regions/regencies') }}/${this.selectedRegency}/districts`);
                        const data = await res.json();
                        if (data.success) this.districts = data.data;
                    } catch (e) {
                        console.error('Failed to load districts', e);
                    } finally {
                        this.loadingDistrict = false;
                    }
                },

                async selectDistrict(d) {
                    this.selectedDistrict = d ? d.id : '';
                    this.selectedDistrictName = d ? d.name : '';
                    this.openDistrict = false;
                    this.searchDistrict = '';
                    if (this.$refs.districtInput) {
                        this.$refs.districtInput.setCustomValidity('');
                    }
                    await this.onDistrictChange();
                },

                async onDistrictChange() {
                    this.selectedVillage = '';
                    this.selectedVillageName = '';
                    this.postalCode = '';
                    this.villages = [];
                    this.updateFullAddress();

                    if (!this.selectedDistrict) return;

                    this.loadingVillage = true;
                    try {
                        const res = await fetch(`{{ url('/regions/districts') }}/${this.selectedDistrict}/villages`);
                        const data = await res.json();
                        if (data.success) this.villages = data.data;
                    } catch (e) {
                        console.error('Failed to load villages', e);
                    } finally {
                        this.loadingVillage = false;
                    }
                },

                selectVillage(v) {
                    if (v) {
                        this.selectedVillage = v.id;
                        this.selectedVillageName = v.name;
                        if (v.postal_code) {
                            this.postalCode = v.postal_code;
                        }
                    } else {
                        this.selectedVillage = '';
                        this.selectedVillageName = '';
                    }
                    this.openVillage = false;
                    this.searchVillage = '';
                    this.onVillageChange();
                },

                onVillageChange() {
                    this.updateFullAddress();
                },

                updateFullAddress() {
                    let cleanStreet = this.cleanStreetAddress(this.streetAddress);
                    let parts = [];
                    if (cleanStreet) parts.push(cleanStreet);
                    if (this.selectedVillageName) parts.push('Kel. ' + this.selectedVillageName);
                    if (this.selectedDistrictName) parts.push('Kec. ' + this.selectedDistrictName);
                    if (this.selectedRegencyName) parts.push(this.selectedRegencyName);
                    if (this.selectedProvinceName) parts.push('Prov. ' + this.selectedProvinceName);
                    if (this.postalCode && this.postalCode.trim()) parts.push(this.postalCode.trim());

                    if (parts.length > 0) {
                        this.fullAddress = parts.join(', ');
                    } else {
                        this.fullAddress = '';
                    }
                },

                closeAll() {
                    this.openProvince = false;
                    this.openRegency = false;
                    this.openDistrict = false;
                    this.openVillage = false;
                }
            }));
        }
    }

    if (window.Alpine) {
        initAddressCascading();
    } else {
        document.addEventListener('alpine:init', initAddressCascading);
    }
})();
</script>
