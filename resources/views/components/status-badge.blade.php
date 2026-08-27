@props(['status' => 'draft', 'label' => null])

@php
    $s = strtolower(trim((string) $status));
    
    // Mapping warna & kelas Tailwind per status enum
    $badgeClasses = match($s) {
        // Success (Hijau)
        'done', 'completed', 'paid', 'approved', 'passed', 'baik', 'posted', 'in', 'pkp' => 
            'bg-status-success-bg text-status-success-text border border-status-success-text/20',
        
        // Active / Sent / Confirmed (Biru)
        'confirmed', 'sent', 'received', 'out' => 
            'bg-status-active-bg text-status-active-text border border-status-active-text/20',
        
        // Pending / Warning (Oranye / Amber)
        'waiting_approval', 'pending', 'partially_received', 'partially_delivered', 'partial', 'in_progress', 'sold_as_reject', 'return_out' => 
            'bg-status-pending-bg text-status-pending-text border border-status-pending-text/30',
        
        // Danger / Cancelled / Rejected / Damage (Merah / Rose)
        'cancelled', 'rejected', 'failed', 'unpaid', 'rusak', 'write_off', 'return_in_damaged', 'reject_out' => 
            'bg-status-danger-bg text-status-danger-text border border-status-danger-text/20',
        
        // Info / In Transit / Transfer (Indigo / Cyan)
        'in_transit', 'transfer_in', 'transfer_out', 'return_in' => 
            'bg-primary-fixed-dim text-on-primary-fixed-variant border border-primary-fixed/40',
        
        // Adjustment / Special (Purple / Slate)
        'adjustment' => 
            'bg-purple-100 text-purple-800 border border-purple-200',

        // Neutral / Non-PKP
        'non_pkp' => 
            'bg-status-neutral-bg text-status-neutral-text border border-border-medium',

        // Fallback Default Neutral (Gray)
        default => 
            'bg-status-neutral-bg text-status-neutral-text border border-border-medium'
    };

    // Formatted label jika tidak di-override
    $displayLabel = $label ?? match($s) {
        'waiting_approval' => 'WAITING APPROVAL',
        'partially_received' => 'PARTIAL RECV',
        'partially_delivered' => 'PARTIAL DELIV',
        'return_in_damaged' => 'RETUR KARANTINA',
        'sold_as_reject' => 'SOLD REJECT',
        'in_transit' => 'IN TRANSIT',
        'in_progress' => 'IN PROGRESS',
        'write_off' => 'WRITE OFF',
        'pkp' => 'PKP',
        'non_pkp' => 'NON-PKP',
        default => strtoupper(str_replace('_', ' ', $s))
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase whitespace-nowrap ' . $badgeClasses]) }}>
    {{ $displayLabel }}
</span>
