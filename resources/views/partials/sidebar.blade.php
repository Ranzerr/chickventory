<aside class="sidebar">
    <div class="logo-container">
        <a class="brand" href="{{ route('dashboard') }}">
            <img src="{{ asset('images/ChickyLogo.jpg') }}" alt="Chicky Fryday Logo" class="brand-logo">
        </a>
    </div>
    <nav class="sidebar-nav" aria-label="Main navigation">
        @php($items = [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => '▦'],
            ['route' => 'products', 'label' => 'Products / Inventory', 'icon' => '□'],
            ['route' => 'stock-in', 'label' => 'Stock In', 'icon' => '↓'],
            ['route' => 'inventory-transactions', 'label' => 'Inventory Transactions', 'icon' => '⇄'],
            ['route' => 'suppliers', 'label' => 'Suppliers', 'icon' => '♙'],
            ['route' => 'reports', 'label' => 'Reports', 'icon' => '▥'],
            ['route' => 'users', 'label' => 'Users', 'icon' => '♙'],
            ['route' => 'settings', 'label' => 'Settings', 'icon' => '⚙'],
            ['route' => 'purchase-orders', 'label' => 'Purchase Orders', 'icon' => '▤'],
            ['route' => 'purchases', 'label' => 'Purchases', 'icon' => '↓'],
            ['route' => 'sales', 'label' => 'Record Sale', 'icon' => '▣'],
            ['route' => 'expenses', 'label' => 'Expenses', 'icon' => '₱'],
        ])
        @foreach ($items as $item)
            <a class="nav-item {{ request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                <span class="nav-icon">{{ $item['icon'] }}</span><span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
    <div class="sidebar-message"><strong>Good day!</strong>
        <p>Keep track of your inventory and keep your business running smoothly.</p>
    </div>
</aside>