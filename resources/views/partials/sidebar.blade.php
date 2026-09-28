@php
    $user = auth()->user();
    $meta = $sidebar['meta'];
    $lastSync = $meta['lastSync'] ? \Illuminate\Support\Carbon::parse($meta['lastSync'])->diffForHumans() : 'nunca';
    $syncOk = $meta['lastSync'] && \Illuminate\Support\Carbon::parse($meta['lastSync'])->gt(now()->subMinutes(15));
    $initials = collect(explode(' ', (string) $user->name))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp
<aside class="sidebar" id="sidebar" aria-label="Navegação principal">
    <div class="sidebar__head">
        <a class="brand" href="{{ route('dashboard') }}">
            @if(!empty($brand['logo']))
                <img class="brand-logo" src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }}">
            @else
                <div class="brand-mark">{{ $brand['mark'] ?? 'EL' }}</div>
            @endif
            <div class="brand-copy">
                <b>{{ $brand['name'] ?? 'EXCLUSIVE' }}</b>
                <span>{{ $brand['subtitle'] ?? 'Locker Master' }}</span>
            </div>
        </a>
        <button class="sidebar__iconbtn" type="button" data-sidebar-collapse title="Recolher menu (Ctrl+B)" aria-label="Recolher menu">
            {!! \App\Support\NavIcons::svg('collapse') !!}
        </button>
    </div>

    <div class="sidebar__search">
        <span class="sidebar__search-ico">{!! \App\Support\NavIcons::svg('search') !!}</span>
        <input id="sidebar-filter" type="search" placeholder="Buscar no menu" autocomplete="off" aria-label="Buscar no menu">
        <kbd>Ctrl K</kbd>
    </div>

    <div class="sidebar__quick" aria-label="Atalhos">
        <a href="{{ route('integracao.index') }}" data-tip="Sincronizar agora">{!! \App\Support\NavIcons::svg('sync') !!}<span>Sync</span></a>
        <a href="{{ route('condominios.create') }}" data-tip="Novo condomínio">{!! \App\Support\NavIcons::svg('plus') !!}<span>Novo</span></a>
        <a href="{{ route('relatorios.index') }}" data-tip="Relatórios">{!! \App\Support\NavIcons::svg('report') !!}<span>Exportar</span></a>
        <a href="{{ route('alertas.index') }}" data-tip="Alertas abertos">{!! \App\Support\NavIcons::svg('bolt') !!}<span>Alertas</span></a>
    </div>

    <nav class="nav" id="sidebar-nav">
        @foreach($sidebar['groups'] as $group)
            <section class="nav-group" data-group="{{ $group['id'] }}">
                <button class="nav-label" type="button" data-group-toggle>
                    <span>{{ $group['label'] }}</span>
                    <i class="nav-chevron"></i>
                </button>
                <div class="nav-items">
                    @foreach($group['items'] as $item)
                        @php
                            $active = request()->routeIs($item['match']);
                            $badge = (int) ($item['badge'] ?? 0);
                        @endphp
                        <a
                            class="nav-link {{ $active ? 'is-active' : '' }}"
                            href="{{ route($item['route']) }}"
                            data-nav-item
                            data-label="{{ $item['label'] }} {{ $item['hint'] }}"
                            title="{{ $item['label'] }} — {{ $item['hint'] }}"
                            data-tip="{{ $item['label'] }}"
                            @if($active) aria-current="page" @endif
                        >
                            <span class="nav-link__ico">{!! \App\Support\NavIcons::svg($item['icon']) !!}</span>
                            <span class="nav-link__txt">
                                <strong>{{ $item['label'] }}</strong>
                                <small>{{ $item['hint'] }}</small>
                            </span>
                            @if($badge > 0)
                                <span class="nav-badge nav-badge--{{ $item['tone'] ?? 'warn' }}">{{ $badge > 99 ? '99+' : $badge }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
        <p class="nav-empty" hidden>Nenhum módulo encontrado.</p>
    </nav>

    <div class="sidebar__foot">
        <div class="sync-pill {{ $syncOk ? 'is-ok' : 'is-stale' }}" data-tip="Última sincronização">
            {!! \App\Support\NavIcons::svg('clock') !!}
            <div>
                <b>{{ $syncOk ? 'API estável' : 'Sync desatualizado' }}</b>
                <span>{{ $lastSync }}</span>
            </div>
        </div>
        <div class="user-card">
            <div class="user-card__avatar">{{ $initials ?: 'EL' }}</div>
            <div class="user-card__meta">
                <strong>{{ $user->name }}</strong>
                <span>{{ strtoupper($user->role) }}</span>
            </div>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="sidebar__iconbtn" type="submit" title="Encerrar sessão" aria-label="Sair">
                    {!! \App\Support\NavIcons::svg('logout') !!}
                </button>
            </form>
        </div>
    </div>
</aside>
<div class="sidebar-overlay" data-sidebar-close hidden></div>
