
document.addEventListener('DOMContentLoaded',()=>{
 document.querySelector('#proposalForm')?.addEventListener('submit',e=>{
 e.preventDefault();const f=e.currentTarget,fd=new FormData(f),row=document.createElement('tr');
 row.innerHTML=`<td>${fd.get('cliente')}</td><td>${fd.get('producto')}</td><td>${fd.get('monto')}</td><td><span class="pill orange">En revisión</span></td><td>Hoy</td>`;
 document.querySelector('#proposalTable tbody').prepend(row);document.querySelector('#proposalModal').classList.remove('show');f.reset();
 });
});
