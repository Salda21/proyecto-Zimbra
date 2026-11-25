/* document.addEventListener('DOMContentLoaded',()=>{
 const form=document.querySelector('#registerForm');
 if(form)form.addEventListener('submit',e=>{
  e.preventDefault();
  const pass=document.querySelector('#password').value;
  const confirmPassword=document.querySelector('#confirmPassword').value;
  if(pass!==confirmPassword){window.ZCNotify.error('Las contraseñas no coinciden. Verifica ambos campos.');return;}
  if(pass.length<8){window.ZCNotify.warning('La contraseña debe tener al menos 8 caracteres.');return;}
  window.ZCNotify.success('Registro validado correctamente. Conecta este formulario con PHP para guardar el usuario.');
 });
 const login=document.querySelector('#loginForm');
 if(login)login.addEventListener('submit',e=>{
  e.preventDefault();
  if(!login.reportValidity())return;
  window.ZCNotify.info('Inicio de sesión de demostración. Conecta la autenticación con PHP.');
 });
});
*/
document.addEventListener('DOMContentLoaded', () => {
    const notificar = (tipo, mensaje) => {
        if (window.ZCNotify && typeof window.ZCNotify[tipo] === 'function') {
            window.ZCNotify[tipo](mensaje);
        } else {
            alert(mensaje);
        }
    };

    // Función compartida para marcar el estado visual de un campo
    function marcarCampo(campo, mensajeEl, esValido, textoError = 'Revisa este campo') {
        if (!campo) return;
        campo.classList.remove('valido', 'invalido');

        if (!mensajeEl) return;

        if (esValido) {
            campo.classList.add('valido');
            mensajeEl.textContent = 'Campo correcto';
            mensajeEl.style.color = 'green';
        } else {
            campo.classList.add('invalido');
            mensajeEl.textContent = textoError;
            mensajeEl.style.color = 'red';
        }
    }

    // Función compartida para enviar un formulario por fetch y manejar JSON/redirección
    async function enviarFormularioFetch(form, mensajeExitoDefecto) {
        const formData = new FormData(form);
        const urlPhp = form.getAttribute('action');

        if (!urlPhp) {
            notificar('error', 'No se ha configurado la ruta de destino (action) en el formulario.');
            return;
        }

        try {
            const response = await fetch(urlPhp, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'fetch'
                }
            });

            const rawText = await response.text();
            let data;

            try {
                data = JSON.parse(rawText);
            } catch {
                console.error('Respuesta no-JSON del servidor:', rawText);
                notificar('error', 'El servidor devolvió una respuesta inesperada.');
                return;
            }

            if (!response.ok || !data.ok) {
                notificar('error', data.mensaje || 'No se pudo completar la operación.');
                return;
            }

            notificar('success', data.mensaje || mensajeExitoDefecto);

            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            }
        } catch (error) {
            console.error('Error de red al enviar formulario:', error);
            notificar('error', 'Error de conexión al enviar los datos al servidor.');
        }
    }

    // ==========================================
    // MÓDULO DE REGISTRO
    // ==========================================
    const registerForm = document.querySelector('#registerForm') || document.querySelector('#formRegistro');

    if (registerForm) {
        const nombre = registerForm.querySelector('#nombre');
        const emailReg = registerForm.querySelector('#email');
        const passwordReg = registerForm.querySelector('#password');
        const confirmarPassword = registerForm.querySelector('#confirmarPassword');

        function validarNombre() {
            if (!nombre) return true;
            const valor = nombre.value.trim();
            const expresion = /^[\p{L}\s'-]+$/u;
            const valido = valor.length >= 3 && expresion.test(valor);

            marcarCampo(
                nombre,
                registerForm.querySelector('#errorNombre'),
                valido,
                'Ingresa un nombre válido de al menos 3 caracteres.'
            );
            return valido;
        }

        function validarEmailRegistro() {
            if (!emailReg) return true;
            const valor = emailReg.value.trim();
            const expresion = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const valido = expresion.test(valor);

            marcarCampo(
                emailReg,
                registerForm.querySelector('#errorEmail'),
                valido,
                'Ingresa un correo electrónico válido.'
            );
            return valido;
        }

        function validarPasswordRegistro() {
            if (!passwordReg) return true;
            const valor = passwordReg.value;
            const valido = valor.length >= 8;

            marcarCampo(
                passwordReg,
                registerForm.querySelector('#errorPassword'),
                valido,
                'La contraseña debe tener mínimo 8 caracteres.'
            );

            if (confirmarPassword && confirmarPassword.value !== '') {
                validarConfirmacion();
            }
            return valido;
        }

        function validarConfirmacion() {
            if (!confirmarPassword || !passwordReg) return true;
            const valido =
                confirmarPassword.value !== '' &&
                confirmarPassword.value === passwordReg.value;

            marcarCampo(
                confirmarPassword,
                registerForm.querySelector('#errorConfirmar'),
                valido,
                'Las contraseñas no coinciden.'
            );
            return valido;
        }

        if (nombre) nombre.addEventListener('input', validarNombre);
        if (emailReg) emailReg.addEventListener('input', validarEmailRegistro);
        if (passwordReg) passwordReg.addEventListener('input', validarPasswordRegistro);
        if (confirmarPassword) confirmarPassword.addEventListener('input', validarConfirmacion);

        registerForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const nombreValido = validarNombre();
            const emailValido = validarEmailRegistro();
            const passwordValido = validarPasswordRegistro();
            const confirmacionValida = validarConfirmacion();

            if (!nombreValido) {
                notificar('error', 'Error: Ingresa un nombre válido de al menos 3 caracteres.');
                nombre?.focus();
                return;
            }

            if (!emailValido) {
                notificar('error', 'Error: Ingresa un correo electrónico válido.');
                emailReg?.focus();
                return;
            }

            if (!passwordValido) {
                notificar('warning', 'La contraseña debe tener mínimo 8 caracteres.');
                passwordReg?.focus();
                return;
            }

            if (!confirmacionValida) {
                notificar('error', 'Las contraseñas no coinciden. Verifica ambos campos.');
                confirmarPassword?.focus();
                return;
            }

            await enviarFormularioFetch(registerForm, '¡Tu cuenta ha sido registrada correctamente!');
        });
    }

    // ==========================================
    // MÓDULO DE INICIO DE SESIÓN (LOGIN)
    // ==========================================
    const loginForm = document.querySelector('#loginForm') || document.querySelector('#formLogin');

    if (loginForm) {
        const emailLogin = loginForm.querySelector('#email');
        const passwordLogin = loginForm.querySelector('#password');

        function validarEmailLogin() {
            if (!emailLogin) return true;
            const valor = emailLogin.value.trim();
            const expresion = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const valido = expresion.test(valor);

            marcarCampo(
                emailLogin,
                loginForm.querySelector('#errorEmail'),
                valido,
                'Ingresa un correo electrónico válido.'
            );
            return valido;
        }

        function validarPasswordLogin() {
            if (!passwordLogin) return true;
            const valor = passwordLogin.value;
            const valido = valor.length > 0;

            marcarCampo(
                passwordLogin,
                loginForm.querySelector('#errorPassword'),
                valido,
                'Debes ingresar tu contraseña.'
            );
            return valido;
        }

        if (emailLogin) emailLogin.addEventListener('input', validarEmailLogin);
        if (passwordLogin) passwordLogin.addEventListener('input', validarPasswordLogin);

        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const correoValido = validarEmailLogin();
            const contraseñaValida = validarPasswordLogin();

            if (!correoValido) {
                notificar('error', 'Error: Ingresa un correo electrónico válido.');
                emailLogin?.focus();
                return;
            }

            if (!contraseñaValida) {
                notificar('error', 'Error: Debes ingresar tu contraseña.');
                passwordLogin?.focus();
                return;
            }

            await enviarFormularioFetch(loginForm, 'Inicio de sesión exitoso.');
        });
    }

    const resetForm = document.querySelector('#resetPasswordForm');

    if (resetForm) {
        const emailReset = resetForm.querySelector('#email');
        const passwordReset = resetForm.querySelector('#password');
        const confirmarReset = resetForm.querySelector('#confirmarPassword');

        function validarEmailReset() {
            if (!emailReset) return true;
            const valor = emailReset.value.trim();
            const expresion = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const valido = expresion.test(valor);

            marcarCampo(
                emailReset,
                resetForm.querySelector('#errorEmail'),
                valido,
                'Ingresa un correo electrónico válido.'
            );
            return valido;
        }

        function validarPasswordReset() {
            if (!passwordReset) return true;
            const valor = passwordReset.value;
            const valido = valor.length >= 8;

            marcarCampo(
                passwordReset,
                resetForm.querySelector('#errorPassword'),
                valido,
                'La contraseña debe tener mínimo 8 caracteres.'
            );

            if (confirmarReset && confirmarReset.value !== '') {
                validarConfirmacionReset();
            }
            return valido;
        }

        function validarConfirmacionReset() {
            if (!confirmarReset || !passwordReset) return true;
            const valido =
                confirmarReset.value !== '' &&
                confirmarReset.value === passwordReset.value;

            marcarCampo(
                confirmarReset,
                resetForm.querySelector('#errorConfirmar'),
                valido,
                'Las contraseñas no coinciden.'
            );
            return valido;
        }

        if (emailReset) emailReset.addEventListener('input', validarEmailReset);
        if (passwordReset) passwordReset.addEventListener('input', validarPasswordReset);
        if (confirmarReset) confirmarReset.addEventListener('input', validarConfirmacionReset);

        resetForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const emailValido = validarEmailReset();
            const passwordValido = validarPasswordReset();
            const confirmacionValida = validarConfirmacionReset();

            if (!emailValido) {
                notificar('error', 'Error: Ingresa un correo electrónico válido.');
                emailReset?.focus();
                return;
            }

            if (!passwordValido) {
                notificar('warning', 'La nueva contraseña debe tener mínimo 8 caracteres.');
                passwordReset?.focus();
                return;
            }

            if (!confirmacionValida) {
                notificar('error', 'Las contraseñas no coinciden. Verifica ambos campos.');
                confirmarReset?.focus();
                return;
            }

            await enviarFormularioFetch(resetForm, '¡Contraseña actualizada correctamente!');
        });
    }

    // ==========================================
    // ALERTAS PROVENIENTES DE LA URL (GET)
    // ==========================================
    const parametros = new URLSearchParams(window.location.search);

    if (parametros.has('error')) {
        const codigoError = parametros.get('error');
        const errores = {
            nombre: 'El nombre ingresado no es válido.',
            email: 'El correo electrónico no es válido.',
            password: 'La contraseña debe tener mínimo 8 caracteres.',
            confirmar: 'Las contraseñas no coinciden.',
            duplicado: 'Este correo electrónico ya se encuentra registrado.',
            registro: 'No fue posible completar el registro.',
            correo: 'Error: El correo electrónico que ingresaste no se encuentra registrado.',
            rol: 'Error: Tu usuario no tiene un rol válido.'
        };

        // Si error=password viene en login o registro, muestra un mensaje claro
        const mensajeError =
            codigoError === 'password' && loginForm && !registerForm
                ? 'Error: La contraseña ingresada no es correcta.'
                : errores[codigoError] || 'Ocurrió un error durante la operación.';

        notificar('error', mensajeError);
    }

    if (parametros.get('registro') === 'exitoso' || parametros.get('registro') === 'ok') {
        notificar('success', '¡Tu cuenta ha sido registrada correctamente!');
    }
});