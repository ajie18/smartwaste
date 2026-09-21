(function () {
    if (typeof Echo === 'undefined' || typeof window.__reverbConfig === 'undefined') {
        console.warn('Echo bridge: laravel-echo or reverb config missing — resident dashboard stays on REST polling only.');
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

    Echo.connector.pusher.connection.bind('error', (err) => {
        console.error('Echo connection error:', err);
    });

    Echo.private('reports')
        .listen('.truck-location', (data) => {
            if (typeof currentTruckId !== 'undefined' && data.truckId === currentTruckId) {
                if (typeof placeTruckMarker === 'function') {
                    placeTruckMarker(data.latitude, data.longitude, true);
                }
            }
        })
        .listen('.new-announcement', () => {
            if (typeof loadAnnouncements === 'function') loadAnnouncements();
            if (typeof showToast === 'function') showToast('New announcement posted', 'info');
        })
        .listen('.assignment-public-status', () => {

            if (typeof loadTodayTruck === 'function') loadTodayTruck();
        });

    console.log('Echo bridge active (resident) — reports channel');
})();