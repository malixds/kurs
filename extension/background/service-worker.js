import { CONFIG_KEYS, DEFAULT_CONFIG, getConfig, shouldShowReminder } from '../shared/storage.js';
import { fetchSurveyQuestions } from '../shared/api.js';

const REMINDER_ALARM = 'daily-wellbeing-reminder';

chrome.runtime.onInstalled.addListener(async () => {
  const config = await getConfig();

  if (!config[CONFIG_KEYS.employeeExternalId]) {
    config[CONFIG_KEYS.employeeExternalId] = `emp-${crypto.randomUUID().slice(0, 8)}`;
    await chrome.storage.local.set(config);
  }

  await scheduleDailyReminder(config[CONFIG_KEYS.reminderHour] ?? DEFAULT_CONFIG.reminderHour);
});

chrome.alarms.onAlarm.addListener(async (alarm) => {
  if (alarm.name !== REMINDER_ALARM) {
    return;
  }

  const config = await getConfig();

  if (!config[CONFIG_KEYS.secretKey]) {
    return;
  }

  if (!shouldShowReminder(config)) {
    return;
  }

  chrome.notifications.create(`wellbeing-${Date.now()}`, {
    type: 'basic',
    iconUrl: chrome.runtime.getURL('assets/icon-128.png'),
    title: 'Daily wellbeing check-in',
    message: 'Take a minute to share how you feel today.',
    priority: 1,
  });
});

chrome.runtime.onMessage.addListener((message, _sender, sendResponse) => {
  if (message.type === 'RESCHEDULE_REMINDER') {
    scheduleDailyReminder(message.hour).then(() => sendResponse({ ok: true }));
    return true;
  }

  if (message.type === 'PREFETCH_QUESTIONS') {
    fetchSurveyQuestions().then((questions) => sendResponse({ ok: true, questions }))
      .catch((error) => sendResponse({ ok: false, error: error.message }));
    return true;
  }

  return false;
});

async function scheduleDailyReminder(hour = 10) {
  await chrome.alarms.clear(REMINDER_ALARM);

  const now = new Date();
  const next = new Date();
  next.setHours(hour, 0, 0, 0);

  if (next <= now) {
    next.setDate(next.getDate() + 1);
  }

  chrome.alarms.create(REMINDER_ALARM, {
    when: next.getTime(),
    periodInMinutes: 24 * 60,
  });
}
