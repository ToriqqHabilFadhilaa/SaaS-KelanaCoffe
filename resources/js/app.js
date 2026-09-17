import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);

        if (!input) {
            return;
        }

        const showIcon = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        `;

        const hideIcon = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 3l18 18"/>
                <path d="M10.58 10.58A2 2 0 0 0 13.42 13.42"/>
                <path d="M9.88 5.08A10.94 10.94 0 0 1 12 5c6.5 0 10 7 10 7a17.5 17.5 0 0 1-5.13 6.13"/>
                <path d="M6.61 6.61A17.9 17.9 0 0 0 2 12s3.5 7 10 7a11.55 11.55 0 0 0 5.39-1.61"/>
            </svg>
        `;

        const setVisible = (visible) => {
            input.type = visible ? 'text' : 'password';
            button.setAttribute('aria-label', visible ? 'Sembunyikan password' : 'Lihat password');
            button.setAttribute('title', visible ? 'Sembunyikan password' : 'Lihat password');
            button.innerHTML = visible ? hideIcon : showIcon;
            button.setAttribute('aria-pressed', visible ? 'true' : 'false');
        };

        setVisible(false);

        button.addEventListener('click', () => {
            const isVisible = input.type === 'text';
            setVisible(!isVisible);
        });
    });
});
