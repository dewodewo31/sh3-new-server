#!/usr/bin/env bash
# Orchestrates staged load tests against the LOCAL docker environment.
# Usage: ./run.sh [phase ...]   e.g. ./run.sh baseline load10 load25
# Phases: baseline load10 load25 load50 load100 stress spike
set -uo pipefail

cd "$(dirname "$0")"
K6="${K6:-$HOME/.local/bin/k6}"
BASE_URL="${BASE_URL:-http://localhost:8000}"
mkdir -p results

PHASES=("$@")
[ ${#PHASES[@]} -eq 0 ] && PHASES=(baseline load10 load25 load50 load100 stress spike)

echo "Target: $BASE_URL | k6: $($K6 version 2>/dev/null || echo MISSING)"
curl -s -o /dev/null --max-time 5 "$BASE_URL/up" || { echo "ABORT: target not reachable"; exit 1; }

for phase in "${PHASES[@]}"; do
  echo ""
  echo "=============================================================="
  echo " PHASE: $phase"
  echo "=============================================================="

  bash monitor.sh "${MONITOR_SECONDS:-600}" "results/metrics_${phase}.csv" &
  MON_PID=$!

  PROFILE="$phase" BASE_URL="$BASE_URL" "$K6" run \
    --summary-export "results/summary_${phase}.json" \
    k6/stress.js > "results/k6_${phase}.log" 2>&1
  rc=$?

  kill "$MON_PID" 2>/dev/null; wait "$MON_PID" 2>/dev/null

  if [ $rc -ne 0 ]; then
    echo ">>> $phase FAILED (k6 exit=$rc) — thresholds tripped or error. STOPPING all further phases."
    tail -40 "results/k6_${phase}.log"
    exit $rc
  fi
  echo ">>> $phase OK"
done

echo ""
echo "All requested phases completed. Results in loadtest/results/"
