@component('mail::message')
{{ __('A new user, :user has been registered at voting-admin.votes365.org', ['user' => $userName]) }}

<p>Name: {{ $userName }}</p>
<p>Email: {{ $userEmail }}</p>

@component('mail::button', ['url' => 'https://www.votes365.org'])
{{ __('Go to votes365.org') }}
@endcomponent

@endcomponent
