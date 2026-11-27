document.addEventListener('DOMContentLoaded', () => {
  const btnActivar = document.getElementById('btnActivarPrueba');
  const btnCampana = document.getElementById('btnNotificaciones');
  const panelNotificaciones = document.getElementById('panelNotificaciones');
  const listaNotificaciones = document.getElementById('listaNotificaciones');
  const contadorBadge = document.getElementById('contadorNotificaciones');
  const sinNotificaciones = document.getElementById('sinNotificaciones');
  const textoOriginalBoton = btnActivar?.textContent.trim() || 'Activar prueba gratuita de 60 días';

  let noLeidas = 0;
  cargarNotificaciones();

  if (btnActivar) {
    btnActivar.addEventListener('click', async () => {
      if (!window.ZCNotify) {
        console.error('No se cargó el sistema visual de notificaciones.');
        return;
      }

      if (location.protocol === 'file:') {
        window.ZCNotify.warning(
          'Para activar la prueba, abre esta página desde un servidor PHP; no funciona al abrir el archivo directamente.'
        );
        return;
      }

      const confirmado = await window.ZCNotify.confirm(
        '¿Confirmas la activación de tu prueba gratuita de Zimbra por 60 días?',
        {
          title: 'Activación de licencia de prueba',
          confirmText: 'Activar ahora',
          cancelText: 'Cancelar'
        }
      );

      if (!confirmado) return;

      btnActivar.disabled = true;
      btnActivar.textContent = 'Activando prueba...';

      try {
        const respuesta = await fetch('../PHP/activar_prueba.php', {
          method: 'POST'
        });
        const datos = await respuesta.json();

        if (!respuesta.ok || datos.status !== 'success') {
          throw new Error(datos.message || 'No se pudo completar la activación.');
        }

        window.ZCNotify.success(
          `Tu prueba gratuita está activa. Vence el ${datos.fecha_expiracion}.`,
          { title: datos.titulo, duration: 6000 }
        );
        agregarItemBandeja(datos.titulo, datos.mensaje, 'Ahora', true);
        btnActivar.textContent = '✓ Prueba de 60 días activa';
      } catch (error) {
        const mensaje = error instanceof Error ? error.message : 'Error de conexión con el servidor.';
        window.ZCNotify.error(mensaje);
        btnActivar.disabled = false;
        btnActivar.textContent = textoOriginalBoton;
      }
    });
  }

  if (btnCampana && panelNotificaciones) {
    btnCampana.addEventListener('click', async () => {
      const abierto = !panelNotificaciones.hidden;
      panelNotificaciones.hidden = abierto;
      btnCampana.setAttribute('aria-expanded', String(!abierto));

      if (abierto || noLeidas === 0) return;

      try {
        const respuesta = await fetch('../PHP/obtener_notificaciones.php?accion=leer', {
          method: 'POST'
        });
        if (!respuesta.ok) throw new Error('No se pudieron marcar las notificaciones como leídas.');

        noLeidas = 0;
        actualizarBadge();
        listaNotificaciones?.querySelectorAll('.notification-item--unread').forEach(item => {
          item.classList.remove('notification-item--unread');
        });
      } catch (error) {
        window.ZCNotify?.error(error instanceof Error ? error.message : 'Error de conexión con el servidor.');
      }
    });

    document.addEventListener('click', event => {
      if (!panelNotificaciones.contains(event.target) && !btnCampana.contains(event.target)) {
        panelNotificaciones.hidden = true;
        btnCampana.setAttribute('aria-expanded', 'false');
      }
    });
  }

  function agregarItemBandeja(titulo, mensaje, fecha, esNueva = false) {
    if (!listaNotificaciones) return;
    sinNotificaciones?.remove();

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
    listaNotificaciones.prepend(item);

    if (esNueva) {
      noLeidas++;
      actualizarBadge();
    }
  }

  function actualizarBadge() {
    if (!contadorBadge) return;
    contadorBadge.textContent = String(noLeidas);
    contadorBadge.hidden = noLeidas === 0;
  }

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

      if (datos.prueba_activa && btnActivar) {
        btnActivar.disabled = true;
        btnActivar.textContent = '✓ Prueba de 60 días activa';
      }

      [...datos.notificaciones].reverse().forEach(notificacion => {
        const esNueva = Number(notificacion.leida) === 0;
        agregarItemBandeja(notificacion.titulo, notificacion.mensaje, notificacion.fecha, esNueva);
      });
    } catch (error) {
      console.error('Error cargando notificaciones:', error);
    }
  }
});
