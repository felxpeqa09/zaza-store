@props(['profile'])
<div class="zaza-profile-avatar">
    @if ($profile->avatar)
        <img src="{{ route('account.avatar') }}" alt="Foto de perfil" width="88" height="88">
    @else
        <i class="bi bi-person" aria-hidden="true"></i>
    @endif
</div>
