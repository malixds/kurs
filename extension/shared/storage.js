const LEGACY_LAST_CHECK_IN_DATE = 'lastCheckInDate';

export const CONFIG_KEYS = {
  apiBaseUrl: 'apiBaseUrl',
  secretKey: 'secretKey',
  employeeExternalId: 'employeeExternalId',
  employeeEmail: 'employeeEmail',
  employeeName: 'employeeName',
  lastCheckInDates: 'lastCheckInDates',
  reminderHour: 'reminderHour',
};

export const DEFAULT_CONFIG = {
  apiBaseUrl: 'http://localhost:8080/api/v1/extension',
  secretKey: '',
  employeeExternalId: '',
  employeeEmail: '',
  employeeName: '',
  lastCheckInDates: {},
  reminderHour: 10,
};

export async function getConfig() {
  const keys = [...Object.values(CONFIG_KEYS), LEGACY_LAST_CHECK_IN_DATE];
  const stored = await chrome.storage.local.get(keys);
  const config = { ...DEFAULT_CONFIG, ...stored };

  const dates = { ...(stored[CONFIG_KEYS.lastCheckInDates] ?? {}) };
  let migrated = false;

  if (stored[LEGACY_LAST_CHECK_IN_DATE] && config[CONFIG_KEYS.employeeExternalId]) {
    dates[config[CONFIG_KEYS.employeeExternalId]] = stored[LEGACY_LAST_CHECK_IN_DATE];
    migrated = true;
  }

  config[CONFIG_KEYS.lastCheckInDates] = dates;

  if (migrated) {
    await chrome.storage.local.set({ [CONFIG_KEYS.lastCheckInDates]: dates });
    await chrome.storage.local.remove(LEGACY_LAST_CHECK_IN_DATE);
  }

  return config;
}

export async function saveConfig(partial) {
  await chrome.storage.local.set(partial);
}

function getEmployeeLastCheckInDate(config) {
  const employeeId = config[CONFIG_KEYS.employeeExternalId];

  if (!employeeId) {
    return '';
  }

  const dates = config[CONFIG_KEYS.lastCheckInDates] ?? {};

  return dates[employeeId] ?? '';
}

export function isExtensionConfigured(config) {
  return Boolean(
    config[CONFIG_KEYS.secretKey]?.trim()
    && config[CONFIG_KEYS.employeeExternalId]?.trim()
    && config[CONFIG_KEYS.employeeEmail]?.trim()
    && config[CONFIG_KEYS.employeeName]?.trim(),
  );
}

export function shouldShowReminder(config) {
  const today = new Date().toISOString().slice(0, 10);

  return getEmployeeLastCheckInDate(config) !== today;
}

export async function markCheckInCompleted() {
  const config = await getConfig();
  const today = new Date().toISOString().slice(0, 10);
  const employeeId = config[CONFIG_KEYS.employeeExternalId];

  if (!employeeId) {
    return;
  }

  const dates = {
    ...(config[CONFIG_KEYS.lastCheckInDates] ?? {}),
    [employeeId]: today,
  };

  return saveConfig({ [CONFIG_KEYS.lastCheckInDates]: dates });
}
