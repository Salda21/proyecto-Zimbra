document.addEventListener('DOMContentLoaded', () => {
  const btnActivar = document.getElementById('btnActivarPrueba');
  const textoOriginalBoton = btnActivar?.textContent.trim() || 'Activar prueba gratuita de 60 días';

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
        window.ZCNotificationInbox.add(datos.titulo, datos.mensaje, 'Ahora', true);
        btnActivar.textContent = '✓ Prueba de 60 días activa';
      } catch (error) {
        const mensaje = error instanceof Error ? error.message : 'Error de conexión con el servidor.';
        window.ZCNotify.error(mensaje);
        btnActivar.disabled = false;
        btnActivar.textContent = textoOriginalBoton;
      }
    });
  }

});
