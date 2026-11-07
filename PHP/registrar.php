<?php
require_once __DIR__ . "/conexion.php";

// 1. Validar que la petición sea por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../formulario_registro.html"); 
    exit;
}

$nombre = trim($_POST["nombre"] ?? "");
$email = trim($_POST["email"] ?? "");
$contraseña = $_POST["password"] ?? "";

// Validaciones básicas de servidor
if (empty($nombre) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($contraseña) < 8) {
    exit("Los datos ingresados no son válidos.");
}

try {
    // 2. Verificar si el correo ya está registrado
    $stmtCheck = $conexion->prepare("SELECT id_usuario FROM usuario WHERE email = ?");
    $stmtCheck->bind_param("s", $email);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows > 0) {
        exit("Error: El correo electrónico ya se encuentra registrado.");
    }
    $stmtCheck->close();

    // 3. Generar el hash seguro de la contraseña
    $passwordHash = password_hash($contraseña, PASSWORD_DEFAULT);

    // 4. Definir el rol por defecto (ej. Rol 4 para usuario)
    $rolPorDefecto = 4; 

    // 5. Preparar la consulta SQL correctamente con '?'
    $sql = $conexion->prepare(
        "INSERT INTO usuario (rol, nombre, email, contraseña, fecha_registro) 
        VALUES (?, ?, ?, ?, NOW())"
    );

    // 6. Vincular los parámetros de forma segura (i = entero, s = string)
    $sql->bind_param("isss", $rolPorDefecto, $nombre, $email, $passwordHash);

    // 7. Ejecutar y redirigir
    if ($sql->execute()) {
        header("Location: ../inicio.html"); 
        exit;
    } else {
        echo "Error al registrar: " . $sql->error;
    }

    $sql->close();
    $conexion->close();

} catch (Exception $e) {
    echo "Ocurrió un error en el sistema. Inténtalo más tarde.";
}
?>