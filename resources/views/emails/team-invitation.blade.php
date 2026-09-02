@component('mail::message')
{{ __('Sei stato invitato a unirti al gruppo :team!', ['team' => $invitation->team->name]) }}

@if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::registration()))
{{ __('Se non hai un account, puoi crearne uno con il pulsante qui sotto. Dopo la creazione, usa il pulsante di accettazione presente in questa email:') }}

@component('mail::button', ['url' => route('register')])
{{ __('Crea account') }}
@endcomponent

{{ __('Se hai già un account, puoi accettare l’invito con il pulsante qui sotto:') }}

@else
{{ __('You may accept this invitation by clicking the button below:') }}
@endif


@component('mail::button', ['url' => $acceptUrl])
{{ __('Accetta invito') }}
@endcomponent

{{ __('Se non ti aspettavi questo invito, puoi ignorare l’email.') }}
@endcomponent
