console.log("✅ dashboard.js ejecutado");

// CPU WIDGET
(() => {
    const canvas = document.getElementById("cpuChart");
    const totalEl = document.getElementById("cpu-total");
    const averageEl = document.getElementById("cpu-average");

    if (!canvas || !totalEl || !averageEl) {
        console.error("❌ Elementos del widget de CPU no encontrados");
        return;
    }

    const ctx = canvas.getContext("2d");

    const cpuChart = new Chart(ctx, {
        type: "bar",
        data: {
            labels: [],
            datasets: [{
                label: "Carga (%)",
                data: [],
                backgroundColor: "rgba(54, 162, 235, 0.6)",
                borderColor: "rgba(54, 162, 235, 1)",
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });

    function actualizarDatosCPU() {
        fetch("/dashboard/cpu_stats.php")
            .then(res => res.json())
            .then(data => {
                const cores = data.cores;
                cpuChart.data.labels = cores.map((_, i) => `Core ${i}`);
                cpuChart.data.datasets[0].data = cores;
                cpuChart.update();

                const total = cores.reduce((a, b) => a + b, 0);
                const average = (total / cores.length).toFixed(2);

                totalEl.textContent = total.toFixed(2);
                averageEl.textContent = average;

                console.log("📊 CPU actualizada:", cores);
            })
            .catch(err => {
                console.error("❌ Error al obtener datos de CPU:", err);
            });
    }

    actualizarDatosCPU();
    setInterval(actualizarDatosCPU, 5000);
})();

// RAM WIDGET
(() => {
    const canvas = document.getElementById("ramChart");
    const totalEl = document.getElementById("ram-total");
    const usedEl = document.getElementById("ram-used");
    const freeEl = document.getElementById("ram-free");
    const cachedEl = document.getElementById("ram-cached");

    if (!canvas || !totalEl || !usedEl || !freeEl || !cachedEl) {
        console.error("❌ Elementos del widget de RAM no encontrados");
        return;
    }

    const ctx = canvas.getContext("2d");

    const ramChart = new Chart(ctx, {
        type: "doughnut",
        data: {
            labels: ["En uso", "Libre", "Reservada"],
            datasets: [{
                data: [],
                backgroundColor: ["#ff6384", "#36a2eb", "#ffce56"],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: "bottom"
                }
            }
        }
    });

    function actualizarDatosRAM() {
        fetch("/dashboard/ram_stats.php")
            .then(res => res.json())
            .then(data => {
                const { total, used, free, cached } = data;

                totalEl.textContent = total;
                usedEl.textContent = used;
                freeEl.textContent = free;
                cachedEl.textContent = cached;

                ramChart.data.datasets[0].data = [used, free, cached];
                ramChart.update();

                console.log("📈 RAM actualizada:", data);
            })
            .catch(err => {
                console.error("❌ Error al obtener datos de RAM:", err);
            });
    }

    actualizarDatosRAM();
    setInterval(actualizarDatosRAM, 5000);
})();
