#!/usr/bin/env python3
"""Summarize k6 summary-export JSONs + monitor CSVs into a compact table."""
import json, sys, glob, csv

def fmt(p):
    try:
        d = json.load(open(f'results/summary_{p}.json'))['metrics']
    except FileNotFoundError:
        return None
    dur = d['http_req_duration']; req = d['http_reqs']; fail = d['http_req_failed']
    vus = d.get('vus_max', {}).get('value', '?')
    err_rate = fail.get('rate', fail.get('value', 0))
    return (f"{p:<10} rps={req['rate']:7.1f}  p50={dur['med']:7.0f}ms  p90={dur.get('p(90)',0):7.0f}ms  "
            f"p95={dur.get('p(95)',0):7.0f}ms  p99={dur.get('p(99)',0):7.0f}ms  max={dur['max']:8.0f}ms  "
            f"err={err_rate*100:5.2f}%  reqs={req['count']:6d}  vus_max={vus}")

phases = sys.argv[1:] or [f.split('summary_')[1].replace('.json','') for f in sorted(glob.glob('results/summary_*.json'))]
for p in phases:
    line = fmt(p)
    if line:
        print(line)

print("\n--- resource peaks per phase (monitor CSV) ---")
for p in phases:
    try:
        rows = list(csv.DictReader(open(f'results/metrics_{p}.csv')))
        if not rows: continue
        app_cpu = max(float(r['app_cpu_pct'] or 0) for r in rows)
        app_mem = max(float(r['app_mem_mb'] or 0) for r in rows)
        my_cpu = max(float(r['mysql_cpu_pct'] or 0) for r in rows)
        conn = max(int(r['db_threads_connected']) if r['db_threads_connected'].isdigit() else 0 for r in rows)
        run_q = max(int(r['db_threads_running']) if r['db_threads_running'].isdigit() else 0 for r in rows)
        slow = max(int(r['db_slow_queries']) if r['db_slow_queries'].isdigit() else 0 for r in rows)
        rc = max(int(r['redis_clients']) if r['redis_clients'].isdigit() else 0 for r in rows)
        rm = max(float(r['redis_usedmem_mb'] or 0) for r in rows)
        load = max(float(r['load1'] or 0) for r in rows)
        print(f"{p:<10} appCPU={app_cpu:5.1f}% appMem={app_mem:6.1f}MB dbCPU={my_cpu:5.1f}% "
              f"dbConn={conn:3d} dbRunning={run_q:2d} slowQ={slow:3d} redisClients={rc:3d} redisMem={rm:5.1f}MB load1={load:.2f}")
    except FileNotFoundError:
        pass
