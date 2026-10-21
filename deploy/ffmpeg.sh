#!/usr/bin/env bash
# Descarga ffmpeg y ffprobe estáticos en bin/ffmpeg/ durante el build de Laravel Cloud,
# donde no se pueden instalar paquetes del sistema. config/media.php los detecta solos.
#
# Si la descarga falla, el despliegue sigue: la app funciona sin ffmpeg
# (los episodios se publican sin procesar).

set -u

case "$(uname -m)" in
    aarch64|arm64) ARCH=arm64 ;;   # Laravel Cloud corre en AWS Graviton (ARM)
    x86_64|amd64)  ARCH=amd64 ;;
    *) echo "ffmpeg: arquitectura $(uname -m) no soportada, se omite"; exit 0 ;;
esac

DEST="$(dirname "$0")/../bin/ffmpeg"
URL="https://johnvansickle.com/ffmpeg/releases/ffmpeg-release-${ARCH}-static.tar.xz"
TMP="$(mktemp -d)"

echo "ffmpeg: descargando build estático ($ARCH)…"
if curl -fsSL --retry 3 "$URL" -o "$TMP/ffmpeg.tar.xz" \
    && tar -xJf "$TMP/ffmpeg.tar.xz" -C "$TMP" --strip-components=1; then
    mkdir -p "$DEST"
    mv "$TMP/ffmpeg" "$TMP/ffprobe" "$DEST/"
    chmod +x "$DEST/ffmpeg" "$DEST/ffprobe"
    "$DEST/ffmpeg" -version | head -n 1
else
    echo "ffmpeg: no se pudo descargar; la app funcionará sin procesar audio"
fi

rm -rf "$TMP"
exit 0
