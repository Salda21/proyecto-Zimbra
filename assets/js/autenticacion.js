document.addEventListener('DOMContentLoaded',()=>{
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
