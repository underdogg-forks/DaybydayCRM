const JSON_PATH_MATCHERS = ['/data', '/users/users', '/calendar-users'];

export function isLikelyJsonPath(path: string): boolean {
  return JSON_PATH_MATCHERS.some((matcher) => path.includes(matcher) || path.endsWith(matcher));
}

export function expectedAuthMutationStatuses(method: string): number[] {
  switch (method) {
    case 'POST':
      return [200, 201, 302, 303, 400, 401, 403, 404, 419, 422];
    case 'PUT':
    case 'PATCH':
      return [200, 302, 303, 400, 401, 403, 404, 405, 419, 422];
    case 'DELETE':
      return [200, 202, 204, 302, 303, 400, 401, 403, 404, 405, 419];
    default:
      return [200, 302, 303, 400, 401, 403, 404, 405, 419, 422];
  }
}
