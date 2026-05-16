import ApexCharts from 'apexcharts';

export default function nutritionCharts(data) {
    return {
        donutChart: null,
        barChart: null,

        init() {
            this.$nextTick(() => {
                this.renderDonut();
                if (data.targets && this.$refs.bar) {
                    this.renderBar();
                }
            });
        },

        destroy() {
            this.donutChart?.destroy();
            this.barChart?.destroy();
        },

        renderDonut() {
            const proteinKcal = Math.round(data.protein_g * 4);
            const fatKcal = Math.round(data.fat_g * 9);
            const carbsKcal = Math.round(data.carbs_g * 4);
            const totalKcal = Math.round(data.total_kcal ?? proteinKcal + fatKcal + carbsKcal);

            const macroKeys = ['protein_g', 'fat_g', 'carbs_g'];
            const macroLabels = [data.labels.protein, data.labels.fat, data.labels.carbs];
            const macroColors = ['#10b981', '#f59e0b', '#6366f1'];
            const kcalPerGram = [4, 9, 4];

            const escapeHtml = (str) =>
                String(str).replace(/[&<>"']/g, (c) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;',
                }[c]));

            const customTooltip = ({ seriesIndex }) => {
                const macroKey = macroKeys[seriesIndex];
                const macroLabel = macroLabels[seriesIndex];
                const macroColor = macroColors[seriesIndex];
                const totalGrams = data[macroKey];
                const sliceKcal = Math.round(totalGrams * kcalPerGram[seriesIndex]);

                const rows = (data.breakdown || [])
                    .map((row) => ({ name: row.name, grams: row[macroKey] || 0 }))
                    .filter((row) => row.grams > 0)
                    .sort((a, b) => b.grams - a.grams);

                const list = rows
                    .map(
                        (row) =>
                            `<li style="display:flex;justify-content:space-between;gap:12px;padding:2px 0;">
                                <span style="color:#475569;">${escapeHtml(row.name)}</span>
                                <span style="color:#0f172a;font-weight:500;white-space:nowrap;">${row.grams.toFixed(1)}g</span>
                            </li>`,
                    )
                    .join('');

                return `
                    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;font-size:12px;min-width:200px;box-shadow:0 4px 12px rgba(15,23,42,0.08);">
                        <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                            <span style="display:inline-block;width:8px;height:8px;border-radius:9999px;background:${macroColor};"></span>
                            <span style="font-weight:600;color:#0f172a;">${escapeHtml(macroLabel)}</span>
                            <span style="margin-left:auto;color:#64748b;">${totalGrams.toFixed(1)}g · ${sliceKcal} ${escapeHtml(data.labels.kcal_unit)}</span>
                        </div>
                        ${rows.length ? `<ul style="list-style:none;margin:0;padding:0;">${list}</ul>` : ''}
                    </div>
                `;
            };

            this.donutChart = new ApexCharts(this.$refs.donut, {
                chart: {
                    type: 'donut',
                    height: 220,
                    fontFamily: 'inherit',
                },
                series: [proteinKcal, fatKcal, carbsKcal],
                labels: macroLabels,
                colors: macroColors,
                plotOptions: {
                    pie: {
                        donut: {
                            size: '60%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: data.labels.kcal_unit,
                                    formatter: () => totalKcal.toLocaleString(),
                                },
                            },
                        },
                    },
                },
                legend: {
                    position: 'bottom',
                    fontSize: '12px',
                    markers: { size: 6 },
                },
                dataLabels: {
                    formatter: (val) => Math.round(val) + '%',
                    style: { fontSize: '11px' },
                },
                tooltip: {
                    custom: customTooltip,
                },
                stroke: { width: 2, colors: ['#fff'] },
            });
            this.donutChart.render();
        },

        renderBar() {
            this.barChart = new ApexCharts(this.$refs.bar, {
                chart: {
                    type: 'bar',
                    height: 200,
                    fontFamily: 'inherit',
                    toolbar: { show: false },
                },
                series: [
                    {
                        name: data.labels.actual,
                        data: [
                            Math.round(data.protein_g * 10) / 10,
                            Math.round(data.fat_g * 10) / 10,
                            Math.round(data.carbs_g * 10) / 10,
                        ],
                    },
                    {
                        name: data.labels.target,
                        data: [
                            data.targets.protein_g,
                            data.targets.fat_g,
                            data.targets.carbs_g,
                        ],
                    },
                ],
                xaxis: {
                    categories: [data.labels.protein, data.labels.fat, data.labels.carbs],
                },
                yaxis: {
                    labels: {
                        formatter: (val) => Math.round(val) + 'g',
                    },
                },
                colors: ['#10b981', '#cbd5e1'],
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        columnWidth: '55%',
                    },
                },
                dataLabels: { enabled: false },
                legend: {
                    position: 'top',
                    fontSize: '12px',
                    markers: { size: 6 },
                },
                grid: {
                    borderColor: '#f1f5f9',
                    strokeDashArray: 4,
                },
            });
            this.barChart.render();
        },
    };
}
