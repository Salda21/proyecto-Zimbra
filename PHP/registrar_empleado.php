<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["id_usuario"])) {
    header("Location: /proyecto-Zimbra/pages/login.html");
    exit;
}

$rutasPosibles = [
    __DIR__ . "/conexion.php",
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
    die("No se encontró conexion.php.");
}

require_once $archivoConexion;

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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /proyecto-Zimbra/pages/administracion.php");
    exit;
}

$nombre = trim($_POST["nombre"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$id_rol = (int) ($_POST["rol"] ?? 0);

// 1 = Administrador, 2 = Vendedor, 3 = Marketing
$rolesPermitidos = [1, 2, 3];

if ($nombre === "" || !filter_var($correo, FILTER_VALIDATE_EMAIL) || !in_array($id_rol, $rolesPermitidos, true)) {
    header("Location: /proyecto-Zimbra/pages/administracion.php?error=datos");
    exit;
}

// Verificar que el rol exista en la tabla rol
$stmtRol = $conexion->prepare("SELECT id_rol FROM rol WHERE id_rol = ? LIMIT 1");
$stmtRol->bind_param("i", $id_rol);
$stmtRol->execute();
$resRol = $stmtRol->get_result();
$rolValido = $resRol && $resRol->num_rows > 0;
$stmtRol->close();

if (!$rolValido) {
    header("Location: /proyecto-Zimbra/pages/administracion.php?error=rol");
    exit;
}

// Evitar correos duplicados
$stmtDup = $conexion->prepare("SELECT id_usuario FROM usuario WHERE email = ? LIMIT 1");
$stmtDup->bind_param("s", $correo);
$stmtDup->execute();
$resDup = $stmtDup->get_result();
$duplicado = $resDup && $resDup->num_rows > 0;
$stmtDup->close();

if ($duplicado) {
    header("Location: /proyecto-Zimbra/pages/administracion.php?error=correo_duplicado");
    exit;
}

$claveHash = password_hash("Zimbra2026*", PASSWORD_DEFAULT);

$stmt = $conexion->prepare("
    INSERT INTO usuario (rol, nombre, email, contraseña, fecha_registro)
    VALUES (?, ?, ?, ?, NOW())
");
$stmt->bind_param("isss", $id_rol, $nombre, $correo, $claveHash);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: /proyecto-Zimbra/pages/administracion.php?usuario=creado");
    exit;
} else {
    $stmt->close();
    header("Location: /proyecto-Zimbra/pages/administracion.php?error=registro");
    exit;
}