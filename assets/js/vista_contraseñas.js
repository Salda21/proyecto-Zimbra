document.querySelectorAll('.toggle-password').forEach((icono) => {
    icono.addEventListener('click', () => {
        const contenedor = icono.closest('.campo-password');
        const input = contenedor ? contenedor.querySelector('input') : null;

        if (!input) return;

        const estaOculta = input.type === 'password';
        input.type = estaOculta ? 'text' : 'password';

        icono.classList.toggle('bx-eye-slash', estaOculta);
        icono.classList.toggle('bx-eye-alt', !estaOculta);
    });
});