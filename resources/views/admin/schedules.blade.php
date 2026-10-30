@extends('layouts.admin', ['active' => 'schedules', 'cssFile' => 'schedules'])

@section('title', 'Schedules')
@section('page-title', 'Schedules')

@section('topbar-actions')
    <button class="topbar-btn" onclick="openModal('assignModal')">+ Assign Route</button>
@endsection

@section('content')

    <div class="stat-grid reveal">
        <div class="stat-card" data-icon="schedules">
            <div class="stat-label">Total Schedules</div>
            <div class="stat-value" id="totalSched">—</div>
        </div>
        <div class="stat-card" data-icon="pending">
            <div class="stat-label">Pending</div>
            <div class="stat-value text-gold" id="pendingSched">—</div>
        </div>
        <div class="stat-card" data-icon="check">
            <div class="stat-label">Completed</div>
            <div class="stat-value text-green" id="doneSched">—</div>
        </div>
    </div>

    <div class="panel reveal">
        <div class="panel-header">
            <div class="panel-title">
                <div class="panel-title-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" /><line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
                    </svg></div>All Pickup Schedules
            </div>
            <div class="flex-row flex-wrap" style="gap:8px">
                <div class="search-bar"><input type="text" placeholder="Search…" id="searchInput" oninput="filterSchedules()"></div>
                <select class="form-control" id="statusFilter" onchange="filterSchedules()" style="width:130px;padding:8px 12px;font-size:0.82rem">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>
        <div class="panel-body-flush">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>#</th><th>Date</th><th>Driver</th><th>Truck</th><th>Zone</th><th>Type</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody id="schedBody">
                        <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text2)"><span class="loading-spinner"></span></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@section('extra-modals')

    <div class="modal-overlay" id="assignModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title" style="display:flex;align-items:center;gap:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" /><line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
                    </svg> Assign Route</div>
                <button class="modal-close" onclick="closeModal('assignModal')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
                    </svg></button>
            </div>
            <div class="modal-body">
                <div class="form-grid form-grid-2">
                    <div class="form-group"><label class="form-label">Driver</label>
                        <select class="form-control" id="aDriver"><option value="">Select driver…</option></select>
                    </div>
                    <div class="form-group"><label class="form-label">Truck</label>
                        <select class="form-control" id="aTruck"><option value="">Select truck…</option></select>
                    </div>
                    <div class="form-group"><label class="form-label">Route / Zone</label>
                        <select class="form-control" id="aRoute"><option value="">Select route…</option></select>
                    </div>
                    <div class="form-group"><label class="form-label">Date</label>
                        <input class="form-control" type="date" id="aDate">
                    </div>
                    <div class="form-group" style="grid-column:1/-1"><label class="form-label">Pickup Type</label>
                        <select class="form-control" id="aType">
                            <option value="regular">Regular</option>
                            <option value="special">Special</option>
                            <option value="recyclable">Recyclable</option>
                            <option value="hazardous">Hazardous</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('assignModal')">Cancel</button>
                <button class="btn btn-gold" onclick="assignRoute()">Assign</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title" style="display:flex;align-items:center;gap:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 20h9" /><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                    </svg> Update Schedule</div>
                <button class="modal-close" onclick="closeModal('editModal')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
                    </svg></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId">
                <div class="form-grid form-grid-2">
                    <div class="form-group"><label class="form-label">Status</label>
                        <select class="form-control" id="editStatus">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group"><label class="form-label">Date</label>
                        <input class="form-control" type="date" id="editDate">
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Pickup Type</label>
                        <select class="form-control" id="editType">
                            <option value="regular">Regular</option>
                            <option value="special">Special</option>
                            <option value="recyclable">Recyclable</option>
                            <option value="hazardous">Hazardous</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
                <button class="btn btn-gold" onclick="updateSchedule()">Save Changes</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="deleteScheduleModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title" style="color:var(--red);display:flex;align-items:center;gap:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                    </svg> Confirm Delete</div>
                <button class="modal-close" onclick="closeModal('deleteScheduleModal')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
                    </svg></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this schedule? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('deleteScheduleModal')">Cancel</button>
                <button class="btn btn-danger" id="deleteScheduleConfirmBtn">Yes, Delete</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/admin/schedules.js') }}?v={{ md5_file(public_path('js/admin/schedules.js')) }}"></script>
@endpush
