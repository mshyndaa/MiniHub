{{-- Usage: @include('partials.avatar', ['user' => $u, 'class' => 'h-10 w-10 rounded-xl text-base']) --}}
@if ($user->avatarUrl())
    <img src="{{ $user->avatarUrl() }}" alt="" class="{{ $class }} shrink-0 object-cover shadow">
@else
    <span class="{{ $class }} grid shrink-0 place-items-center bg-gradient-to-br from-sky-400 via-pink-400 to-amber-300 font-extrabold text-white shadow">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
@endif
