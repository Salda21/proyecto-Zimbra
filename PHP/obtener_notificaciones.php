<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');
require_once __DIR__ . "/conexion.php";

if (!isset($_SESSION["id_usuario"])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Inicia sesión para consultar tus notificaciones."]);
    exit;
}

$id_usuario = (int)$_SESSION["id_usuario"];

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_GET["accion"] ?? "") === "leer") {
        $upd = $conexion->prepare(
            "UPDATE notificaciones SET estado = 'Leida'
             WHERE id_usuario = ? AND estado = 'No leida'"
        );
        $upd->bind_param("i", $id_usuario);
        $upd->execute();
        echo json_encode(["status" => "ok"]);
        exit;
    }

    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Método no permitido."]);
        exit;
    }

    $stmt = $conexion->prepare(
        "SELECT id_notificacion, tipo, contenido, estado,
                DATE_FORMAT(fecha_generacion, '%d/%m %H:%i') AS fecha
         FROM notificaciones
         WHERE id_usuario = ?
         ORDER BY id_notificacion DESC
         LIMIT 10"
    );
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $notificaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $tituloActivacion = "¡Prueba de 60 días activada!";
    $prefijoActivacion = $tituloActivacion . " - ";
    foreach ($notificaciones as &$notificacion) {
        $esActivacion = $notificacion["tipo"] === "Alerta comercial"
            && strpos($notificacion["contenido"], $prefijoActivacion) === 0;
        $notificacion["titulo"] = $esActivacion ? $tituloActivacion : $notificacion["tipo"];
        $notificacion["mensaje"] = $esActivacion
            ? substr($notificacion["contenido"], strlen($prefijoActivacion))
            : $notificacion["contenido"];
        $notificacion["leida"] = $notificacion["estado"] === "No leida" ? 0 : 1;
    }
    unset($notificacion);

    $check = $conexion->prepare(
        "SELECT id_notificacion FROM notificaciones
         WHERE id_usuario = ? AND tipo = 'Alerta comercial'
           AND contenido LIKE ? LIMIT 1"
    );
    $prefijoActivacionBusqueda = $tituloActivacion . "%";
    $check->bind_param("is", $id_usuario, $prefijoActivacionBusqueda);
    $check->execute();

    echo json_encode([
        "notificaciones" => $notificaciones,
        "prueba_activa" => $check->get_result()->num_rows > 0
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("Error al consultar notificaciones del usuario {$id_usuario}: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "No se pudieron cargar las notificaciones."]);
}
?>
