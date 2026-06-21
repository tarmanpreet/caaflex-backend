Gentile {{ $deadline->assignee->name }},

ti ricordiamo che la scadenza "{{ $deadline->title }}" è imminente.

**Dettagli scadenza:**
- Titolo: {{ $deadline->title }}
- Data: {{ $deadline->deadline_at->format('d/m/Y H:i') }}
- Pratica: {{ $deadline->practice->title ?? 'N/A' }}

@if($deadline->notes)
**Note:**
{{ $deadline->notes }}
@endif

Cordiali saluti,
Il sistema di gestione pratiche.