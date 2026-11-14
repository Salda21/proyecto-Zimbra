
document.addEventListener('DOMContentLoaded',()=>{
 const add=document.querySelector('#prospectForm');
 add?.addEventListener('submit',e=>{e.preventDefault();const fd=new FormData(add);const row=document.createElement('tr');
 row.innerHTML=`<td>${escapeHtml(fd.get('nombre'))}</td><td>${escapeHtml(fd.get('nivel'))}</td><td>Ahora</td><td>${escapeHtml(fd.get('asignado'))}</td><td><span class="pill green">Nuevo</span></td>`;
 document.querySelector('#prospectTable tbody').prepend(row);document.querySelector('#prospectModal').classList.remove('show');add.reset();});
});
function escapeHtml(s){return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
