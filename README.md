
# ZimbraConnect

Sitio web de ZimbraConnect con páginas HTML/CSS/JavaScript y endpoints PHP conectados a MySQL.

## Tecnologías
- HTML5
- CSS3 (variables de color, grid, flexbox y diseño responsive)
- JavaScript vanilla

## Cómo ejecutarlo localmente
1. Instala PHP y MySQL (o usa una distribución como XAMPP) e inicia el servidor web y MySQL.
2. Comprueba que PHP tenga habilitada la extensión `mysqli` (`php -m` debe mostrar `mysqli`). Si no aparece, puedes habilitarla solo para el servidor integrado con `php -d extension=mysqli -S localhost:8000`, o habilítala en el `php.ini` que indica `php --ini` y reinicia el servidor web.
3. Configura en `PHP/conexion.php` el host, usuario, contraseña y nombre de la base de datos de tu instalación.
4. Desde la carpeta raíz del proyecto, inicia el servidor integrado de PHP con `php -S localhost:8000` (o el comando del paso 2 si MySQLi no está habilitado globalmente), o configura el proyecto bajo la carpeta web de Apache.
5. Abre `http://localhost:8000/login.html` o `http://localhost:8000/registro.html`.

No abras los archivos mediante `file://` ni uses Live Server para probar el inicio de sesión o el registro: esos modos no ejecutan PHP. Si el servidor web usa otro puerto o una subcarpeta, ajusta la URL y las rutas de redirección PHP según corresponda.

## Estructura
- `index.html`: landing page.
- `registro.html`: registro e inicio de sesión.
- `pages/`: dashboard y módulos del CRM.
- `assets/css/`: estilos globales, variables y estilos por área.
- `assets/js/`: interacciones de cada módulo.
- `assets/img/logos/`: espacio para los recursos gráficos definitivos.

## Importante
Los formularios de inicio de sesión y registro envían sus datos a los endpoints de `PHP/`. Un error del navegador indicando que no pudo conectar con el servidor suele significar que la página no se sirve desde Apache/PHP o que la ruta del endpoint no existe; un error de MySQL se debe revisar después de confirmar que PHP está ejecutándose y que `mysqli` está habilitado.

Los clientes que activan la prueba de 60 días llegan a su chat privado al iniciar sesión. Desde allí pueden buscar otros clientes por nombre, iniciar conversaciones y recibir mensajes y notificaciones. Esta función usa las tablas existentes `conversaciones`, `mensajes` y `notificaciones`; requiere PHP, MySQL y sesiones activas.

Para producción se deben revisar la autenticación y los permisos por rol (Administrador, Vendedor y Marketing), y enlazar el envío de comunicaciones comerciales con el servidor Zimbra propio.

Los reportes están configurados para generación manual y exportación compatible con Excel. No se implementan bandejas personales ni integraciones con Gmail, Outlook, Mailchimp o SendGrid.
