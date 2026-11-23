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

$id_usuario = (int) ($_SESSION['id_usuario'] ?? 0);

if (!$id_usuario) {
    header("Location: /proyecto-Zimbra/pages/login.html");
    exit;
}

// Verificar que no haya registrado ya la versión Gratuita (solo se permite 1 vez)
$stmtGratuita = $conexion->prepare("
    SELECT id_descarga
    FROM descargas
    WHERE id_usuario = ? AND tipo = 'Gratuita'
    LIMIT 1
");
$stmtGratuita->bind_param("i", $id_usuario);
$stmtGratuita->execute();
$resGratuita = $stmtGratuita->get_result();
$yaTieneGratuita = $resGratuita && $resGratuita->num_rows > 0;
$stmtGratuita->close();

if ($yaTieneGratuita) {
    header("Location: /proyecto-Zimbra/pages/inicio.php?descarga=ya_usada");
    exit;
}

$version = 'Zimbra 14.0';
$tipo    = 'Gratuita';
$estado  = 'Completada';

$conexion->begin_transaction();

try {
    // 1. Insertar registro de descarga Gratuita
    $stmt = $conexion->prepare("
        INSERT INTO descargas (
            id_usuario,
            fecha_descarga,
            version,
            tipo,
            estado
        ) VALUES (?, NOW(), ?, ?, ?)
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
    die("Error al registrar la descarga gratuita: " . $e->getMessage());
}

?>

