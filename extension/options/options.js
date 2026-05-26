import { CONFIG_KEYS, DEFAULT_CONFIG, getConfig, saveConfig } from '../shared/storage.js';

const form = document.getElementById('options-form');
const saveStatus = document.getElementById('save-status');

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

form.addEventListener('submit', async (event) => {
  event.preventDefault();

  const payload = {
    [CONFIG_KEYS.apiBaseUrl]: fields.apiBaseUrl.value.trim(),
    [CONFIG_KEYS.secretKey]: fields.secretKey.value.trim(),
    [CONFIG_KEYS.employeeExternalId]: fields.employeeExternalId.value.trim(),
    [CONFIG_KEYS.employeeEmail]: fields.employeeEmail.value.trim(),
    [CONFIG_KEYS.employeeName]: fields.employeeName.value.trim(),
    [CONFIG_KEYS.reminderHour]: Number(fields.reminderHour.value),
  };

  await saveConfig(payload);

  chrome.runtime.sendMessage({
    type: 'RESCHEDULE_REMINDER',
    hour: payload[CONFIG_KEYS.reminderHour],
  });

  saveStatus.textContent = 'Settings saved.';
});
