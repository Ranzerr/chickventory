<aside class="sidebar">
    <div class="logo-container">
        <a class="brand" href="{{ route('dashboard') }}">
            <img src="{{ asset('images/ChickyLogo.jpg') }}" alt="Chicky Fryday Logo" class="brand-logo">
        </a>
    </div>
    <nav class="sidebar-nav" aria-label="Main navigation">
        @php
            // Retrieve current authenticated user
            $user = auth()->user();

            // Extract role string safely from user object or passed view variable
            $rawRole = strtolower(trim((string) ($user?->role ?? $user?->user_role ?? $currentUserRole ?? '')));

            // Flexible admin check matching 'admin', 'administrator', '1', or a boolean flag
            $isAdmin = in_array($rawRole, ['admin', 'administrator', '1'], true) || (bool) ($user?->is_admin ?? false);

            $items = [
                ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => '▦', 'admin_only' => false],
                ['route' => 'products', 'label' => 'Products / Inventory', 'icon' => '□', 'admin_only' => false],
                ['route' => 'stock-in', 'label' => 'Stock In', 'icon' => '↓', 'admin_only' => false],
                ['route' => 'inventory-transactions', 'label' => 'Inventory Transactions', 'icon' => '⇄', 'admin_only' => false],
                ['route' => 'suppliers', 'label' => 'Suppliers', 'icon' => '♙', 'admin_only' => false],
                ['route' => 'reports', 'label' => 'Reports', 'icon' => '▥', 'admin_only' => false],
                ['route' => 'users', 'label' => 'Users', 'icon' => '♙', 'admin_only' => true],
                ['route' => 'settings', 'label' => 'Settings', 'icon' => '⚙', 'admin_only' => true],
                ['route' => 'purchase-orders', 'label' => 'Purchase Orders', 'icon' => '▤', 'admin_only' => false],
                ['route' => 'purchases', 'label' => 'Purchases', 'icon' => '↓', 'admin_only' => false],
                ['route' => 'sales', 'label' => 'Manual / Backup Sale', 'icon' => '▣', 'admin_only' => true],
                ['route' => 'expenses', 'label' => 'Expenses', 'icon' => '₱', 'admin_only' => false],
            ];
        @endphp

        @foreach ($items as $item)
            @if (!$item['admin_only'] || $isAdmin)
                <a class="nav-item {{ request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                    <span class="nav-icon">{{ $item['icon'] }}</span><span>{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>
    <div class="sidebar-message"><strong>Good day!</strong>
        <p>Keep track of your inventory and keep your business running smoothly.</p>
    </div>
</aside>