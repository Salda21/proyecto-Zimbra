
document.addEventListener('DOMContentLoaded',()=>{
 const form=document.querySelector('#messageForm'),input=document.querySelector('#messageInput'),list=document.querySelector('#messageList');
 form?.addEventListener('submit',e=>{e.preventDefault();const value=input.value.trim();if(!value)return;
 const el=document.createElement('div');el.className='message mine';el.textContent=value;list.appendChild(el);input.value='';list.scrollTop=list.scrollHeight;});
 document.querySelectorAll('[data-conversation]').forEach(item=>item.addEventListener('click',()=>{
  document.querySelectorAll('[data-conversation]').forEach(x=>x.classList.remove('selected'));item.classList.add('selected');
  document.querySelector('#chatContact').textContent=item.dataset.conversation;
 }));
});
