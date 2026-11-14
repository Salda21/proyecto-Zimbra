
<?php
require_once __DIR__ . "/conexion.php";

// Función para regresar al formulario con un error
function regresarError($error) {
    header("Location: ../formulario_registro.html?error=" . urlencode($error));
    exit;
}

// 1. Comprobar que la petición sea POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../formulario_registro.html");
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

        $sql->close();
        $conexion->close();

        header("Location: ../inicio.html?registro=exitoso");
        exit;

    } else {
        $sql->close();
        regresarError("registro");
    }

} catch (mysqli_sql_exception $e) {

    error_log($e->getMessage());
    regresarError("registro");

}
?>