import { CONFIG_KEYS, DEFAULT_CONFIG, getConfig, saveConfig } from '../shared/storage.js';
import { testConnection } from '../shared/api.js';

const form = document.getElementById('options-form');
const saveStatus = document.getElementById('save-status');
const testBtn = document.getElementById('test-connection');

const fields = {
  apiBaseUrl: document.getElementById('apiBaseUrl'),
  secretKey: document.getElementById('secretKey'),
  employeeExternalId: document.getElementById('employeeExternalId'),
  employeeEmail: document.getElementById('employeeEmail'),
  employeeName: document.getElementById('employeeName'),
  reminderHour: document.getElementById('reminderHour'),
};

init();

async function init() {
  const config = await getConfig();

  fields.apiBaseUrl.value = config[CONFIG_KEYS.apiBaseUrl] ?? DEFAULT_CONFIG.apiBaseUrl;
  fields.secretKey.value = config[CONFIG_KEYS.secretKey] ?? '';
  fields.employeeExternalId.value = config[CONFIG_KEYS.employeeExternalId] ?? '';
  fields.employeeEmail.value = config[CONFIG_KEYS.employeeEmail] ?? '';
  fields.employeeName.value = config[CONFIG_KEYS.employeeName] ?? '';
  fields.reminderHour.value = config[CONFIG_KEYS.reminderHour] ?? DEFAULT_CONFIG.reminderHour;
}

function showStatus(message, isError = false) {
  saveStatus.textContent = message;
  saveStatus.classList.toggle('ds-status--error', isError);
  saveStatus.classList.toggle('ds-status--success', !isError);
}

function validateApiUrl(raw) {
  let url;

  try {
    url = new URL(raw);
  } catch {
    throw new Error('Некорректный URL API.');
  }

  const isLocalhost = ['localhost', '127.0.0.1'].includes(url.hostname);

  if (url.protocol !== 'https:' && !(url.protocol === 'http:' && isLocalhost)) {
    throw new Error('URL API должен использовать HTTPS (HTTP допустим только для localhost).');
  }

  return url;
}

// For non-localhost APIs the origin is covered by optional_host_permissions
// and must be granted at runtime, otherwise fetch() will be blocked.
async function ensureHostPermission(url) {
  const origin = `${url.origin}/*`;

  const granted = await chrome.permissions.contains({ origins: [origin] })
    || await chrome.permissions.request({ origins: [origin] });

  if (!granted) {
    throw new Error('Доступ к этому адресу не разрешён. Подтвердите запрос разрешения.');
  }
}

form.addEventListener('submit', async (event) => {
  event.preventDefault();

  const email = fields.employeeEmail.value.trim();
  const name = fields.employeeName.value.trim();

  if (!email) {
    showStatus('Укажите email (как в трекере).', true);
    return;
  }

  if (!name) {
    showStatus('Укажите имя сотрудника.', true);
    return;
  }

  let apiUrl;

  try {
    apiUrl = validateApiUrl(fields.apiBaseUrl.value.trim());
    await ensureHostPermission(apiUrl);
  } catch (error) {
    showStatus(error.message, true);
    return;
  }

  const payload = {
    [CONFIG_KEYS.apiBaseUrl]: fields.apiBaseUrl.value.trim(),
    [CONFIG_KEYS.secretKey]: fields.secretKey.value.trim(),
    [CONFIG_KEYS.employeeExternalId]: fields.employeeExternalId.value.trim(),
    [CONFIG_KEYS.employeeEmail]: email,
    [CONFIG_KEYS.employeeName]: name,
    [CONFIG_KEYS.reminderHour]: Number(fields.reminderHour.value),
  };

  await saveConfig(payload);

  chrome.runtime.sendMessage({
    type: 'RESCHEDULE_REMINDER',
    hour: payload[CONFIG_KEYS.reminderHour],
  });

  showStatus('Настройки сохранены.');
});

testBtn.addEventListener('click', async () => {
  const secretKey = fields.secretKey.value.trim();

  if (secretKey.length !== 48) {
    showStatus('Секретный ключ должен содержать 48 символов.', true);
    return;
  }

  testBtn.disabled = true;
  showStatus('Проверка соединения…', false);

  try {
    const apiUrl = validateApiUrl(fields.apiBaseUrl.value.trim());
    await ensureHostPermission(apiUrl);
    const questionCount = await testConnection(fields.apiBaseUrl.value.trim(), secretKey);
    showStatus(`Соединение установлено: доступно вопросов — ${questionCount}.`);
  } catch (error) {
    showStatus(error.message, true);
  } finally {
    testBtn.disabled = false;
  }
});
