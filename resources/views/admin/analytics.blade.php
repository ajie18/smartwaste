@extends('layouts.admin', ['active' => 'analytics', 'cssFile' => 'analytics'])

@section('title', 'Analytics')
@section('page-title', 'Analytics')

@section('topbar-actions')
    <button class="topbar-btn outline" onclick="exportExcel()">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <polyline points="7 10 12 15 17 10" />
            <line x1="12" y1="15" x2="12" y2="3" />
        </svg> Export Excel</button>
@endsection

@section('content')

    <div class="panel reveal section-gap" id="aiInsightsPanel">
        <div class="panel-header">
            <div class="panel-title">
                <div class="panel-title-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 3l1.9 4.6L18.5 9.5l-4.6 1.9L12 16l-1.9-4.6L5.5 9.5l4.6-1.9L12 3z" />
                        <path d="M19 15l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8.8-2z" />
                    </svg></div>AI Insights
            </div>
            <button class="btn btn-gold btn-sm" id="aiInsightBtn" onclick="loadAiInsight()"><svg
                    xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3l1.9 4.6L18.5 9.5l-4.6 1.9L12 16l-1.9-4.6L5.5 9.5l4.6-1.9L12 3z" />
                    <path d="M19 15l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8.8-2z" />
                </svg> Generate Insight</button>
        </div>
        <div class="panel-body" id="aiInsightBody">
            <div class="empty-state" style="padding:24px;">
                <div class="empty-state-text">Click "Generate Insight" for an AI-written summary of report trends, pickup
                    completion, and what needs attention right now.</div>
            </div>
        </div>
    </div>

    <div class="stat-grid reveal" id="kpiGrid">
        <div class="stat-card" data-icon="users">
            <div class="stat-label">Users</div>
            <div class="stat-value" id="k-users">—</div>
        </div>
        <div class="stat-card" data-icon="trucks">
            <div class="stat-label">Trucks</div>
            <div class="stat-value" id="k-trucks">—</div>
        </div>
        <div class="stat-card" data-icon="schedules">
            <div class="stat-label">Schedules</div>
            <div class="stat-value" id="k-sched">—</div>
        </div>
        <div class="stat-card" data-icon="reports">
            <div class="stat-label">Total Reports</div>
            <div class="stat-value" id="k-reports">—</div>
        </div>
        <div class="stat-card" data-icon="fuel">
            <div class="stat-label">Fuel (L)</div>
            <div class="stat-value" id="k-fuel">—</div>
        </div>
        <div class="stat-card" data-icon="check">
            <div class="stat-label">Completed</div>
            <div class="stat-value text-green" id="k-done">—</div>
        </div>
    </div>

    <div class="chart-grid">

        <div class="panel reveal">
            <div class="panel-header">
                <div class="panel-title">
                    <div class="panel-title-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <circle cx="12" cy="12" r="4" />
                        </svg></div>Report Type Distribution
                </div>
            </div>
            <div class="panel-body">
                <div class="chart-box"><canvas id="donutChart"></canvas></div>
            </div>
        </div>

        <div class="panel reveal">
            <div class="panel-header">
                <div class="panel-title">
                    <div class="panel-title-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10" />
                            <line x1="12" y1="20" x2="12" y2="4" />
                            <line x1="6" y1="20" x2="6" y2="14" />
                        </svg></div>Report Breakdown
                </div>
            </div>
            <div class="panel-body">
                <div class="metric-grid" id="metricRows"></div>
            </div>
        </div>

        <div class="panel reveal chart-grid-wide">
            <div class="panel-header">
                <div class="panel-title">
                    <div class="panel-title-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                            <line x1="16" y1="2" x2="16" y2="6" />
                            <line x1="8" y1="2" x2="8" y2="6" />
                            <line x1="3" y1="10" x2="21" y2="10" />
                        </svg></div>Schedule Status Overview
                </div>
            </div>
            <div class="panel-body">
                <div class="chart-box" style="height:220px"><canvas id="barChart"></canvas></div>
            </div>
        </div>

    </div>

    <div class="panel reveal">
        <div class="panel-header">
            <div class="panel-title">
                <div class="panel-title-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 3h18v18H3z" />
                        <path d="M3 9h18" />
                        <path d="M3 15h18" />
                        <path d="M9 3v18" />
                    </svg></div>Report Data Table
            </div>
            <button class="btn btn-outline btn-sm" onclick="exportExcel()">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="7 10 12 15 17 10" />
                    <line x1="12" y1="15" x2="12" y2="3" />
                </svg> Export to Excel</button>
        </div>
        <div class="panel-body-flush">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Report Type</th>
                        <th>Total Count</th>
                        <th>% of Total</th>
                        <th>Visual</th>
                    </tr>
                </thead>
                <tbody id="analyticsTable">
                    <tr>
                        <td colspan="4" style="text-align:center;padding:28px;color:var(--text2)"><span
                                class="loading-spinner"></span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/admin/analytics.js') }}?v={{ md5_file(public_path('js/admin/analytics.js')) }}"></script>
    <script>
        async function loadAiInsight() {
            const btn = document.getElementById('aiInsightBtn');
            const body = document.getElementById('aiInsightBody');
            btn.disabled = true;
            btn.textContent = 'Thinking…';
            body.innerHTML =
                '<div style="text-align:center;padding:24px;color:var(--text2)"><span class="loading-spinner"></span></div>';

            try {
                const res = await API.get('/admin/ai/analytics-insight');
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();

                body.innerHTML = `
                    <div style="display:flex;gap:12px;align-items:flex-start;">
                        <div style="flex-shrink:0;width:32px;height:32px;border-radius:50%;background:var(--gold-dim);color:var(--gold);display:flex;align-items:center;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 3l1.9 4.6L18.5 9.5l-4.6 1.9L12 16l-1.9-4.6L5.5 9.5l4.6-1.9L12 3z" />
                            </svg>
                        </div>
                        <div style="flex:1;white-space:pre-line;line-height:1.6;font-size:0.88rem;color:var(--text);">${escHtmlSafe(data.insight)}</div>
                    </div>
                    <div style="margin-top:14px;font-size:0.68rem;color:var(--text3);font-family:var(--mono);">Generated ${new Date(data.generated_at).toLocaleString()}</div>
                `;
            } catch (err) {
                console.error('AI insight error:', err);
                body.innerHTML =
                    '<div class="empty-state" style="padding:24px;"><div class="empty-state-text">Could not generate an insight right now. Check that GROQ_API_KEY is set and try again.</div></div>';
            } finally {
                btn.disabled = false;
                btn.innerHTML =
                    '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 4.6L18.5 9.5l-4.6 1.9L12 16l-1.9-4.6L5.5 9.5l4.6-1.9L12 3z" /><path d="M19 15l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8.8-2z" /></svg> Generate Insight';
            }
        }

        function escHtmlSafe(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));
        }
    </script>
@endpush
