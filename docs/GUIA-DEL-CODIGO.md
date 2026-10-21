# Guía del código de UploadHub

Un recorrido por cómo funciona la aplicación, pensado para entenderla leyendo el código, no de memoria. Cada capítulo dice **qué archivos abrir**, **qué fijarse** y **por qué está hecho así**. Al final de cada uno hay un **ejercicio** (un cambio pequeño para hacer tú) y **preguntas de entrevista**: si sabes contestarlas con tus palabras, lo has entendido.

Orden recomendado: de arriba abajo. Cada capítulo lleva una o dos horas con el ejercicio incluido.

---

## 1. El camino de una petición

**Idea:** en Laravel, una URL entra por una **ruta**, que llama a un **controlador**, que usa **modelos** para leer la base de datos y devuelve una **vista**.

Sigue la petición `GET /programas/codigo-y-cafe` con estos archivos abiertos:

1. `routes/web.php`: busca `shows.show`. Fíjate en que `{show}` se convierte solo en un objeto `Show`: es el *route model binding*. En `app/Models/Show.php`, `getRouteKeyName()` devuelve `slug`, y por eso se busca por slug y no por id.
2. `app/Http/Controllers/ShowController.php` → método `show()`. Carga el programa, sus episodios visibles y si estás suscrito.
3. `resources/views/shows/show.blade.php`: la vista. Usa componentes como `<x-episode-row>` (en `resources/views/components/`).
4. `resources/views/layouts/app.blade.php`: el esqueleto común (cabecera, pie y reproductor).

**Fíjate:** las rutas `/programas/crear` y `/programas/{show}` chocarían, porque «crear» se tomaría por un programa. Por eso las rutas con `{show}` están **al final** de `web.php`.

**Ejercicio:** añade al pie de página (`resources/views/layouts/footer.blade.php`) un enlace a tu perfil de GitHub. Recarga y comprueba que sale en todas las páginas.

**Preguntas de entrevista**
- ¿Qué es el route model binding y cómo cambias el campo por el que se busca?
- ¿Por qué importa el orden de las rutas?

---

## 2. Los datos: modelos, relaciones y consultas

**Archivos:** `app/Models/Audio.php` (un episodio; la tabla se sigue llamando `audios` por el TFG original), `Show.php`, `User.php` y `database/migrations/`.

**Fíjate en:**
- **Relaciones:** `Audio::user()` (pertenece a), `Show::episodes()` (tiene muchos) y `Audio::likes()` (muchos a muchos, con la tabla intermedia `audio_user_likes`).
- **Scopes** en `Audio.php`: `scopeVisibleTo()` (públicos más los míos), `scopePublic()` y `scopeSearch()`. Se usan como `Audio::visibleTo($user)->search('laravel')`: lógica de consulta reutilizable.
- **El problema N+1:** si en una lista de 20 episodios haces `$episodio->user->name`, Laravel lanza 1 consulta para la lista y 20 más, una por autor. La solución es el *eager loading*: `->with(['user', 'show'])`. En `app/Providers/AppServiceProvider.php`, `Model::preventLazyLoading()` hace que en desarrollo eso **lance un error**, así que no se escapa ninguno.
- **Eventos del modelo:** en `Audio::booted()`, al borrar un episodio se borran también su archivo y su portada. En `User::booted()`, al borrar una cuenta se borran uno a uno sus episodios, para que cada uno limpie sus archivos.

**Ejercicio:** en `ExploreController`, quita `'show'` del `->with([...])` y abre `/explorar`. Verás el error de carga perezosa. Léelo, vuelve a ponerlo y comprueba que desaparece.

**Preguntas de entrevista**
- ¿Qué es el problema N+1 y cómo lo detectas y lo solucionas?
- ¿Para qué sirve un scope?
- ¿Por qué no basta el `cascadeOnDelete` de la base de datos para borrar los archivos?

---

## 3. Quién puede hacer qué: políticas y validación

**Archivos:** `app/Policies/AudioPolicy.php`, `CommentPolicy.php`, `app/Http/Requests/EpisodeRequest.php` y `ShowRequest.php`.

**Fíjate en:**
- **Políticas:** concentran los permisos. `AudioPolicy::view()` devuelve `Response::denyAsNotFound()` en un episodio privado ajeno: responde **404 en vez de 403**, para no revelar siquiera que existe. Los controladores solo llaman a `Gate::authorize('view', $audio)`.
- **`Gate::before`** en `AppServiceProvider`: los administradores pueden borrar cualquier cosa sin repetir esa regla en cada política.
- **FormRequests:** la validación sale del controlador. `EpisodeRequest::after()` añade reglas que no caben en una línea, como «tiene que haber exactamente un origen: archivo, subida por trozos o YouTube».

**Ejercicio:** haz que un comentario solo se pueda borrar en las primeras 24 horas, salvo si eres el autor del episodio. Pista: `CommentPolicy::delete()` y `$comment->created_at`. Añade un test en `tests/Feature/EpisodeTest.php`.

**Preguntas de entrevista**
- ¿Dónde pondrías una regla de permisos y por qué no en el controlador?
- ¿Por qué un 404 es mejor que un 403 para un recurso privado?

---

## 4. Subir un episodio, de principio a fin

La pieza más completa: toca JavaScript, controladores, un servicio y una cola. Síguela en este orden:

1. **Navegador** (`resources/js/uploader.js`): al elegir el archivo, `upload()` lo parte en trozos de 5 MB (`file.slice`) y los envía uno a uno. Si uno falla, `sendChunk()` lo reintenta hasta 3 veces. La barra de progreso es `received / size`.
2. **Servidor** (`app/Http/Controllers/UploadController.php` y `app/Services/ChunkedUpload.php`):
   - `start()` crea un token (UUID) y un archivo `.part` vacío;
   - `append()` añade cada trozo, comprobando que llega en orden (`offset`);
   - el estado de la subida se guarda en la caché.
3. **Publicar** (`EpisodeController::store()`): el formulario envía los datos y el token. `ChunkedUpload::claim()` comprueba el **contenido real** del archivo (`finfo`) y lo mueve al disco de medios. Un HTML con extensión `.mp3` se rechaza.
4. **Segundo plano** (`app/Jobs/ProcessAudio.php`, que usa `app/Services/AudioProcessor.php`): la cola ejecuta ffmpeg para leer la duración, convertir a MP3 con el volumen normalizado y calcular la forma de onda. El usuario no espera: el episodio ya está publicado.

**Por qué por trozos:** PHP limita el tamaño de cada petición (`upload_max_filesize`). Por trozos se sube un archivo de 500 MB aunque el límite sea de 40 MB, se ve el progreso real y un corte de conexión solo obliga a repetir un trozo.

**Ejercicio:** cambia `MEDIA_CHUNK_MB` a 1 en tu `.env`. Sube un archivo de unos 5 MB y, en las herramientas de desarrollo del navegador (pestaña Red), cuenta las peticiones a `/subidas/...`.

**Preguntas de entrevista**
- ¿Por qué el procesado va en una cola y no en la propia petición?
- ¿Cómo evitas que alguien suba un archivo malicioso con extensión de audio?
- ¿Qué pasa si se repite un trozo por un reintento?

---

## 5. Servir el audio con seguridad

**Archivos:** `EpisodeController::stream()` y `download()`, y `app/Support/Media.php`.

**Fíjate en:**
- Los audios están en un **disco privado**: no hay URL directa. Todo pasa por `/episodios/{id}/stream`, que comprueba los permisos antes.
- `response()->file()` admite cabeceras **Range**, por eso el reproductor puede saltar al minuto 30 sin descargar lo anterior. El test `el stream admite saltar (Range)` lo comprueba.
- El `Content-Type` se deduce **del propio archivo**, nunca de lo que dijo el navegador al subirlo, y se envía con `X-Content-Type-Options: nosniff`. Si no, un MP3 manipulado declarado como `text/html` podría ejecutar JavaScript en tu dominio (XSS almacenado).
- En producción (S3/R2) la ruta responde con una **URL firmada temporal** (`temporaryUrl`), válida 30 minutos.

**Preguntas de entrevista**
- ¿Qué es una URL firmada y para qué sirve?
- ¿Qué es `nosniff` y qué ataque evita?

---

## 6. El reproductor que no se corta

**Archivos:** `resources/js/player.js`, `resources/views/layouts/player.blade.php`, `resources/js/app.js` y `app/Http/Controllers/PlayController.php`.

**Fíjate en:**
- **Turbo** (`app.js`) intercepta los clics en enlaces: pide la página nueva por `fetch` y cambia el `<body>` sin recargar. Así el JavaScript y el audio siguen vivos.
- El reproductor tiene `data-turbo-permanent`: Turbo **no lo sustituye** al cambiar de página, mueve el mismo elemento al body nuevo.
- El estado (qué suena, la cola, la velocidad) está en un **store de Alpine** (`Alpine.store('player')`). Cualquier botón de cualquier página llama a `$store.player.play(episodio)`.
- **Escuchas:** al darle al play, `registerPlay()` avisa al servidor. Cada 15 s, `sendBeat()` envía la posición, y de ahí salen el «continuar donde lo dejaste» y la curva de retención. El oyente se identifica con un hash, sin guardar datos personales.
- **Un fallo real que hubo que arreglar:** el adaptador de Alpine para Turbo volvía a poner `x-cloak` en el reproductor antes de guardar la página en caché y la barra desaparecía. Mira el listener `turbo:before-cache` en `player.js`.

**Ejercicio:** añade 0,5× a las velocidades disponibles (constante `SPEEDS` en `player.js`). Ejecuta `npm run dev` y pruébalo.

**Preguntas de entrevista**
- ¿Cómo consigues que el audio no se corte al navegar en una app que no es una SPA?
- ¿Por qué no contar una escucha nueva si se le vuelve a dar al play antes de 30 minutos?

---

## 7. Estadísticas y gráficas

**Archivos:** `app/Http/Controllers/StatsController.php`, `resources/views/components/charts/` y `app/Support/ChartScale.php`.

**Fíjate en:**
- Las cifras se calculan **en SQL**, no en PHP: `COUNT(DISTINCT listener_hash)` para los oyentes y `AVG(CASE …)` para el porcentaje escuchado. El `CASE` está escrito para que funcione igual en SQLite y en MySQL.
- **Curva de retención** (`retention()`): para cada 5 % del episodio cuenta cuántas escuchas llegaron hasta ahí, con un `SUM(CASE WHEN max_position >= …)` por punto, todo en una sola consulta.
- Las gráficas son **SVG generado en el servidor** (Blade), sin librerías de JavaScript. Alpine solo añade el tooltip.

**Ejercicio:** añade la tarjeta «Escuchas por oyente» (escuchas ÷ oyentes únicos) en `resources/views/stats/index.blade.php`. Ojo con dividir entre cero.

---

## 8. Programas, RSS, API y administración

- **RSS** (`FeedController` y `resources/views/feeds/show.blade.php`): un XML con las etiquetas `itunes:*` que piden Apple y Spotify. Solo incluye episodios públicos con archivo, porque los de YouTube no se pueden incluir en un feed. El test comprueba que el XML es válido.
- **API** (`routes/api.php`, `app/Http/Controllers/Api/V1/` y `app/Http/Resources/`):
  - se autentica con **tokens de Sanctum**;
  - los *API Resources* deciden qué campos salen: el correo solo se le devuelve a su dueño;
  - `UseSanctumGuard` permite que una ruta pública reconozca el token si se envía.
- **Administración** (`app/Http/Controllers/Admin/`):
  - el middleware `admin` protege todo el panel;
  - `EnsureUserIsNotBanned` cierra la sesión de una cuenta bloqueada en su siguiente petición;
  - las denuncias usan una relación **polimórfica** (`reportable`), que puede apuntar a un episodio, un programa o un comentario.

**Preguntas de entrevista**
- ¿Qué es una relación polimórfica y cuándo la usarías?
- ¿Cómo versionarías una API?

---

## 9. Tests y CI

**Archivos:** `tests/Pest.php`, `tests/Feature/` y `.github/workflows/tests.yml`.

**Fíjate en:**
- `Pest.php` prepara cada test con una base de datos limpia (`RefreshDatabase`), **discos falsos** (`Storage::fake`) y roles y categorías ya creados.
- Las factories (`database/factories/`) crean datos de prueba, por ejemplo `Audio::factory()->withFile()->private()->create()`.
- Los servicios externos se sustituyen por **dobles**: `Http::fake()` para la API de OpenAI y clases anónimas que implementan `Transcriber` y `Summarizer`.
- `PagesTest` recorre todas las páginas con los datos de demo: si alguien introduce un N+1, falla.
- El CI ejecuta todo con **SQLite y con MySQL 8.4**, porque hay cosas, como algunos índices, que solo fallan en MySQL.

**Ejercicio:** escribe un test que compruebe que un usuario no puede suscribirse a un programa sin iniciar sesión. Ejecútalo con `php artisan test --filter=suscribirse`.

---

## 10. Despliegue y modo demo

**Archivos:** `docs/DESPLIEGUE.md`, `config/demo.php`, `app/Support/Demo.php`, `deploy/ffmpeg.sh` y el comando `demo:reset` en `routes/console.php`.

**Fíjate en:**
- Todo lo que cambia entre local y producción está en **variables de entorno** (`config/media.php`, `config/ai.php`), no en el código.
- El modo demo:
  - limita las subidas y protege las cuentas compartidas;
  - `demo:reset` vacía las tablas **de la base de datos de la app**. En MySQL, `Schema::getTables()` lista las de todo el servidor, y por eso se filtra por esquema; ese fallo se encontró al probar con MariaDB.
- `deploy/ffmpeg.sh` descarga ffmpeg durante el build, porque la plataforma no deja instalar paquetes del sistema.

**Preguntas de entrevista**
- ¿Cómo gestionas la configuración entre entornos?
- ¿Qué harías si un trabajo de la cola tarda más de lo que permite la plataforma?
