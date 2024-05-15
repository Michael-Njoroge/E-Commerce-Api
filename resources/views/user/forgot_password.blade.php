@component('mail::message')
Hello **{{ $user->firstname }} {{ $user->lastname }}**,

You are receiving this email because we received a forgot password request for your account.

Please follow this link to reset your password. The link is valid for the next 10 minutes: [Reset
Password]({{ $resetUrl }})

Thank you!<br>
Best regards,<br>
ICT Team
@endcomponent
