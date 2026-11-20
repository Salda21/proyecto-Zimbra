/*
document.addEventListener('DOMContentLoaded',()=>{
 document.querySelector('#userForm')?.addEventListener('submit',e=>{
 e.preventDefault();const fd=new FormData(e.currentTarget),tr=document.createElement('tr');
 tr.innerHTML=`<td>${fd.get('nombre')}</td><td>${fd.get('correo')}</td><td>${fd.get('rol')}</td><td><span class="pill green">Activo</span></td><td><button class="icon-btn" title="Editar">✎</button></td>`;
 document.querySelector('#userTable tbody').prepend(tr);document.querySelector('#userModal').classList.remove('show');e.currentTarget.reset();
 });
});
*/

document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);

  if (window.ZCNotify) {
    if (params.get('usuario') === 'creado') {
      window.ZCNotify.success('El usuario se registró correctamente en la base de datos.');
      params.delete('usuario');
    } else if (params.get('error') === 'correo_duplicado') {
      window.ZCNotify.error('Ese correo ya está registrado.');
      params.delete('error');
    } else if (params.get('error') === 'datos') {
      window.ZCNotify.warning('Completa todos los campos correctamente.');
      params.delete('error');
    } else if (params.get('error') === 'rol') {
      window.ZCNotify.error('El rol seleccionado no es válido.');
      params.delete('error');
    } else if (params.get('error') === 'registro') {
      window.ZCNotify.error('No se pudo registrar el usuario en la base de datos.');
      params.delete('error');
    }

    const nuevaUrl = `${window.location.pathname}${params.toString() ? '?' + params.toString() : ''}`;
    window.history.replaceState({}, '', nuevaUrl);
  }
});