(function () {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function (error) {
                console.warn('CleanCity PWA registration failed:', error);
            });
        });
    }
})();