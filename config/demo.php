<?php

return [

    /*
    | Modo demostración, para la demo pública del portfolio:
    | - límites de subida para que nadie llene el almacenamiento;
    | - botón para entrar con una cuenta de ejemplo sin registrarse;
    | - las cuentas de ejemplo no se pueden modificar ni borrar;
    | - cada noche se borra todo y se vuelven a cargar los datos de ejemplo (demo:reset).
    |
    | ¡Nunca activarlo en una instalación con datos reales! demo:reset borra la base de datos.
    */
    'enabled' => (bool) env('DEMO_MODE', false),

    // Cuenta con la que se entra desde el botón "Probar la demo"
    'login_email' => env('DEMO_LOGIN_EMAIL', 'laura@demo.test'),

    // Las cuentas de ejemplo (DemoSeeder) usan este dominio
    'accounts_domain' => '@demo.test',

    'max_upload_mb' => (int) env('DEMO_MAX_UPLOAD_MB', 25),

    // Cuántos episodios y programas puede crear cada cuenta al día
    'max_episodes_per_day' => (int) env('DEMO_MAX_EPISODES_PER_DAY', 3),
    'max_shows_per_day' => (int) env('DEMO_MAX_SHOWS_PER_DAY', 1),

    // Hora del reinicio diario (zona horaria de la app)
    'reset_at' => env('DEMO_RESET_AT', '04:00'),

];
