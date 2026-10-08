// fetch con el token CSRF de Laravel. Se lee en cada petición porque cambia al iniciar o cerrar sesión.
export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export function csrfFetch(url, options = {}) {
    const headers = {
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
        ...(options.body && !(options.body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}),
        ...(options.headers ?? {}),
    };

    return fetch(url, { credentials: 'same-origin', ...options, headers });
}
