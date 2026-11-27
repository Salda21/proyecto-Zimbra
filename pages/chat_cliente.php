<?php
session_start();

$basePath = rtrim(str_replace("\\", "/", dirname(dirname($_SERVER["SCRIPT_NAME"]))), "/");
if (!isset($_SESSION["id_usuario"]) || (int) ($_SESSION["rol"] ?? 0) !== 4) {
    header("Location: " . $basePath . "/login.html");
    exit;
}

if (empty($_SESSION["chat_csrf_token"])) {
    $_SESSION["chat_csrf_token"] = bin2hex(random_bytes(32));
}

$nombre = htmlspecialchars($_SESSION["nombre"] ?? "Cliente", ENT_QUOTES, "UTF-8");
$basePathHtml = htmlspecialchars($basePath, ENT_QUOTES, "UTF-8");
$csrfToken = htmlspecialchars($_SESSION["chat_csrf_token"], ENT_QUOTES, "UTF-8");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis chats | ZimbraConnect</title>
    <link rel="stylesheet" href="<?= $basePathHtml ?>/assets/css/estilos.css">
    <link rel="stylesheet" href="<?= $basePathHtml ?>/assets/css/componentes.css">
    <link rel="stylesheet" href="<?= $basePathHtml ?>/assets/css/chat_cliente.css">
</head>
<body>
    <main class="client-chat" id="clientChat"
        data-api="<?= $basePathHtml ?>/PHP/chat_clientes.php"
        data-csrf="<?= $csrfToken ?>"
        data-current-user="<?= (int) $_SESSION["id_usuario"] ?>">
        <header class="client-chat__topbar">
            <a class="brand" href="<?= $basePathHtml ?>/index.html">
                <span class="brand-mark">➤</span>ZimbraConnect
            </a>
            <div class="client-chat__actions">
                <span class="client-chat__welcome">Hola, <?= $nombre ?></span>
                <div class="notification-menu">
                    <button class="icon-btn notification-trigger" id="btnNotificaciones" type="button"
                        title="Notificaciones" aria-label="Abrir notificaciones"
                        aria-controls="panelNotificaciones" aria-expanded="false">
                        ♧<span class="notification-badge" id="contadorNotificaciones" hidden>0</span>
                    </button>
                    <section class="notification-panel" id="panelNotificaciones" aria-label="Notificaciones" hidden>
                        <h2>Notificaciones</h2>
                        <ul id="listaNotificaciones" class="notification-list">
                            <li class="notification-empty" id="sinNotificaciones">No tienes notificaciones.</li>
                        </ul>
                    </section>
                </div>
                <a class="client-chat__logout" href="<?= $basePathHtml ?>/PHP/cerrar_sesion.php">Cerrar sesión</a>
            </div>
        </header>

        <section class="client-chat__workspace" aria-label="Chat entre clientes">
            <aside class="client-chat__conversations">
                <h1>Mis chats</h1>
                <label class="client-chat__search-label" for="contactSearch">Buscar clientes por nombre</label>
                <input id="contactSearch" type="search" autocomplete="off"
                    placeholder="Escribe al menos 2 letras">
                <ul class="client-chat__contact-results" id="contactResults" aria-label="Resultados de clientes" hidden></ul>
                <h2>Conversaciones</h2>
                <ul class="client-chat__conversation-list" id="conversationList" aria-label="Conversaciones">
                    <li class="client-chat__empty">Cargando tus chats...</li>
                </ul>
            </aside>

            <section class="client-chat__thread" aria-label="Mensajes">
                <header class="client-chat__thread-header">
                    <div class="client-chat__avatar" id="contactAvatar">Z</div>
                    <div>
                        <strong id="contactName">Selecciona un chat</strong>
                        <small>Conversación privada entre clientes</small>
                    </div>
                </header>
                <div class="client-chat__messages" id="messageList" aria-live="polite">
                    <p class="client-chat__placeholder">Busca a otro cliente por su nombre para iniciar una conversación.</p>
                </div>
                <form class="client-chat__composer" id="messageForm">
                    <label class="visually-hidden" for="messageInput">Escribe un mensaje</label>
                    <textarea id="messageInput" maxlength="4000" rows="1"
                        placeholder="Escribe un mensaje..." disabled required></textarea>
                    <button class="btn btn-primary" id="sendMessage" type="submit" disabled>Enviar</button>
                </form>
            </section>
        </section>
    </main>
    <script src="<?= $basePathHtml ?>/assets/js/notificaciones.js"></script>
    <script src="<?= $basePathHtml ?>/assets/js/bandeja_notificaciones.js"></script>
    <script src="<?= $basePathHtml ?>/assets/js/prueba_usuario.js"></script>
    <script src="<?= $basePathHtml ?>/assets/js/chat_clientes.js"></script>
</body>
</html>
