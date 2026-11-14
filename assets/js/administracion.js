
document.addEventListener('DOMContentLoaded',()=>{
 document.querySelector('#userForm')?.addEventListener('submit',e=>{
 e.preventDefault();const fd=new FormData(e.currentTarget),tr=document.createElement('tr');
 tr.innerHTML=`<td>${fd.get('nombre')}</td><td>${fd.get('correo')}</td><td>${fd.get('rol')}</td><td><span class="pill green">Activo</span></td><td><button class="icon-btn" title="Editar">✎</button></td>`;
 document.querySelector('#userTable tbody').prepend(tr);document.querySelector('#userModal').classList.remove('show');e.currentTarget.reset();
 });
});
