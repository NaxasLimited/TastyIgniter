@php
    $serviceType = $record->rops_pos_service_type
        ? str($record->rops_pos_service_type)->replace('_', ' ')->title()
        : null;
    $posStatus = $record->rops_pos_status
        ? str($record->rops_pos_status)->replace('_', ' ')->title()
        : null;
@endphp

@if ($record->rops_pos_id)
    <div class="small">
        <strong>POS #{{ $record->rops_pos_id }}</strong>
        <div class="text-muted">{{ collect([$serviceType, $posStatus])->filter()->join(' - ') }}</div>
        <div>{{ $record->rops_pos_table ?: 'No table' }}</div>
        <div class="text-muted">{{ $record->rops_pos_waiter ? 'Waiter: '.$record->rops_pos_waiter : 'No waiter' }}</div>
    </div>
@else
    <span class="text-muted">-</span>
@endif
