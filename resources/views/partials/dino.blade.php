{{-- The CJ Hub mascot. Inherits the text colour; the eye uses the brand blue. --}}
<svg {{ $attributes ?? '' }} class="{{ $class ?? 'h-10 w-10' }}" viewBox="0 10 62 51" fill="currentColor" aria-hidden="true">
    {{-- tail --}}
    <path d="M17 34C9 36 3 32 1 24c6 4 11 3 17 2z"/>
    {{-- back spikes --}}
    <path d="M19 27l3-8 5 6zM28 24l4-9 5 8zM37 24l4-7 4 8z"/>
    {{-- body + neck + head --}}
    <ellipse cx="30" cy="38" rx="17" ry="14"/>
    <circle cx="44" cy="24" r="11"/>
    <rect x="44" y="19" width="16" height="11" rx="5.5"/>
    <path d="M36 30c2 4 4 8 4 12H26z"/>
    {{-- legs --}}
    <rect x="21" y="47" width="8" height="12" rx="3.5"/>
    <rect x="34" y="47" width="8" height="12" rx="3.5"/>
    {{-- face --}}
    <circle cx="46" cy="22" r="2.4" fill="#0ea5e9"/>
    <circle cx="46.8" cy="21.2" r=".8" fill="#fff"/>
    <path d="M50 27.5c2 1.4 4.6 1.4 6.2 0" fill="none" stroke="#0ea5e9" stroke-width="1.6" stroke-linecap="round"/>
    <circle cx="57" cy="22" r=".9" fill="#0ea5e9"/>
</svg>
