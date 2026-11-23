/* OdontoBarón · comportamiento del sitio público */
(function () {
  'use strict';

  // --- Menú en móvil ------------------------------------------------
  var boton = document.querySelector('[data-menu]');
  var menu  = document.getElementById('menu-principal');

  if (boton && menu) {
    boton.addEventListener('click', function () {
      var abierto = menu.getAttribute('data-abierto') === 'si';
      menu.setAttribute('data-abierto', abierto ? 'no' : 'si');
      boton.setAttribute('aria-expanded', String(!abierto));
    });
    menu.addEventListener('click', function (ev) {
      if (ev.target.tagName === 'A') {
        menu.setAttribute('data-abierto', 'no');
        boton.setAttribute('aria-expanded', 'false');
      }
    });
    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape' && menu.getAttribute('data-abierto') === 'si') {
        menu.setAttribute('data-abierto', 'no');
        boton.setAttribute('aria-expanded', 'false');
        boton.focus();
      }
    });
  }

  // --- Línea en la cabecera al bajar --------------------------------
  var cabecera = document.querySelector('[data-cabecera]');
  if (cabecera) {
    var marcar = function () {
      cabecera.setAttribute('data-fija', window.scrollY > 8 ? 'si' : 'no');
    };
    marcar();
    window.addEventListener('scroll', marcar, { passive: true });
  }

  // --- Selección de día y hora en el agendamiento -------------------
  var agenda = document.querySelector('[data-agenda]');
  if (agenda) {
    var campoFecha = agenda.querySelector('[name="fecha"]');
    var campoHora  = agenda.querySelector('[name="hora"]');
    var cajaHoras  = agenda.querySelector('[data-horas]');
    var resumenDia = agenda.querySelector('[data-resumen-dia]');
    var enviar     = agenda.querySelector('[data-enviar]');
    var disponibles = {};

    try {
      disponibles = JSON.parse(agenda.getAttribute('data-agenda') || '{}');
    } catch (e) {
      disponibles = {};
    }

    var revisarEnvio = function () {
      if (!enviar) return;
      var listo = campoFecha.value && campoHora.value;
      enviar.toggleAttribute('disabled', !listo);
    };

    var pintarHoras = function (fecha) {
      var horas = disponibles[fecha] || [];
      cajaHoras.innerHTML = '';
      campoHora.value = '';

      if (!horas.length) {
        cajaHoras.innerHTML = '<p class="plomo menudo">No quedan horarios libres ese día. Elija otra fecha.</p>';
        revisarEnvio();
        return;
      }

      horas.forEach(function (h) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'hora';
        b.setAttribute('aria-pressed', 'false');
        b.textContent = formatoHora(h);
        b.addEventListener('click', function () {
          cajaHoras.querySelectorAll('.hora').forEach(function (o) {
            o.setAttribute('aria-pressed', 'false');
          });
          b.setAttribute('aria-pressed', 'true');
          campoHora.value = h;
          revisarEnvio();
        });
        cajaHoras.appendChild(b);
      });
      revisarEnvio();
    };

    agenda.querySelectorAll('.dia').forEach(function (dia) {
      dia.addEventListener('click', function () {
        agenda.querySelectorAll('.dia').forEach(function (o) {
          o.setAttribute('aria-pressed', 'false');
        });
        dia.setAttribute('aria-pressed', 'true');
        campoFecha.value = dia.dataset.fecha;
        if (resumenDia) resumenDia.textContent = dia.dataset.legible || '';
        pintarHoras(dia.dataset.fecha);
      });
    });

    // abre con el primer día disponible
    var primero = agenda.querySelector('.dia');
    if (primero) primero.click();
  }

  function formatoHora(hhmm) {
    var p = hhmm.split(':');
    var h = parseInt(p[0], 10);
    var sufijo = h < 12 ? 'a. m.' : 'p. m.';
    var h12 = h % 12 === 0 ? 12 : h % 12;
    return h12 + ':' + p[1] + ' ' + sufijo;
  }

  // --- Confirmación antes de cancelar -------------------------------
  document.querySelectorAll('[data-confirmar]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!window.confirm(form.getAttribute('data-confirmar'))) {
        ev.preventDefault();
      }
    });
  });
})();
