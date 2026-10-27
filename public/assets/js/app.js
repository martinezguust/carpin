/*
 * =====================================================
 * Carpintería Carpin – public/assets/js/app.js
 * Mejoras de la interfaz con JavaScript nativo. Las reglas
 * de negocio viven en el servidor; aquí solo se mejora la
 * experiencia (SRS 14.3, regla 8):
 *   - campana de notificaciones que se actualiza cada 60 s;
 *   - confirmación antes de acciones irreversibles (RNF-07);
 *   - botones de imprimir;
 *   - mostrar u ocultar bloques de formularios;
 *   - formas de pago permitidas según el rol;
 *   - abrir la pestaña indicada en la dirección (#actividades).
 * =====================================================
 */

"use strict";

/* ===== Configuración ===== */

// Cada cuánto se consulta la campana (RF-62)
const INTERVALO_CAMPANA_MS = 60000;

// URL base del sistema, ej. "http://localhost/carpin/public"
const URL_BASE = document.body.dataset.urlBase || "";


/* ===== Campana de notificaciones (RF-60 a RF-62) ===== */

/**
 * Pide al servidor las notificaciones y actualiza la campana.
 * La petición lleva el encabezado X-Carpin-Sondeo para que no
 * cuente como actividad del usuario (RF-02).
 *
 * Recibe: nada.
 * Devuelve: nada.
 */
function actualizarCampana() {
    const url = URL_BASE + "/notificaciones/json";

    fetch(url, {
        credentials: "same-origin",
        headers: {
            "Accept": "application/json",
            "X-Carpin-Sondeo": "1"
        }
    })
        .then(respuesta => {
            // Sesión vencida: se regresa al inicio de sesión
            if (respuesta.status === 401) {
                window.location.href = URL_BASE + "/login";
                return null;
            }

            return respuesta.json();
        })
        .then(datos => {
            if (datos !== null) {
                dibujarCampana(datos);
            }
        })
        .catch(error => {
            console.log("No se pudieron consultar las notificaciones:", error);
        });
}

/**
 * Dibuja el contador y la lista de la campana.
 *
 * Recibe: datos, objeto con sinLeer y notificaciones.
 * Devuelve: nada.
 */
function dibujarCampana(datos) {
    const contador = document.getElementById("campanaContador");
    const lista = document.getElementById("campanaLista");

    if (contador === null || lista === null) {
        return;
    }

    // Contador de no leídas
    contador.textContent = datos.sinLeer;
    contador.hidden = datos.sinLeer === 0;

    // Lista de las más recientes
    lista.replaceChildren();

    if (datos.notificaciones.length === 0) {
        const vacia = document.createElement("p");
        vacia.className = "campana__vacia";
        vacia.textContent = "No tienes notificaciones.";
        lista.appendChild(vacia);
        return;
    }

    datos.notificaciones.forEach(notificacion => {
        lista.appendChild(crearElementoCampana(notificacion));
    });
}

/**
 * Crea un aviso de la campana: un formulario que, al pulsarlo,
 * marca la notificación como leída y abre su enlace.
 *
 * Recibe: notificacion, objeto con id, mensaje, fecha y leida.
 * Devuelve: el elemento <form> listo para agregarse.
 */
function crearElementoCampana(notificacion) {
    const tokenCsrf = document.querySelector('meta[name="csrf-token"]').content;

    const formulario = document.createElement("form");
    formulario.method = "post";
    formulario.action = URL_BASE + "/notificaciones/" + notificacion.id + "/leer";

    const campoToken = document.createElement("input");
    campoToken.type = "hidden";
    campoToken.name = "_csrf";
    campoToken.value = tokenCsrf;

    const boton = document.createElement("button");
    boton.type = "submit";
    boton.className = "campana__elemento";

    if (!notificacion.leida) {
        boton.classList.add("campana__elemento--nueva");
    }

    const mensaje = document.createElement("span");
    mensaje.className = "campana__mensaje";
    mensaje.textContent = notificacion.mensaje;

    const fecha = document.createElement("span");
    fecha.className = "campana__fecha";
    fecha.textContent = notificacion.fecha;

    boton.append(mensaje, fecha);
    formulario.append(campoToken, boton);

    return formulario;
}


/* ===== Confirmación de acciones irreversibles (RNF-07) ===== */

/**
 * Pide confirmación antes de enviar los formularios que tienen
 * el atributo data-confirmar.
 *
 * Recibe: nada.
 * Devuelve: nada.
 */
function prepararConfirmaciones() {
    document.querySelectorAll("form[data-confirmar]").forEach(formulario => {
        formulario.addEventListener("submit", evento => {
            if (!window.confirm(formulario.dataset.confirmar)) {
                evento.preventDefault();
            }
        });
    });
}


/* ===== Botones de imprimir ===== */

/**
 * Hace que los botones con data-imprimir abran el cuadro de impresión.
 *
 * Recibe: nada.
 * Devuelve: nada.
 */
function prepararImpresion() {
    document.querySelectorAll("[data-imprimir]").forEach(boton => {
        boton.addEventListener("click", () => {
            window.print();
        });
    });
}


/* ===== Mostrar u ocultar bloques de un formulario ===== */

/**
 * Para los radios con data-muestra y data-oculta: al elegirlos,
 * muestra un bloque y oculta otro (ej. cliente nuevo / existente).
 *
 * Recibe: nada.
 * Devuelve: nada.
 */
function prepararBloquesAlternos() {
    document.querySelectorAll("input[type=radio][data-muestra]").forEach(radio => {
        radio.addEventListener("change", () => {
            const mostrar = document.querySelector(radio.dataset.muestra);
            const ocultar = document.querySelector(radio.dataset.oculta);

            if (radio.checked && mostrar !== null && ocultar !== null) {
                mostrar.hidden = false;
                ocultar.hidden = true;
            }
        });
    });
}


/* ===== Formas de pago según el rol ===== */

/**
 * En los formularios de usuario, deja disponibles solo las formas de
 * pago que permite el rol elegido. El servidor también lo valida.
 *
 * Recibe: nada.
 * Devuelve: nada.
 */
function prepararFormasDePago() {
    document.querySelectorAll("select[data-formas-por-rol]").forEach(selectorRol => {
        const formasPorRol = JSON.parse(selectorRol.dataset.formasPorRol);
        const selectorForma = document.querySelector(selectorRol.dataset.selectorForma);

        if (selectorForma === null) {
            return;
        }

        const aplicarRol = () => {
            const permitidas = formasPorRol[selectorRol.value];

            // Sin rol elegido: todas las opciones disponibles
            if (permitidas === undefined) {
                selectorForma.disabled = false;
                return;
            }

            // Administrador: sin forma de pago
            selectorForma.disabled = permitidas.length === 0;

            Array.from(selectorForma.options).forEach(opcion => {
                if (opcion.value !== "") {
                    opcion.disabled = !permitidas.includes(opcion.value);
                }
            });

            const opcionActual = selectorForma.selectedOptions[0];

            if (selectorForma.disabled || (opcionActual && opcionActual.disabled)) {
                selectorForma.value = "";
            }
        };

        selectorRol.addEventListener("change", aplicarRol);
        aplicarRol();
    });
}


/* ===== Pestaña indicada en la dirección ===== */

/**
 * Si la dirección termina en #nombre y existe una pestaña con ese
 * destino, la abre (ej. /proyectos/5#actividades).
 *
 * Recibe: nada.
 * Devuelve: nada.
 */
function abrirPestanaDeDireccion() {
    if (window.location.hash === "" || typeof bootstrap === "undefined") {
        return;
    }

    const selector = '[data-bs-toggle="tab"][data-bs-target="' + window.location.hash + '"]';
    const pestana = document.querySelector(selector);

    if (pestana !== null) {
        bootstrap.Tab.getOrCreateInstance(pestana).show();
    }
}


/* ===== Inicio ===== */

document.addEventListener("DOMContentLoaded", () => {
    prepararConfirmaciones();
    prepararImpresion();
    prepararBloquesAlternos();
    prepararFormasDePago();
    abrirPestanaDeDireccion();

    if (document.getElementById("campanaLista") !== null) {
        actualizarCampana();
        window.setInterval(actualizarCampana, INTERVALO_CAMPANA_MS);
    }
});
