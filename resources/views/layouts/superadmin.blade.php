@props([
    'pageInfo',
    'parishes',
])

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageInfo['page.title'] }} | Wspólnota | Panel superadministratora</title>
    <meta name="description" content="{{ $pageInfo['meta.description'] }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/css/adminlte.min.css">
    <script src="https://kit.fontawesome.com/33bf9a820a.js" crossorigin="anonymous"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">

        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"><i
                                class="fa-solid fa-bars"></i></a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">

                                                   <li class="nav-item dropdown user-menu">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="https://ui-avatars.com/api/?background=random&name={{ Auth::user()->name }}" class="user-image rounded-circle shadow" alt="User Image">
                            <span class="d-none d-md-inline">{{ Auth::user()->name }}</span>
                        </a>

                                                           <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                            <li class="user-header text-bg-primary">
                                <img src="./assets/img/user2-160x160.jpg" class="rounded-circle shadow" alt="User Image">
                                <p>
                                    {{ Auth::user()->name }}
                                    <small>Superadministrator</small>
                                 </p>
                            </li>
                            <li class="user-body">
                               <div class="row">
                                    <div class="col-4 text-center">
                                        <a href="#">Link</a>
                                    </div>
                                    <div class="col-4 text-center">
                                        <a href="#">Link</a>
                                    </div>
                                    <div class="col-4 text-center">
                                        <a href="#">Link</a>
                                    </div>
                                </div>
                            </li>
                            <li class="user-footer">
                                <a href="#" class="btn btn-default btn-flat">Profil</a>
                                <a href="{{ route('logout') }}" class="btn btn-default btn-flat float-end">Wyloguj</a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
        <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">

                       
                                   <div class="sidebar-brand">
                <a href="{{ route('superadmin.dashboard') }}" class="brand-link"><span class="brand-text fw-light">Zarządzanie usługą</span></a>
            </div>
            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu"
                        data-accordion="false">
                        <li class="nav-item">
                            <a href="{{ route('superadmin.dashboard') }}" class="nav-link">
                                <i class="nav-icon fa-solid fa-gauge-high"></i>
                                <p>Kokpit</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('superadmin.users.index') }}" class="nav-link">
                                <i class="nav-icon fa-solid fa-users"></i>
                                <p>Użytkownicy</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('superadmin.masses.index') }}" class="nav-link">
                                <i class="nav-icon fa-solid fa-person-praying"></i>
                                <p>Msze święte</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="mb-0">{{ $pageInfo['page.title'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="app-content">
                <div class="container-fluid">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/js/adminlte.min.js"></script>
    @livewireScripts
</body>

</html>