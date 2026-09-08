@if ($record->rops_pos_id)
    <div class="small">
        <div>{{ $record->rops_pos_cashier ? 'Cashier: '.$record->rops_pos_cashier : 'No cashier' }}</div>
        <div class="text-muted">{{ $record->rops_pos_shift ? 'Shift #'.$record->rops_pos_shift : 'No shift' }}</div>

        @if ($record->rops_pos_receipt)
            <div><strong>{{ $record->rops_pos_receipt }}</strong></div>
            <div class="text-muted">{{ $record->rops_pos_tender ?: 'Tender recorded' }}</div>
        @else
            <span class="text-muted">Unpaid / no receipt</span>
        @endif
    </div>
@else
    <span class="text-muted">-</span>
@endif
