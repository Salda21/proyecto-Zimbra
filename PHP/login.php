
<?php
session_start();

require_once __DIR__ . "/conexion.php";

// 1. Validar que la petición sea POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.html");
    exit;
}   

// 2. Recibir los datos
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

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

// 4. Comprobar si el correo está registrado
if (!$usuario) {
    header("Location: /zimbra/login.html?error=correo");
    exit;
}

// 5. Comprobar la contraseña
if (!password_verify($password, $usuario["contraseña"])) {
    header("Location: /zimbra/login.html?error=password");
    exit;
}

// 6. Inicio de sesión correcto
session_regenerate_id(true);

$_SESSION["id_usuario"] = $usuario["id_usuario"];
$_SESSION["rol"] = (int)$usuario["rol"];
$_SESSION["nombre"] = $usuario["nombre"];
$_SESSION["email"] = $usuario["email"];

// 7. Redireccionar según el rol
switch ($_SESSION["rol"]) {

    case 1:
        header("Location: /zimbra/dashboard_admin.php");
        exit;

    case 2:
        header("Location: /zimbra/dashboard_vendedor.php");
        exit;

    case 3:
        header("Location: /zimbra/dashboard_marketing.html");
        exit;

    case 4:
        header("Location: /zimbra/inicio.html");
        exit;

    default:
        session_destroy();
        header("Location: /zimbra/login.html?error=rol");
        exit;
}
?>