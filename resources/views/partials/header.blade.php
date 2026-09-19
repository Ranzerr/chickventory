<header class="header">
    <button class="menu-toggle" type="button" data-menu-toggle aria-label="Open navigation">☰</button>
    <div>
        <p class="eyebrow">Chicky Fryday</p>
        <h2>Inventory Management System</h2>
    </div>
    <div class="user-profile">
        <button class="notification" type="button" aria-label="Notifications">♧<span>{{ $notificationCount }}</span></button>
        <div class="user-avatar">{{ $currentUserInitial }}</div>
        <div class="user-details"><strong>{{ $currentUserName }}</strong><small>{{ $currentUserRole }}</small></div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="outline-btn">Log Out</button>
        </form>
    </div>
</header>