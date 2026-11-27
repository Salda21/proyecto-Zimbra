<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");
require_once __DIR__ . "/conexion.php";

function responderChat(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION["id_usuario"], $_SESSION["rol"]) || (int) $_SESSION["rol"] !== 4) {
    responderChat(401, ["ok" => false, "mensaje" => "Inicia sesión con una cuenta de cliente para usar el chat."]);
}

$idUsuario = (int) $_SESSION["id_usuario"];
$metodo = $_SERVER["REQUEST_METHOD"];

if ($metodo === "POST") {
    $token = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";
    if (!isset($_SESSION["chat_csrf_token"]) || !hash_equals($_SESSION["chat_csrf_token"], $token)) {
        responderChat(403, ["ok" => false, "mensaje" => "La sesión expiró. Recarga la página e inténtalo de nuevo."]);
    }

    $accion = $_POST["accion"] ?? "";

    if ($accion === "iniciar") {
        $idContacto = filter_var($_POST["id_contacto"] ?? null, FILTER_VALIDATE_INT);
        if (!$idContacto || $idContacto === $idUsuario) {
            responderChat(400, ["ok" => false, "mensaje" => "Selecciona un cliente válido."]);
        }

        $transaccionIniciada = false;
        try {
            $contacto = $conexion->prepare("SELECT id_usuario FROM usuario WHERE id_usuario = ? AND rol = 4");
            $contacto->bind_param("i", $idContacto);
            $contacto->execute();
            if (!$contacto->get_result()->fetch_assoc()) {
                responderChat(404, ["ok" => false, "mensaje" => "No se encontró ese cliente."]);
            }
            $contacto->close();

            $idMenor = min($idUsuario, $idContacto);
            $idMayor = max($idUsuario, $idContacto);
            $conexion->begin_transaction();
            $transaccionIniciada = true;

            $buscar = $conexion->prepare("
                SELECT id_conversacion
                FROM conversaciones
                WHERE (id_usuario = ? AND id_vendedor = ?)
                   OR (id_usuario = ? AND id_vendedor = ?)
                LIMIT 1
                FOR UPDATE
            ");
            $buscar->bind_param("iiii", $idMenor, $idMayor, $idMayor, $idMenor);
            $buscar->execute();
            $conversacion = $buscar->get_result()->fetch_assoc();
            $buscar->close();

            if ($conversacion) {
                $idConversacion = (int) $conversacion["id_conversacion"];
            } else {
                $crear = $conexion->prepare("
                    INSERT INTO conversaciones (id_usuario, id_vendedor, fecha_inicio, estado)
                    VALUES (?, ?, NOW(), 'ABIERTA')
                ");
                $crear->bind_param("ii", $idMenor, $idMayor);
                $crear->execute();
                $idConversacion = (int) $conexion->insert_id;
                $crear->close();
            }

            $conexion->commit();
            $transaccionIniciada = false;
            responderChat(200, ["ok" => true, "id_conversacion" => $idConversacion]);
        } catch (Throwable $e) {
            if ($transaccionIniciada) {
                $conexion->rollback();
            }
            error_log("Error al iniciar chat de cliente {$idUsuario}: " . $e->getMessage());
            responderChat(500, ["ok" => false, "mensaje" => "No se pudo iniciar la conversación."]);
        }
    }

    if ($accion === "enviar") {
        $idConversacion = filter_var($_POST["id_conversacion"] ?? null, FILTER_VALIDATE_INT);
        $contenido = trim($_POST["contenido"] ?? "");

        if (!$idConversacion || $contenido === "" || strlen($contenido) > 4000) {
            responderChat(400, ["ok" => false, "mensaje" => "El mensaje debe tener entre 1 y 4000 bytes."]);
        }

        $transaccionIniciada = false;
        try {
            $conversacion = $conexion->prepare("
                SELECT c.id_usuario, c.id_vendedor,
                       remitente.nombre AS nombre_remitente,
                       destinatario.id_usuario AS id_destinatario
                FROM conversaciones c
                INNER JOIN usuario participante_a ON participante_a.id_usuario = c.id_usuario AND participante_a.rol = 4
                INNER JOIN usuario participante_b ON participante_b.id_usuario = c.id_vendedor AND participante_b.rol = 4
                INNER JOIN usuario remitente ON remitente.id_usuario = ? AND remitente.rol = 4
                INNER JOIN usuario destinatario
                    ON destinatario.id_usuario = CASE
                        WHEN c.id_usuario = ? THEN c.id_vendedor
                        ELSE c.id_usuario
                    END
                WHERE c.id_conversacion = ?
                  AND (c.id_usuario = ? OR c.id_vendedor = ?)
                LIMIT 1
            ");
            $conversacion->bind_param("iiiii", $idUsuario, $idUsuario, $idConversacion, $idUsuario, $idUsuario);
            $conversacion->execute();
            $datosConversacion = $conversacion->get_result()->fetch_assoc();
            $conversacion->close();

            if (!$datosConversacion) {
                responderChat(404, ["ok" => false, "mensaje" => "No se encontró esa conversación."]);
            }

            $idDestinatario = (int) $datosConversacion["id_destinatario"];
            $conexion->begin_transaction();
            $transaccionIniciada = true;

            $insertarMensaje = $conexion->prepare("
                INSERT INTO mensajes (id_conversacion, id_remitente, contenido, fecha_envio, estado_lectura)
                VALUES (?, ?, ?, NOW(), 'NO_LEIDO')
            ");
            $insertarMensaje->bind_param("iis", $idConversacion, $idUsuario, $contenido);
            $insertarMensaje->execute();
            $idMensaje = (int) $conexion->insert_id;
            $insertarMensaje->close();

            $textoNotificacion = "Nuevo mensaje de " . $datosConversacion["nombre_remitente"];
            $notificacion = $conexion->prepare("
                INSERT INTO notificaciones
                    (id_usuario, id_conversacion, id_mensaje, tipo, contenido, fecha_generacion, estado)
                VALUES (?, ?, ?, 'mensaje', ?, NOW(), 'No leida')
            ");
            $notificacion->bind_param("iiis", $idDestinatario, $idConversacion, $idMensaje, $textoNotificacion);
            $notificacion->execute();
            $notificacion->close();

            $conexion->commit();
            $transaccionIniciada = false;
            responderChat(201, ["ok" => true, "id_mensaje" => $idMensaje]);
        } catch (Throwable $e) {
            if ($transaccionIniciada) {
                $conexion->rollback();
            }
            error_log("Error al enviar mensaje en conversación {$idConversacion}: " . $e->getMessage());
            responderChat(500, ["ok" => false, "mensaje" => "No se pudo enviar el mensaje."]);
        }
    }

    if ($accion === "leer") {
        $idConversacion = filter_var($_POST["id_conversacion"] ?? null, FILTER_VALIDATE_INT);
        if (!$idConversacion) {
            responderChat(400, ["ok" => false, "mensaje" => "Conversación no válida."]);
        }

        $pertenencia = $conexion->prepare("
            SELECT c.id_conversacion
            FROM conversaciones c
            INNER JOIN usuario participante_a ON participante_a.id_usuario = c.id_usuario AND participante_a.rol = 4
            INNER JOIN usuario participante_b ON participante_b.id_usuario = c.id_vendedor AND participante_b.rol = 4
            WHERE c.id_conversacion = ?
              AND (c.id_usuario = ? OR c.id_vendedor = ?)
            LIMIT 1
        ");
        $pertenencia->bind_param("iii", $idConversacion, $idUsuario, $idUsuario);
        $pertenencia->execute();
        if (!$pertenencia->get_result()->fetch_assoc()) {
            responderChat(404, ["ok" => false, "mensaje" => "No se encontró esa conversación."]);
        }

        $leer = $conexion->prepare("
            UPDATE mensajes
            SET estado_lectura = 'LEIDO'
            WHERE id_conversacion = ?
              AND id_remitente <> ?
              AND estado_lectura = 'NO_LEIDO'
        ");
        $leer->bind_param("ii", $idConversacion, $idUsuario);
        $leer->execute();

        $leerNotificaciones = $conexion->prepare("
            UPDATE notificaciones
            SET estado = 'Leida'
            WHERE id_usuario = ?
              AND id_conversacion = ?
              AND tipo = 'mensaje'
              AND estado = 'No leida'
        ");
        $leerNotificaciones->bind_param("ii", $idUsuario, $idConversacion);
        $leerNotificaciones->execute();
        responderChat(200, ["ok" => true]);
    }

    responderChat(400, ["ok" => false, "mensaje" => "Acción no válida."]);
}

if ($metodo !== "GET") {
    responderChat(405, ["ok" => false, "mensaje" => "Método no permitido."]);
}

$accion = $_GET["accion"] ?? "";

try {
    if ($accion === "contactos") {
        $busqueda = trim($_GET["q"] ?? "");
        if (strlen($busqueda) < 2) {
            responderChat(200, ["contactos" => []]);
        }

        $termino = "%" . $busqueda . "%";
        $contactos = $conexion->prepare("
            SELECT id_usuario, nombre
            FROM usuario
            WHERE rol = 4 AND id_usuario <> ? AND nombre LIKE ?
            ORDER BY nombre
            LIMIT 20
        ");
        $contactos->bind_param("is", $idUsuario, $termino);
        $contactos->execute();
        responderChat(200, ["contactos" => $contactos->get_result()->fetch_all(MYSQLI_ASSOC)]);
    }

    if ($accion === "conversaciones") {
        $conversaciones = $conexion->prepare("
            SELECT c.id_conversacion,
                   otro.id_usuario AS id_contacto,
                   otro.nombre AS nombre_contacto,
                   ultimo.id_mensaje AS ultimo_id_mensaje,
                   ultimo.contenido AS ultimo_mensaje,
                   ultimo.id_remitente AS ultimo_remitente,
                   DATE_FORMAT(ultimo.fecha_envio, '%d/%m %H:%i') AS fecha_ultimo_mensaje,
                   (SELECT COUNT(*)
                    FROM mensajes no_leidos
                    WHERE no_leidos.id_conversacion = c.id_conversacion
                      AND no_leidos.id_remitente <> ?
                      AND no_leidos.estado_lectura = 'NO_LEIDO') AS no_leidos
            FROM conversaciones c
            INNER JOIN usuario participante_a ON participante_a.id_usuario = c.id_usuario AND participante_a.rol = 4
            INNER JOIN usuario participante_b ON participante_b.id_usuario = c.id_vendedor AND participante_b.rol = 4
            INNER JOIN usuario otro ON otro.id_usuario = CASE
                WHEN c.id_usuario = ? THEN c.id_vendedor
                ELSE c.id_usuario
            END
            LEFT JOIN mensajes ultimo ON ultimo.id_mensaje = (
                SELECT m.id_mensaje
                FROM mensajes m
                WHERE m.id_conversacion = c.id_conversacion
                ORDER BY m.fecha_envio DESC, m.id_mensaje DESC
                LIMIT 1
            )
            WHERE c.id_usuario = ? OR c.id_vendedor = ?
            ORDER BY ultimo.fecha_envio DESC, c.id_conversacion DESC
        ");
        $conversaciones->bind_param("iiii", $idUsuario, $idUsuario, $idUsuario, $idUsuario);
        $conversaciones->execute();
        responderChat(200, ["conversaciones" => $conversaciones->get_result()->fetch_all(MYSQLI_ASSOC)]);
    }

    if ($accion === "mensajes") {
        $idConversacion = filter_var($_GET["id_conversacion"] ?? null, FILTER_VALIDATE_INT);
        if (!$idConversacion) {
            responderChat(400, ["ok" => false, "mensaje" => "Conversación no válida."]);
        }

        $pertenencia = $conexion->prepare("
            SELECT c.id_conversacion
            FROM conversaciones c
            INNER JOIN usuario participante_a ON participante_a.id_usuario = c.id_usuario AND participante_a.rol = 4
            INNER JOIN usuario participante_b ON participante_b.id_usuario = c.id_vendedor AND participante_b.rol = 4
            WHERE c.id_conversacion = ?
              AND (c.id_usuario = ? OR c.id_vendedor = ?)
            LIMIT 1
        ");
        $pertenencia->bind_param("iii", $idConversacion, $idUsuario, $idUsuario);
        $pertenencia->execute();
        if (!$pertenencia->get_result()->fetch_assoc()) {
            responderChat(404, ["ok" => false, "mensaje" => "No se encontró esa conversación."]);
        }

        $mensajes = $conexion->prepare("
            SELECT m.id_mensaje, m.id_remitente, u.nombre AS nombre_remitente,
                   m.contenido, DATE_FORMAT(m.fecha_envio, '%d/%m/%Y %H:%i') AS fecha_envio
            FROM mensajes m
            INNER JOIN usuario u ON u.id_usuario = m.id_remitente
            WHERE m.id_conversacion = ?
            ORDER BY m.fecha_envio DESC, m.id_mensaje DESC
            LIMIT 200
        ");
        $mensajes->bind_param("i", $idConversacion);
        $mensajes->execute();
        $lista = array_reverse($mensajes->get_result()->fetch_all(MYSQLI_ASSOC));
        foreach ($lista as &$mensaje) {
            $mensaje["mio"] = (int) $mensaje["id_remitente"] === $idUsuario;
        }
        unset($mensaje);

        responderChat(200, ["mensajes" => $lista]);
    }

    responderChat(400, ["ok" => false, "mensaje" => "Acción no válida."]);
} catch (Throwable $e) {
    error_log("Error de chat para usuario {$idUsuario}: " . $e->getMessage());
    responderChat(500, ["ok" => false, "mensaje" => "No se pudo completar la operación de chat."]);
}
?>
