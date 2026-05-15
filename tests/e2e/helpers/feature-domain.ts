import { expect, type APIRequestContext, type APIResponse, type Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from './config';
import { fetchCsrfToken } from './csrf';

async function jsonHeaders(page: Page) {
  const csrf = await fetchCsrfToken(page);
  return {
    Accept: 'application/json',
    'X-CSRF-TOKEN': csrf,
    'X-Requested-With': 'XMLHttpRequest',
  };
}

async function pageHtml(request: APIRequestContext, path: string): Promise<string> {
  const response = await request.get(`${PLAYWRIGHT_BASE_URL}${path}`, { failOnStatusCode: false });
  return response.text();
}

function selectOptions(html: string, selectName: string): string[] {
  const selectRegex = new RegExp(`<select[^>]*name=["']${selectName}["'][^>]*>([\\s\\S]*?)</select>`, 'i');
  const section = html.match(selectRegex)?.[1] ?? '';
  const optionRegex = /<option[^>]*value=["']([^"']+)["'][^>]*>/gi;
  const values: string[] = [];
  for (const match of section.matchAll(optionRegex)) {
    const value = (match[1] ?? '').trim();
    if (value.length > 0) {
      values.push(value);
    }
  }

  if (values.length === 0) {
    throw new Error(`No option values found for select: ${selectName}`);
  }

  return values;
}

export class ClientActions {
  static async create(page: Page, request: APIRequestContext, companyName: string) {
    const html = await pageHtml(request, '/clients/create');
    const industryId = selectOptions(html, 'industry_id')[0];
    const userId = selectOptions(html, 'user_id')[0];
    const headers = await jsonHeaders(page);

    const response = await request.post(`${PLAYWRIGHT_BASE_URL}/clients`, {
      failOnStatusCode: false,
      headers,
      form: {
        name: `${companyName} Contact`,
        company_name: companyName,
        email: `${Date.now()}@example.com`,
        primary_number: '12345678',
        secondary_number: '87654321',
        vat: `${Date.now()}`.slice(-8),
        zipcode: '1000',
        city: 'Copenhagen',
        industry_id: industryId,
        user_id: userId,
      },
    });

    return { response, companyName };
  }

  static async data(request: APIRequestContext, search = '') {
    return request.get(`${PLAYWRIGHT_BASE_URL}/clients/data?draw=1&start=0&length=25&search[value]=${encodeURIComponent(search)}`, {
      failOnStatusCode: false,
      headers: { Accept: 'application/json' },
    });
  }
}

export class LeadActions {
  static async create(page: Page, request: APIRequestContext, title: string) {
    const html = await pageHtml(request, '/leads/create');
    const statusId = selectOptions(html, 'status_id')[0];
    const userAssignedId = selectOptions(html, 'user_assigned_id')[0];
    const clientExternalId = selectOptions(html, 'client_external_id')[0];
    const headers = await jsonHeaders(page);

    const response = await request.post(`${PLAYWRIGHT_BASE_URL}/leads`, {
      failOnStatusCode: false,
      maxRedirects: 0,
      headers,
      form: {
        title,
        description: 'Playwright lead description',
        status_id: statusId,
        user_assigned_id: userAssignedId,
        client_external_id: clientExternalId,
        deadline: '2030-01-01',
        contact_time: '10:30',
      },
    });

    return { response, title };
  }

  static async data(request: APIRequestContext, search = '') {
    return request.get(`${PLAYWRIGHT_BASE_URL}/leads/data?draw=1&start=0&length=25&search[value]=${encodeURIComponent(search)}`, {
      failOnStatusCode: false,
      headers: { Accept: 'application/json' },
    });
  }
}

export class ProjectActions {
  static async create(page: Page, request: APIRequestContext, title: string) {
    const html = await pageHtml(request, '/projects/create');
    const statusId = selectOptions(html, 'status_id')[0];
    const userAssignedId = selectOptions(html, 'user_assigned_id')[0];
    const clientExternalId = selectOptions(html, 'client_external_id')[0];
    const headers = await jsonHeaders(page);

    const response = await request.post(`${PLAYWRIGHT_BASE_URL}/projects`, {
      failOnStatusCode: false,
      headers,
      form: {
        title,
        description: 'Playwright project description',
        status_id: statusId,
        user_assigned_id: userAssignedId,
        client_external_id: clientExternalId,
        deadline: '2030-01-01',
      },
    });

    return { response, title };
  }

  static async data(request: APIRequestContext, search = '') {
    return request.get(`${PLAYWRIGHT_BASE_URL}/projects/data?draw=1&start=0&length=25&search[value]=${encodeURIComponent(search)}`, {
      failOnStatusCode: false,
      headers: { Accept: 'application/json' },
    });
  }
}

export class TaskActions {
  static async create(page: Page, request: APIRequestContext, title: string) {
    const html = await pageHtml(request, '/tasks/create');
    const statusId = selectOptions(html, 'status_id')[0];
    const userAssignedId = selectOptions(html, 'user_assigned_id')[0];
    const clientExternalId = selectOptions(html, 'client_external_id')[0];
    const headers = await jsonHeaders(page);

    const response = await request.post(`${PLAYWRIGHT_BASE_URL}/tasks`, {
      failOnStatusCode: false,
      headers,
      form: {
        title,
        description: 'Playwright task description',
        status_id: statusId,
        user_assigned_id: userAssignedId,
        client_external_id: clientExternalId,
        deadline: '2030-01-01',
      },
    });

    return { response, title };
  }

  static async data(request: APIRequestContext, search = '') {
    return request.get(`${PLAYWRIGHT_BASE_URL}/tasks/data?draw=1&start=0&length=25&search[value]=${encodeURIComponent(search)}`, {
      failOnStatusCode: false,
      headers: { Accept: 'application/json' },
    });
  }
}

export class RoleActions {
  static async create(page: Page, request: APIRequestContext, name: string) {
    const headers = await jsonHeaders(page);
    return request.post(`${PLAYWRIGHT_BASE_URL}/roles`, {
      failOnStatusCode: false,
      maxRedirects: 0,
      headers,
      form: {
        name,
        description: `${name} description`,
      },
    });
  }

  static async data(request: APIRequestContext, search = '') {
    return request.get(`${PLAYWRIGHT_BASE_URL}/roles/data?draw=1&start=0&length=25&search[value]=${encodeURIComponent(search)}`, {
      failOnStatusCode: false,
      headers: { Accept: 'application/json' },
    });
  }
}

export class UserActions {
  static async create(page: Page, request: APIRequestContext, name: string, email: string) {
    const html = await pageHtml(request, '/users/create');
    const roleId = selectOptions(html, 'roles')[0];
    const departmentId = selectOptions(html, 'departments')[0];
    const headers = await jsonHeaders(page);

    return request.post(`${PLAYWRIGHT_BASE_URL}/users`, {
      failOnStatusCode: false,
      maxRedirects: 0,
      headers,
      form: {
        name,
        email,
        password: 'amazingpassword123',
        password_confirmation: 'amazingpassword123',
        roles: roleId,
        departments: departmentId,
      },
    });
  }

  static async data(request: APIRequestContext, search = '') {
    return request.get(`${PLAYWRIGHT_BASE_URL}/users/data?draw=1&start=0&length=25&search[value]=${encodeURIComponent(search)}`, {
      failOnStatusCode: false,
      headers: { Accept: 'application/json' },
    });
  }
}

export class SettingActions {
  static async updateOverall(page: Page, request: APIRequestContext, payload: Record<string, string | number>) {
    const headers = await jsonHeaders(page);
    return request.patch(`${PLAYWRIGHT_BASE_URL}/settings/overall`, {
      failOnStatusCode: false,
      headers,
      data: payload,
    });
  }
}

export class DomainAssertions {
  static async expectValidationError(response: APIResponse, field: string) {
    expect(response.status()).toBe(422);
    const payload = await response.json();
    expect(payload.errors).toBeTruthy();
    expect(Object.keys(payload.errors)).toContain(field);
  }

  static async expectDataContainsTitle(response: APIResponse, value: string) {
    expect(response.status()).toBe(200);
    const payload = await response.json();
    const text = JSON.stringify(payload);
    expect(text).toContain(value);
  }
}
