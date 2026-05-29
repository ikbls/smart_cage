let sensorChart;

// ================= FETCH REALTIME =================

async function realtime() {

    try {

        const response =
        await fetch(
            "api.php?t=" +
            Date.now(),
            {
                cache: "no-store"
            }
        );

        const result =
        await response.json();

        console.log(result);

        if (
            result.status === "success" &&
            result.data.length > 0
        ) {

            const latest =
            result.data[0];

            // ================= STATUS =================

            document.getElementById(
                "status-text"
            ).innerText =
            "Realtime Connected";

            // ================= TEMPERATURE =================

            document.getElementById(
                "temp-val"
            ).innerText =
            parseFloat(
                latest.temperature
            ).toFixed(1)
            + "°C";

            // ================= HUMIDITY =================

            document.getElementById(
                "hum-val"
            ).innerText =
            parseFloat(
                latest.humidity
            ).toFixed(1)
            + "%";

            // ================= FAN =================

            const fanCard =
            document.getElementById(
                "fan-card"
            );

            const fanStatus =
            document.getElementById(
                "fan-status"
            );

            const fanSubtext =
            document.getElementById(
                "fan-subtext"
            );

            if (
                latest.fan_status === "ON"
            ) {

                fanStatus.innerText =
                "ON";

                fanSubtext.innerText =
                "Relay Fan: ON";

                fanCard.classList.add(
                    "fan-active-card"
                );

            } else {

                fanStatus.innerText =
                "OFF";

                fanSubtext.innerText =
                "Relay Fan: OFF";

                fanCard.classList.remove(
                    "fan-active-card"
                );
            }

            // ================= LAMP =================

            const lampCard =
            document.getElementById(
                "lamp-card"
            );

            const lampStatus =
            document.getElementById(
                "lamp-status"
            );

            const lampSubtext =
            document.getElementById(
                "lamp-subtext"
            );

            if (
                latest.lamp_status === "ON"
            ) {

                lampStatus.innerText =
                "ON";

                lampSubtext.innerText =
                "Relay Lampu: ON";

                lampCard.classList.add(
                    "lamp-active-card"
                );

            } else {

                lampStatus.innerText =
                "OFF";

                lampSubtext.innerText =
                "Relay Lampu: OFF";

                lampCard.classList.remove(
                    "lamp-active-card"
                );
            }

            // ================= LOG =================

            const logList =
            document.getElementById(
                "log-list"
            );

            logList.innerHTML = "";

            result.data
            .slice(0, 8)
            .forEach(item => {

                logList.innerHTML += `

                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">

                    <p class="text-xs text-slate-400">

                        ${item.created_at}

                    </p>

                    <p class="font-bold mt-1">

                        🌡 ${item.temperature}°C
                        |
                        💧 ${item.humidity}%

                    </p>

                    <p class="text-sm mt-1">

                        FAN:
                        ${item.fan_status}

                        |

                        LAMP:
                        ${item.lamp_status}

                    </p>

                </div>
                `;
            });

            // ================= CHART =================

            const reversedData =
            [...result.data]
            .reverse()
            .slice(-10);

            sensorChart.data.labels =
            reversedData.map(item => {

                return new Date(
                    item.created_at
                ).toLocaleTimeString(
                    'id-ID'
                );
            });

            sensorChart.data.datasets[0].data =
            reversedData.map(item =>
                parseFloat(
                    item.temperature
                )
            );

            sensorChart.data.datasets[1].data =
            reversedData.map(item =>
                parseFloat(
                    item.humidity
                )
            );

            sensorChart.update();
        }

    } catch(error) {

        console.log(error);

        document.getElementById(
            "status-text"
        ).innerText =
        "Disconnected";
    }
}

// ================= INIT CHART =================

window.onload = function () {

    const ctx =
    document.getElementById(
        'sensorChart'
    ).getContext('2d');

    sensorChart =
    new Chart(ctx, {

        type: 'line',

        data: {

            labels: [],

            datasets: [

                {
                    label: 'Suhu',

                    data: [],

                    borderColor:
                    '#f97316',

                    backgroundColor:
                    'transparent',

                    borderWidth: 3,

                    tension: 0.4
                },

                {
                    label: 'Humidity',

                    data: [],

                    borderColor:
                    '#3b82f6',

                    backgroundColor:
                    'transparent',

                    borderWidth: 3,

                    tension: 0.4
                }
            ]
        },

        options: {

            responsive: true,

            maintainAspectRatio: false
        }
    });

    realtime();

    // ================= REALTIME =================

    setInterval(() => {

        realtime();

    }, 5000);
};