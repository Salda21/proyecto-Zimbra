<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');
require_once __DIR__ . "/conexion.php";

function responder(int $codigo, array $respuesta): void
{
    http_response_code($codigo);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(405, ["status" => "error", "message" => "Método no permitido."]);
}

if (!isset($_SESSION["id_usuario"], $_SESSION["rol"]) || (int)$_SESSION["rol"] !== 4) {
    responder(401, ["status" => "error", "message" => "Debes iniciar sesión como cliente para activar la prueba."]);
}

$id_usuario = (int)$_SESSION["id_usuario"];
$titulo = "¡Prueba de 60 días activada!";
$fecha_fin = date("Y-m-d", strtotime("+60 days"));
$transaccionIniciada = false;

try {
    // Esta notificación sirve también como registro de una activación previa.
    $check = $conexion->prepare(
        "SELECT id_notificacion FROM notificaciones
         WHERE id_usuario = ? AND tipo = 'Alerta comercial'
           AND contenido LIKE ? LIMIT 1"
    );
    $prefijoActivacion = $titulo . "%";
    $check->bind_param("is", $id_usuario, $prefijoActivacion);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        responder(409, [
            "status" => "error",
            "message" => "Ya tienes una prueba gratuita de 60 días activa en tu cuenta."
        ]);
    }

    $mensaje = "Tu prueba gratuita de Zimbra está activa. Vence el $fecha_fin.";
    $contenido = "$titulo - $mensaje";
    $conexion->begin_transaction();
    $transaccionIniciada = true;

    $stmtNotificacion = $conexion->prepare(
        "INSERT INTO notificaciones
         (id_usuario, tipo, contenido, fecha_generacion, estado)
         VALUES (?, 'Alerta comercial', ?, NOW(), 'No leida')"
    );
    $stmtNotificacion->bind_param("is", $id_usuario, $contenido);
    $stmtNotificacion->execute();

    $conexion->commit();
    $transaccionIniciada = false;

    echo json_encode([
        "status" => "success",
        "titulo" => $titulo,
        "mensaje" => $mensaje,
        "fecha_expiracion" => $fecha_fin
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($transaccionIniciada) {
        try {
            $conexion->rollback();
        } catch (Throwable $rollbackError) {
            error_log("Error al revertir la activación: " . $rollbackError->getMessage());
        }
    }
    error_log("Error al activar la prueba de usuario {$id_usuario}: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al procesar la activación."], JSON_UNESCAPED_UNICODE);
}
?>