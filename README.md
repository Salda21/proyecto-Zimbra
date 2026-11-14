
# ZimbraConnect – Frontend

Frontend demostrativo inspirado en los nueve mockups entregados.

## Tecnologías
- HTML5
- CSS3 (variables de color, grid, flexbox y diseño responsive)
- JavaScript vanilla

## Cómo ejecutarlo
1. Descomprime `ZimbraConnect.zip`.
2. Abre la carpeta en Visual Studio Code.
3. Abre `index.html` con Live Server (recomendado) o sirve la carpeta desde Apache.
4. Entra desde la página principal. El acceso al panel está en las vistas internas bajo `pages/`.

## Estructura
- `index.html`: landing page.
- `registro.html`: registro e inicio de sesión.
- `pages/`: dashboard y módulos del CRM.
- `assets/css/`: estilos globales, variables y estilos por área.
- `assets/js/`: interacciones de cada módulo.
- `assets/img/logos/`: espacio para los recursos gráficos definitivos.

## Importante
Esta entrega es únicamente el frontend. Los formularios, chats, altas y acciones muestran interacciones de demostración en el navegador. Para producción se deben conectar con PHP y MySQL, implementar autenticación y permisos por rol (Administrador, Vendedor y Marketing), y enlazar el envío de comunicaciones comerciales con el servidor Zimbra propio.

Los reportes están configurados para generación manual y exportación compatible con Excel. No se implementan bandejas personales ni integraciones con Gmail, Outlook, Mailchimp o SendGrid.
