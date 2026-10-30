@extends('layouts.admin', ['active' => 'dashboard', 'cssFile' => 'dashboard'])

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('topbar-actions')
    <a href="{{ route('admin.announcements') }}" class="topbar-btn">+ New Announcement</a>
@endsection

@section('content')

    <div class="stat-grid reveal" id="statGrid">
        <div class="stat-card" data-icon="users">
            <div class="stat-label">Total Users</div>
            <div class="stat-value" id="s-users">—</div>
            <div class="stat-sub">All registered</div>
        </div>
        <div class="stat-card" data-icon="driver">
            <div class="stat-label">Drivers</div>
            <div class="stat-value" id="s-drivers">—</div>
            <div class="stat-sub">Active accounts</div>
        </div>
        <div class="stat-card" data-icon="trucks">
            <div class="stat-label">Trucks</div>
            <div class="stat-value" id="s-trucks">—</div>
            <div class="stat-sub">Fleet vehicles</div>
        </div>
        <div class="stat-card" data-icon="routes">
            <div class="stat-label">Routes</div>
            <div class="stat-value" id="s-routes">—</div>
            <div class="stat-sub">Active zones</div>
        </div>
        <div class="stat-card" data-icon="reports">
            <div class="stat-label">Reports</div>
            <div class="stat-value" id="s-reports">—</div>
            <div class="stat-sub">Resident reports</div>
        </div>
        <div class="stat-card" data-icon="fuel">
            <div class="stat-label">Fuel Used</div>
            <div class="stat-value" id="s-fuel">—</div>
            <div class="stat-sub">Liters logged</div>
        </div>
    </div>

    <div class="panel section-gap reveal">
        <div class="panel-header">
            <div class="panel-title"><span class="panel-title-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
                    </svg></span>Quick Actions</div>
        </div>
        <div class="panel-body">
            <div class="quick-actions-grid">
                <a href="{{ route('admin.users') }}" class="quick-action"><span class="quick-action-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="8" r="4" /><path d="M4 20c0-4 4-6 8-6s8 2 8 6" />
                        </svg></span>Add User</a>
                <a href="{{ route('admin.trucks') }}" class="quick-action"><span class="quick-action-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" /><path d="M15 18H9" />
                            <path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14" />
                            <circle cx="17" cy="18" r="2" /><circle cx="7" cy="18" r="2" />
                        </svg></span>Add Truck</a>
                <a href="{{ route('admin.routes') }}" class="quick-action"><span class="quick-action-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="3 11 22 2 13 21 11 13 3 11" />
                        </svg></span>Add Route</a>
                <a href="{{ route('admin.schedules') }}" class="quick-action"><span class="quick-action-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" /><line x1="16" y1="2" x2="16" y2="6" />
                            <line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
                        </svg></span>Assign Route</a>
            </div>
        </div>
    </div>

    <div class="dash-grid">
        <div class="panel reveal">
            <div class="panel-header">
                <div class="panel-title"><span class="panel-title-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10" /><line x1="12" y1="20" x2="12" y2="4" /><line x1="6" y1="20" x2="6" y2="14" />
                        </svg></span>Report Types</div>
            </div>
            <div class="panel-body">
                <div class="chart-wrap"><canvas id="reportChart"></canvas></div>
            </div>
        </div>

        <div class="panel reveal">
            <div class="panel-header">
                <div class="panel-title"><span class="panel-title-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="8" y1="6" x2="21" y2="6" /><line x1="8" y1="12" x2="21" y2="12" /><line x1="8" y1="18" x2="21" y2="18" />
                            <line x1="3" y1="6" x2="3.01" y2="6" /><line x1="3" y1="12" x2="3.01" y2="12" /><line x1="3" y1="18" x2="3.01" y2="18" />
                        </svg></span>Recent Reports</div>
                <a href="{{ route('admin.reports') }}" class="btn btn-outline btn-sm">View All</a>
            </div>
            <div class="panel-body" id="recentReports">
                <div class="empty-state"><span class="empty-state-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <polyline points="22 12 16 12 14 15 10 15 8 12 2 12" />
                            <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" />
                        </svg></span>
                    <div class="empty-state-text">Loading...</div>
                </div>
            </div>
        </div>

        <div class="panel reveal dash-grid-wide">
            <div class="panel-header">
                <div class="panel-title"><span class="panel-title-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" /><line x1="16" y1="2" x2="16" y2="6" />
                            <line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
                        </svg></span>Upcoming Pickups</div>
                <a href="{{ route('admin.schedules') }}" class="btn btn-outline btn-sm">View All</a>
            </div>
            <div class="panel-body-flush">
                <table class="data-table" id="pickupTable">
                    <thead>
                        <tr><th>Date</th><th>Driver</th><th>Truck</th><th>Zone</th><th>Status</th></tr>
                    </thead>
                    <tbody id="pickupBody">
                        <tr><td colspan="5" class="empty-state-text" style="text-align:center;padding:24px;color:var(--text2)">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/admin/dashboard.js') }}?v={{ md5_file(public_path('js/admin/dashboard.js')) }}"></script>
@endpush
