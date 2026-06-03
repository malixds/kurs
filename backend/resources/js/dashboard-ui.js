/**
 * Shared dashboard UI helpers (analysis / deep-analysis inline scripts).
 */
export function createShowStatus(statusEl) {
    return function showStatus(message, type = 'info') {
        statusEl.classList.remove('hidden');
        statusEl.replaceChildren();
        statusEl.className = 'ds-status ds-status--' + type;

        if (type === 'loading') {
            statusEl.append(createLoader(), createStatusText(message));
            return;
        }

        if (type === 'success' || type === 'error') {
            statusEl.append(createStatusIcon(type), createStatusText(message));
            return;
        }

        statusEl.append(createStatusText(message));
    };
}

function createLoader() {
    const loader = document.createElement('span');
    loader.className = 'ds-loader';
    loader.setAttribute('aria-hidden', 'true');

    return loader;
}

function createStatusIcon(type) {
    const icon = document.createElement('span');
    icon.className = 'ds-status-icon ds-status-icon--' + type;
    icon.setAttribute('aria-hidden', 'true');

    return icon;
}

function createStatusText(message) {
    const text = document.createElement('span');
    text.className = 'ds-status__text';
    text.textContent = message;

    return text;
}

export function renderRecommendation(container, rawText) {
    container.replaceChildren();

    if (!rawText || !String(rawText).trim()) {
        container.textContent = '—';
        return;
    }

    const text = String(rawText).trim();
    const blocks =
        /^\d+[\.\)]\s/mu.test(text)
            ? text.split(/\n+(?=\d+[\.\)]\s)/u).map((block) => block.trim()).filter(Boolean)
            : text.split(/\n{2,}/).map((block) => block.trim()).filter(Boolean);

    const wrap = document.createElement('div');
    wrap.className = 'ds-recommendation-blocks';

    blocks.forEach((block) => {
        const match = block.match(/^(\d+)[\.\)]\s*([\s\S]*)$/u);
        const item = document.createElement('div');
        item.className = 'ds-recommendation-blocks__item';

        const body = (match ? match[2] : block).trim();
        const paragraphs = body
            .split(/\n{2,}/)
            .map((para) => para.replace(/^\d+[\.\)]\s*/, '').replace(/\s*\n\s*/g, ' ').trim())
            .filter(Boolean);

        if (paragraphs.length === 0) {
            item.textContent = body.replace(/^\d+[\.\)]\s*/, '');
        } else {
            paragraphs.forEach((para, index) => {
                const p = document.createElement('p');
                p.className = 'ds-recommendation-blocks__para';
                if (index > 0) {
                    p.classList.add('ds-recommendation-blocks__para--spaced');
                }
                p.textContent = para;
                item.append(p);
            });
        }

        wrap.append(item);
    });

    container.append(wrap);
}

if (typeof window !== 'undefined') {
    window.DsDashboard = {
        createShowStatus,
        renderRecommendation,
    };

    window.dispatchEvent(new Event('ds-dashboard-ready'));
}

/**
 * Run callback after Vite bundle exposed DsDashboard (inline scripts load earlier).
 */
export function whenDsDashboardReady(callback) {
    if (typeof window !== 'undefined' && window.DsDashboard) {
        callback();
        return;
    }

    const run = () => {
        if (window.DsDashboard) {
            callback();
        }
    };

    if (typeof window !== 'undefined') {
        window.addEventListener('ds-dashboard-ready', run, { once: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run, { once: true });
    } else {
        queueMicrotask(run);
    }
}
