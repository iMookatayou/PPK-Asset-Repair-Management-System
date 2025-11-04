@props(['name','class' => 'w-5 h-5'])

@php
  $custom = [
    'bar-chart-3' => '<path d="M3 3v18h18"/><path d="M13 13h4v6h-4z"/><path d="M7 9h4v10H7z"/><path d="M17 3v4"/>',
    'wrench'      => '<path d="M15.5 5.5a5 5 0 0 0-7.07 7.07L3 18v3h3l5.43-5.43a5 5 0 0 0 7.07-7.07l-2 2-3-3 2-2z"/>',
    'briefcase'   => '<path d="M3 7h18v13H3z"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M3 13h18"/>',
    'users'       => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 1 1 0 7.75"/>',
    'user'        => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'hospital'    => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><path d="M12 7v6"/><path d="M9 10h6"/><path d="M8 21v-6h8v6"/>',

    'inbox' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 12h6l2 3h4l2-3h6"/>',

'clipboard-list' => '<rect x="6" y="6" width="12" height="14" rx="2"/><path d="M9 6h6v4H9z"/><path d="M9 13h6"/><path d="M9 17h6"/>',


    'hard-hat' => '
      <path d="M2 18h20"/>
      <path d="M10 18v-2a2 2 0 0 1 2-2 2 2 0 0 1 2 2v2"/>
      <path d="M4 18v-4a8 8 0 1 1 16 0v4"/>
      <path d="M10 10V5a2 2 0 1 1 4 0v5"/>
    ',

    'hammer' => '
      <path d="M15 12l-8.6-8.6a2 2 0 0 1 2.8-2.8L17 9l-2 2 6 6-2 2-6-6-2 2z"/>
      <path d="M2 22h6"/>
    ',
  ];

  $paths = $custom[$name] ?? null;
@endphp

@if ($paths)
  <svg {{ $attributes->merge(['class' => $class]) }}
       xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
       fill="none" stroke="currentColor" stroke-width="2"
       stroke-linecap="round" stroke-linejoin="round">
    {!! $paths !!}
  </svg>
@else
  <svg {{ $attributes->merge(['class' => $class]) }} xmlns="http://www.w3.org/2000/svg"
       viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
       stroke-linecap="round" stroke-linejoin="round">
    <rect x="4" y="4" width="16" height="16" rx="2"/>
  </svg>
@endif
