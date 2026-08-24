<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>BAAO ASSESSORS OFFICE - TMDS</title>
    <link rel="icon" type="image/png" href="/images/sidelogo.png" />
    <link rel="apple-touch-icon" href="/images/sidelogo.png" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div id="app"></div>
</body>
</html>
