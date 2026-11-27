<?php
session_start();

require_once __DIR__ . "/conexion.php";

$basePath = rtrim(str_replace("\\", "/", dirname(dirname($_SERVER["SCRIPT_NAME"]))), "/");

$esFetch = (
    isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "fetch"
);

function responderLoginError($codigo, $mensaje, $redirectNormal, $esFetch) {
    if ($esFetch) {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "ok" => false,
            "codigo" => $codigo,
            "mensaje" => $mensaje
        ]);
        exit;
    }

    header("Location: " . $redirectNormal);
    exit;
}

function responderLoginExito($mensaje, $destino, $esFetch) {
    if ($esFetch) {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "ok" => true,
            "mensaje" => $mensaje,
            "redirect" => $destino
        ]);
        exit;
    }

    header("Location: " . $destino);
    exit;
}

// 1. Validar que la petición sea POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.html");
    exit;
}

// 2. Recibir los datos
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responderLoginError(
        "correo",
        "Ingresa un correo electrónico válido.",
        $basePath . "/login.html?error=correo",
        $esFetch
    );
}

if ($password === "") {
    responderLoginError(
        "password",
        "Debes ingresar tu contraseña.",
        $basePath . "/login.html?error=password",
        $esFetch
    );
}

// 3. Consultar usuario y rol
$sql = $conexion->prepare("
    SELECT
        u.id_usuario,
        u.rol,
        u.nombre,
        u.email,
        u.contraseña
    FROM usuario u
    INNER JOIN rol r ON u.rol = r.id_rol
    WHERE u.email = ?
    LIMIT 1
");

$sql->bind_param("s", $email);
$sql->execute();

$resultado = $sql->get_result();
$usuario = $resultado->fetch_assoc();
$sql->close();

// 4. Comprobar si el correo está registrado
if (!$usuario) {
    responderLoginError(
        "correo",
        "El correo electrónico que ingresaste no se encuentra registrado.",
        $basePath . "/login.html?error=correo",
        $esFetch
    );
}

// 5. Comprobar la contraseña
if (!password_verify($password, $usuario["contraseña"])) {
    responderLoginError(
        "password",
        "La contraseña ingresada no es correcta.",
        $basePath . "/login.html?error=password",
        $esFetch
    );
}

// 6. Inicio de sesión correcto
session_regenerate_id(true);

$_SESSION["id_usuario"] = (int) $usuario["id_usuario"];
$_SESSION["rol"] = (int) $usuario["rol"];
$_SESSION["nombre"] = $usuario["nombre"];
$_SESSION["email"] = $usuario["email"];

// 7. Determinar destino según el rol
$destino = null;

switch ($_SESSION["rol"]) {
    case 1:
        $destino = $basePath . "/pages/dashboard.php";
        break;

    case 2:
        $destino = "/zimbra/dashboard_vendedor.php";
        break;

    case 3:
        $destino = "/zimbra/dashboard_marketing.html";
        break;

    case 4:
        $stmtAccesoCliente = $conexion->prepare("
            SELECT
                EXISTS (
                    SELECT 1
                    FROM descargas
                    WHERE id_usuario = ?
                      AND tipo IN ('Gratuita', 'Premium')
                ) OR EXISTS (
                    SELECT 1
                    FROM notificaciones
                    WHERE id_usuario = ?
                      AND tipo = 'Alerta comercial'
                      AND contenido LIKE ?
                ) AS tiene_acceso
        ");
        $prefijoActivacion = "¡Prueba de 60 días activada!%";
        $stmtAccesoCliente->bind_param(
            "iis",
            $_SESSION["id_usuario"],
            $_SESSION["id_usuario"],
            $prefijoActivacion
        );
        $stmtAccesoCliente->execute();
        $accesoCliente = $stmtAccesoCliente->get_result()->fetch_assoc();
        $stmtAccesoCliente->close();

        if ($accesoCliente && (int) $accesoCliente["tiene_acceso"] === 1) {
            $destino = $basePath . "/pages/chat_cliente.php";
        } else {
            $destino = $basePath . "/pages/inicio.php";
        }
        break;

    default:
        session_destroy();
        $conexion->close();
        responderLoginError(
            "rol",
            "Tu usuario no tiene un rol válido.",
            $basePath . "/login.html?error=rol",
            $esFetch
        );
}

$conexion->close();

responderLoginExito(
    "¡Bienvenido, " . $usuario["nombre"] . "!",
    $destino,
    $esFetch
);
?>