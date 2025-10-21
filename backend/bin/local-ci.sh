#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")"/.. && pwd)"
cd "$ROOT_DIR"

./bin/coverage-gate.sh
npm run test:e2e:report

php artisan qa:dashboard --limit=5 || true
