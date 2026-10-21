#!/usr/bin/env bash
# Descarga ffmpeg y ffprobe estáticos en bin/ffmpeg/ durante el build de Laravel Cloud,
# donde no se pueden instalar paquetes del sistema. config/media.php los detecta solos.
#
# Origen: builds estáticos de BtbN/FFmpeg-Builds publicados en GitHub (rama estable 8.1, LGPL).
# Si la descarga falla o tarda demasiado, el despliegue sigue: la app funciona sin ffmpeg
# (los episodios se publican sin procesar).

set -u

VERSION="8.1"

case "$(uname -m)" in
    aarch64|arm64) ARCH=linuxarm64 ;;   # Laravel Cloud corre en AWS Graviton (ARM)
    x86_64|amd64)  ARCH=linux64 ;;
    *) echo "ffmpeg: arquitectura $(uname -m) no soportada, se omite"; exit 0 ;;
esac

NAME="ffmpeg-n${VERSION}-latest-${ARCH}-lgpl-${VERSION}"
URL="https://github.com/BtbN/FFmpeg-Builds/releases/download/latest/${NAME}.tar.xz"
DEST="$(cd "$(dirname "$0")/.." && pwd)/bin/ffmpeg"
TMP="$(mktemp -d)"

echo "ffmpeg: descargando ${NAME}…"
# Máximo 5 minutos: nunca bloquear el despliegue por una descarga lenta
if curl -fsSL --retry 2 --connect-timeout 20 --max-time 300 "$URL" -o "$TMP/ffmpeg.tar.xz" \
    && tar -xJf "$TMP/ffmpeg.tar.xz" -C "$TMP" "${NAME}/bin/ffmpeg" "${NAME}/bin/ffprobe"; then
    mkdir -p "$DEST"
    mv "$TMP/${NAME}/bin/ffmpeg" "$TMP/${NAME}/bin/ffprobe" "$DEST/"
    chmod +x "$DEST/ffmpeg" "$DEST/ffprobe"
    "$DEST/ffmpeg" -hide_banner -version | head -n 1
else
    echo "ffmpeg: no se pudo descargar; la app funcionará sin procesar audio"
fi

rm -rf "$TMP"
exit 0
