/* Notificaciones visuales de ZimbraConnect */
(()=>{
 const icons={success:"✓",error:"!",warning:"⚠",info:"i"};
 const titles={success:"¡Todo listo!",error:"No se pudo completar",warning:"Atención",info:"Información"};
 let region;
 function getRegion(){if(!region){region=document.createElement("div");region.className="zc-toast-region";region.setAttribute("aria-live","polite");region.setAttribute("aria-atomic","false");document.body.appendChild(region)}return region}
 function notify(message,type="info",options={}){
  const duration=options.duration??4500, title=options.title||titles[type]||titles.info;
  const toast=document.createElement("div");toast.className=`zc-toast zc-toast--${type}`;
  const icon=document.createElement("div");icon.className="zc-toast__icon";icon.textContent=icons[type]||icons.info;
  const content=document.createElement("div");
  const heading=document.createElement("div");heading.className="zc-toast__title";heading.textContent=title;
  const body=document.createElement("div");body.className="zc-toast__message";body.textContent=String(message);
  content.append(heading,body);
  const close=document.createElement("button");close.className="zc-toast__close";close.type="button";close.setAttribute("aria-label","Cerrar notificación");close.textContent="×";
  const progress=document.createElement("div");progress.className="zc-toast__progress";progress.style.animationDuration=`${duration}ms`;
  toast.append(icon,content,close,progress);getRegion().appendChild(toast);
  let timer=setTimeout(remove,duration);
  function remove(){clearTimeout(timer);toast.classList.add("zc-leaving");toast.addEventListener("animationend",()=>toast.remove(),{once:true});setTimeout(()=>toast.remove(),300)}
  close.addEventListener("click",remove);
  return toast;
 }
 function confirmDialog(message,options={}){
  return new Promise(resolve=>{
   const backdrop=document.createElement("div");backdrop.className="zc-dialog-backdrop";
   const dialog=document.createElement("section");dialog.className="zc-dialog";dialog.setAttribute("role","alertdialog");dialog.setAttribute("aria-modal","true");
   const symbol=document.createElement("div");symbol.className="zc-dialog__symbol";symbol.textContent="?";
   const title=document.createElement("h3");title.textContent=options.title||"¿Deseas continuar?";
   const text=document.createElement("p");text.textContent=message;
   const actions=document.createElement("div");actions.className="zc-dialog__actions";
   const cancel=document.createElement("button");cancel.className="zc-dialog__cancel";cancel.textContent=options.cancelText||"Cancelar";
   const accept=document.createElement("button");accept.className="zc-dialog__accept";accept.textContent=options.confirmText||"Continuar";
   actions.append(cancel,accept);dialog.append(symbol,title,text,actions);backdrop.appendChild(dialog);document.body.appendChild(backdrop);
   function finish(value){backdrop.remove();resolve(value)}
   cancel.addEventListener("click",()=>finish(false));accept.addEventListener("click",()=>finish(true));
   backdrop.addEventListener("click",e=>{if(e.target===backdrop)finish(false)});
   const key=e=>{if(e.key==="Escape"){finish(false);document.removeEventListener("keydown",key)}};
   document.addEventListener("keydown",key);accept.focus();
  });
 }
 window.ZCNotify={show:notify,confirm:confirmDialog,success:m=>notify(m,"success"),error:m=>notify(m,"error"),warning:m=>notify(m,"warning"),info:m=>notify(m,"info")};
})();
