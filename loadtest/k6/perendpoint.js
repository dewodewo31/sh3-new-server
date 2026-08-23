import http from 'k6/http';
import { Trend } from 'k6/metrics';

// Measures intrinsic per-endpoint service time: 1 VU, sequential, fixed
// iterations per endpoint. No queueing -> ranking reflects processing cost.
const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const ITERS = Number(__ENV.ITERS || 40);

export const options = {
  vus: 1,
  duration: `${ITERS * 15 / 10}s`, // loose upper bound; script self-limits below
  discardResponseBodies: true,
};

const ENDPOINTS = [
  ['health', '/up'],
  ['events_list', '/api/v1/events'],
  ['events_upcoming', '/api/v1/events/upcoming'],
  ['event_detail', '/api/v1/events/4'],
  ['event_participants', '/api/v1/events/4/participants'],
  ['galleries', '/api/v1/galleries'],
  ['albums_list', '/api/v1/gallery-albums'],
  ['album_detail', '/api/v1/gallery-albums/7'],
  ['categories', '/api/v1/categories'],
  ['merch_list', '/api/v1/merchandise'],
  ['merch_detail', '/api/v1/merchandise/1'],
  ['sponsors', '/api/v1/sponsors'],
  ['org_index', '/api/v1/organization'],
  ['org_stats', '/api/v1/organization/stats'],
  ['org_tree', '/api/v1/organization/tree'],
];

const trends = {};
for (const [name] of ENDPOINTS) trends[name] = new Trend(`t_${name}`, true);

export default function () {
  for (const [name, path] of ENDPOINTS) {
    const res = http.get(`${BASE_URL}${path}`, { timeout: '30s' });
    trends[name].add(res.timings.duration);
    if (res.status !== 200) console.error(`${name} -> ${res.status}`);
  }
}
