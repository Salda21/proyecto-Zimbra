
const formulario = document.getElementById("formLogin");

const email = document.getElementById("email");
const password = document.getElementById("password");

// Función para marcar los campos
function marcarCampo(campo, mensaje, valido, texto) {

    campo.classList.remove("valido", "invalido");

    if (valido) {
        campo.classList.add("valido");
        mensaje.textContent = "Campo correcto";
        mensaje.style.color = "green";
    } else {
        campo.classList.add("invalido");
        mensaje.textContent = texto;
        mensaje.style.color = "red";
    }
}

// Validar correo
function validarEmail() {

    const valor = email.value.trim();

    const expresion = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    const valido = expresion.test(valor);

    marcarCampo(
        email,
        document.getElementById("errorEmail"),
        valido,
        "Ingresa un correo electrónico válido."
    );

    return valido;
}

// Validar contraseña
function validarPassword() {

    const valor = password.value;

    const valido = valor.length > 0;

    marcarCampo(
        password,
        document.getElementById("errorPassword"),
        valido,
        "Debes ingresar tu contraseña."
    );

    return valido;
}

// Validar mientras el usuario escribe
email.addEventListener("input", validarEmail);
password.addEventListener("input", validarPassword);

// Validar al enviar
formulario.addEventListener("submit", function(event) {

    const correoValido = validarEmail();
    const contraseñaValida = validarPassword();

    if (!correoValido || !contraseñaValida) {

        event.preventDefault();

        if (!correoValido) {
            alert("Error: Ingresa un correo electrónico válido.");
            email.focus();

        } else {
            alert("Error: Debes ingresar tu contraseña.");
            password.focus();
        }
    }
});

// Mostrar alertas devueltas por PHP
const parametros = new URLSearchParams(window.location.search);

if (parametros.get("error") === "correo") {
    alert("Error: El correo electrónico que ingresaste no se encuentra registrado.");
}

if (parametros.get("error") === "password") {
    alert("Error: La contraseña ingresada no es correcta.");
}

if (parametros.get("error") === "rol") {
    alert("Error: Tu usuario no tiene un rol válido.");
}