#!/usr/bin/env bash
# Samples app/DB/Redis/host metrics during load tests.
# Usage: ./monitor.sh <duration_seconds> <output_csv>
set -euo pipefail

DURATION="${1:-300}"
OUT="${2:-results/metrics.csv}"

MYSQL_C="sh3-mysql"
REDIS_C="sh3-redis"
APP_C="sh3-app"

echo "ts,app_cpu_pct,app_mem_mb,mysql_cpu_pct,mysql_mem_mb,redis_cpu_pct,redis_mem_mb,load1,host_mem_used_mb,db_threads_connected,db_threads_running,db_slow_queries,db_questions,redis_clients,redis_usedmem_mb" > "$OUT"

end=$((SECONDS + DURATION))
while [ "$SECONDS" -lt "$end" ]; do
  ts=$(date +%s)

  stats=$(docker stats --no-stream --format '{{.Name}} {{.CPUPerc}} {{.MemUsage}}' \
    "$APP_C" "$MYSQL_C" "$REDIS_C" 2>/dev/null || echo "")

  app_cpu=""; app_mem=""
  my_cpu=""; my_mem=""
  rd_cpu=""; rd_mem=""
  while read -r name cpu mem; do
    [ -z "${name:-}" ] && continue
    mem_mb=$(echo "$mem" | awk -F'/' '{gsub(/[ MiBGiB]/,"",$1); print $1}')
    unit=$(echo "$mem" | awk -F'/' '{print $1}' | grep -oE '[MG]iB' || true)
    case "$unit" in GiB) mem_mb=$(awk "BEGIN{print $mem_mb*1024}");; esac
    case "$name" in
      "$APP_C")    app_cpu=$(echo "$cpu" | tr -d '%'); app_mem=$mem_mb;;
      "$MYSQL_C")  my_cpu=$(echo "$cpu" | tr -d '%');  my_mem=$mem_mb;;
      "$REDIS_C")  rd_cpu=$(echo "$cpu" | tr -d '%');  rd_mem=$mem_mb;;
    esac
  done <<< "$stats"

  load1=$(cut -d' ' -f1 /proc/loadavg)
  host_mem=$(free -m | awk '/^Mem:/{print $3}')

  db=$(docker exec "$MYSQL_C" sh -c 'mysqladmin -uroot -p"$MYSQL_ROOT_PASSWORD" extended-status 2>/dev/null' || true)
  db_val() { echo "$db" | awk -v k="$1" -F'|' '{gsub(/ /,"",$2); if ($2==k){gsub(/ /,"",$3); print $3; exit}}'; }
  threads_conn=$(db_val Threads_connected)
  threads_run=$(db_val Threads_running)
  slow_q=$(db_val Slow_queries)
  questions=$(db_val Questions)

  rinfo=$(docker exec "$REDIS_C" redis-cli info clients 2>/dev/null; docker exec "$REDIS_C" redis-cli info memory 2>/dev/null || true)
  redis_clients=$(echo "$rinfo" | awk -F: '/^connected_clients/{gsub("\r",""); print $2; exit}')
  redis_mem=$(echo "$rinfo" | awk -F: '/^used_memory:/{gsub("\r",""); printf "%.1f", $2/1048576; exit}')

  echo "$ts,${app_cpu:-0},${app_mem:-0},${my_cpu:-0},${my_mem:-0},${rd_cpu:-0},${rd_mem:-0},$load1,$host_mem,${threads_conn:-NA},${threads_run:-NA},${slow_q:-NA},${questions:-NA},${redis_clients:-NA},${redis_mem:-NA}" >> "$OUT"
  sleep 2
done
echo "monitoring done -> $OUT"
