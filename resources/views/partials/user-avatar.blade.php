@php
    $avatarUser ??= $user ?? null;
    $avatarClass = trim('avatar '.($class ?? ''));
    $avatarInitial = $avatarUser ? strtoupper(mb_substr($avatarUser->name, 0, 1)) : '';
@endphp

<span class="{{ $avatarClass }}">
    @if ($avatarUser?->profile_photo_url)
        <img src="{{ $avatarUser->profile_photo_url }}" alt="{{ $avatarUser->name }}">
    @else
        {{ $avatarInitial }}
    @endif
</span>
