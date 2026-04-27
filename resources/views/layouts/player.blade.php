<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ config('app.name') }} — Music Player</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    @livewireStyles
    @vite(['resources/js/app.js'])

    <style>
        :root {
            --sp-black: #000000;
            --sp-dark: #121212;
            --sp-card: #181818;
            --sp-hover: #282828;
            --sp-green: #1DB954;
            --sp-green-hover: #1ed760;
            --sp-text: #FFFFFF;
            --sp-muted: #B3B3B3;
            --sp-sidebar-w: 240px;
            --sp-player-h: 90px;
        }

        * { box-sizing: border-box; }

        body {
            background: var(--sp-dark);
            color: var(--sp-text);
            font-family: 'Instrument Sans', sans-serif;
            margin: 0;
            overflow: hidden;
            height: 100vh;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.5); }

        /* Range input */
        input[type=range] {
            -webkit-appearance: none;
            appearance: none;
            height: 4px;
            border-radius: 2px;
            background: rgba(255,255,255,0.3);
            outline: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        input[type=range]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 12px; height: 12px;
            border-radius: 50%;
            background: #fff;
            opacity: 0;
            transition: opacity 0.2s;
        }
        input[type=range]:hover { background: rgba(255,255,255,0.5); }
        input[type=range]:hover::-webkit-slider-thumb { opacity: 1; }
        input[type=range].progress-bar-input { background: linear-gradient(to right, var(--sp-green) 0%, var(--sp-green) var(--progress, 0%), rgba(255,255,255,0.3) var(--progress, 0%)); }
        input[type=range].volume-input { background: linear-gradient(to right, #fff 0%, #fff var(--vol, 80%), rgba(255,255,255,0.3) var(--vol, 80%)); }
    </style>
</head>
<body>
    {{ $slot }}

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
</body>
</html>
