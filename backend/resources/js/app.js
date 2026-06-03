import './bootstrap';
import './date-picker';
import './dashboard-ui';
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
                    borderColor: '#6F63FF',
                    backgroundColor: 'rgba(111, 99, 255, 0.12)',
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
                        ticks: { color: '#8C92A3' },
                        grid: { color: 'rgba(0, 0, 0, 0.06)' },
                    },
                    x: {
                        ticks: { color: '#8C92A3' },
                        grid: { color: 'rgba(0, 0, 0, 0.04)' },
                    },
                },
                plugins: {
                    legend: { labels: { color: '#5F6473' } },
                },
            },
        });
    }
}
