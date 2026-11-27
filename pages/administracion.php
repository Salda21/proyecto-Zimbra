<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Comprobar que exista una sesión
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}

// Buscar conexion.php en las rutas habituales del proyecto
$rutasPosibles = [
    __DIR__ . "/../PHP/conexion.php",
    __DIR__ . "/../conexion.php",
    __DIR__ . "/../config/conexion.php",
    __DIR__ . "/../includes/conexion.php",
];

$archivoConexion = null;
foreach ($rutasPosibles as $ruta) {
    if (file_exists($ruta)) {
        $archivoConexion = $ruta;
        break;
    }
}

if (!$archivoConexion) {
    die("No se encontró conexion.php. Revisa en qué carpeta está guardado.");
}

require_once $archivoConexion;

// Unificar nombre de variable de conexión si usa $conn o $mysqli
if (!isset($conexion)) {
    if (isset($conn)) {
        $conexion = $conn;
    } elseif (isset($mysqli)) {
        $conexion = $mysqli;
    }
}

if (!isset($conexion) || !($conexion instanceof mysqli)) {
    die("La variable de conexión no es una instancia válida de mysqli.");
}

$nombre = htmlspecialchars($_SESSION["nombre"] ?? "");

$usuariosRes = $conexion->query("
    SELECT
        u.id_usuario,
        u.nombre,
        u.email,
        r.nombre_rol
    FROM usuario u
    INNER JOIN rol r ON u.rol = r.id_rol
    WHERE r.nombre_rol IN ('Administrador', 'Marketing', 'Vendedor')
    ORDER BY u.id_usuario DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración | ZimbraConnect</title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/componentes.css">
</head>
<body>
<div class="app-layout">
 <aside class="sidebar">
    <a class="brand" href="../index.html"><span class="brand-mark">➤</span>ZimbraConnect</a>
        <div class="side-label">Menú principal</div>
        <a class="side-link " href="dashboard.html"><span>▦</span>Dashboard</a>
            <a class="side-link " href="chat.html">    <span>◉</span>Chats</a>
            <a class="side-link " href="visitantes.html"><span>◌</span>Visitantes</a>
            <a class="side-link " href="prospectos.html"><span>♧</span>Prospectos</a>
            <a class="side-link " href="descargas.html"><span>⇩</span>Descargas</a>
            <a class="side-link " href="propuestas.html"><span>▤</span>Propuestas y ventas</a>
            <a class="side-link " href="marketing.html"><span>✉</span>Marketing</a>
            <a class="side-link " href="reportes.html"><span>▥</span>Reportes</a>
            <a class="side-link " href="alertas.html"><span>♢</span>Alertas</a>
            <a class="side-link active" href="administracion.php"><span>⚙</span>Configuración</a>
            <div class="sidebar-user">
                <span class="avatar">AD</span>
                <div>
                    <b><?php echo $nombre; ?></b>
                    <br><span style="color:#b8d3f3">Administrador</span>
                </div>
            </div>
        </aside>
<main class="app-main">
    <header class="topbar">
        <div style="display:flex;align-items:center;gap:12px">
            <button class="icon-btn mobile-menu" data-menu-toggle>☰</button>
    <div>
        <b>Administración</b>
        <div class="muted" style="font-size:11px">ZimbraConnect / Administración</div>
    </div>
</div>
<div class="top-actions">
    <input class="search" placeholder="Buscar..." style="max-width:210px">
    <div class="notification-menu">
        <button class="icon-btn notification-trigger" id="btnNotificaciones" type="button"
            title="Notificaciones" aria-label="Abrir notificaciones"
            aria-controls="panelNotificaciones" aria-expanded="false">
            ♧<span class="notification-badge" id="contadorNotificaciones" hidden>0</span>
        </button>
        <section class="notification-panel" id="panelNotificaciones" aria-label="Notificaciones" hidden>
            <h2>Notificaciones</h2>
            <ul id="listaNotificaciones" class="notification-list">
                <li class="notification-empty" id="sinNotificaciones">No tienes notificaciones.</li>
            </ul>
        </section>
    </div>
    <span class="avatar">AT</span></div></header><section class="page-content">
<div class="section-head">
    <div>
        <h2>Gestión de usuarios</h2>
        <p>Administra los accesos, roles y estados de los usuarios.</p>
    </div>
    <button class="btn btn-primary" data-modal-open="#userModal">＋ Nuevo usuario</button>
</div>
<div class="card panel">
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
        <input data-search="#userTable" placeholder="⌕ Buscar usuario..." style="max-width:280px">
        <select data-filter="#userTable" style="max-width:180px">
            <option value="">Todos los roles</option>
            <option>Administrador</option>
            <option>Vendedor</option>
            <option>Marketing</option>
        </select></div><div class="table-wrap">
            <table id="userTable">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($usuariosRes && $usuariosRes->num_rows > 0): ?>
                        <?php while ($u = $usuariosRes->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($u['nombre']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['nombre_rol']) ?></td>
                <td><span class="pill green">Activo</span></td>
                <td>✎ 🗑</td>
            </tr>
            <?php endwhile; ?>
            <?php else: ?>
        <tr>
            <td colspan="5" class="muted">No hay usuarios registrados.</td>
        </tr>
    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<div class="modal" id="userModal">
    <div class="modal-card">
        <button class="icon-btn close" data-modal-close>✕</button>
        <h2>Nuevo usuario</h2>
        <form id="userForm" action="/proyecto-Zimbra/PHP/registrar_empleado.php" method="POST">
            <div class="field">
                <label>Nombre</label>
                <input name="nombre" required>
            </div>
            <div class="field">
                <label>Correo</label>
                <input name="correo" type="email" required>
            </div>
            <div class="field">
                <label>Rol</label>
                <select name="rol">
                    <option value="1">Administrador</option>
                    <option value="2">Vendedor</option>
                    <option value="3">Marketing</option>
                </select>
            </div>
           <button class="btn btn-primary" type="submit">Crear usuario</button>
        </form>
    </div>
</div>
</section>
</main>
</div>
<script src="../assets/js/notificaciones.js"></script>
<script src="../assets/js/bandeja_notificaciones.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/administracion.js"></script>
</body>
</html>