<?php
/*
require_once __DIR__ . "/conexion.php";

// Función para regresar al formulario con un error
function regresarError($error) {
    header("Location: ../formulario_registro.html?error=" . urlencode($error));
    exit;
}

// 1. Comprobar que la petición sea POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../registro.html");
    exit;
}

// 2. Recibir los datos
$nombre = trim($_POST["nombre"] ?? "");
$email = trim($_POST["email"] ?? "");
$contraseña = $_POST["password"] ?? "";
$confirmar = $_POST["confirmarPassword"] ?? "";


// 3. Validar nombre
if (
    strlen($nombre) < 3 ||
    !preg_match("/^[\p{L}\s'-]+$/u", $nombre)
) {
    regresarError("nombre");
}

// 4. Validar correo
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    regresarError("email");
}

// 5. Validar contraseña
if (strlen($contraseña) < 8) {
    regresarError("password");
}

if ($contraseña !== $confirmar) {
    regresarError("confirmar");
}
try {

    // 7. Comprobar si el correo ya existe
    $stmtCheck = $conexion->prepare(
        "SELECT id_usuario FROM usuario WHERE email = ?"
    );

    $stmtCheck->bind_param("s", $email);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows > 0) {
        $stmtCheck->close();
        regresarError("duplicado");
    }

    $stmtCheck->close();

    // 8. Encriptar la contraseña
    $passwordHash = password_hash($contraseña, PASSWORD_DEFAULT);

    // 9. Asignar rol por defecto
    $rolPorDefecto = 4;

    // 10. Insertar usuario
    $sql = $conexion->prepare(
        "INSERT INTO usuario
        (rol, nombre, email, contraseña, fecha_registro)
        VALUES (?, ?, ?, ?, NOW())"
    );

    $sql->bind_param(
        "isss",
        $rolPorDefecto,
        $nombre,
        $email,
        $passwordHash
    );

   if ($sql->execute()) {
        $nuevoId = $conexion->insert_id;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION["id_usuario"] = (int) $nuevoId;
        $_SESSION["rol"] = $rolPorDefecto;
        $_SESSION["nombre"] = $nombre;
        $_SESSION["email"] = $email;

        $sql->close();
        $conexion->close();

        header("Location: /proyecto-Zimbra/pages/inicio.php?registro=ok");
        exit;
    }else {
        $sql->close();
        regresarError("registro");
    }

} catch (mysqli_sql_exception $e) {

    error_log($e->getMessage());
    regresarError("registro");

}
     */

require_once __DIR__ . "/conexion.php";

$esFetch = (
    isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "fetch"
);

$basePath = rtrim(str_replace("\\", "/", dirname(dirname($_SERVER["SCRIPT_NAME"]))), "/");

function responderError($error, $esFetch) {
    $mensajes = [
        "nombre" => "El nombre debe tener al menos 3 caracteres y solo letras o espacios.",
        "email" => "Ingresa un correo electrónico válido.",
        "password" => "La contraseña debe tener al menos 8 caracteres.",
        "confirmar" => "Las contraseñas no coinciden.",
        "duplicado" => "Ese correo electrónico ya está registrado.",
        "registro" => "Ocurrió un error al guardar el usuario."
    ];

    if ($esFetch) {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "ok" => false,
            "codigo" => $error,
            "mensaje" => $mensajes[$error] ?? "Error en el formulario."
        ]);
        exit;
    }

    header("Location: ../formulario_registro.html?error=" . urlencode($error));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../registro.html");
    exit;
}

$nombre = trim($_POST["nombre"] ?? "");
$email = trim($_POST["email"] ?? "");
$contraseña = $_POST["password"] ?? "";
$confirmar = $_POST["confirmarPassword"] ?? "";

if (strlen($nombre) < 3 || !preg_match("/^[\p{L}\s'-]+$/u", $nombre)) {
    responderError("nombre", $esFetch);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responderError("email", $esFetch);
}

if (strlen($contraseña) < 8) {
    responderError("password", $esFetch);
}

if ($contraseña !== $confirmar) {
    responderError("confirmar", $esFetch);
}

try {
    $stmtCheck = $conexion->prepare("SELECT id_usuario FROM usuario WHERE email = ?");
    $stmtCheck->bind_param("s", $email);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows > 0) {
        $stmtCheck->close();
        responderError("duplicado", $esFetch);
    }
    $stmtCheck->close();

    $passwordHash = password_hash($contraseña, PASSWORD_DEFAULT);
    $rolPorDefecto = 4;

    $sql = $conexion->prepare(
        "INSERT INTO usuario (rol, nombre, email, contraseña, fecha_registro) VALUES (?, ?, ?, ?, NOW())"
    );
    $sql->bind_param("isss", $rolPorDefecto, $nombre, $email, $passwordHash);

    if ($sql->execute()) {
        $nuevoId = $conexion->insert_id;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION["id_usuario"] = (int) $nuevoId;
        $_SESSION["rol"] = $rolPorDefecto;
        $_SESSION["nombre"] = $nombre;
        $_SESSION["email"] = $email;

        $sql->close();
        $conexion->close();

        $destino = $basePath . "/pages/inicio.php?registro=ok";

        if ($esFetch) {
            header("Content-Type: application/json; charset=utf-8");
            echo json_encode([
                "ok" => true,
                "mensaje" => "¡Usuario registrado correctamente!",
                "redirect" => $destino
            ]);
            exit;
        }

        header("Location: " . $destino);
        exit;
    }

    $sql->close();
    responderError("registro", $esFetch);
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    responderError("registro", $esFetch);
}
?>
