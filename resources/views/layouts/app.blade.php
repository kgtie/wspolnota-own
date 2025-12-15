@props(['currentParish'])

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Wspólnota') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    @vite(['resources/css/app.css', 'resources/css/md3.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body>

    <div class="container-fluid">
        <div class="row justify-content-center">

            <div class="col-lg-3 d-none d-lg-block desktop-sidebar">
                <h3 class="fw-bold mb-5 ps-3 text-primary-md"></i>{{ $currentParish->name }}</h3>

                <div class="list-group list-group-flush">
                    <a href="{{ route('app.home', $currentParish->slug) }}"
                        class="list-group-item list-group-item-action bg-transparent border-0 {{ request()->routeIs('app.home') ? 'fw-bold text-primary-md' : 'text-secondary' }} fs-5 mb-3"><i
                            class="bi bi-house-door-fill me-3"></i>Moja parafia</a>
                    <a href="#"
                        class="list-group-item list-group-item-action bg-transparent border-0 text-secondary fs-5 mb-3"><i
                            class="bi bi-calendar3 me-3"></i>Kalendarz</a>
                    <a href="#"
                        class="list-group-item list-group-item-action bg-transparent border-0 text-secondary fs-5 mb-3"><i
                            class="bi bi-people me-3"></i>Wspólnota</a>
                    <a href="#"
                        class="list-group-item list-group-item-action bg-transparent border-0 text-secondary fs-5 mb-3"><i
                            class="bi bi-gear me-3"></i>Ustawienia</a>
                </div>
            </div>

            <div class="col-12 col-md-10 col-lg-6 py-4">
                {{ $slot }}
            </div>
        </div>
    </div>

    <nav class="mobile-bottom-nav d-lg-none shadow-lg">
        <a href="{{ route('app.home', $currentParish->slug) }}"
            class="nav-item {{ request()->routeIs('app.home') ? 'active' : '' }}">
            <div class="nav-icon-box"><i class="bi bi-house-door-fill"></i></div>
            <span>Start</span>
        </a>
        <a href="#" class="nav-item">
            <div class="nav-icon-box"><i class="bi bi-calendar3"></i></div>
            <span>Plan</span>
        </a>
        <a href="#" class="nav-item">
            <div class="nav-icon-box"><i class="bi bi-chat-quote"></i></div>
            <span>Ogłoszenia</span>
        </a>
        <a href="#" class="nav-item">
            <div class="nav-icon-box"><i class="bi bi-person"></i></div>
            <span>Profil</span>
        </a>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
</body>

</html>