import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://localhost';
const SHARE_TOKEN = __ENV.SHARE_TOKEN;
const GROUP_ID = __ENV.GROUP_ID;
const SHARE_ID = __ENV.SHARE_ID;
const MAP_TOKEN_ID = __ENV.MAP_TOKEN_ID;
const CONDITION_KEY = __ENV.CONDITION_KEY;
const SUMMARY_GENERATED_AT = __ENV.SUMMARY_GENERATED_AT;
const SESSION_COOKIE = __ENV.SESSION_COOKIE;
const XSRF_TOKEN = __ENV.XSRF_TOKEN;
const INERTIA_VERSION = __ENV.INERTIA_VERSION || 'transparency-load-test';

if (!SHARE_TOKEN) {
  throw new Error('Set SHARE_TOKEN to the public condition timer summary token before running the load test.');
}

export const options = {
  scenarios: {
    transparency_journey: {
      executor: 'constant-vus',
      vus: Number(__ENV.VUS || 10),
      duration: __ENV.DURATION || '2m',
      exec: 'runTransparencyJourney',
    },
  },
  thresholds: {
    'http_req_duration{journey:share}': ['p(95)<500'],
    'http_req_duration{journey:ack}': ['p(95)<750'],
    'http_req_duration{journey:extend}': ['p(95)<1000'],
    http_req_failed: ['rate<0.01'],
  },
};

function authenticatedHeaders(additional = {}) {
  const headers = {
    ...additional,
  };

  if (SESSION_COOKIE) {
    headers.Cookie = `laravel_session=${SESSION_COOKIE}`;
  }

  if (XSRF_TOKEN) {
    headers['X-XSRF-TOKEN'] = XSRF_TOKEN;
    headers['X-CSRF-TOKEN'] = XSRF_TOKEN;
  }

  return headers;
}

export function runTransparencyJourney() {
  const viewHeaders = {
    'X-Inertia': 'true',
    'X-Inertia-Version': INERTIA_VERSION,
    ...authenticatedHeaders(),
  };

  const shareResponse = http.get(`${BASE_URL}/shares/condition-timers/${SHARE_TOKEN}`, {
    headers: viewHeaders,
    tags: { journey: 'share', step: 'view' },
  });

  check(shareResponse, {
    'share view succeeded': (res) => res.status === 200,
  });

  sleep(1 + Math.random());

  if (GROUP_ID && MAP_TOKEN_ID && CONDITION_KEY && SUMMARY_GENERATED_AT) {
    const acknowledgementResponse = http.post(
      `${BASE_URL}/groups/${GROUP_ID}/condition-timers/acknowledgements`,
      JSON.stringify({
        map_token_id: Number(MAP_TOKEN_ID),
        condition_key: CONDITION_KEY,
        summary_generated_at: SUMMARY_GENERATED_AT,
        source: 'offline',
        queued_at: new Date().toISOString(),
      }),
      {
        headers: authenticatedHeaders({
          'Content-Type': 'application/json',
          Accept: 'application/json',
        }),
        tags: { journey: 'ack', step: 'offline' },
      },
    );

    check(acknowledgementResponse, {
      'acknowledgement accepted': (res) => res.status === 200,
    });

    sleep(0.5 + Math.random());
  }

  if (GROUP_ID && SHARE_ID) {
    const extendResponse = http.patch(
      `${BASE_URL}/groups/${GROUP_ID}/condition-timers/player-summary/share-links/${SHARE_ID}/extend`,
      JSON.stringify({ expiry_preset: '24h' }),
      {
        headers: authenticatedHeaders({
          'Content-Type': 'application/json',
          Accept: 'application/json',
        }),
        tags: { journey: 'extend', step: 'preset' },
      },
    );

    check(extendResponse, {
      'extension request ok': (res) => res.status === 200 || res.status === 302,
    });
  }

  sleep(1);
}
