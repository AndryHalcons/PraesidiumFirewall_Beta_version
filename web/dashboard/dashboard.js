(() => {
    if (window.praesidiumDashboardCleanup) {
        window.praesidiumDashboardCleanup();
    }

    const root = document.getElementById('praesidium-dashboard');
    if (!root || typeof Chart === 'undefined') {
        return;
    }

    const i18n = window.PRAESIDIUM_DASHBOARD_I18N || {};
    const refreshMs = 5000;
    let stopped = false;
    let timerId = null;
    let previousNetworkSample = null;
    let cpuChart = null;
    let ramChart = null;

    const setStatus = (text, mode = '') => {
        const status = document.getElementById('dashboard-refresh-status');
        if (!status) return;
        status.textContent = text;
        status.dataset.mode = mode;
    };

    const formatBytes = bytes => {
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let value = Number(bytes) || 0;
        let unit = 0;
        while (value >= 1024 && unit < units.length - 1) {
            value /= 1024;
            unit += 1;
        }
        return `${value.toFixed(value >= 10 || unit === 0 ? 0 : 1)} ${units[unit]}`;
    };

    const formatRate = bytesPerSecond => `${formatBytes(bytesPerSecond)}/s`;

    const getJson = async url => {
        const response = await fetch(url, { cache: 'no-store' });
        const data = await response.json();
        if (!response.ok || data.error) {
            throw new Error(data.error || 'request failed');
        }
        return data;
    };

    const createCharts = () => {
        const cpuCanvas = document.getElementById('cpuChart');
        const ramCanvas = document.getElementById('ramChart');
        if (!cpuCanvas || !ramCanvas) return false;

        cpuChart = new Chart(cpuCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: i18n.cpuPercentLabel || 'CPU %',
                    data: [],
                    backgroundColor: 'rgba(58, 134, 255, 0.72)',
                    borderColor: 'rgba(58, 134, 255, 1)',
                    borderWidth: 1,
                    borderRadius: 5,
                    minBarLength: 3
                }]
            },
            options: {
                indexAxis: 'y',
                animation: false,
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: context => `${context.parsed.x.toFixed(1)}%`
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: 'rgba(148, 163, 184, 0.14)' },
                        ticks: { callback: value => `${value}%` }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { autoSkip: false }
                    }
                }
            }
        });

        ramChart = new Chart(ramCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: [i18n.ramUsedLabel || 'Used', i18n.ramFreeLabel || 'Free', i18n.ramCachedLabel || 'Cached'],
                datasets: [{
                    data: [0, 0, 0],
                    backgroundColor: ['#3a86ff', '#2dc653', '#ffbe0b'],
                    borderColor: '#111827',
                    borderWidth: 2
                }]
            },
            options: {
                animation: false,
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: { legend: { position: 'bottom' } }
            }
        });

        return true;
    };

    const updateCpu = async () => {
        const data = await getJson('/dashboard/cpu_stats.php');
        const cores = Array.isArray(data.cores) ? data.cores : [];
        const cpuBox = document.querySelector('.dashboard-chart-box-cpu');
        if (cpuBox) {
            const dynamicHeight = Math.min(760, Math.max(240, cores.length * 34));
            cpuBox.style.height = `${dynamicHeight}px`;
        }
        cpuChart.data.labels = cores.map((_, index) => `${i18n.coreLabel || 'Core'} ${index}`);
        cpuChart.data.datasets[0].data = cores.map(value => Number(value) || 0);
        cpuChart.update();

        const average = Number(data.average ?? 0);
        const averageEl = document.getElementById('dashboard-cpu-average');
        if (averageEl) averageEl.textContent = `${average.toFixed(1)}%`;

        const list = document.getElementById('dashboard-cpu-list');
        if (list) {
            list.innerHTML = cores.map((value, index) => {
                const percent = Math.max(0, Math.min(100, Number(value) || 0));
                const visibleWidth = percent > 0 ? percent : 1;
                return `
                    <div class="dashboard-core-pill">
                        <div class="dashboard-core-pill-top">
                            <span>${i18n.coreLabel || 'Core'} ${index}</span>
                            <strong>${percent.toFixed(1)}%</strong>
                        </div>
                        <div class="dashboard-core-bar" aria-hidden="true">
                            <span style="width: ${visibleWidth}%"></span>
                        </div>
                    </div>
                `;
            }).join('');
        }
    };

    const updateRam = async () => {
        const data = await getJson('/dashboard/ram_stats.php');
        const total = Number(data.total || 0);
        const used = Number(data.used || 0);
        const free = Number(data.free || 0);
        const cached = Number(data.cached || 0);
        const percent = Number(data.used_percent || 0);

        document.getElementById('ram-total').textContent = `${total} MB`;
        document.getElementById('ram-used').textContent = `${used} MB`;
        document.getElementById('ram-free').textContent = `${free} MB`;
        document.getElementById('ram-cached').textContent = `${cached} MB`;
        document.getElementById('dashboard-ram-used-percent').textContent = `${percent.toFixed(1)}%`;

        ramChart.data.datasets[0].data = [used, free, cached];
        ramChart.update();
    };

    const updateNetwork = async () => {
        const sample = await getJson('/dashboard/net_stats.php');
        const tbody = document.querySelector('#bandwidth-table tbody');
        if (!tbody) return;

        const interfaces = Array.isArray(sample.interfaces) ? sample.interfaces : [];
        if (!interfaces.length) {
            tbody.innerHTML = `<tr><td colspan="5">${i18n.noInterfaces || 'No interfaces'}</td></tr>`;
            previousNetworkSample = sample;
            return;
        }

        const previousByName = new Map((previousNetworkSample?.interfaces || []).map(item => [item.name, item]));
        const elapsed = previousNetworkSample ? Math.max(0.001, Number(sample.timestamp) - Number(previousNetworkSample.timestamp)) : 0;

        tbody.innerHTML = interfaces.map(item => {
            const previous = previousByName.get(item.name);
            const rxRate = previous ? Math.max(0, (item.rx_bytes - previous.rx_bytes) / elapsed) : 0;
            const txRate = previous ? Math.max(0, (item.tx_bytes - previous.tx_bytes) / elapsed) : 0;
            return `
                <tr>
                    <td><strong>${item.name}</strong></td>
                    <td>${formatRate(rxRate)}</td>
                    <td>${formatRate(txRate)}</td>
                    <td>${formatBytes(item.rx_bytes)}</td>
                    <td>${formatBytes(item.tx_bytes)}</td>
                </tr>
            `;
        }).join('');

        previousNetworkSample = sample;
    };

    const refresh = async () => {
        if (stopped) return;
        try {
            await Promise.all([updateCpu(), updateRam(), updateNetwork()]);
            setStatus(`${i18n.updated || 'Updated'} ${new Date().toLocaleTimeString()}`);
        } catch (error) {
            setStatus(i18n.error || 'Error', 'error');
        }
    };

    window.praesidiumDashboardCleanup = () => {
        stopped = true;
        if (timerId) {
            clearInterval(timerId);
            timerId = null;
        }
        if (cpuChart) {
            cpuChart.destroy();
            cpuChart = null;
        }
        if (ramChart) {
            ramChart.destroy();
            ramChart = null;
        }
    };

    if (createCharts()) {
        setStatus(i18n.loading || 'Loading...');
        refresh();
        timerId = setInterval(refresh, refreshMs);
    }
})();
