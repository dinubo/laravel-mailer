@component('mail::message')

{{ $body }}

@slot('subcopy')
Don't want to receive our emails anymore?<br />
<a href="%unsubscribe-link%">Unsubscribe here</a>
@endslot

@endcomponent
