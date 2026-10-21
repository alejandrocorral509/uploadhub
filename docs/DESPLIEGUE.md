# Desplegar la demo en Laravel Cloud

Guía paso a paso para publicar UploadHub como demo pública en [Laravel Cloud](https://cloud.laravel.com). Plan **Starter**: 5 $/mes con 5 $ de uso incluidos (el primer mes es gratis). El plan admite **aplicaciones ilimitadas**, así que otros proyectos se pueden añadir a la misma cuenta y cada uno solo gasta lo que consume.

## Cómo queda montado

| Pieza | Recurso de Laravel Cloud | Para qué |
|---|---|---|
| La web | App compute **Flex** con Scale-to-Zero | Se duerme cuando no hay visitas y despierta en menos de un segundo |
| Base de datos | **Laravel MySQL** Flex | Laravel Cloud no admite SQLite |
| Audios | Bucket **privado** (Laravel Object Storage, sobre Cloudflare R2), disco `media` | Los audios solo se sirven con URLs firmadas tras comprobar permisos |
| Portadas | Bucket **público**, disco `covers` | Imágenes accesibles por URL |
| Procesado de audio | **Managed queue** Flex | Despierta solo cuando hay trabajos |
| Reinicio nocturno de la demo | **Task scheduler** | Ejecuta `demo:reset` cada noche |

## 1. Cuenta y aplicación

1. Crea la cuenta en <https://cloud.laravel.com> con el plan **Starter**.
2. Conecta tu cuenta de GitHub y dale acceso al repositorio `uploadhub`.
3. **New application** → elige el repositorio, la rama `master` y una región de Europa (por ejemplo, Frankfurt).
4. En **Settings → Spending limits** pon un límite de gasto, por ejemplo 10 $, para que nunca haya sorpresas.

## 2. Recursos (en el lienzo del entorno)

1. **Add database** → **Laravel MySQL**, tamaño Flex más pequeño, con Scale-to-Zero activado. Las variables `DB_*` se configuran solas.
2. **Add bucket** → **Laravel Object Storage**:
   - Primer bucket: nombre `uploadhub-media`, visibilidad **privada**, disk name **`media`** y márcalo como disco por defecto.
   - Segundo bucket: nombre `uploadhub-covers`, visibilidad **pública**, disk name **`covers`**. Copia su **URL pública** de la página de ajustes del bucket; la necesitarás en el paso 4.
3. **Add compute → Managed queue**: tipo estándar, compute **Flex**, memoria **1 GiB** (da 512 MiB de disco temporal para procesar el audio) y entre 0 y 2 workers.
4. En el compute de la app, activa **Scale-to-Zero** y el **Task scheduler**.

## 3. Build y deploy

En **Settings → Deployments**:

**Build commands**

```bash
composer install --no-dev --optimize-autoloader --no-interaction
bash deploy/ffmpeg.sh
npm ci --audit false
npm run build
```

`deploy/ffmpeg.sh` descarga un ffmpeg estático para ARM, porque Laravel Cloud no permite instalar paquetes del sistema. Si fallase, el despliegue sigue y los episodios se publican sin procesar.

**Deploy commands**

```bash
php artisan migrate --force
```

## 4. Variables de entorno

En **Settings → Environment variables** (`APP_KEY` y las de la base de datos y los buckets las pone Laravel Cloud):

```dotenv
APP_NAME=UploadHub
APP_ENV=production
APP_DEBUG=false
APP_LOCALE=es
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=es_ES
SESSION_SECURE_COOKIE=true

# Archivos: los nombres de disco del paso 2
MEDIA_DISK=media
COVERS_DISK=covers
COVERS_PUBLIC_URL=https://...    # la URL pública del bucket de portadas

# Las colas Flex cortan los trabajos a los 90 s
MEDIA_JOB_TIMEOUT=85
MEDIA_MAX_UPLOAD_MB=25

# Demo pública
DEMO_MODE=true
DEMO_PASSWORD=una-contraseña-inventada
ADMIN_NAME=Alejandro
ADMIN_EMAIL=tu-correo@ejemplo.com
ADMIN_PASSWORD=una-contraseña-larga-y-única

# IA: en preparación, desactivada
AI_TRANSCRIPTION_DRIVER=none
AI_SUMMARY_DRIVER=none

MAIL_MAILER=log
```

Pon `ADMIN_PASSWORD` y `DEMO_PASSWORD` como **secretos**: Laravel Cloud los oculta en el panel.

## 5. Primer despliegue

1. Pulsa **Deploy**.
2. Cuando termine, en la pestaña **Commands** ejecuta, una sola vez:

   ```bash
   php artisan db:seed --force
   php artisan db:seed --class=DemoSeeder --force
   ```

   El primero crea los roles, las categorías y tu administrador; el segundo, los datos de ejemplo.

## 6. Comprobar que todo funciona

- [ ] La portada carga y el aviso de demo aparece arriba.
- [ ] «Entrar como creadora de ejemplo» entra en la cuenta de Laura.
- [ ] Un episodio se reproduce y el audio no se corta al navegar.
- [ ] Las portadas se ven. Si no, revisa `COVERS_PUBLIC_URL`.
- [ ] Al subir un MP3 corto, en **Monitoring → Queues** aparece el trabajo `ProcessAudio` y termina bien. Si falla por ffmpeg, mira el log del build. Como último recurso, pon `MEDIA_PROCESSING=false`.
- [ ] El feed RSS de un programa abre (`/programas/codigo-y-cafe/feed.xml`).
- [ ] `/admin` funciona con tu cuenta de administrador.

## Coste esperado

Con Scale-to-Zero en la web y en la base de datos, una demo con pocas visitas gasta céntimos al mes en compute, base de datos, almacenamiento y cola. El total debería quedarse en los **5 $ del plan**, porque el uso entra en el crédito incluido.

Un detalle: mientras la app está dormida, el programador de tareas no se ejecuta, así que el reinicio nocturno solo ocurre si la app está despierta. Si nadie ha visitado la demo, no hay nada que reiniciar.

## Añadir más proyectos

Cada proyecto es una **aplicación** nueva en la misma organización: repite los pasos 1 a 5 con su repositorio. El plan Starter no limita el número de aplicaciones; cada una suma su propio uso al crédito de 5 $.
