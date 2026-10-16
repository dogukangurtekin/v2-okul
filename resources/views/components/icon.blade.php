@props(['name'])
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes }}>
@switch($name)
    @case('home') <path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/> @break
    @case('building') <path d="M3 21h18"/><path d="M5 21V9l7-5 7 5v12"/><path d="M9 21v-5h6v5"/><path d="M10 11h4"/> @break
    @case('cap') <path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11.5V16c0 1.5 3 3 6 3s6-1.5 6-3v-4.5"/> @break
    @case('users') <circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="9" r="2.5"/><path d="M17 14.2c2.4.3 4 2.2 4 4.8"/> @break
    @case('upload') <path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 20h16"/> @break
    @case('idcard') <rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M6.5 16c.5-1.5 1.5-2 2.5-2s2 .5 2.5 2M14 10h4M14 14h3"/> @break
    @case('book') <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16"/><path d="M9 8h7"/> @break
    @case('clipboard') <rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4h6v3H9z"/><path d="M8.5 12h7M8.5 16h5"/> @break
    @case('gamepad') <rect x="2.5" y="7" width="19" height="11" rx="5"/><path d="M7 10.5v4M5 12.5h4"/><circle cx="15.5" cy="11.5" r="1"/><circle cx="18" cy="14" r="1"/> @break
    @case('help') <circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.7.4-1 1-1 1.7"/><circle cx="12" cy="17" r=".6"/> @break
    @case('bolt') <path d="M13 2 4 14h7l-1 8 9-12h-7z"/> @break
    @case('monitor') <rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 21h8M12 16v5"/> @break
    @case('chart') <path d="M4 20V4"/><path d="M4 20h16"/><rect x="7" y="11" width="3" height="6"/><rect x="12" y="7" width="3" height="10"/><rect x="17" y="13" width="2" height="4"/> @break
    @case('calendar') <rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M8 3v4M16 3v4M3.5 10h17"/><path d="m9 15 2 2 4-4"/> @break
    @case('star') <path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/> @break
    @case('bell') <path d="M6 16V11a6 6 0 1 1 12 0v5l1.5 2h-15z"/><path d="M10 21h4"/> @break
    @case('megaphone') <path d="M3 11v3l12 5V6z"/><path d="M15 9.5a3 3 0 0 1 0 5"/><path d="M6 15v4h3"/> @break
    @case('message') <path d="M4 5h16v11H9l-5 4z"/> @break
    @case('lifebuoy') <circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3.5"/><path d="m5.6 5.6 3.9 3.9M14.5 14.5l3.9 3.9M18.4 5.6l-3.9 3.9M9.5 14.5l-3.9 3.9"/> @break
    @case('cog') <circle cx="12" cy="12" r="3"/><path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.3 5.3l2.1 2.1M16.6 16.6l2.1 2.1M18.7 5.3l-2.1 2.1M7.4 16.6l-2.1 2.1"/> @break
    @case('logout') <path d="M9 4H5v16h4"/><path d="M15 8l4 4-4 4M19 12H9"/> @break
    @case('menu') <path d="M4 6h16M4 12h16M4 18h16"/> @break
    @case('plus') <path d="M12 5v14M5 12h14"/> @break
    @case('trophy') <path d="M8 4h8v5a4 4 0 0 1-8 0z"/><path d="M8 6H4v1a3 3 0 0 0 3 3M16 6h4v1a3 3 0 0 1-3 3"/><path d="M12 13v4M8.5 21h7M9.5 17h5"/> @break
    @case('medal') <circle cx="12" cy="9" r="5"/><path d="m8.5 13-2 8 5.5-3 5.5 3-2-8"/> @break
    @case('check') <path d="m5 12.5 4.5 4.5L19 7.5"/> @break
    @case('arrow') <path d="M5 12h14M13 6l6 6-6 6"/> @break
    @case('alert') <path d="M12 3 2 20h20z"/><path d="M12 10v4"/><circle cx="12" cy="17" r=".6"/> @break
    @default <circle cx="12" cy="12" r="8"/>
@endswitch
</svg>
