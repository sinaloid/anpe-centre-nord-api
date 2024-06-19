@component('mail::message')
# Vérification à deux facteurs

Bonjour,

Votre code de vérification à deux facteurs est : **{{ $two_factor_code }}**

Ce code expirera dans 10 minutes.

Si vous n'avez pas demandé ce code, veuillez ignorer cet email.

Merci,<br>
{{ config('app.name') }}
@endcomponent
