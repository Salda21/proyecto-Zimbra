
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

// 1. Verificar que el usuario haya registrado primero su versión Gratuita
$stmtGratuita = $conexion->prepare("
    SELECT id_descarga
    FROM descargas
    WHERE id_usuario = ? AND tipo = 'Gratuita'
    LIMIT 1
");
$stmtGratuita->bind_param("i", $id_usuario);
$stmtGratuita->execute();
$resGratuita = $stmtGratuita->get_result();
$tieneGratuita = $resGratuita && $resGratuita->num_rows > 0;
$stmtGratuita->close();

if (!$tieneGratuita) {
    header("Location: /proyecto-Zimbra/pages/inicio.php?premium=requiere_gratuita");
    exit;
}

// 2. Verificar que no haya activado ya la versión Premium (solo se permite 1 vez)
$stmtPremium = $conexion->prepare("
    SELECT id_descarga
    FROM descargas
    WHERE id_usuario = ? AND tipo = 'Premium'
    LIMIT 1
");
$stmtPremium->bind_param("i", $id_usuario);
$stmtPremium->execute();
$resPremium = $stmtPremium->get_result();
$yaTienePremium = $resPremium && $resPremium->num_rows > 0;
$stmtPremium->close();

if ($yaTienePremium) {
    header("Location: /proyecto-Zimbra/pages/inicio.php?premium=ya_usada");
    exit;
}

$version = 'Zimbra 14.1';
$tipo    = 'Premium'; // Coincide con ENUM ('Gratuita', 'Premium')
$estado  = 'Completada';

$conexion->begin_transaction();

try {
    // 3. Insertar registro Premium dejando fecha_fin_prueba en NULL
    $stmt = $conexion->prepare("
        INSERT INTO descargas (
            id_usuario,
            fecha_descarga,
            fecha_inicio_prueba,
            fecha_fin_prueba,
            version,
            tipo,
            estado
        ) VALUES (?, NOW(), NOW(), NULL, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $id_usuario, $version, $tipo, $estado);
    $stmt->execute();
    $stmt->close();

    // 4. Registrar la actividad en la tabla actividades
    $stmtAct = $conexion->prepare("
        INSERT INTO actividades (id_usuario, tipo_actividad, fecha_actividad)
        VALUES (?, 'descarga', NOW())
    ");
    $stmtAct->bind_param("i", $id_usuario);
    $stmtAct->execute();
    $stmtAct->close();

    $conexion->commit();

    // Redirigir de vuelta a pages/inicio.php
    header("Location: /proyecto-Zimbra/pages/inicio.php?premium=ok");
    exit;

} catch (Exception $e) {
    $conexion->rollback();
    die("Error al registrar la versión premium: " . $e->getMessage());
}
?>

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

$version = 'Zimbra 14.1';
$tipo    = 'Premium'; // Coincide con ENUM ('Gratuita', 'Premium')
$estado  = 'Completada';

$conexion->begin_transaction();

try {
    // 1. Insertar registro Premium dejando fecha_fin_prueba en NULL
    $stmt = $conexion->prepare("
        INSERT INTO descargas (
            id_usuario,
            fecha_descarga,
            fecha_inicio_prueba,
            fecha_fin_prueba,
            version,
            tipo,
            estado
        ) VALUES (?, NOW(), NOW(), NULL, ?, ?, ?)
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
    header("Location: /proyecto-Zimbra/pages/inicio.php?premium=ok");
    exit;

} catch (Exception $e) {
    $conexion->rollback();
    die("Error al registrar la versión premium: " . $e->getMessage());
}
?>
