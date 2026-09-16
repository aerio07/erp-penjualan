@props([
    'name' => 'tax_document',
    'id' => null,
    'label' => 'Dokumen NPWP / NIK',
    'accept' => '.pdf,.jpg,.jpeg,.png,.webp',
    'value' => null,
    'currentFileUrl' => null,
    'helperText' => 'Format file: PDF, JPG, PNG, atau WEBP (Maksimal 3MB)',
    'required' => false,
])

@php
    $inputId = $id ?? $name;
    $existingUrl = $currentFileUrl ?? ($value ? Storage::url($value) : null);
    $existingFileName = $value ? basename($value) : null;
@endphp

<div 
    x-data="{
        fileName: '',
        fileSize: '',
        filePreview: '',
        isDragging: false,
        existingFile: @js($value ? true : false),
        existingFileName: @js($existingFileName),
        existingUrl: @js($existingUrl),
        
        handleFileSelect(event) {
            const file = event.target.files ? event.target.files[0] : null;
            this.processFile(file);
        },

        handleDrop(event) {
            this.isDragging = false;
            const file = event.dataTransfer.files ? event.dataTransfer.files[0] : null;
            if (file) {
                const input = this.$refs.fileInput;
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                input.files = dataTransfer.files;
                this.processFile(file);
            }
        },

        processFile(file) {
            if (!file) return;
            this.fileName = file.name;
            const sizeInKb = Math.round(file.size / 1024);
            this.fileSize = sizeInKb > 1024 ? (sizeInKb / 1024).toFixed(1) + ' MB' : sizeInKb + ' KB';
            
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => { this.filePreview = e.target.result; };
                reader.readAsDataURL(file);
            } else {
                this.filePreview = '';
            }
        },

        clearFile() {
            this.fileName = '';
            this.fileSize = '';
            this.filePreview = '';
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        }
    }"
    class="form-group mb-4"
>
    @if($label)
    <label class="form-label font-bold text-xs uppercase tracking-wider text-[#0e1b35] mb-1.5 flex items-center justify-between" for="{{ $inputId }}">
        <span>
            {{ $label }}
            @if($required)<span style="color:var(--danger);">*</span>@endif
        </span>
        <span class="text-[11px] font-normal text-slate-500 lowercase">opsional lampiran</span>
    </label>
    @endif

    <div 
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop($event)"
        class="border-2 border-dashed rounded-lg p-3.5 transition-all text-center relative"
        :class="isDragging ? 'border-primary bg-blue-50/50' : 'border-slate-300 hover:border-slate-400 bg-slate-50/40'"
        style="min-height: 90px; display: flex; flex-direction: column; justify-content: center; align-items: center;"
    >
        <input 
            type="file" 
            name="{{ $name }}" 
            id="{{ $inputId }}" 
            x-ref="fileInput"
            accept="{{ $accept }}"
            @change="handleFileSelect($event)"
            @if($required && !$value) required @endif
            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
        />

        {{-- When a new file is chosen --}}
        <template x-if="fileName">
            <div class="flex items-center justify-between w-full px-2 py-1 bg-white border border-slate-200 rounded-md shadow-2xs z-20">
                <div class="flex items-center gap-2.5 overflow-hidden">
                    <template x-if="filePreview">
                        <img :src="filePreview" alt="Preview" class="w-9 h-9 object-cover rounded border border-slate-200" />
                    </template>
                    <template x-if="!filePreview">
                        <div class="w-9 h-9 bg-blue-50 text-blue-600 rounded flex items-center justify-center font-bold text-xs">
                            <i class="fa-solid fa-file-pdf text-base"></i>
                        </div>
                    </template>
                    <div class="text-left overflow-hidden">
                        <div class="text-xs font-semibold text-slate-800 truncate max-w-[220px]" x-text="fileName"></div>
                        <div class="text-[11px] text-slate-500" x-text="fileSize"></div>
                    </div>
                </div>
                <button 
                    type="button" 
                    @click.stop="clearFile()" 
                    class="text-slate-400 hover:text-red-500 p-1.5 rounded transition-colors"
                    title="Hapus file"
                >
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </template>

        {{-- Default Drop Area when no new file selected --}}
        <template x-if="!fileName">
            <div>
                <div class="flex items-center justify-center gap-2 text-slate-600 text-xs font-medium">
                    <i class="fa-solid fa-cloud-arrow-up text-primary text-base"></i>
                    <span>Pilih atau seret dokumen NPWP / NIK ke sini</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1">{{ $helperText }}</div>
            </div>
        </template>
    </div>

    {{-- Existing File Info if editing and no new file selected --}}
    <template x-if="existingFile && !fileName">
        <div class="mt-2 flex items-center justify-between px-3 py-2 bg-slate-100 border border-slate-200 rounded-md text-xs text-slate-700">
            <div class="flex items-center gap-2 truncate">
                <i class="fa-solid fa-file-lines text-slate-500"></i>
                <span class="truncate">Dokumen tersimpan: <strong x-text="existingFileName"></strong></span>
            </div>
            <a :href="existingUrl" target="_blank" class="text-primary hover:underline font-semibold flex items-center gap-1 shrink-0 ml-2">
                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> Lihat
            </a>
        </div>
    </template>

    @error($name)
        <div class="invalid-feedback" style="display:block; margin-top:4px; font-size:12px; color:#b91c1c;">{{ $message }}</div>
    @enderror
</div>
