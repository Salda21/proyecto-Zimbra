<?php
session_start();

// Comprobar que exista una sesión
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}

$nombre = htmlspecialchars($_SESSION["nombre"]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class="bienvenida">
    <h1>¡Hola, usuario <?php echo $nombre; ?>!</h1>

    <p>Bienvenido a Zimbra.</p>

</div>
</body>
</html>