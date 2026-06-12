<style>
.chart-container-bas {
    width: 100%;
    height: 310px;
    margin-bottom: 40px;
    background-color: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    padding: 15px;
}
</style>

<!-- عناصر الرسم البياني -->
<div class="chart-container-bas">
    <canvas id="ChartMn"></canvas>
</div>
<div class="chart-container-bas">
    <canvas id="chart_last_hour_avg"></canvas>
</div>
<?php foreach (['chunk_0', 'chunk_1', 'chunk_2', 'chunk_3'] as $id): ?>
    <div class="chart-container-bas">
        <canvas id="chart_<?= $id ?>"></canvas>
    </div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", async function () {
    const response = await fetch("bas_chart_data.json");
    const data = await response.json();

    // ChartMn
    new Chart(document.getElementById("ChartMn").getContext('2d'), {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                { label: 'All', data: data.basmnbas, borderColor: 'red', fill: false, yAxisID: 'A' },
                { label: '1000 EV', data: data.basmnEV, borderColor: '#800080', fill: false, yAxisID: 'A' },
                { label: '12000 C24 BHUB', data: data.basmnC24Bhub, borderColor: '#f5458e', fill: false, yAxisID: 'A' },
                { label: '12001 C24 Direkt', data: data.basmnC24Direct, borderColor: '#005ea8', fill: false, yAxisID: 'A' },
                { label: '12100 HC', data: data.basmnHC, borderColor: '#ebc934', fill: false, yAxisID: 'A' },
                { label: '12300 INVIA', data: data.basmnINVIA, borderColor: '#47c6ed', fill: false, yAxisID: 'A' },
                { label: '10090 AMA', data: data.basmnAMA, borderColor: '#cc9f81', fill: false, yAxisID: 'A' },
                { label: 'Avg BA Sec', data: data.avgmin, borderColor: 'green', fill: false, yAxisID: 'B' }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                A: { type: 'linear', position: 'left' },
                B: { type: 'linear', position: 'right', grid: { drawOnChartArea: false } }
            }
        }
    });

    // chart_last_hour_avg
    new Chart(document.getElementById("chart_last_hour_avg").getContext('2d'), {
        type: 'line',
        data: {
            labels: data.avgmin_labels,
            datasets: [
                { label: 'COUNT BA Minute', data: data.basmnbas, borderColor: '#4e4ef5', fill: false, yAxisID: 'A' },
                { label: 'Avg BA Sec', data: data.avgmin, borderColor: 'green', fill: false, yAxisID: 'B' }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            elements: {
                line: {
                    tension: 0.5,
                    borderWidth: 1.2
                },
                point: {
                    radius: 1.5,
                    hoverRadius: 4,
                    backgroundColor: (ctx) => ctx.dataset.borderColor
                }
            },
            plugins: {
                legend: {
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'rectRounded',
                        boxWidth: 12
                    }
                }
            },
            scales: {
                A: { type: 'linear', position: 'left' },
                B: { type: 'linear', position: 'right', grid: { drawOnChartArea: false } }
            }
        }
    });

    // رسم المخططات الزمنية chunk_0 ~ chunk_3
    Object.entries(data.chunks).forEach(([id, chunk]) => {
        const ctx = document.getElementById(`chart_${id}`).getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: Object.keys(chunk.count),
                datasets: [
                    { label: 'COUNT BA Minute', data: Object.values(chunk.count), borderColor: '#4e4ef5', fill: false, yAxisID: 'A' },
                    { label: 'Avg BA Sec', data: Object.values(chunk.avg).map(v => Math.round(v * 10) / 10), borderColor: 'green', fill: false, yAxisID: 'B' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                elements: {
                    line: { tension: 0.5, borderWidth: 0.9 },
                    point: { radius: 1, hoverRadius: 4 }
                },
                scales: {
                    x: {
                        ticks: {
                            callback: function(value, index, ticks) {
                                const label = this.getLabelForValue(value);
                                return label.includes(" ") ? label.split(" ")[1] : label;
                            }
                        }
                    },
                    A: { type: 'linear', position: 'left' },
                    B: { type: 'linear', position: 'right', grid: { drawOnChartArea: false } }
                }
            }
        });
    });
});
</script>

        
