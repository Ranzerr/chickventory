<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Chickventory' }} | Chickventory</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @include('partials.sidebar')
    <div class="app-shell">
        @include('partials.header')
        <main class="main-content">
            @yield('content')
        </main>
        @include('partials.footer')
    </div>
    @stack('modals')
    <script>
        const menuButton = document.querySelector('[data-menu-toggle]');
        const sidebar = document.querySelector('.sidebar');
        menuButton?.addEventListener('click', () => sidebar?.classList.toggle('is-open'));

        document.querySelectorAll('[data-modal-open]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = document.getElementById(button.dataset.modalOpen);
                if (modal) {
                    modal.hidden = false;
                    modal.querySelector('input, select, textarea')?.focus();
                }
            });
        });

        document.querySelectorAll('[data-modal-close]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = button.closest('[data-modal]');
                if (modal) modal.hidden = true;
            });
        });
    </script>
</body>
</html>
