/*
 * =====================================================
 * Carpintería Carpin – public/assets/js/avance.js
 * Gráfica de dona con el avance del mueble para el cliente
 * (RF-30, mockup 8). Usa Chart.js 4 (local) y los colores
 * de la paleta: verde para lo completado y gris cálido para
 * lo pendiente (SRS 10.1).
 * =====================================================
 */

"use strict";

/**
 * Lee un color de las variables CSS de la paleta.
 *
 * Recibe: nombre, variable CSS, ej. "--carpin-exito".
 * Devuelve: el color en texto, ej. "#4E8F3A".
 */
function colorDePaleta(nombre) {
    return getComputedStyle(document.documentElement).getPropertyValue(nombre).trim();
}

/**
 * Dibuja la dona de avance en el <canvas id="graficaAvance">.
 * El porcentaje viene en el atributo data-avance.
 *
 * Recibe: nada.
 * Devuelve: nada.
 */
function dibujarGraficaAvance() {
    const lienzo = document.getElementById("graficaAvance");

    if (lienzo === null || typeof Chart === "undefined") {
        return;
    }

    const avance = Math.min(100, Math.max(0, parseFloat(lienzo.dataset.avance) || 0));
    const pendiente = Math.round((100 - avance) * 100) / 100;

    new Chart(lienzo, {
        type: "doughnut",
        data: {
            labels: ["Completado", "Pendiente"],
            datasets: [
                {
                    data: [avance, pendiente],
                    backgroundColor: [
                        colorDePaleta("--carpin-exito"),
                        colorDePaleta("--carpin-pendiente")
                    ],
                    borderWidth: 0
                }
            ]
        },
        options: {
            cutout: "72%",
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: false
                }
            }
        }
    });
}

document.addEventListener("DOMContentLoaded", dibujarGraficaAvance);
