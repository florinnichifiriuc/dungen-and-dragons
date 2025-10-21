#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")"/.. && pwd)"
cd "$ROOT_DIR"

STORAGE_BASE="$ROOT_DIR/storage/qa/coverage"
HTML_DIR="$STORAGE_BASE/html"
CLOVER_FILE="$STORAGE_BASE/clover.xml"
LATEST_JSON="$STORAGE_BASE/latest.json"
HISTORY_FILE="$STORAGE_BASE/history.jsonl"
THRESHOLD=80

mkdir -p "$HTML_DIR"

run_tests() {
  set +e
  php artisan test --coverage --min="$THRESHOLD" --coverage-html="$HTML_DIR" --coverage-clover="$CLOVER_FILE"
  local exit_code=$?
  set -e
  return "$exit_code"
}

parse_coverage() {
  if [[ ! -f "$CLOVER_FILE" ]]; then
    echo "0"
    return
  fi

  php -r '
    $file = $argv[1];
    if (!file_exists($file)) {
        echo "0";
        return;
    }
    $xml = simplexml_load_file($file);
    if (!$xml) {
        echo "0";
        return;
    }
    $metrics = $xml->xpath("//metrics");
    if (!$metrics || !isset($metrics[0]["statements"]) || (float)$metrics[0]["statements"] === 0.0) {
        echo "0";
        return;
    }
    $covered = (float)$metrics[0]["coveredstatements"];
    $statements = (float)$metrics[0]["statements"];
    $percentage = $statements > 0 ? ($covered / $statements) * 100 : 0;
    echo number_format($percentage, 2, '.', '');
  ' "$CLOVER_FILE"
}

record_history() {
  local status="$1"
  local percentage="$2"
  local timestamp
  timestamp="$(date -u +"%Y-%m-%dT%H:%M:%SZ")"

  cat <<JSON > "$LATEST_JSON"
{
  "timestamp": "${timestamp}",
  "status": "${status}",
  "percentage": ${percentage},
  "threshold": ${THRESHOLD}
}
JSON

  echo "{\"timestamp\":\"${timestamp}\",\"status\":\"${status}\",\"percentage\":${percentage},\"threshold\":${THRESHOLD}}" >> "$HISTORY_FILE"
}

run_tests
exit_code=$?
coverage_value="$(parse_coverage)"

if [[ "$coverage_value" == "" ]]; then
  coverage_value="0"
fi

status="failed"
if [[ "$exit_code" -eq 0 ]]; then
  status="passed"
fi

record_history "$status" "$coverage_value"

echo "Coverage ${status} – ${coverage_value}% (threshold ${THRESHOLD}%). Reports: ${HTML_DIR}"

exit "$exit_code"
