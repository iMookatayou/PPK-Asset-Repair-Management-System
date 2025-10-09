<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-10 sm:pt-0 bg-gray-100 dark:bg-gray-900">
            <div class="mb-4">
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div
                class="w-full sm:max-w-md mt-2 px-6 py-6 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg
                       border border-transparent dark:border-gray-700/40"
            >
                {{-- เนื้อหาแต่ละหน้า (ฟอร์ม ฯลฯ) --}}
                <div class="space-y-4">
                    {{ $slot }}
                </div>

                {{-- แอ็กชันท้ายฟอร์ม (ปุ่มต่าง ๆ) --}}
                @isset($actions)
                    <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2">
                        {{ $actions }}
                    </div>
                @endisset
            </div>
        </div>
    </body>
</html>
