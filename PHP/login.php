<?php
require_once __DIR__ . "/conexion.php";

// 1. Validar que la petición sea por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.html"); 
    exit;
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

$sql = $conexion->prepare("SELECT email,contraseña from usuario where email=? limit 1" );

$sql->bind_param("s", $email);
$sql->execute();

$resultado = $sql->get_result();
$usuario = $resultado->fetch_assoc();

if ($usuario && password_verify($password, $usuario["contraseña"])) {

    session_regenerate_id(true);
    $_SESSION["email"] = $usuario["email"];

    header("Location: ../inicio.html");
    exit;

} else {
    header("Location: ../login.php?error=1");
    exit;
}

$sql->close();
$conexion->close();
?>

?>