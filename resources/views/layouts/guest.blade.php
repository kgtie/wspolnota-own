<!DOCTYPE html>
@php
    $guestTitle = trim($__env->yieldContent('title', config('app.name', 'Wspólnota').' | Dostęp do panelu'));
    $guestDescription = trim($__env->yieldContent('meta_description', 'Logowanie i odzyskiwanie dostępu do panelu Wspólnota.'));
    $guestCanonical = trim($__env->yieldContent('canonical', url()->current()));
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $guestDescription }}">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <link rel="canonical" href="{{ $guestCanonical }}">
    <title>{{ $guestTitle }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/css/md3.css', 'resources/js/app.js'])
</head>

<body class="d-flex align-items-center min-vh-100 justify-content-center py-4">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5 col-xl-4">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-primary display-6">⛪ Wspólnota</h2>
                </div>

                <div class="card md-card p-4 p-md-5">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
