@props([
  'href' => '#',
  'variant' => 'secondary', // primary | secondary | ghost
])

@php
$base = 'inline-flex items-center justify-center h-10 px-4 rounded-md
          font-semibold text-xs uppercase tracking-widest transition
          focus:outline-none focus:ring-2 focus:ring-indigo-500
          focus:ring-offset-2 dark:focus:ring-offset-gray-800';

$variants = [
  'primary'   => 'bg-indigo-600 text-white hover:bg-indigo-700',
  'secondary' => 'border border-gray-300 dark:border-gray-500
                  bg-gray-100 text-gray-800 hover:bg-gray-200
                  dark:bg-gray-600 dark:text-gray-100 dark:hover:bg-gray-500',
  'ghost'     => 'bg-transparent text-gray-300 hover:text-white',
];

$classes = $base.' '.($variants[$variant] ?? $variants['secondary']);
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
  {{ $slot }}
</a>
