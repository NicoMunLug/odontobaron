/* OdontoBarón · panel interno */
(function () {
  'use strict';

  var boton = document.querySelector('[data-lateral]');
  var lateral = document.getElementById('lateral');
  if (boton && lateral) {
    boton.addEventListener('click', function () {
      var abierto = lateral.getAttribute('data-abierto') === 'si';
      lateral.setAttribute('data-abierto', abierto ? 'no' : 'si');
      boton.setAttribute('aria-expanded', String(!abierto));
    });
    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape') {
        lateral.setAttribute('data-abierto', 'no');
        boton.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // Los filtros se aplican al cambiar, sin botón aparte
  document.querySelectorAll('[data-filtro]').forEach(function (campo) {
    campo.addEventListener('change', function () {
      campo.form.submit();
    });
  });

  document.querySelectorAll('[data-confirmar]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!window.confirm(form.getAttribute('data-confirmar'))) ev.preventDefault();
    });
  });
})();
