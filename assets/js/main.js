document.addEventListener('DOMContentLoaded',()=>{
 const sidebar=document.querySelector('.sidebar');
 document.querySelectorAll('[data-menu-toggle]').forEach(b=>b.addEventListener('click',()=>sidebar?.classList.toggle('open')));
 document.querySelectorAll('[data-confirm]').forEach(b=>b.addEventListener('click',async()=>{
  const accepted=await window.ZCNotify.confirm(b.dataset.confirm);
  if(accepted)window.ZCNotify.success('La acción se completó correctamente.');
 }));
 document.querySelectorAll('[data-search]').forEach(input=>input.addEventListener('input',()=>{
  const target=document.querySelector(input.dataset.search); if(!target)return;
  const q=input.value.toLowerCase();
  target.querySelectorAll('tbody tr').forEach(tr=>tr.style.display=tr.innerText.toLowerCase().includes(q)?'':'none');
 }));
 document.querySelectorAll('[data-filter]').forEach(sel=>sel.addEventListener('change',()=>{
  const target=document.querySelector(sel.dataset.filter); if(!target)return;
  const q=sel.value.toLowerCase();
  target.querySelectorAll('tbody tr').forEach(tr=>tr.style.display=!q||tr.innerText.toLowerCase().includes(q)?'':'none');
 }));
 document.querySelectorAll('[data-modal-open]').forEach(b=>b.addEventListener('click',()=>document.querySelector(b.dataset.modalOpen)?.classList.add('show')));
 document.querySelectorAll('[data-modal-close]').forEach(b=>b.addEventListener('click',()=>b.closest('.modal')?.classList.remove('show')));
 document.querySelectorAll('form[data-demo-form]').forEach(f=>f.addEventListener('submit',e=>{
  e.preventDefault();if(!f.reportValidity())return;
  window.ZCNotify.success('Formulario validado. La conexión con el servidor se implementará en el backend.');
  f.reset();
 }));
});
