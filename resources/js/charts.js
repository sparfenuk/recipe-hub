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
            const totalKcal = proteinKcal + fatKcal + carbsKcal;

            this.donutChart = new ApexCharts(this.$refs.donut, {
                chart: {
                    type: 'donut',
                    height: 220,
                    fontFamily: 'inherit',
                },
                series: [proteinKcal, fatKcal, carbsKcal],
                labels: [data.labels.protein, data.labels.fat, data.labels.carbs],
                colors: ['#10b981', '#f59e0b', '#6366f1'],
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
                    y: {
                        formatter: (val) => val + ' ' + data.labels.kcal_unit,
                    },
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
