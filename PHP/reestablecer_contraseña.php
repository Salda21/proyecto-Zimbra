<?php
ob_start();

require_once __DIR__ . "/conexion.php";

$esFetch = (
    isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "fetch"
);

function responderJson(array $payload, int $statusCode = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($statusCode);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function responderResetError(string $codigo, string $mensaje, bool $esFetch, int $statusCode = 200): void
{
    if ($esFetch) {
        responderJson([
            "ok" => false,
            "codigo" => $codigo,
            "mensaje" => $mensaje
        ], $statusCode);
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header("Location: ../restablecer_password.html?error=" . urlencode($codigo));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header("Location: ../restablecer_password.html");
    exit;
}

// 1. Recibir y normalizar datos
$email = strtolower(trim($_POST["email"] ?? ""));
$nuevaPassword = $_POST["password"] ?? "";
$confirmarPassword = $_POST["confirmarPassword"] ?? "";

// 2. Validar correo electrónico
if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responderResetError(
        "email",
        "Ingresa un correo electrónico válido.",
        $esFetch
    );
}

// 3. Validar longitud y coincidencia de contraseña
if (strlen($nuevaPassword) < 8) {
    responderResetError(
        "password",
        "La nueva contraseña debe tener mínimo 8 caracteres.",
        $esFetch
    );
}

if ($nuevaPassword !== $confirmarPassword) {
    responderResetError(
        "confirmar",
        "Las contraseñas no coinciden.",
        $esFetch
    );
}

try {
    if (!isset($conexion) || !($conexion instanceof mysqli)) {
        throw new RuntimeException("La conexión a la base de datos no está disponible.");
    }

    // 4. Verificar que el usuario exista en la tabla `usuario`
    $stmtCheck = $conexion->prepare(
        "SELECT id_usuario FROM usuario WHERE email = ? LIMIT 1"
    );

    if (!$stmtCheck) {
        throw new RuntimeException("Error al preparar consulta de verificación: " . $conexion->error);
    }

    $stmtCheck->bind_param("s", $email);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows === 0) {
        $stmtCheck->close();
        $conexion->close();
        responderResetError(
            "correo",
            "No existe ninguna cuenta registrada con ese correo electrónico.",
            $esFetch
        );
    }

    $stmtCheck->close();

    // 5. Encriptar nueva contraseña y actualizar en `usuario`
    $passwordHash = password_hash($nuevaPassword, PASSWORD_DEFAULT);

    $stmtUpdate = $conexion->prepare(
        "UPDATE usuario SET contraseña = ? WHERE email = ?"
    );

    if (!$stmtUpdate) {
        throw new RuntimeException("Error al preparar actualización: " . $conexion->error);
    }

    $stmtUpdate->bind_param("ss", $passwordHash, $email);
    $ejecutado = $stmtUpdate->execute();
    $stmtUpdate->close();
    $conexion->close();

    if (!$ejecutado) {
        responderResetError(
            "actualizar",
            "No fue posible actualizar la contraseña.",
            $esFetch
        );
    }

    $destino = "/proyecto-zimbra/login.html?reset=ok";

    if ($esFetch) {
        responderJson([
            "ok" => true,
            "mensaje" => "¡Contraseña actualizada correctamente! Redirigiendo al inicio de sesión...",
            "redirect" => $destino
        ]);
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header("Location: " . $destino);
    exit;
} catch (Throwable $e) {
    error_log("Error en restablecer_password.php: " . $e->getMessage());
    responderResetError(
        "actualizar",
        "Ocurrió un error al actualizar la contraseña.",
        $esFetch,
        500
    );
}