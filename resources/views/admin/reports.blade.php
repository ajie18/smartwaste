@extends('layouts.admin', ['active' => 'reports', 'cssFile' => 'reports'])

@section('title', 'Reports & Resident Locations')
@section('page-title', 'Reports & Resident Locations')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
@endpush

@section('content')

    <div class="stat-grid reveal" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
        <div class="stat-card" data-icon="pending">
            <div class="stat-label">Missed Pickup</div>
            <div class="stat-value" id="c-missed">—</div>
            <div class="report-type-bar" style="background:var(--gold)"></div>
        </div>
        <div class="stat-card" data-icon="bin">
            <div class="stat-label">Overflowing Bin</div>
            <div class="stat-value" id="c-overflow">—</div>
            <div class="report-type-bar" style="background:var(--green)"></div>
        </div>
        <div class="stat-card" data-icon="reports">
            <div class="stat-label">Illegal Dumping</div>
            <div class="stat-value" id="c-illegal">—</div>
            <div class="report-type-bar" style="background:var(--red)"></div>
        </div>
        <div class="stat-card" data-icon="users">
            <div class="stat-label">Active Residents</div>
            <div class="stat-value" id="c-residents">—</div>
            <div class="report-type-bar" style="background:var(--blue)"></div>
        </div>
    </div>

    <div class="reports-layout">

        <div class="panel reveal">
            <div class="panel-header">
                <div class="panel-title">
                    <div class="panel-title-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="3 11 22 2 13 21 11 13 3 11" />
                        </svg></div>Location Map
                </div>
                <span class="badge badge-gold" id="mapPinCount">0 pins</span>
            </div>
            <div class="map-toolbar">
                <div class="map-dot-legend">
                    <span><span class="dot" style="background:#ffc13c;"></span>Missed Pickup</span>
                    <span><span class="dot" style="background:#4ade80;"></span>Overflowing Bin</span>
                    <span><span class="dot" style="background:#f87171;"></span>Illegal Dumping</span>
                    <span><span class="dot" style="background:#60a5fa;"></span>Resident Location</span>
                </div>
                <button class="map-tool-btn" id="satBtn" onclick="toggleSat()" style="margin-left:auto;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3" />
                        <path d="M12 2v2" />
                        <path d="M12 20v2" />
                        <path d="M4.93 4.93l1.41 1.41" />
                        <path d="M17.66 17.66l1.41 1.41" />
                        <path d="M2 12h2" />
                        <path d="M20 12h2" />
                        <path d="M4.93 19.07l1.41-1.41" />
                        <path d="M17.66 6.34l1.41-1.41" />
                    </svg> Satellite</button>
                <button class="map-tool-btn" onclick="fitAllPins()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                        <circle cx="12" cy="10" r="3" />
                    </svg> Fit All Pins</button>
            </div>
            <div class="map-panel-wrap">
                <div id="reportMap"></div>
            </div>
            <div style="padding:8px 14px;font-size:0.69rem;color:var(--text2);">
                Click a pin to view details. Yellow/green/red = reports, Blue = resident locations.
            </div>
        </div>

        <div class="panel reveal">
            <div class="search-row">
                <div class="search-bar search-bar-full">
                    <input type="text" placeholder="Search reports..." id="searchInput" oninput="applyFilters()">
                </div>
            </div>
            <div class="filter-row">
                <select class="type-filter-select" id="typeFilter" onchange="applyFilters()">
                    <option value="">All Types</option>
                    <option value="missed_pickup">Missed Pickup</option>
                    <option value="overflowing_bin">Overflowing Bin</option>
                    <option value="illegal_dumping">Illegal Dumping</option>
                </select>
                <select class="type-filter-select" id="dateFilterSelect" onchange="setDateFilter(this.value)">
                    <option value="all">All Time</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
                <select class="type-filter-select" id="statusFilterSelect" onchange="setStatusFilter(this.value)">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <div class="tab-buttons">
                <button class="tab-btn active" onclick="switchTab('reports')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6" />
                        <line x1="8" y1="12" x2="21" y2="12" />
                        <line x1="8" y1="18" x2="21" y2="18" />
                        <line x1="3" y1="6" x2="3.01" y2="6" />
                        <line x1="3" y1="12" x2="3.01" y2="12" />
                        <line x1="3" y1="18" x2="3.01" y2="18" />
                    </svg> Resident Reports</button>
                <button class="tab-btn" onclick="switchTab('residents')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                        <circle cx="12" cy="10" r="3" />
                    </svg> Live Resident Locations</button>
            </div>

            <div id="reports-tab" class="tab-content active">
                <div class="panel-header" style="border-bottom: none; padding: 0 0 12px 0;">
                    <div class="panel-title">
                        <div class="panel-title-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16"
                                height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                                <line x1="12" y1="9" x2="12" y2="13" />
                                <line x1="12" y1="17" x2="12.01" y2="17" />
                            </svg></div>Resident Reports
                    </div>
                    <span class="badge badge-gold" id="visibleCount">0</span>
                </div>
                <div class="panel-body-flush" style="max-height: 550px; overflow-y: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Reporter</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Photo</th>
                                <th>Map</th>
                            </tr>
                        </thead>
                        <tbody id="reportsBody">
                            <tr>
                                <td colspan="7" style="text-align:center;padding:32px;color:var(--text2)"><span
                                        class="loading-spinner"></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="residents-tab" class="tab-content">
                <div class="panel-header" style="border-bottom: none; padding: 0 0 12px 0;">
                    <div class="panel-title">
                        <div class="panel-title-icon"><svg xmlns="http://www.w3.org/2000/svg" width="14"
                                height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                                <circle cx="12" cy="10" r="3" />
                            </svg></div>Active Residents (Last 2 Hours)
                    </div>
                    <span class="badge badge-gold" id="residentCount">0</span>
                </div>
                <div class="resident-list" id="residentList" style="max-height: 550px; overflow-y: auto;">
                    <div style="text-align:center;padding:32px;color:var(--text2)"><span class="loading-spinner"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('extra-modals')
    <div class="modal-overlay" id="viewReportModal">
        <div class="modal" style="max-width:540px;">
            <div class="modal-header">
                <div class="modal-title" style="display:flex;align-items:center;gap:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6" />
                        <line x1="8" y1="12" x2="21" y2="12" />
                        <line x1="8" y1="18" x2="21" y2="18" />
                        <line x1="3" y1="6" x2="3.01" y2="6" />
                        <line x1="3" y1="12" x2="3.01" y2="12" />
                        <line x1="3" y1="18" x2="3.01" y2="18" />
                    </svg> Report Details
                </div>
                <button class="modal-close" onclick="closeModal('viewReportModal')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg></button>
            </div>
            <div class="modal-body" id="reportModalBody"></div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('viewReportModal')">Close</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="viewResidentModal">
        <div class="modal" style="max-width:400px;">
            <div class="modal-header">
                <div class="modal-title" style="display:flex;align-items:center;gap:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 20c0-4 4-6 8-6s8 2 8 6" />
                    </svg> Resident Details
                </div>
                <button class="modal-close" onclick="closeModal('viewResidentModal')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg></button>
            </div>
            <div class="modal-body" id="residentModalBody"></div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('viewResidentModal')">Close</button>
            </div>
        </div>
    </div>

    <div class="lightbox-overlay" id="lightbox" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg></button>
        <img id="lightboxImg" src="" alt="Report photo">
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin/reports.js') }}?v={{ md5_file(public_path('js/admin/reports.js')) }}"></script>
@endpush
