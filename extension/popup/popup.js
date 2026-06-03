import { fetchSurveyQuestions, submitCheckIn } from '../shared/api.js';
import {
  CONFIG_KEYS,
  getConfig,
  isExtensionConfigured,
  markCheckInCompleted,
  shouldShowReminder,
} from '../shared/storage.js';

const statusText = document.getElementById('status-text');
const setupPanel = document.getElementById('setup-panel');
const surveyForm = document.getElementById('survey-form');
const successPanel = document.getElementById('success-panel');
const questionsContainer = document.getElementById('questions');
const submitBtn = document.getElementById('submit-btn');

document.getElementById('open-options').addEventListener('click', () => {
  chrome.runtime.openOptionsPage();
});

surveyForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  submitBtn.disabled = true;

  try {
    const answers = collectAnswers();
    await submitCheckIn(answers);
    await markCheckInCompleted();
    showSuccess();
  } catch (error) {
    statusText.textContent = error.message;
    statusText.classList.remove('ds-status--success');
    statusText.classList.add('ds-status--error');
    submitBtn.disabled = false;
  }
});

init();

async function init() {
  const config = await getConfig();

  if (!isExtensionConfigured(config)) {
    showSetup();
    return;
  }

  if (!shouldShowReminder(config)) {
    showAlreadyCompleted();
    return;
  }

  try {
    const questions = await fetchSurveyQuestions();
    renderQuestions(questions);
    surveyForm.classList.remove('hidden');
    statusText.textContent = 'Расскажите, как вы себя чувствуете сегодня.';
    statusText.classList.remove('ds-status--error', 'ds-status--success');
  } catch (error) {
    statusText.textContent = error.message;
    statusText.classList.remove('ds-status--success');
    statusText.classList.add('ds-status--error');
  }
}

function showSetup() {
  setupPanel.classList.remove('hidden');
  statusText.textContent = 'Расширение ещё не настроено.';
  statusText.classList.remove('ds-status--error', 'ds-status--success');
}

function showSuccess() {
  surveyForm.classList.add('hidden');
  successPanel.classList.remove('hidden');
  statusText.textContent = 'Check-in отправлен.';
  statusText.classList.remove('ds-status--error');
  statusText.classList.add('ds-status--success');
}

function showAlreadyCompleted() {
  successPanel.classList.remove('hidden');
  successPanel.querySelector('.ext-success__title').textContent = 'Вы уже прошли check-in сегодня.';
  successPanel.querySelector('.ds-text-muted').textContent = 'Новый опрос будет доступен завтра.';
  statusText.textContent = 'На сегодня всё готово.';
  statusText.classList.remove('ds-status--error');
  statusText.classList.add('ds-status--success');
}

function renderQuestions(questions) {
  questionsContainer.innerHTML = '';

  questions.forEach((question) => {
    const block = document.createElement('div');
    block.className = 'ds-question';
    block.dataset.questionId = question.id;
    block.dataset.questionType = question.type;

    const label = document.createElement('span');
    label.className = 'ds-question__label';
    label.textContent = question.question;
    block.appendChild(label);

    if (question.type === 'scale') {
      block.appendChild(renderScale(question));
    } else if (question.type === 'boolean') {
      block.appendChild(renderBoolean());
    } else {
      block.appendChild(renderText());
    }

    questionsContainer.appendChild(block);
  });
}

function renderScale(question) {
  const min = question.options?.min ?? 1;
  const max = question.options?.max ?? 5;
  const wrapper = document.createElement('div');
  wrapper.className = 'ds-scale';
  wrapper.setAttribute('role', 'group');

  for (let value = min; value <= max; value += 1) {
    const label = document.createElement('label');
    label.className = 'ds-scale__option';
    const input = document.createElement('input');
    input.type = 'radio';
    input.name = `question-${question.id}`;
    input.value = String(value);
    input.required = true;
    label.appendChild(input);
    label.append(String(value));
    wrapper.appendChild(label);
  }

  return wrapper;
}

function renderBoolean() {
  const select = document.createElement('select');
  select.className = 'ds-select';
  select.required = true;
  select.innerHTML = `
    <option value="">Выберите…</option>
    <option value="yes">Да</option>
    <option value="no">Нет</option>
  `;
  return select;
}

function renderText() {
  const textarea = document.createElement('textarea');
  textarea.className = 'ds-textarea';
  textarea.rows = 3;
  textarea.placeholder = 'Комментарий (необязательно)';
  return textarea;
}

function collectAnswers() {
  return [...questionsContainer.querySelectorAll('.ds-question')].map((block) => {
    const questionId = Number(block.dataset.questionId);
    const type = block.dataset.questionType;
    let answer = '';

    if (type === 'scale') {
      answer = block.querySelector('input[type="radio"]:checked')?.value ?? '';
    } else if (type === 'boolean') {
      answer = block.querySelector('select')?.value ?? '';
    } else {
      answer = block.querySelector('textarea')?.value ?? '';
    }

    return {
      question_id: questionId,
      answer,
    };
  }).filter((item) => item.answer !== '');
}
