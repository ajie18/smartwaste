
(function () {
    if (typeof Echo === 'undefined' || typeof window.__reverbConfig === 'undefined') {
        console.warn('Echo bridge: laravel-echo or reverb config missing — falling back to REST polling only.');
        return;
    }

    const cfg = window.__reverbConfig;
    const token = localStorage.getItem('token');

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: cfg.key,
        wsHost: cfg.host,
        wsPort: cfg.port,
        wssPort: cfg.port,
        forceTLS: cfg.scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: 'application/json',
            },
        },
    });

    Echo.connector.pusher.connection.bind('connected', () => {
        console.log('Echo: connected to Reverb');
        if (typeof updateConnectionStatus === 'function') updateConnectionStatus(true);
    });

    Echo.connector.pusher.connection.bind('disconnected', () => {
        console.log('Echo: disconnected from Reverb');
        if (typeof updateConnectionStatus === 'function') updateConnectionStatus(false);
    });

    Echo.connector.pusher.connection.bind('error', (err) => {
        console.error('Echo connection error:', err);
    });

    if (driverId) {
        Echo.private(`driver.${driverId}`)
            .listen('.assignment-status-changed', (data) => {
                console.log('Status changed:', data);
                routeStatus = data.status;
                toast(`Route status: ${data.status}`, 'info');
                loadToday();

                if (data.status === 'completed' || data.status === 'cancelled') {
                    if (currentAssignmentId) {
                        localStorage.removeItem(`driver_progress_${currentAssignmentId}`);
                    }
                }
            })
            .listen('.report-submitted', () => {
                toast('Report submitted successfully!', 'success');
            })
            .listen('.profile-updated', () => {
                toast('Profile saved successfully!', 'success');
                loadProfile();
            })
            .listen('.stop-updated', (data) => {
                console.log('Stop updated:', data);
            });
    }

    Echo.private('reports')
        .listen('.new-resident-report', (report) => {
            if (!report || !report.id) return;
            if (allIssues.some((r) => r.id === report.id)) return;
            report._isNew = true;
            allIssues.unshift(report);
            renderIssueMarkers();
            renderIssuesListPanel();
            updateIssuesBadge();
            const badge = document.getElementById('issuesBadge');
            if (badge) {
                badge.classList.add('pulse');
                setTimeout(() => badge.classList.remove('pulse'), 4000);
            }
            toast(`New report: ${ISSUE_LABELS[report.type] || 'Issue'}`, 'info');
            setTimeout(() => { report._isNew = false; }, 3000);
        })
        .listen('.new-announcement', (data) => {
            console.log('New announcement:', data);
            toast(`${data.title || 'New announcement'}`, 'info');
            loadAnnouncements();
            const badge = document.getElementById('annBadge');
            if (badge) {
                const current = parseInt(badge.textContent) || 0;
                badge.textContent = current + 1;
                badge.style.display = 'flex';
            }
        })
        .listen('.report-status-changed', (data) => {
            if (!data || !data.reportId) return;
            if (data.status && data.status !== 'pending' && data.status !== 'in_progress') {
                allIssues = allIssues.filter((r) => r.id !== data.reportId);
                renderIssueMarkers();
                renderIssuesListPanel();
                updateIssuesBadge();
            }
        });

    console.log(`Echo bridge active — driver.${driverId} + reports channels`);
})();
