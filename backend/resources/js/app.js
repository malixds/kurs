import './bootstrap';
import Chart from 'chart.js/auto';

const dataElement = document.getElementById('dashboard-data');

if (dataElement) {
    const payload = JSON.parse(dataElement.textContent);

    const trendLabels = payload.trends.map((item) => item.date);
    const trendValues = payload.trends.map((item) => item.average_score);

    const trendCanvas = document.getElementById('moodTrendChart');
    if (trendCanvas) {
        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Average mood',
                    data: trendValues,
                    borderColor: '#818cf8',
                    backgroundColor: 'rgba(129, 140, 248, 0.15)',
                    fill: true,
                    tension: 0.35,
                }],
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        suggestedMin: 1,
                        suggestedMax: 5,
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(148, 163, 184, 0.15)' },
                    },
                    x: {
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(148, 163, 184, 0.08)' },
                    },
                },
                plugins: {
                    legend: { labels: { color: '#cbd5e1' } },
                },
            },
        });
    }

    const departmentCanvas = document.getElementById('departmentChart');
    if (departmentCanvas) {
        new Chart(departmentCanvas, {
            type: 'bar',
            data: {
                labels: payload.departments.map((item) => item.department_name),
                datasets: [{
                    label: 'Department average',
                    data: payload.departments.map((item) => item.average_score),
                    backgroundColor: '#6366f1',
                }],
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        suggestedMin: 1,
                        suggestedMax: 5,
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(148, 163, 184, 0.15)' },
                    },
                    x: {
                        ticks: { color: '#94a3b8' },
                        grid: { display: false },
                    },
                },
                plugins: {
                    legend: { display: false },
                },
            },
        });
    }
}
