import { getConfig } from './storage.js';

async function request(path, options = {}) {
  const config = await getConfig();
  const url = `${config.apiBaseUrl.replace(/\/$/, '')}/${path.replace(/^\//, '')}`;

  const response = await fetch(url, {
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(options.headers ?? {}),
    },
    ...options,
  });

  const payload = await response.json().catch(() => ({}));

  if (!response.ok) {
    const message = payload.message
      ?? payload.errors
      ?? `Request failed with status ${response.status}`;
    throw new Error(typeof message === 'string' ? message : JSON.stringify(message));
  }

  return payload;
}

export async function fetchSurveyQuestions() {
  const config = await getConfig();

  const payload = await request(`survey/questions?secret_key=${encodeURIComponent(config.secretKey)}`, {
    method: 'GET',
  });

  return payload.data ?? [];
}

export async function submitCheckIn(answers) {
  const config = await getConfig();

  return request('check-in', {
    method: 'POST',
    body: JSON.stringify({
      secret_key: config.secretKey,
      employee: {
        external_id: config.employeeExternalId,
        email: config.employeeEmail || undefined,
        name: config.employeeName || undefined,
      },
      answers,
    }),
  });
}
