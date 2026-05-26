import { fetchSurveyQuestions, submitCheckIn } from '../shared/api.js';
import { CONFIG_KEYS, getConfig, markCheckInCompleted, shouldShowReminder } from '../shared/storage.js';

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
    statusText.classList.add('error');
    submitBtn.disabled = false;
  }
});

init();

async function init() {
  const config = await getConfig();

  if (!config[CONFIG_KEYS.secretKey]) {
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
    statusText.textContent = 'Share how you feel today.';
  } catch (error) {
    statusText.textContent = error.message;
    statusText.classList.add('error');
  }
}

function showSetup() {
  setupPanel.classList.remove('hidden');
  statusText.textContent = 'Extension is not configured yet.';
}

function showSuccess() {
  surveyForm.classList.add('hidden');
  successPanel.classList.remove('hidden');
  statusText.textContent = 'Check-in completed.';
}

function showAlreadyCompleted() {
  successPanel.classList.remove('hidden');
  statusText.textContent = 'You already completed today\'s check-in.';
}

function renderQuestions(questions) {
  questionsContainer.innerHTML = '';

  questions.forEach((question) => {
    const block = document.createElement('div');
    block.className = 'question-block';
    block.dataset.questionId = question.id;
    block.dataset.questionType = question.type;

    const label = document.createElement('label');
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
  wrapper.className = 'scale-options';

  for (let value = min; value <= max; value += 1) {
    const label = document.createElement('label');
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
  select.required = true;
  select.innerHTML = `
    <option value="">Select...</option>
    <option value="yes">Yes</option>
    <option value="no">No</option>
  `;
  return select;
}

function renderText() {
  const textarea = document.createElement('textarea');
  textarea.rows = 3;
  textarea.placeholder = 'Optional comment';
  return textarea;
}

function collectAnswers() {
  return [...questionsContainer.querySelectorAll('.question-block')].map((block) => {
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
