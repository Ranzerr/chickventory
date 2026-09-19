@extends('layouts.app')
@section('content')
    <div class="content">
        <div class="page-header">
            <div>
                <h1>Settings</h1>
                <p>Configure your inventory management system.</p>
            </div>
        </div>
        <div class="panel">
            <h2 style="color: var(--orange); margin-top: 0;">System Information</h2>
            <div class="form-grid"><label>System Name<input
                        value="{{ $settings->get('system_name', 'Chicky Fryday Inventory Management System') }}"
                        readonly></label><label>Currency<input value="{{ $settings->get('currency', 'PHP') }}"
                        readonly></label><label class="full">Description<textarea
                        readonly>{{ $settings->get('description', 'Inventory management for Chicky Fryday.') }}</textarea></label>
            </div>
        </div>
        <div class="panel">
            <h2 style="color: var(--orange); margin-top: 0;">Inventory Settings</h2>
            <div class="form-grid"><label>Low Stock Threshold<input value="{{ $settings->get('low_stock_threshold', 10) }}"
                        readonly></label><label>Default Unit<input value="{{ $settings->get('default_unit', 'Pieces') }}"
                        readonly></label></div>
        </div>
        <div class="integration-card">
            <h2>Ordering System Integration</h2>
                <div class="integration-info"><span>Status: <strong class="connected">Connected</strong></span><span>Data
                    source: <strong>{{ strtoupper(config('database.default')) }}</strong></span></div>
        </div>
    </div>
@endsection