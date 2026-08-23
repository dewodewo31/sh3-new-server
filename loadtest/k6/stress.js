import http from 'k6/http';
import { check } from 'k6';
import { Counter } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const PROFILE = __ENV.PROFILE || 'baseline';

const unexpectedStatus = new Counter('app_unexpected_status');

// ---- Scenario profiles -----------------------------------------------------
// Ramps are gradual by design; thresholds below auto-abort on degradation.
const PROFILES = {
  // Baseline: measure healthy response time & error floor
  baseline: {
    executor: 'ramping-vus',
    stages: [
      { duration: '20s', target: 1 },
      { duration: '40s', target: 3 },
      { duration: '10s', target: 0 },
    ],
  },
  load10:   { executor: 'constant-vus', vus: 10,  duration: '60s' },
  load25:   { executor: 'constant-vus', vus: 25,  duration: '60s' },
  load50:   { executor: 'constant-vus', vus: 50,  duration: '90s' },
  load100:  { executor: 'constant-vus', vus: 100, duration: '90s' },
  // Stress: gradual ramp to 200 VUs; aborts early if thresholds trip
  stress: {
    executor: 'ramping-vus',
    stages: [
      { duration: '45s', target: 25 },
      { duration: '45s', target: 50 },
      { duration: '45s', target: 100 },
      { duration: '45s', target: 150 },
      { duration: '45s', target: 200 },
      { duration: '90s', target: 200 },
      { duration: '15s', target: 0 },
    ],
    gracefulStop: '15s',
  },
  // Spike: sudden 10 -> 100 -> 10
  spike: {
    executor: 'ramping-vus',
    stages: [
      { duration: '30s', target: 10 },
      { duration: '15s', target: 100 },
      { duration: '120s', target: 100 },
      { duration: '15s', target: 10 },
      { duration: '45s', target: 10 },
      { duration: '10s', target: 0 },
    ],
    gracefulStop: '15s',
  },
};

export const options = {
  scenarios: {
    public_read: Object.assign({ exec: 'mixedTraffic', gracefulStop: '10s' }, PROFILES[PROFILE]),
  },
  // Safety rails: run stops automatically if these are violated
  thresholds: {
    'http_req_failed':   [{ threshold: 'rate<0.05', abortOnFail: true, delayAbortEval: '10s' }],
    'http_req_duration': [{ threshold: 'p(95)<15000', abortOnFail: true, delayAbortEval: '10s' }],
    'checks':            ['rate>0.95'],
  },
  discardResponseBodies: true,
  summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)'],
  tags: { profile: PROFILE },
};

// ---- Test data discovery ----------------------------------------------------
export function setup() {
  const ids = { events: [1], albums: [1], merch: [1] };
  try {
    const res = http.get(`${BASE_URL}/api/v1/events`);
    if (res.status === 200) {
      const arr = res.json('data') || [];
      if (arr.length) ids.events = arr.map((e) => e.id);
    }
    const alb = http.get(`${BASE_URL}/api/v1/gallery-albums`);
    if (alb.status === 200) {
      const arr = alb.json('data') || [];
      if (arr.length) ids.albums = arr.map((a) => a.id);
    }
    const mer = http.get(`${BASE_URL}/api/v1/merchandise`);
    if (mer.status === 200) {
      const arr = mer.json('data') || [];
      if (arr.length) ids.merch = arr.map((m) => m.id);
    }
  } catch (e) {
    console.error(`setup discovery failed, using fallback ids: ${e}`);
  }
  console.log(`setup: ${ids.events.length} events, ${ids.albums.length} albums, ${ids.merch.length} merch items`);
  return ids;
}

// ---- Weighted read-only traffic mix -----------------------------------------
// READ-ONLY public GET endpoints only. No POST/PUT/PATCH/DELETE anywhere.
const REQ_TIMEOUT = '30s';

function get(path, name) {
  return http.get(`${BASE_URL}${path}`, { tags: { name }, timeout: REQ_TIMEOUT });
}

function pick(arr) { return arr[Math.floor(Math.random() * arr.length)]; }

export function mixedTraffic(data) {
  const r = Math.random() * 100;
  let res;

  if (r < 5)        res = get('/up', 'health');
  else if (r < 15)  res = get('/api/v1/events', 'events_list');            // Redis-cached 60s
  else if (r < 25)  res = get('/api/v1/events/upcoming', 'events_upcoming'); // DB
  else if (r < 40)  res = get(`/api/v1/events/${pick(data.events)}`, 'event_detail'); // DB heavy eager-load
  else if (r < 50)  res = get(`/api/v1/events/${pick(data.events)}/participants`, 'event_participants'); // DB
  else if (r < 58)  res = get('/api/v1/galleries', 'galleries');           // DB
  else if (r < 63)  res = get('/api/v1/gallery-albums', 'albums_list');    // DB
  else if (r < 70)  res = get(`/api/v1/gallery-albums/${pick(data.albums)}`, 'album_detail'); // DB
  else if (r < 75)  res = get('/api/v1/categories', 'categories');         // Redis-cached 3600s
  else if (r < 80)  res = get('/api/v1/merchandise', 'merch_list');        // DB
  else if (r < 83)  res = get(`/api/v1/merchandise/${pick(data.merch)}`, 'merch_detail'); // DB
  else if (r < 88)  res = get('/api/v1/sponsors', 'sponsors');             // DB
  else if (r < 93)  res = get('/api/v1/organization', 'org_index');        // DB search
  else if (r < 97)  res = get('/api/v1/organization/stats', 'org_stats');  // DB aggregate
  else              res = get('/api/v1/organization/tree', 'org_tree');    // Redis-cached 3600s

  const ok = check(res, { 'status 200': (resp) => resp.status === 200 });
  if (!ok) unexpectedStatus.add(1);
}
