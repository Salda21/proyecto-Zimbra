
const formulario = document.getElementById("formRegistro");

const nombre = document.getElementById("nombre");
const email = document.getElementById("email");
const password = document.getElementById("password");
const confirmarPassword = document.getElementById("confirmarPassword");

// Mostrar el estado de cada campo
function marcarCampo(campo, mensaje, esValido) {
    campo.classList.remove("valido", "invalido");

    if (esValido) {
        campo.classList.add("valido");
        mensaje.textContent = "Campo correcto";
        mensaje.style.color = "green";
    } else {
        campo.classList.add("invalido");
        mensaje.textContent = "Revisa este campo";
        mensaje.style.color = "red";
    }
}

// Validar nombre
function validarNombre() {
    const valor = nombre.value.trim();
    const expresion = /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s'-]+$/;

    const valido = valor.length >= 3 && expresion.test(valor);

    marcarCampo(
        nombre,
        document.getElementById("errorNombre"),
        valido
    );

    return valido;
}

// Validar correo
function validarEmail() {
    const valor = email.value.trim();
    const expresion = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    const valido = expresion.test(valor);

    marcarCampo(
        email,
        document.getElementById("errorEmail"),
        valido
    );

    return valido;
}

// Validar contraseña
function validarPassword() {
    const valor = password.value;

    const valido = valor.length >= 8;

    marcarCampo(
        password,
        document.getElementById("errorPassword"),
        valido
    );

    // Si cambia la contraseña, volver a comprobar la confirmación
    if (confirmarPassword.value !== "") {
        validarConfirmacion();
    }

    return valido;
}

function validarConfirmacion() {

    const valido =
        confirmarPassword.value !== "" &&
        confirmarPassword.value === password.value;

    marcarCampo(
        confirmarPassword,
        document.getElementById("errorConfirmar"),
        valido
    );

    return valido;
}

// Validación mientras el usuario escribe
nombre.addEventListener("input", validarNombre);
email.addEventListener("input", validarEmail);
password.addEventListener("input", validarPassword);
confirmarPassword.addEventListener("input", validarConfirmacion);

// Validación al enviar el formulario
formulario.addEventListener("submit", function(event) {

    const nombreValido = validarNombre();
    const emailValido = validarEmail();
    const passwordValido = validarPassword();
    const confirmacionValida = validarConfirmacion();

    if (!nombreValido || !emailValido ||
        !passwordValido || !confirmacionValida) {

        event.preventDefault();

        if (!nombreValido) {
            alert("Error: Ingresa un nombre válido de al menos 3 caracteres.");
            nombre.focus();
        } else if (!emailValido) {
            alert("Error: Ingresa un correo electrónico válido.");
            email.focus();
        } else if (!passwordValido) {
            alert("Error: La contraseña debe tener mínimo 8 caracteres.");
            password.focus();
        } else if (!confirmacionValida) {
            alert("Error: Las contraseñas no coinciden.");
            confirmarPassword.focus();
        }
    }
});

// Mostrar alertas que provengan del backend
const parametros = new URLSearchParams(window.location.search);

if (parametros.has("error")) {
    const errores = {
        nombre: "El nombre ingresado no es válido.",
        email: "El correo electrónico no es válido.",
        password: "La contraseña debe tener mínimo 8 caracteres.",
        confirmar: "Las contraseñas no coinciden.",
        duplicado: "Este correo electrónico ya se encuentra registrado.",
        registro: "No fue posible completar el registro."
    };

    alert(errores[parametros.get("error")] ||
          "Ocurrió un error durante el registro.");
}

if (parametros.get("registro") === "exitoso") {
    alert("¡Tu cuenta ha sido registrada correctamente!");
}
