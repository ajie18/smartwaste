(function () {
    const excluded = '.tab-btn, .theme-toggle, .close-btn, .back-btn, .map-tool-btn, [data-no-request-lock]';

    document.addEventListener('click', (event) => {
        const button = event.target.closest('button');
        if (!button || button.disabled || button.matches(excluded)) return;

        button.disabled = true;
        button.dataset.requestLocked = 'true';
        window.setTimeout(() => {
            button.disabled = false;
            delete button.dataset.requestLocked;
        }, 5000);
    }, true);
})();
