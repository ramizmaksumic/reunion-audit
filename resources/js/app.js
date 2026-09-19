import Chart from 'chart.js/auto';

document.addEventListener('alpine:init', () => {
    /**
     * A circular "Lighthouse-style" score indicator (0-100), rendered as a
     * two-segment doughnut chart. Usage: x-data="scoreRing(76, '#2563eb')"
     * on an element containing a <canvas x-ref="canvas">.
     */
    Alpine.data('scoreRing', (score, color) => ({
        init() {
            new Chart(this.$refs.canvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [score, Math.max(0, 100 - score)],
                        backgroundColor: [color, 'rgba(161, 161, 170, 0.25)'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    cutout: '78%',
                    rotation: -90,
                    circumference: 360,
                    responsive: true,
                    maintainAspectRatio: true,
                    animation: { duration: 500 },
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false },
                    },
                },
            });
        },
    }));

    /**
     * A 0-100 bar chart comparing scores across a set of labelled categories.
     * Usage: x-data="scoreBarChart(['A', 'B'], [82, 71], ['#2563eb', '#16a34a'])"
     */
    Alpine.data('scoreBarChart', (labels, values, colors) => ({
        init() {
            new Chart(this.$refs.canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderRadius: 6,
                        maxBarThickness: 56,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            min: 0,
                            max: 100,
                            ticks: { stepSize: 25 },
                            grid: { color: 'rgba(161, 161, 170, 0.2)' },
                        },
                        x: {
                            grid: { display: false },
                        },
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false },
                    },
                    animation: { duration: 500 },
                },
            });
        },
    }));
});
