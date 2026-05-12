import fs from 'node:fs';
import path from 'node:path';
import { execSync } from 'node:child_process';

export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

export interface RouteCase {
  method: HttpMethod;
  path: string;
  dynamic: boolean;
  middleware: string[];
}

const SUPPORTED_METHODS = new Set<HttpMethod>(['GET', 'POST', 'PUT', 'PATCH', 'DELETE']);

function normalizePath(rawPath: string): string {
  const withoutDomain = rawPath.replace(/^https?:\/\/[^/]+/i, '');
  const trimmed = withoutDomain.trim();
  if (!trimmed) {
    return '/';
  }

  return trimmed.startsWith('/') ? trimmed : `/${trimmed}`;
}

function isStaticLiteralPath(rawPath: string): boolean {
  return !rawPath.includes('$') && !rawPath.includes('->') && !rawPath.includes('::');
}

function normalizeMiddleware(raw: unknown): string[] {
  if (Array.isArray(raw)) {
    return raw.map((item) => String(item));
  }

  if (typeof raw === 'string') {
    return raw
      .split(',')
      .map((item) => item.trim())
      .filter(Boolean);
  }

  return [];
}

function expandResource(resource: string): RouteCase[] {
  const base = normalizePath(resource);

  return [
    { method: 'GET', path: base, dynamic: false, middleware: ['auth', 'web'] },
    { method: 'GET', path: `${base}/create`, dynamic: false, middleware: ['auth', 'web'] },
    { method: 'POST', path: base, dynamic: false, middleware: ['auth', 'web'] },
    { method: 'GET', path: `${base}/{resource}`, dynamic: true, middleware: ['auth', 'web'] },
    { method: 'GET', path: `${base}/{resource}/edit`, dynamic: true, middleware: ['auth', 'web'] },
    { method: 'PUT', path: `${base}/{resource}`, dynamic: true, middleware: ['auth', 'web'] },
    { method: 'PATCH', path: `${base}/{resource}`, dynamic: true, middleware: ['auth', 'web'] },
    { method: 'DELETE', path: `${base}/{resource}`, dynamic: true, middleware: ['auth', 'web'] },
  ];
}

function fromArtisanRouteList(): RouteCase[] {
  const json = execSync('php artisan route:list --json', { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] });
  const parsed = JSON.parse(json) as Array<Record<string, unknown>>;

  const routeCases: RouteCase[] = [];

  for (const route of parsed) {
    const middleware = normalizeMiddleware(route.middleware);
    if (!middleware.includes('web')) {
      continue;
    }

    const rawUri = String(route.uri ?? '').trim();
    if (!rawUri || rawUri.startsWith('_debugbar') || rawUri.startsWith('up')) {
      continue;
    }

    const methods = String(route.method ?? '')
      .split('|')
      .map((method) => method.trim().toUpperCase())
      .filter((method): method is HttpMethod => SUPPORTED_METHODS.has(method as HttpMethod));

    for (const method of methods) {
      const normalizedPath = normalizePath(rawUri);
      routeCases.push({
        method,
        path: normalizedPath,
        dynamic: normalizedPath.includes('{'),
        middleware,
      });
    }
  }

  return routeCases;
}

function fromWebPhpFallback(): RouteCase[] {
  const webPhp = fs.readFileSync(path.join(process.cwd(), 'routes/web.php'), 'utf8');
  const routeCases: RouteCase[] = [];
  const verbMatches = webPhp.matchAll(/Route::(get|post|put|patch|delete)\(\s*'([^']+)'/gi);

  for (const match of verbMatches) {
    const method = match[1].toUpperCase() as HttpMethod;
    if (!SUPPORTED_METHODS.has(method)) {
      continue;
    }

    const normalizedPath = normalizePath(match[2]);
    routeCases.push({
      method,
      path: normalizedPath,
      dynamic: normalizedPath.includes('{'),
      middleware: ['auth', 'web'],
    });
  }

  const resourceMatches = webPhp.matchAll(/Route::resource\(\s*'([^']+)'/g);
  for (const match of resourceMatches) {
    routeCases.push(...expandResource(match[1]));
  }

  routeCases.push(
    { method: 'GET', path: '/login', dynamic: false, middleware: ['web'] },
    { method: 'POST', path: '/login', dynamic: false, middleware: ['web'] },
    { method: 'POST', path: '/logout', dynamic: false, middleware: ['auth', 'web'] },
    { method: 'GET', path: '/register', dynamic: false, middleware: ['web'] },
    { method: 'POST', path: '/register', dynamic: false, middleware: ['web'] },
    { method: 'GET', path: '/password/reset', dynamic: false, middleware: ['web'] },
    { method: 'POST', path: '/password/email', dynamic: false, middleware: ['web'] },
  );

  return routeCases;
}

function dedupe(routeCases: RouteCase[]): RouteCase[] {
  const seen = new Set<string>();
  const deduped: RouteCase[] = [];

  for (const routeCase of routeCases) {
    const key = `${routeCase.method} ${routeCase.path}`;
    if (seen.has(key)) {
      continue;
    }

    seen.add(key);
    deduped.push(routeCase);
  }

  return deduped;
}

export function loadWebRouteCases(): RouteCase[] {
  try {
    return dedupe(fromArtisanRouteList());
  } catch {
    return dedupe(fromWebPhpFallback());
  }
}

export function interpolateRoutePath(rawPath: string): string {
  return rawPath.replace(/\{([^}]+)\??\}/g, (_match, token: string) => {
    const key = token.toLowerCase();
    if (key.includes('external') || key.includes('uuid')) {
      return '00000000-0000-0000-0000-000000000001';
    }

    if (key.includes('query')) {
      return 'search-term';
    }

    if (key.includes('type')) {
      return 'task';
    }

    return '1';
  });
}

export function malformedInterpolatedRoutePath(rawPath: string): string {
  return rawPath.replace(/\{([^}]+)\??\}/g, 'invalid-@@@');
}

export function loadPhpUnitHttpCalls(): RouteCase[] {
  const testsRoot = path.join(process.cwd(), 'tests');
  const files: string[] = [];
  const stack = [testsRoot];

  while (stack.length > 0) {
    const current = stack.pop()!;
    const entries = fs.readdirSync(current, { withFileTypes: true });
    for (const entry of entries) {
      const fullPath = path.join(current, entry.name);
      if (entry.isDirectory()) {
        stack.push(fullPath);
        continue;
      }

      if (entry.isFile() && entry.name.endsWith('Test.php')) {
        files.push(fullPath);
      }
    }
  }

  const routeCases: RouteCase[] = [];

  for (const filePath of files) {
    const content = fs.readFileSync(filePath, 'utf8');
    const directMatches = content.matchAll(
      /\$this->(get|post|put|patch|delete|getJson|postJson|putJson|patchJson|deleteJson)\(\s*['"]([^'"]+)['"]/g
    );

    for (const match of directMatches) {
      const method = match[1].replace(/json/i, '').toUpperCase() as HttpMethod;
      if (!SUPPORTED_METHODS.has(method)) {
        continue;
      }

      const rawPath = match[2];
      if (!rawPath.startsWith('/') || !isStaticLiteralPath(rawPath)) {
        continue;
      }

      const normalizedPath = normalizePath(rawPath);
      routeCases.push({
        method,
        path: normalizedPath,
        dynamic: normalizedPath.includes('{'),
        middleware: normalizedPath === '/login' || normalizedPath === '/register' ? ['web'] : ['auth', 'web'],
      });
    }

    const jsonMatches = content.matchAll(/\$this->json\(\s*['"]([A-Z]+)['"]\s*,\s*['"]([^'"]+)['"]/g);
    for (const match of jsonMatches) {
      const method = match[1].toUpperCase() as HttpMethod;
      if (!SUPPORTED_METHODS.has(method)) {
        continue;
      }

      const rawPath = match[2];
      if (!rawPath.startsWith('/') || !isStaticLiteralPath(rawPath)) {
        continue;
      }

      const normalizedPath = normalizePath(rawPath);
      routeCases.push({
        method,
        path: normalizedPath,
        dynamic: normalizedPath.includes('{'),
        middleware: normalizedPath === '/login' || normalizedPath === '/register' ? ['web'] : ['auth', 'web'],
      });
    }
  }

  return dedupe(routeCases);
}
