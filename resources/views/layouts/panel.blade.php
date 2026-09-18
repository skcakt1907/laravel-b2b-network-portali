<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Panel') &middot; DN Unity</title>

    <meta name="robots" content="noindex, nofollow">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
    @stack('styles')
</head>
<body>

<div class="panel-wrap">

    <aside class="sidebar" id="sidebar">
        <a href="{{ url('/panel') }}" class="brand">DN <span>Unity</span></a>

        <nav class="flex-grow-1">
            <div class="sidebar-section">Menu</div>
            @include('partials.sidebar-link', ['href' => route('panel'),       'icon' => 'grid-1x2',       'label' => 'Panel'])
            @include('partials.sidebar-link', ['href' => route('etkinlikler'), 'icon' => 'calendar-event', 'label' => 'Etkinlikler'])

            {{-- Misafirin uye dizini ve cuzdani yoktur; rotalar da 'uye' middleware'i ile kapali --}}
            @can('viewAny', App\Models\User::class)
                @include('partials.sidebar-link', ['href' => route('uyeler'), 'icon' => 'people',  'label' => 'Uye Dizini'])
                @include('partials.sidebar-link', ['href' => route('cuzdan'), 'icon' => 'wallet2', 'label' => 'Unitycoin'])
            @endcan

            @include('partials.sidebar-link', ['href' => route('profilim'), 'icon' => 'person-badge', 'label' => 'Profilim'])

            @if(auth()->check() && auth()->user()->isAdmin())
                <div class="sidebar-section">Yonetim</div>
                @include('partials.sidebar-link', ['href' => route('yonetim.basvurular'),  'icon' => 'inbox',        'label' => 'Basvurular'])
                @include('partials.sidebar-link', ['href' => route('yonetim.uyeler'),      'icon' => 'person-gear',  'label' => 'Uye Yonetimi'])
                @include('partials.sidebar-link', ['href' => route('yonetim.etkinlikler'), 'icon' => 'calendar-plus','label' => 'Etkinlik Yonetimi'])
                @include('partials.sidebar-link', ['href' => route('yonetim.coin'),        'icon' => 'coin',         'label' => 'Coin Yonetimi'])
            @endif
        </nav>
    </aside>

    <div class="sidebar-backdrop d-none" id="sidebarBackdrop"></div>

    <div class="panel-main">

        <header class="topbar">
            <button class="btn btn-ghost btn-sm sidebar-toggle" id="sidebarToggle" type="button" aria-label="Menu">
                <i class="bi bi-list"></i>
            </button>

            <div class="ms-auto d-flex align-items-center gap-3">
                @auth
                    {{-- Misafirin Unitycoin cuzdani yoktur --}}
                    @unless(auth()->user()->isMisafir())
                        <span class="badge-ui badge-gold">
                            <i class="bi bi-coin"></i> {{ number_format(auth()->user()->coinBalance(), 0, ',', '.') }}
                        </span>
                    @endunless
                    <div class="dropdown">
                        <button class="btn btn-ghost btn-sm dropdown-toggle" data-bs-toggle="dropdown" type="button">
                            {{ auth()->user()->name }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ url('/profilim') }}">Profilim</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ url('/cikis') }}">
                                    @csrf
                                    <button class="dropdown-item" type="submit">Cikis yap</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endauth
            </div>
        </header>

        <main class="panel-body">
            <h1 class="page-title">@yield('title', 'Panel')</h1>
            @hasSection('subtitle')
                <p class="page-sub">@yield('subtitle')</p>
            @endif

            @include('partials.flash')

            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Mobilde yan menuyu ac/kapa
    (function () {
        const sb = document.getElementById('sidebar');
        const bd = document.getElementById('sidebarBackdrop');
        const tg = document.getElementById('sidebarToggle');
        if (!sb || !bd || !tg) return;

        const kapat = () => { sb.classList.remove('open'); bd.classList.add('d-none'); };
        tg.addEventListener('click', () => {
            sb.classList.toggle('open');
            bd.classList.toggle('d-none', !sb.classList.contains('open'));
        });
        bd.addEventListener('click', kapat);
    })();
</script>
@stack('scripts')
</body>
</html>
