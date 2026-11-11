#!/bin/bash
#
# Builds plg_system_pinprotect.zip and the release files in build/dist/pinprotect/.
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUILD="$ROOT/build"
PLG="$ROOT/plg_system_pinprotect"
EXCLUDES=(-x "*.DS_Store" -x "__MACOSX/*")

VERSION="$(sed -n 's:.*<version>\(.*\)</version>.*:\1:p' "$PLG/pinprotect.xml" | head -1)"

echo "==> Checking PHP syntax"
find "$PLG" -name "*.php" -print0 | xargs -0 -n1 php -l > /dev/null

echo "==> Building plg_system_pinprotect.zip ($VERSION)"
rm -f "$BUILD/plg_system_pinprotect.zip"
(cd "$PLG" && zip -r -q -X "$BUILD/plg_system_pinprotect.zip" . "${EXCLUDES[@]}")

php "$BUILD/make-update.php" "$BUILD/plg_system_pinprotect.zip"
