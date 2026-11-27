<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$basePath = rtrim(str_replace("\\", "/", dirname(dirname($_SERVER["SCRIPT_NAME"]))), "/");

// Comprobar sesión antes de consultar la base de datos
if (!isset($_SESSION["id_usuario"])) {
    header("Location: " . $basePath . "/login.html");
    exit;
}

// Buscar conexion.php en rutas habituales del proyecto
$rutasPosibles = [
    __DIR__ . "/../conexion.php",
    __DIR__ . "/../PHP/conexion.php",
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
    die("No se encontró conexion.php. Revisa la carpeta donde guardaste el archivo de conexión.");
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

$id_usuario = (int) $_SESSION["id_usuario"];
$nombre = htmlspecialchars($_SESSION["nombre"] ?? "");

// Consultar descargas recientes una sola vez
$resultado = null;
$stmt = $conexion->prepare("
    SELECT
        DATE_FORMAT(fecha_descarga, '%d/%m/%Y') AS fecha_formateada,
        version,
        tipo,
        estado
    FROM descargas
    WHERE id_usuario = ?
    ORDER BY fecha_descarga DESC, id_descarga DESC
");

if ($stmt) {
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Descarga de software | ZimbraConnect</title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/componentes.css">
</head>

<body>
    <div class="bienvenida">
        <h1>Bienvenido a Zimbra, usuario <?php echo $nombre; ?>!👋</h1>
        <main class="app-main">
            <header class="topbar">
                <div style="display:flex;align-items:center;gap:12px">
                    <button class="icon-btn mobile-menu" data-menu-toggle>☰</button>
                    <div>
                        <b>Descarga de software</b>
                        <div class="muted" style="font-size:11px">ZimbraConnect / Descarga de software</div>
                    </div>
                </div>
                <div class="top-actions">
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
                    <a href="<?= htmlspecialchars($basePath . "/PHP/cerrar_sesion.php", ENT_QUOTES, "UTF-8") ?>">
                        <span class="avatar">X</span>
                    </a>
                </div>
            </header>
            <section class="page-content">
                <div class="card"
                    style="padding:28px;background:linear-gradient(115deg,#0756b8,#2c86f1);color:#fff;display:flex;justify-content:space-between;align-items:center;gap:20px;overflow:hidden">
                    <div>
                        <h2 style="color:white">⇩ Descarga Zimbra</h2>
                        <p>Obtén la versión gratuita o inicia tu prueba de 60 días del potente software de colaboración.</p>
                        <button class="btn btn-orange" id="btnActivarPrueba" type="button">
                            Activar prueba gratuita de 60 días
                        </button>
                    </div>
                    <div style="font-size:76px;opacity:.7">☁</div>
                </div>

                <div class="grid grid-2" style="margin-top:18px">
                    <div class="card panel" style="text-align:center">
                        <h2>Versión gratuita</h2>
                        <p class="muted">Uso ilimitado</p>
                       <a class="btn btn-outline"
                           href="javascript:void(0)"
                           data-href="<?= htmlspecialchars($basePath . "/PHP/descarga_gratuita.php", ENT_QUOTES, "UTF-8") ?>"
                           data-confirm="¿Deseas iniciar la descarga gratuita?">Descargar ahora</a>
                    </div>
                    <div class="card panel" style="text-align:center">
                       <h2>Versión premium</h2>
                        <p class="muted">Todas las funciones</p>
                        <a class="btn btn-primary"
                           href="javascript:void(0)"
                           data-href="<?= htmlspecialchars($basePath . "/PHP/descarga_premium.php", ENT_QUOTES, "UTF-8") ?>"
                           data-confirm="¿Deseas volverte premium?">Adquirir la versión premium</a>
                    </div>
                </div>

                <div class="card panel" style="margin-top:18px">
                    <div class="panel-title">Tus descargas recientes</div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Versión</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($resultado && $resultado->num_rows > 0): ?>
                                    <?php while ($fila = $resultado->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($fila['fecha_formateada']) ?></td>
                                            <td><?= htmlspecialchars($fila['version']) ?></td>
                                            <td><?= htmlspecialchars($fila['tipo']) ?></td>
                                            <td>
                                                <span text-success rounded-pill px-3 py-2" class="pill green">
                                                    <?= htmlspecialchars($fila['estado']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="muted">Aún no tienes descargas registradas.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>
    <script src="../assets/js/notificaciones.js"></script>
    <script src="../assets/js/bandeja_notificaciones.js"></script>
    <script src="../assets/js/prueba_usuario.js"></script>
    <script src="../assets/js/main.js"></script>
</body>

</html>