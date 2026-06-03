const MONTHS_RU = [
    'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
    'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь',
];

const WEEKDAYS_RU = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];

function parseIsoDate(iso) {
    if (!iso) {
        return null;
    }

    const [year, month, day] = iso.split('-').map(Number);

    if (!year || !month || !day) {
        return null;
    }

    return new Date(year, month - 1, day);
}

function toIsoDate(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');

    return `${y}-${m}-${d}`;
}

function formatDisplay(iso) {
    const date = parseIsoDate(iso);

    if (!date) {
        return 'Выберите дату';
    }

    const d = String(date.getDate()).padStart(2, '0');
    const m = String(date.getMonth() + 1).padStart(2, '0');

    return `${d}.${m}.${date.getFullYear()}`;
}

function startOfMonth(date) {
    return new Date(date.getFullYear(), date.getMonth(), 1);
}

function addMonths(date, count) {
    return new Date(date.getFullYear(), date.getMonth() + count, 1);
}

function isSameDay(a, b) {
    return a.getFullYear() === b.getFullYear()
        && a.getMonth() === b.getMonth()
        && a.getDate() === b.getDate();
}

function isBeforeDay(a, b) {
    return toIsoDate(a) < toIsoDate(b);
}

function isAfterDay(a, b) {
    return toIsoDate(a) > toIsoDate(b);
}

function buildCalendarDays(viewMonth) {
    const first = startOfMonth(viewMonth);
    const startOffset = (first.getDay() + 6) % 7;
    const gridStart = new Date(first);
    gridStart.setDate(first.getDate() - startOffset);

    const days = [];

    for (let i = 0; i < 42; i += 1) {
        const day = new Date(gridStart);
        day.setDate(gridStart.getDate() + i);
        days.push(day);
    }

    return days;
}

const pickerRegistry = new WeakMap();

class DsDatePicker {
    constructor(input) {
        this.input = input;
        pickerRegistry.set(input, this);
        this.min = parseIsoDate(input.getAttribute('min'));
        this.max = parseIsoDate(input.getAttribute('max'));
        this.viewMonth = parseIsoDate(input.value) ?? new Date();
        this.isOpen = false;

        this.wrapper = document.createElement('div');
        this.wrapper.className = 'ds-date-field';

        this.trigger = document.createElement('button');
        this.trigger.type = 'button';
        this.trigger.className = 'ds-date-trigger';
        this.trigger.setAttribute('aria-haspopup', 'dialog');
        this.trigger.setAttribute('aria-expanded', 'false');

        this.popover = document.createElement('div');
        this.popover.className = 'ds-datepicker';
        this.popover.setAttribute('role', 'dialog');
        this.popover.hidden = true;

        this.header = document.createElement('div');
        this.header.className = 'ds-datepicker__header';

        this.prevBtn = document.createElement('button');
        this.prevBtn.type = 'button';
        this.prevBtn.className = 'ds-datepicker__nav';
        this.prevBtn.setAttribute('aria-label', 'Предыдущий месяц');
        this.prevBtn.innerHTML = '‹';

        this.title = document.createElement('div');
        this.title.className = 'ds-datepicker__title';

        this.nextBtn = document.createElement('button');
        this.nextBtn.type = 'button';
        this.nextBtn.className = 'ds-datepicker__nav';
        this.nextBtn.setAttribute('aria-label', 'Следующий месяц');
        this.nextBtn.innerHTML = '›';

        this.weekdays = document.createElement('div');
        this.weekdays.className = 'ds-datepicker__weekdays';

        this.grid = document.createElement('div');
        this.grid.className = 'ds-datepicker__grid';

        this.header.append(this.prevBtn, this.title, this.nextBtn);
        this.popover.append(this.header, this.weekdays, this.grid);

        WEEKDAYS_RU.forEach((label) => {
            const cell = document.createElement('span');
            cell.className = 'ds-datepicker__weekday';
            cell.textContent = label;
            this.weekdays.append(cell);
        });

        input.parentNode?.insertBefore(this.wrapper, input);
        this.wrapper.append(this.trigger, this.popover, input);

        input.type = 'hidden';
        input.classList.add('ds-date-native');
        input.tabIndex = -1;
        input.setAttribute('aria-hidden', 'true');

        this.syncTrigger();
        this.render();
        this.bindEvents();
    }

    bindEvents() {
        this.trigger.addEventListener('click', () => this.toggle());
        this.prevBtn.addEventListener('click', () => this.changeMonth(-1));
        this.nextBtn.addEventListener('click', () => this.changeMonth(1));

        this.input.addEventListener('change', () => {
            this.refreshFromInput();
        });

        const attrObserver = new MutationObserver(() => this.refreshFromInput());
        attrObserver.observe(this.input, {
            attributes: true,
            attributeFilter: ['min', 'max', 'value'],
        });

        document.addEventListener('click', (event) => {
            if (!this.wrapper.contains(event.target)) {
                this.close();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.close();
            }
        });
    }

    refreshFromInput() {
        this.min = parseIsoDate(this.input.getAttribute('min'));
        this.max = parseIsoDate(this.input.getAttribute('max'));
        this.viewMonth = parseIsoDate(this.input.value) ?? this.viewMonth;
        this.syncTrigger();
        this.render();
    }

    syncTrigger() {
        this.trigger.textContent = formatDisplay(this.input.value);
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.viewMonth = parseIsoDate(this.input.value) ?? this.viewMonth;
        this.isOpen = true;
        this.popover.hidden = false;
        this.trigger.setAttribute('aria-expanded', 'true');
        this.render();
    }

    close() {
        this.isOpen = false;
        this.popover.hidden = true;
        this.trigger.setAttribute('aria-expanded', 'false');
    }

    changeMonth(delta) {
        this.viewMonth = addMonths(this.viewMonth, delta);
        this.render();
    }

    selectDay(day) {
        if (this.min && isBeforeDay(day, this.min)) {
            return;
        }

        if (this.max && isAfterDay(day, this.max)) {
            return;
        }

        this.input.value = toIsoDate(day);
        this.input.dispatchEvent(new Event('change', { bubbles: true }));
        this.input.dispatchEvent(new Event('input', { bubbles: true }));
        this.syncTrigger();
        this.close();
    }

    render() {
        const selected = parseIsoDate(this.input.value);
        const today = new Date();
        const monthStart = startOfMonth(this.viewMonth);

        this.title.textContent = `${MONTHS_RU[monthStart.getMonth()]} ${monthStart.getFullYear()}`;

        const prevMonthEnd = new Date(monthStart);
        prevMonthEnd.setDate(0);
        const nextMonthStart = addMonths(monthStart, 1);

        this.prevBtn.disabled = this.min ? isBeforeDay(prevMonthEnd, this.min) : false;
        this.nextBtn.disabled = this.max ? isAfterDay(nextMonthStart, this.max) : false;

        this.grid.replaceChildren();

        buildCalendarDays(this.viewMonth).forEach((day) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'ds-datepicker__day';
            button.textContent = String(day.getDate());

            const outsideMonth = day.getMonth() !== monthStart.getMonth();
            const disabled = (this.min && isBeforeDay(day, this.min))
                || (this.max && isAfterDay(day, this.max));

            if (outsideMonth) {
                button.classList.add('ds-datepicker__day--outside');
            }

            if (selected && isSameDay(day, selected)) {
                button.classList.add('ds-datepicker__day--selected');
            }

            if (isSameDay(day, today)) {
                button.classList.add('ds-datepicker__day--today');
            }

            if (disabled) {
                button.disabled = true;
                button.classList.add('ds-datepicker__day--disabled');
            } else {
                button.addEventListener('click', () => this.selectDay(day));
            }

            this.grid.append(button);
        });
    }
}

export function initDsDatePickers(root = document) {
    root.querySelectorAll('input[type="date"]:not(.ds-date-native)').forEach((input) => {
        if (input.closest('.ds-date-field')) {
            return;
        }

        new DsDatePicker(input);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initDsDatePickers());
} else {
    initDsDatePickers();
}
