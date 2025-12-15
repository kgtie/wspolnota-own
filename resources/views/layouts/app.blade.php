@props([
    'currentParish',
    'pageInfo',
])

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageInfo['meta.title'] }} | {{ $currentParish['name'] }} | Wspólnota</title>
    <meta name="description" content="{{ $pageInfo['meta.description'] }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />

    @vite(['resources/css/app.css', 'resources/css/md3.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body>

    <div class="container-fluid">
        <div class="row justify-content-center">

            <div class="col-lg-3 d-none d-lg-block desktop-sidebar">
                <h3 class="fw-bold mb-5 ps-3 text-primary-md text-center"></i>{{ $currentParish->name }}</h3>

                <div class="list-group list-group-flush">
                    <a href="{{ route('app.home', $currentParish->slug) }}"
                        class="list-group-item list-group-item-action bg-transparent border-0 {{ request()->routeIs('app.home') ? 'fw-bold text-primary-md' : 'text-secondary' }} fs-5 mb-3">
                        <span
                            class="material-symbols-outlined {{ request()->routeIs('app.home') ? 'filled' : '' }}">church</span><span>Moja
                            parafia</span></a>
                    <a href="{{ route('app.mass_calendar', $currentParish->slug) }}"
                        class="list-group-item list-group-item-action bg-transparent border-0 {{ request()->routeIs('app.mass_calendar') ? 'fw-bold text-primary-md' : 'text-secondary' }} fs-5 mb-3"><span
                            class="material-symbols-outlined {{ request()->routeIs('app.mass_calendar') ? 'filled' : '' }}">calendar_month</span><span>Kalendarz
                            intencji</span></a>
                    <a href="{{ route('app.announcements', $currentParish->slug) }}"
                        class="list-group-item list-group-item-action bg-transparent border-0 {{ request()->routeIs('app.announcements') ? 'fw-bold text-primary-md' : 'text-secondary' }} fs-5 mb-3"><span
                            class="material-symbols-outlined {{ request()->routeIs('app.announcements') ? 'filled' : '' }}">data_info_alert</span><span>Ogłoszenia
                            parafialne</span></a>
                    <a href="{{ route('app.office', $currentParish->slug) }}"
                        class="list-group-item list-group-item-action bg-transparent border-0 {{ request()->routeIs('app.office') ? 'fw-bold text-primary-md' : 'text-secondary' }} fs-5 mb-3"><span
                            class="material-symbols-outlined {{ request()->routeIs('app.office') ? 'filled' : '' }}">chat</span><span>Kancelaria
                            online</span></a>
                </div>
            </div>

            <div class="col-12 col-md-10 col-lg-6 py-4">

                <div class="d-flex justify-content-between align-items-center mb-4 px-2">
                    <div>
                        <small class="text-muted">Szczęść Boże!</small>                       

                       
                                               <h2 class="fw-bold m-0">{{ $pageInfo['page.title'] }}</h2>
                    </div>
                    @auth
                        <img src="https://ui-avatars.com/api/?background=random&name={{ Auth::user()->name }}" class="rounded-circle border" width="45" height="45" alt="Avatar" data-bs-toggle="offcanvas" href="#rightPanel" role="button" aria-controls="rightPanel" />
                    @endauth
                    @guest
                        <img src="https://ui-avatars.com/api/?background=random&name=NN" class="rounded-circle border" width="45" height="45" alt="Avatar" data-bs-toggle="offcanvas" href="#rightPanel" role="button" aria-controls="rightPanel" />
                    @endguest
                </div>

                {{ $slot }}
            </div>
        </div>
    </div>

    <nav class="mobile-bottom-nav d-lg-none shadow-lg">
        <a href="{{ route('app.home', $currentParish->slug) }}"
            class="nav-item {{ request()->routeIs('app.home') ? 'active' : '' }}">
            <div class="nav-icon-box"><span
                    class="material-symbols-outlined {{ request()->routeIs('app.home') ? 'filled' : '' }}">church</span>
            </div>
            <span>Moja parafia</span>
        </a>
        <a href="{{ route('app.mass_calendar', $currentParish->slug) }}"
            class="nav-item {{ request()->routeIs('app.mass_calendar') ? 'active' : '' }}">
            <div class="nav-icon-box"><span
                    class="material-symbols-outlined {{ request()->routeIs('app.mass_calendar') ? 'filled' : '' }}">calendar_month</span>
            </div>
            <span>Kalendarz Mszy św.</span>
        </a>
        <a href="{{ route('app.announcements', $currentParish->slug) }}"
            class="nav-item {{ request()->routeIs('app.announcements') ? 'active' : '' }}">
            <div class="nav-icon-box"><span
                    class="material-symbols-outlined {{ request()->routeIs('app.announcements') ? 'filled' : '' }}">data_info_alert</span>
            </div>
            <span>Ogłoszenia parafialne</span>
        </a>
        <a href="{{ route('app.office', $currentParish->slug) }}"
            class="nav-item {{ request()->routeIs('app.office') ? 'active' : '' }}">
            <div class="nav-icon-box"><span
                    class="material-symbols-outlined {{ request()->routeIs('app.office') ? 'filled' : '' }}">chat</span>
            </div>
      <span>Kancelaria online</span>
        </a>
    </nav>
      
    <div class="offcanvas offcanvas-end" tabindex="-1" id="rightPanel" aria-labelledby="rightPanelLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="rightPanelLabel">Offcanvas</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div>
                Some text as placeholder. In real life you can have the elements you have chosen. Like, text, images, lists, etc.
            </div>
            <div class="dropdown mt-3">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Dropdown button
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Action</a></li>
                    <li><a class="dropdown-item" href="#">Another action</a></li>
                    <li><a class="dropdown-item" href="#">Something else here</a></li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
</body>

</html>