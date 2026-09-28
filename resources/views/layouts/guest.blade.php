<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#07080c">
    <title>@yield('title', 'Acesso seguro') · {{ $brand['name'] ?? 'Exclusive Locker Master' }}</title>
    @if(!empty($brand['favicon']))
        <link rel="icon" href="{{ $brand['favicon'] }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/master.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <style>:root { --gold: {{ $brand['primary'] ?? '#E2A84A' }}; }</style>
</head>
<body class="auth-body">
    @yield('content')
    <script src="{{ asset('js/auth.js') }}"></script>
</body>
</html>
