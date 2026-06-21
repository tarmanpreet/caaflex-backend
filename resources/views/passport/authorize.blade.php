<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autorizza accesso</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f3f4f6; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.08); padding: 2.5rem; max-width: 460px; width: 100%; }
        .app-name { font-size: 1.4rem; font-weight: 700; color: #111; }
        .description { color: #6b7280; margin: .75rem 0 1.5rem; font-size: .95rem; line-height: 1.5; }
        .scopes { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; }
        .scopes h3 { font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; color: #9ca3af; margin-bottom: .6rem; }
        .scopes ul { list-style: none; }
        .scopes li { font-size: .9rem; color: #374151; padding: .25rem 0; }
        .scopes li::before { content: '✓ '; color: #10b981; font-weight: 700; }
        .actions { display: flex; gap: .75rem; }
        .btn { flex: 1; padding: .75rem; border-radius: 8px; border: none; font-size: .95rem; font-weight: 600; cursor: pointer; transition: opacity .15s; }
        .btn:hover { opacity: .85; }
        .btn-approve { background: #4f46e5; color: #fff; }
        .btn-deny { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }
    </style>
</head>
<body>
<div class="card">
    <div class="app-name">{{ $client->name }}</div>
    <p class="description">Questa applicazione richiede accesso al tuo account. Vuoi autorizzarla?</p>

    @if(count($scopes) > 0)
    <div class="scopes">
        <h3>Permessi richiesti</h3>
        <ul>
            @foreach($scopes as $scope)
            <li>{{ $scope->description }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="actions">
        {{-- Approve --}}
        <form method="POST" action="{{ route('passport.authorizations.approve') }}" style="flex:1">
            @csrf
            <input type="hidden" name="state" value="{{ $request->state }}">
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="btn btn-approve">Autorizza</button>
        </form>

        {{-- Deny --}}
        <form method="POST" action="{{ route('passport.authorizations.deny') }}" style="flex:1">
            @csrf
            @method('DELETE')
            <input type="hidden" name="state" value="{{ $request->state }}">
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="btn btn-deny">Nega</button>
        </form>
    </div>
</div>
</body>
</html>
