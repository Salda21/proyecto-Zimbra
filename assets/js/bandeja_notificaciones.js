(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const boton = document.getElementById('btnNotificaciones');
    const panel = document.getElementById('panelNotificaciones');
    const lista = document.getElementById('listaNotificaciones');
    const badge = document.getElementById('contadorNotificaciones');
    const vacio = document.getElementById('sinNotificaciones');
    const botonActivar = document.getElementById('btnActivarPrueba');

    if (!boton || !panel || !lista) return;

    let noLeidas = 0;

    function actualizarBadge() {
      if (!badge) return;
      badge.textContent = String(noLeidas);
      badge.hidden = noLeidas === 0;
    }

    function agregarItem(titulo, mensaje, fecha, esNueva = false) {
      vacio?.remove();

      const item = document.createElement('li');
      item.className = `notification-item${esNueva ? ' notification-item--unread' : ''}`;

      const encabezado = document.createElement('div');
      encabezado.className = 'notification-item__header';
      const tituloElemento = document.createElement('strong');
      tituloElemento.textContent = titulo;
      const fechaElemento = document.createElement('time');
      fechaElemento.textContent = fecha;
      encabezado.append(tituloElemento, fechaElemento);

      const mensajeElemento = document.createElement('p');
      mensajeElemento.textContent = mensaje;
      item.append(encabezado, mensajeElemento);
      lista.prepend(item);

      if (esNueva) {
        noLeidas++;
        actualizarBadge();
      }
    }

    boton.addEventListener('click', async () => {
      const abierto = !panel.hidden;
      panel.hidden = abierto;
      boton.setAttribute('aria-expanded', String(!abierto));

      if (abierto || noLeidas === 0) return;

      try {
        const respuesta = await fetch('../PHP/obtener_notificaciones.php?accion=leer', {
          method: 'POST'
        });
        if (!respuesta.ok) throw new Error('No se pudieron marcar las notificaciones como leídas.');

        noLeidas = 0;
        actualizarBadge();
        lista.querySelectorAll('.notification-item--unread').forEach(item => {
          item.classList.remove('notification-item--unread');
        });
      } catch (error) {
        window.ZCNotify?.error(error instanceof Error ? error.message : 'Error de conexión con el servidor.');
      }
    });

    document.addEventListener('click', event => {
      if (!panel.contains(event.target) && !boton.contains(event.target)) {
        panel.hidden = true;
        boton.setAttribute('aria-expanded', 'false');
      }
    });

    window.ZCNotificationInbox = { add: agregarItem };

    async function cargarNotificaciones() {
      if (location.protocol === 'file:') return;

      try {
        const respuesta = await fetch('../PHP/obtener_notificaciones.php');
        if (respuesta.status === 401) return;
        if (!respuesta.ok) throw new Error('No se pudieron cargar las notificaciones.');

        const datos = await respuesta.json();
        if (!Array.isArray(datos.notificaciones)) {
          throw new Error('El servidor devolvió un formato de notificaciones no válido.');
        }

        if (datos.prueba_activa && botonActivar) {
          botonActivar.disabled = true;
          botonActivar.textContent = '✓ Prueba de 60 días activa';
        }

        [...datos.notificaciones].reverse().forEach(notificacion => {
          agregarItem(
            notificacion.titulo,
            notificacion.mensaje,
            notificacion.fecha,
            Number(notificacion.leida) === 0
          );
        });
      } catch (error) {
        console.error('Error cargando notificaciones:', error);
      }
    }

    cargarNotificaciones();
  });
})();
