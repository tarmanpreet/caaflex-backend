Gentile @if($recipientType === 'client'){{ $appointment->client->first_name }} {{ $appointment->client->last_name }}@else{{ $appointment->assignedUser?->name }}@endif,

il suo appuntamento del {{ $appointment->scheduled_at->format('d/m/Y') }} alle {{ $appointment->scheduled_at->format('H:i') }} è stato confermato.

@if($appointment->practiceType)Tipo pratica: {{ $appointment->practiceType->name }}@endif

Grazie.
