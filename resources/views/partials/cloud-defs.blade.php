{{-- Shared SVG cloud used by the dashboard and profile banners: <svg><use href="#cloud"/></svg> --}}
<svg width="0" height="0" class="absolute" aria-hidden="true">
    <defs>
        <filter id="cloud-edge" filterUnits="userSpaceOnUse" x="-10" y="-10" width="280" height="140"><feGaussianBlur stdDeviation="0.9"/></filter>
            <linearGradient id="cloud-fill" gradientUnits="userSpaceOnUse" x1="0" y1="20" x2="0" y2="116">
            <stop offset="0" stop-color="#ffffff"/>
            <stop offset="0.55" stop-color="#ffffff"/>
            <stop offset="1" stop-color="#cfe4fa"/>
        </linearGradient>
        <symbol id="cloud" viewBox="-10 -10 280 140">
            <g filter="url(#cloud-edge)" fill="url(#cloud-fill)">
                <circle cx="46" cy="84" r="26"/><circle cx="86" cy="62" r="34"/><circle cx="132" cy="48" r="40"/><circle cx="178" cy="62" r="34"/><circle cx="216" cy="84" r="26"/>
                <rect x="20" y="82" width="222" height="32" rx="16"/>
            </g>
            <g fill="#ffffff" opacity=".85"><circle cx="118" cy="36" r="16"/><circle cx="150" cy="40" r="11"/><circle cx="80" cy="54" r="10"/></g>
        </symbol>
    </defs>
</svg>
