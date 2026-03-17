#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUTPUT_DIR="${1:-$ROOT_DIR/.build/seohost}"
RELEASE_DIR="$OUTPUT_DIR/release"
ARCHIVE_PATH="$OUTPUT_DIR/wspolnota-seohost-release.zip"
INCLUDE_STORAGE_DATA=0

if [ "${1:-}" = "--with-storage-data" ]; then
    INCLUDE_STORAGE_DATA=1
    OUTPUT_DIR="${2:-$ROOT_DIR/.build/seohost}"
    RELEASE_DIR="$OUTPUT_DIR/release"
    ARCHIVE_PATH="$OUTPUT_DIR/wspolnota-seohost-release.zip"
fi

require_command() {
    if ! command -v "$1" >/dev/null 2>&1; then
        echo "Brakuje wymaganego polecenia: $1" >&2
        exit 1
    fi
}

require_command npm
require_command rsync
require_command zip

if [ ! -d "$ROOT_DIR/vendor" ]; then
    echo "Brakuje katalogu vendor/. Uruchom najpierw: composer install" >&2
    exit 1
fi

if [ ! -f "$ROOT_DIR/.env.production.example" ]; then
    echo "Brakuje pliku .env.production.example." >&2
    exit 1
fi

mkdir -p "$OUTPUT_DIR"
rm -rf "$RELEASE_DIR" "$ARCHIVE_PATH"

echo "==> Budowanie assetow Vite"
(cd "$ROOT_DIR" && npm run build)

echo "==> Tworzenie katalogu release"
mkdir -p "$RELEASE_DIR"

RSYNC_ARGS=(
    -a
    --delete
    --exclude='.git/'
    --exclude='.cursor/'
    --exclude='.idea/'
    --exclude='.vscode/'
    --exclude='.env'
    --exclude='.env.production'
    --exclude='.build/'
    --exclude='node_modules/'
    --exclude='tests/'
    --exclude='CLAUDE.md'
    --exclude='GEMINI.md'
    --exclude='codex-rn-api-full.zip'
    --exclude='codex-rn-api-minimum.zip'
    --exclude='storage/framework/cache/*'
    --exclude='storage/framework/sessions/*'
    --exclude='storage/framework/testing/*'
    --exclude='storage/framework/views/*'
    --exclude='storage/logs/*'
    --exclude='bootstrap/cache/*.php'
    --exclude='.DS_Store'
)

if [ "$INCLUDE_STORAGE_DATA" -eq 0 ]; then
    RSYNC_ARGS+=(
        --exclude='storage/app/office/***'
        --exclude='storage/app/public/***'
    )
fi

rsync "${RSYNC_ARGS[@]}" "$ROOT_DIR/" "$RELEASE_DIR/"

mkdir -p \
    "$RELEASE_DIR/storage/app/public" \
    "$RELEASE_DIR/storage/app/office" \
    "$RELEASE_DIR/storage/framework/cache" \
    "$RELEASE_DIR/storage/framework/sessions" \
    "$RELEASE_DIR/storage/framework/testing" \
    "$RELEASE_DIR/storage/framework/views" \
    "$RELEASE_DIR/storage/logs" \
    "$RELEASE_DIR/bootstrap/cache"

touch \
    "$RELEASE_DIR/storage/app/public/.gitignore" \
    "$RELEASE_DIR/storage/app/office/.gitignore" \
    "$RELEASE_DIR/storage/framework/.gitignore" \
    "$RELEASE_DIR/storage/framework/cache/.gitignore" \
    "$RELEASE_DIR/storage/framework/sessions/.gitignore" \
    "$RELEASE_DIR/storage/framework/testing/.gitignore" \
    "$RELEASE_DIR/storage/logs/.gitignore" \
    "$RELEASE_DIR/bootstrap/cache/.gitignore"

(cd "$OUTPUT_DIR" && zip -qr "$(basename "$ARCHIVE_PATH")" release)

echo "==> Gotowe"
echo "Katalog release: $RELEASE_DIR"
echo "Archiwum ZIP:    $ARCHIVE_PATH"
if [ "$INCLUDE_STORAGE_DATA" -eq 0 ]; then
    echo "Tryb:            tylko kod aplikacji (bez danych z storage/app/public i storage/app/office)"
else
    echo "Tryb:            kod aplikacji wraz z danymi z storage/app/public i storage/app/office"
fi
