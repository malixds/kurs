import { getConfig } from './storage.js';

const REQUEST_TIMEOUT_MS = 15000;

function buildUrl(baseUrl, path) {
  return `${baseUrl.replace(/\/$/, '')}/${path.replace(/^\//, '')}`;
}

function extractErrorMessage(payload, status) {
  if (typeof payload.message === 'string' && payload.message !== '') {
    return payload.message;
  }

  if (payload.errors && typeof payload.errors === 'object') {
    return Object.values(payload.errors).flat().join(' ');
  }

  return `Request failed with status ${status}`;
}

async function rawRequest(baseUrl, secretKey, path, options = {}) {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);

  let response;

  try {
    response = await fetch(buildUrl(baseUrl, path), {
      signal: controller.signal,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Company-Key': secretKey,
        ...(options.headers ?? {}),
      },
      ...options,
    });
  } catch (error) {
    if (error.name === 'AbortError') {
      throw new Error('Сервер не ответил за 15 секунд. Проверьте URL API и соединение.');
    }
    throw new Error('Не удалось подключиться к серверу. Проверьте URL API.');
  } finally {
    clearTimeout(timeoutId);
  }

  const payload = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new Error(extractErrorMessage(payload, response.status));
  }

  return payload;
}

async function request(path, options = {}) {
  const config = await getConfig();

  return rawRequest(config.apiBaseUrl, config.secretKey, path, options);
}

export async function fetchSurveyQuestions() {
  const payload = await request('survey/questions', { method: 'GET' });

  return payload.data ?? [];
}

export async function testConnection(baseUrl, secretKey) {
  const payload = await rawRequest(baseUrl, secretKey, 'survey/questions', { method: 'GET' });

  return (payload.data ?? []).length;
}

export async function submitCheckIn(answers) {
  const config = await getConfig();

  return request('check-in', {
    method: 'POST',
    body: JSON.stringify({
      employee: {
        external_id: config.employeeExternalId,
        email: config.employeeEmail.trim(),
        name: config.employeeName.trim(),
      },
      answers,
    }),
  });
}
