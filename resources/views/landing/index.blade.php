<!DOCTYPE html>
<html lang="pl" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description"
        content="Wspólnota to nowoczesna platforma do zarządzania parafią w chmurze. Intuicyjna aplikacja dla proboszcza i parafian. Dołącz do listy oczekujących już dziś!">
    <meta name="keywords" content="Wspólnota, aplikacja, logowanie, zarządzanie">
    <meta name="author" content="Konrad Gruza">
    <meta name="theme-color" content="#f0f0f0ff" />

    <title>⛪ Wspólnota - Platforma do zarządzania parafią</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- CUSTOM CSS --- */
        body {
            font-family: 'Poppins', sans-serif;
            transition: background 0.5s ease;
        }

        /* Gradienty tła */
        [data-bs-theme="light"] body {
            background: linear-gradient(120deg, #e0c3fc 0%, #8ec5fc 100%);
            background-attachment: fixed;
        }

        [data-bs-theme="dark"] body {
            background: linear-gradient(120deg, #2b1055 0%, #1e3c72 100%);
            background-attachment: fixed;
        }

        /* Glassmorphism - Klasa Bazowa */
        .glass-card {
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        [data-bs-theme="light"] .glass-card {
            background: rgba(255, 255, 255, 0.6);
        }

        [data-bs-theme="dark"] .glass-card {
            background: rgba(33, 37, 41, 0.6);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(79, 70, 229, 0.2);
        }

        /* Nawigacja */
        .navbar-glass {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        [data-bs-theme="dark"] .navbar-glass {
            background: rgba(0, 0, 0, 0.2);
        }

        /* Ikony i Teksty */
        .icon-lg {
            font-size: 3.5rem;
            background: -webkit-linear-gradient(45deg, #4F46E5, #F59E0B);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Tło dekoracyjne */
        .shape {
            position: absolute;
            border-radius: 50%;
            z-index: -1;
            filter: blur(60px);
            animation: float 8s infinite ease-in-out;
            opacity: 0.6;
        }

        .s1 {
            width: 300px;
            height: 300px;
            background: #667eea;
            top: -50px;
            left: -50px;
        }

        .s2 {
            width: 400px;
            height: 400px;
            background: #764ba2;
            bottom: 10%;
            right: -50px;
            animation-delay: 2s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-30px);
            }
        }

        .btn-gradient {
            background: linear-gradient(90deg, #4F46E5 0%, #7c3aed 100%);
            color: white;
            border: none;
        }

        .btn-gradient:hover {
            background: linear-gradient(90deg, #4338ca 0%, #6d28d9 100%);
            color: white;
            box-shadow: 0 0 15px rgba(79, 70, 229, 0.5);
        }

        /* --- NOWE STYLE DLA FAQ (Accordion) --- */
        .accordion-glass .accordion-item {
            background: transparent;
            border: none;
            margin-bottom: 1rem;
        }

        .accordion-glass .accordion-button {
            background: rgba(255, 255, 255, 0.4);
            border-radius: 15px !important;
            backdrop-filter: blur(5px);
            box-shadow: none;
            font-weight: 600;
            color: inherit;
            /* Dziedziczy kolor tekstu z body */
        }

        [data-bs-theme="dark"] .accordion-glass .accordion-button {
            background: rgba(0, 0, 0, 0.3);
            color: #fff;
        }

        .accordion-glass .accordion-button:not(.collapsed) {
            background: rgba(79, 70, 229, 0.1);
            /* Lekki fiolet po otwarciu */
            color: var(--bs-primary);
            box-shadow: none;
        }

        .accordion-glass .accordion-body {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 0 0 15px 15px;
            padding: 1.5rem;
        }

        [data-bs-theme="dark"] .accordion-glass .accordion-body {
            background: rgba(0, 0, 0, 0.2);
        }

        /* --- NOWE STYLE DLA ROADMAPY --- */
        .roadmap-step {
            position: relative;
            padding-left: 30px;
            border-left: 2px solid rgba(79, 70, 229, 0.3);
            padding-bottom: 30px;
        }

        .roadmap-step:last-child {
            border-left: none;
        }

        .roadmap-dot {
            position: absolute;
            left: -11px;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--bs-primary);
            border: 4px solid var(--bs-body-bg);
            /* Otwór w środku */
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body>
    @if (session('status'))
        <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
            <div id="statusToast" class="toast align-items-center text-bg-success border-0" role="alert"
                aria-live="assertive" aria-atomic="true" data-bs-delay="5000">

                <div class="d-flex">
                    <div class="toast-body">
                        {{ session('status') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                        aria-label="Close">
                    </button>
                </div>
            </div>
        </div>
    @endif


    <div class="overflow-hidden position-fixed w-100 h-100" style="z-index: -2; pointer-events: none;">
        <div class="shape s1"></div>
        <div class="shape s2"></div>
    </div>

    <nav class="navbar navbar-expand-lg fixed-top navbar-glass">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                ⛪ Wspólnota App
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-3">
                    <li class="nav-item"><a class="nav-link" href="#details">Funkcje</a></li>
                    <li class="nav-item"><a class="nav-link" href="#roadmap">Rozwój</a></li>
                    <li class="nav-item"><a class="nav-link" href="#faq">Pytania</a></li>
                    <li class="nav-item">
                        <button class="btn btn-sm btn-outline-secondary rounded-circle" id="themeToggle">
                            <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
                        </button>
                    </li>
                    <li class="nav-item">
                        <a href="#newsletter" class="btn btn-primary btn-sm rounded-pill px-3">Dołącz</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="d-flex align-items-center min-vh-100 text-center position-relative pt-5">
        <div class="container pt-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <span class="badge rounded-pill bg-warning text-dark mb-3 px-3 py-2 shadow-sm">
                        <i class="bi bi-rocket-takeoff-fill me-1"></i> Wersja Beta 0.1
                    </span>
                    <h1 class="display-3 fw-bold mb-4">Zarządzanie Parafią<br>w Epoce Cyfrowej</h1>
                    <p class="lead mb-5 opacity-75">
                        Nowoczesny system w chmurze dla Proboszcza i intuicyjna aplikacja dla Parafian.
                        Cyfryzacja, która łączy, a nie dzieli.
                    </p>
                    <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                        <a href="#newsletter" class="btn btn-gradient btn-lg rounded-pill px-5 shadow">Zapisz się na
                            listę oczekujących</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section id="details" class="py-5">
        <div class="container py-5">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card glass-card h-100 border-0 p-4 text-center text-md-start">
                        <div class="card-body">
                            <i class="bi bi-building-check icon-lg mb-3 d-block"></i>
                            <h3 class="card-title fw-bold">Dla Parafii</h3>
                            <p class="card-text opacity-75 mb-4">Bezpieczna i szybka administracja parafią.</p>
                            <ul class="list-unstyled text-start">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Zarządzanie
                                    intencjami oraz ogłoszeniami parafialnymi</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Kancelaria
                                    online</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i>Bezpośredni
                                    kontakt z parafianami</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card glass-card h-100 border-0 p-4 text-center text-md-start">
                        <div class="card-body">
                            <i class="bi bi-phone-vibrate icon-lg mb-3 d-block"></i>
                            <h3 class="card-title fw-bold">Dla Wiernych</h3>
                            <p class="card-text opacity-75 mb-4">Wszystkie sprawy parafialne w kieszeni.</p>
                            <ul class="list-unstyled text-start">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-warning me-2"></i>Natychmiastowe
                                    powiadomienia
                                    o mszach</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-warning me-2"></i>Kontakt z
                                    proboszczem online</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-warning me-2"></i>Wygodny
                                    podgląd intencji mszalnych i ogłoszeń parafialnych</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="roadmap" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Nasz plan działania</h2>
                <p class="opacity-75">Transparentnie o tym, gdzie jesteśmy i dokąd zmierzamy.</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="roadmap-step">
                        <div class="roadmap-dot"></div>
                        <div class="glass-card p-4 ms-3">
                            <span class="badge bg-success mb-2">Ukończono</span>
                            <h5 class="fw-bold">Faza Koncepcyjna</h5>
                            <p class="small mb-0 opacity-75">Wiemy już, co chcemy stworzyć. Mamy konkretny plan na
                                zbudowanie nowoczesnej Usługi, z której skorzystają nasze parafie oraz wszyscy wierni,
                                którzy chcą być jeszcze bliżej swojej parafii. Na wyciągnięcie ręki. Po telefon.</p>
                        </div>
                    </div>

                    <div class="roadmap-step">
                        <div class="roadmap-dot bg-warning"></div>
                        <div class="glass-card p-4 ms-3 border-warning">
                            <span class="badge bg-warning text-dark mb-2">Jesteśmy tutaj</span>
                            <h5 class="fw-bold">Budujemy usługę i aplikację</h5>
                            <p class="small mb-0 opacity-75">Tworzymy oprogramowanie, rozwijamy nasze bazy danych oraz
                                opracowujemy szereg zabezpieczeń dla naszych użytkowników. Rozpoczynamy także pierwsze
                                testy z parafiami. Prowadzimy rownież zapisy do używania Usługi.</p>
                        </div>
                    </div>

                    <div class="roadmap-step">
                        <div class="roadmap-dot bg-secondary"></div>
                        <div class="glass-card p-4 ms-3">
                            <span class="badge bg-secondary mb-2">2026? :)</span>
                            <h5 class="fw-bold">Publiczna premiera</h5>
                            <p class="small mb-0 opacity-75">Chcemy jak najszybciej oddać naszą Usługę do użytkowania
                                przez parafie i parafian. Dlatego też zapraszamy do zapisu na listę oczekujących. To
                                realne wsparcie naszego porjektu :)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="faq" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Pytania i Odpowiedzi</h2>
                <p class="opacity-75">Rozwiewamy wątpliwości technologiczne i duszpasterskie.</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion accordion-glass" id="faqAccordion">

                        <div class="accordion-item glass-card mb-3 p-0">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#faq1">
                                    <i class="bi bi-shield-lock-fill me-2 text-primary"></i>
                                    Czy dane parafian w chmurze są bezpieczne?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Absolutnie. Bezpieczeństwo to nasz priorytet. Korzystamy z szyfrowania klasy
                                    bankowej (SSL/TLS). Ponadto wrazżliwe informacje w naszej bazie danych są
                                    szyfrowane. Dostęp do nich jest ograniczony do konta użytkownika. Spełniamy
                                    wszystkie wymogi RODO. Nikt niepowołany nie ma dostępu do danych.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item glass-card mb-3 p-0">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#faq2">
                                    <i class="bi bi-phone me-2 text-warning"></i>
                                    Czy starsi parafianie poradzą sobie z aplikacją?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Projektujemy aplikację mobilną z myślą o seniorach. Duże czcionki, wysoki kontrast i
                                    maksymalnie uproszczony interfejs. Dodatkowo, system pozwala parafii na
                                    uporządkowany wydruk tradycyjnych ogłoszeń oraz listy intencji mszalnych
                                    bezpośrednio z systemu, więc nikt nie zostanie wykluczony.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item glass-card mb-3 p-0">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#faq3">
                                    <i class="bi bi-wallet2 me-2 text-success"></i>
                                    Ile to kosztuje parafię?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Przede wszystkim - aplikacja zawsze będzie darmowa dla wiernych - użytkowników
                                    aplikacji.W fazie rozwoju (dopóki ona trwa) Usługa Wspólnoyta jest darmowa dla
                                    parafii, którzy tę usługę testują. Docelowo model będzie oparty o dogodny abonament
                                    dla parafii (SaaS). Pierwsze współpracujące parafie mogą liczyć na atrakcyjne
                                    zniżki.
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="newsletter" class="py-5 mb-5">
        <div class="container">
            <div class="glass-card p-5 text-center position-relative overflow-hidden">
                <div class="position-absolute top-0 start-0 w-100 h-100 bg-gradient opacity-10"
                    style="background: linear-gradient(45deg, var(--bs-primary), transparent);"></div>

                <div class="position-relative z-1">
                    <h2 class="fw-bold mb-3">Zbudujmy to razem</h2>
                    <p class="mb-4 opacity-75" style="max-width: 600px; margin: 0 auto;">
                        Dołącz do newslettera, aby otrzymać powiadomienie o starcie i
                        <strong>atrakcyjną zniżkę</strong> na abonament dla Twojej parafii.
                    </p>
                    <livewire:landing.join-waitlist />
                </div>
            </div>
        </div>
    </section>

    <footer class="text-center py-4 opacity-50">
        <div class="container">
            <small>&copy; 2025 Wspólnota</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
    <script>
        const themeToggleBtn = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const htmlElement = document.documentElement;

        const setTheme = (theme) => {
            if (theme === 'auto') {
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    htmlElement.setAttribute('data-bs-theme', 'dark');
                    themeIcon.classList.replace('bi-sun-fill', 'bi-moon-stars-fill');
                } else {
                    htmlElement.setAttribute('data-bs-theme', 'light');
                    themeIcon.classList.replace('bi-moon-stars-fill', 'bi-sun-fill');
                }
            } else {
                htmlElement.setAttribute('data-bs-theme', theme);
                if (theme === 'dark') {
                    themeIcon.classList.replace('bi-sun-fill', 'bi-moon-stars-fill');
                } else {
                    themeIcon.classList.replace('bi-moon-stars-fill', 'bi-sun-fill');
                }
            }
        };

        const savedTheme = localStorage.getItem('theme');
        setTheme(savedTheme ? savedTheme : 'auto');

        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
            localStorage.setItem('theme', newTheme);
        });
    </script>
    @if (session('status'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const toastEl = document.getElementById('statusToast');
                if (toastEl) {
                    const toast = new bootstrap.Toast(toastEl);
                    toast.show();
                }
            });
        </script>
    @endif
</body>

</html>