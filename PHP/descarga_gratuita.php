<?php
session_start();
require_once __DIR__ . "/conexion.php";

// Respaldo por si la conexión en conexion.php se llama $conn o $mysqli
if (!isset($conexion)) {
    if (isset($conn)) {
        $conexion = $conn;
    } elseif (isset($mysqli)) {
        $conexion = $mysqli;
    }
}

if (!isset($conexion) || !($conexion instanceof mysqli)) {
    die("Error de configuración: no se encontró la conexión a la base de datos.");
}

$id_usuario = $_SESSION['id_usuario'] ?? null;

if (!$id_usuario) {
    header("Location: /proyecto-Zimbra/pages/login.html");
    exit;
}

$version = 'Zimbra 14.0';
$tipo    = 'Gratuita'; // Coincide con ENUM ('Gratuita', 'Premium')
$estado  = 'Completada';

$conexion->begin_transaction();

try {
    // 1. Insertar registro en descargas sumando 60 días a fecha_inicio_prueba
    $stmt = $conexion->prepare("
        INSERT INTO descargas (
            id_usuario,
            fecha_descarga,
            fecha_inicio_prueba,
            fecha_fin_prueba,
            version,
            tipo,
            estado
        ) VALUES (?, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 60 DAY), ?, ?, ?)
    ");
    $stmt->bind_param("isss", $id_usuario, $version, $tipo, $estado);
    $stmt->execute();
    $stmt->close();

    // 2. Registrar la actividad en la tabla actividades
    $stmtAct = $conexion->prepare("
        INSERT INTO actividades (id_usuario, tipo_actividad, fecha_actividad)
        VALUES (?, 'descarga', NOW())
    ");
    $stmtAct->bind_param("i", $id_usuario);
    $stmtAct->execute();
    $stmtAct->close();

    $conexion->commit();

    // Redirigir de vuelta a pages/inicio.php
    header("Location: /proyecto-Zimbra/pages/inicio.php?descarga=ok");
    exit;

} catch (Exception $e) {
    $conexion->rollback();
    die("Error al registrar la descarga: " . $e->getMessage());
}