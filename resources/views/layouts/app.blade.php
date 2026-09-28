<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#07080c">
    <title>@yield('title', 'Master') · {{ $brand['name'] ?? 'Exclusive Locker' }}</title>
    @if(!empty($brand['favicon']))
        <link rel="icon" href="{{ $brand['favicon'] }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,500;9..144,700&family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/master.css') }}">
    <style>:root { --gold: {{ $brand['primary'] ?? '#E2A84A' }}; }</style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        try {
            if (localStorage.getItem('el.sidebar.collapsed') === '1' && window.innerWidth > 1100) {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    </script>
</head>
<body>
<div class="app">
    @include('partials.sidebar')
    <section class="main">
        <div class="topbar">
            <button class="sidebar__iconbtn topbar__menu" type="button" data-sidebar-open aria-label="Abrir menu">
                {!! \App\Support\NavIcons::svg('menu') !!}
            </button>
            <form class="search" method="get" action="{{ request()->routeIs('historico.*') ? route('historico.index') : route('correspondencias.index') }}">
                <span>{!! \App\Support\NavIcons::svg('search') !!}</span>
                <input name="q" value="{{ request('q') }}" placeholder="Buscar correspondência, morador, unidade, senha...">
            </form>
            <a class="topbar__alert" href="{{ route('alertas.index') }}" title="Central de alertas">
                {!! \App\Support\NavIcons::svg('bell') !!}
                @if(($sidebar['meta']['alertas'] ?? 0) > 0)
                    <i>{{ $sidebar['meta']['alertas'] > 9 ? '9+' : $sidebar['meta']['alertas'] }}</i>
                @endif
            </a>
            <div class="userchip">
                <div class="avatar"></div>
                <div>
                    <strong>{{ auth()->user()->name }}</strong><br>
                    <span class="muted" style="font-size:12px">{{ strtoupper(auth()->user()->role) }}</span>
                </div>
                <form method="post" action="{{ route('logout') }}">@csrf
                    <button class="btn ghost" type="submit">Sair</button>
                </form>
            </div>
        </div>
        @if(session('ok')) <div class="flash">{{ session('ok') }}</div> @endif
        @if($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif
        @yield('content')
    </section>
</div>
<script src="{{ asset('js/sidebar.js') }}"></script>
@yield('scripts')
</body>
</html>
