import type { APIRequestContext } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from './config';
import { interpolateRoutePath, type HttpMethod } from './route-coverage';

export async function callRouteSmoke(
  request: APIRequestContext,
  method: HttpMethod,
  path: string,
  csrfToken: string
) {
  return request.fetch(`${PLAYWRIGHT_BASE_URL}${interpolateRoutePath(path)}`, {
    method,
    failOnStatusCode: false,
    headers: {
      Accept: 'application/json, text/plain, */*',
      'X-CSRF-TOKEN': csrfToken,
    },
    data: {
      _token: csrfToken,
    },
  });
}
