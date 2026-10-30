<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="api-base" content="/{{ trim(config('app.api_prefix', 'api'), '/') }}">
    <title>@yield('title', 'Dashboard') · CleanCity Admin</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/style.js') }}?v={{ md5_file(public_path('js/style.js')) }}"></script>
    <script src="{{ asset('js/request-guard.js') }}?v={{ md5_file(public_path('js/request-guard.js')) }}"></script>
    <link rel="stylesheet" href="{{ asset('css/admin/' . ($cssFile ?? 'dashboard') . '.css') }}">
    @stack('head')
</head>

<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10" />
                    <polyline points="1 20 1 14 7 14" />
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" />
                </svg></div>
            <div class="sidebar-logo-text">CleanCity<span>Waste Reporting &amp; Collection</span></div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-label">Overview</div>
            <a href="{{ route('admin.dashboard') }}" class="nav-item {{ ($active ?? '') === 'dashboard' ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="9" /><rect x="14" y="3" width="7" height="5" />
                        <rect x="14" y="12" width="7" height="9" /><rect x="3" y="16" width="7" height="5" />
                    </svg></span>Dashboard</a>
            <div class="nav-section-label">Management</div>
            <a href="{{ route('admin.users') }}" class="nav-item {{ ($active ?? '') === 'users' ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg></span>Users</a>
            <a href="{{ route('admin.trucks') }}" class="nav-item {{ ($active ?? '') === 'trucks' ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" /><path d="M15 18H9" />
                        <path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14" />
                        <circle cx="17" cy="18" r="2" /><circle cx="7" cy="18" r="2" />
                    </svg></span>Trucks</a>
            <a href="{{ route('admin.routes') }}" class="nav-item {{ ($active ?? '') === 'routes' ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="3 11 22 2 13 21 11 13 3 11" />
                    </svg></span>Routes</a>
            <a href="{{ route('admin.schedules') }}" class="nav-item {{ ($active ?? '') === 'schedules' ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" /><line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
                    </svg></span>Schedules</a>
            <div class="nav-section-label">Community</div>
            <a href="{{ route('admin.reports') }}" class="nav-item {{ ($active ?? '') === 'reports' ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                        <line x1="12" y1="9" x2="12" y2="13" /><line x1="12" y1="17" x2="12.01" y2="17" />
                    </svg></span>Reports</a>
            <a href="{{ route('admin.announcements') }}" class="nav-item {{ ($active ?? '') === 'announcements' ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 11l18-5v12L3 13v-2z" /><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6" />
                    </svg></span>Announcements</a>
            <div class="nav-section-label">Analytics</div>
            <a href="{{ route('admin.analytics') }}" class="nav-item {{ ($active ?? '') === 'analytics' ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10" /><line x1="12" y1="20" x2="12" y2="4" /><line x1="6" y1="20" x2="6" y2="14" />
                    </svg></span>Analytics</a>

            <div class="nav-section-label" style="margin-top:20px;border-top:1px solid var(--border2);padding-top:16px;">Preferences</div>
            <button class="theme-toggle" id="themeToggleBtn">
                <span class="theme-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                    </svg></span>
                <span class="theme-label">Dark Mode</span>
                <span class="theme-switch"><span class="theme-knob"></span></span>
            </button>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4" /><path d="M4 20c0-4 4-6 8-6s8 2 8 6" />
                    </svg></div>
                <div>
                    <div class="sidebar-user-name" id="adminName">Admin</div>
                    <div class="sidebar-user-role">Administrator</div>
                </div>
            </div>
            <button type="button" class="sidebar-logout" style="margin-top:10px;display:flex;" onclick="openModal('logoutConfirmModal')">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><polyline points="16 17 21 12 16 7" /><line x1="21" y1="12" x2="9" y2="12" />
                </svg> &nbsp;Logout</button>
        </div>
    </aside>

    <div class="topbar">
        <div style="display:flex;align-items:center;gap:14px;">
            <button class="topbar-hamburger" id="hamburger"><span></span><span></span><span></span></button>
            <div class="topbar-left">
                <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
            </div>
        </div>
        <div class="topbar-right">
            <div style="font-size:0.72rem;color:var(--text2);font-family:var(--mono)" id="clock"></div>
            @yield('topbar-actions')
        </div>
    </div>

    <main class="main">
        @yield('content')
    </main>

    <!-- Logout Modal (shared across every admin page) -->
    <div class="modal-overlay" id="logoutConfirmModal">
        <div class="modal" style="max-width:400px;">
            <div class="modal-header">
                <div class="modal-title" style="color:var(--red);display:flex;align-items:center;gap:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z" /><line x1="10" y1="12" x2="10" y2="12.01" />
                    </svg> Confirm Logout</div>
                <button class="modal-close" onclick="closeModal('logoutConfirmModal')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
                    </svg></button>
            </div>
            <div class="modal-body">
                <p style="color:var(--text2);">Are you sure you want to log out of the admin panel?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('logoutConfirmModal')">Cancel</button>
                <button class="btn btn-danger" onclick="doLogout()">Yes, Logout</button>
            </div>
        </div>
    </div>

    @yield('extra-modals')

    <div class="toast-container" id="toastContainer"></div>

    @stack('scripts')
</body>

</html>