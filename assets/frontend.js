(() => {
'use strict';
const initialized=new WeakSet();
function initialize(root){
 if(initialized.has(root))return; initialized.add(root);
 const c=JSON.parse(root.dataset.grw),track=root.querySelector('.grw-track'),viewport=root.querySelector('.grw-viewport');
 let queue=[],flushTimer,consent=!c.consent||window.grwAnalyticsConsent===true,token;
 try {token=sessionStorage.getItem('grw-session');if(!token){token=Array.from(crypto.getRandomValues(new Uint8Array(16)),n=>n.toString(16).padStart(2,'0')).join('');sessionStorage.setItem('grw-session',token)}}catch{token=Array.from(crypto.getRandomValues(new Uint8Array(16)),n=>n.toString(16).padStart(2,'0')).join('')}
 const flush=()=>{if(!queue.length||!c.track||!consent)return;const events=queue.splice(0,30);fetch(c.endpoint,{method:'POST',credentials:'omit',keepalive:true,headers:{'Content-Type':'application/json'},body:JSON.stringify({events,session:token,consent})}).catch(()=>{});};
 const event=type=>{if(!c.track||!consent||navigator.globalPrivacyControl||navigator.doNotTrack==='1')return;queue.push({widget:c.widget,event:type});clearTimeout(flushTimer);flushTimer=setTimeout(flush,1500)};
 let observed=false;
 const impression=()=>{if(observed||!consent)return;observed=true;event('impression');try{const key='grw-view-'+c.widget;if(!sessionStorage.getItem(key)){event('unique');sessionStorage.setItem(key,'1')}}catch{event('unique')}};
 let visible=false,visibilityTimer;
 const observer=new IntersectionObserver(entries=>{visible=entries[0].isIntersecting&&entries[0].intersectionRatio>=.3;clearTimeout(visibilityTimer);if(visible)visibilityTimer=setTimeout(impression,1000)},{threshold:[0,.3]});observer.observe(root);
 window.addEventListener('grw:consent',e=>{consent=e.detail===true;if(consent&&visible)impression();if(!consent)queue=[]});
 window.addEventListener('pagehide',flush);
 root.querySelectorAll('img').forEach(img=>img.addEventListener('error',()=>img.remove()));
 root.addEventListener('click',e=>{
  const more=e.target.closest('.grw-more'); if(more){event('readmore');event('interaction');const text=more.parentElement.querySelector('.grw-content');
   if(c.expansion==='modal'){const dialog=document.createElement('dialog'),close=document.createElement('button'),p=document.createElement('p');close.textContent='×';close.setAttribute('aria-label',window.GRW_STRINGS?.close||'Close');p.textContent=text.dataset.full;dialog.append(close,p);root.append(dialog);close.onclick=()=>dialog.close();dialog.addEventListener('close',()=>{dialog.remove();more.focus()});dialog.showModal()}
   else{const open=more.getAttribute('aria-expanded')!=='true';more.setAttribute('aria-expanded',String(open));more.textContent=open?more.dataset.less:more.dataset.more;text.textContent=open?text.dataset.full:text.dataset.short;text.classList.toggle('grw-expanded',open)}
  }
  const link=e.target.closest('[data-event]');if(link)event(link.dataset.event);
 });
 if(!track||root.classList.contains('grw-grid'))return;
 const originals=Array.from(track.children),n=originals.length,prev=root.querySelector('.grw-prev'),next=root.querySelector('.grw-next'),dots=root.querySelector('.grw-dots');
 let index=0,step=0,shown=1,loop=false,timer,hover=false,focused=false,interacted=false,animating=false,resizeTimer;
 const pause=()=>{clearInterval(timer)};
 const start=()=>{pause();if(c.autoplay&&n>shown&&!hover&&!focused&&!interacted&&!document.hidden&&!matchMedia('(prefers-reduced-motion: reduce)').matches)timer=setInterval(()=>move(1,false),c.interval)};
 const position=(animate=true)=>{track.style.transitionDuration=animate?c.duration+'ms':'0ms';track.style.transform=`translateX(-${(index+(loop?n:0))*step}px)`;
  const current=((index%n)+n)%n; if(prev)prev.disabled=n<=shown||(!loop&&index===0);if(next)next.disabled=n<=shown||(!loop&&index>=n-shown);
  Array.from(track.children).forEach((card,i)=>{const start=index+(loop?n:0);const active=i>=start&&i<start+shown;card.setAttribute('aria-hidden',String(!active));card.inert=!active});
  dots?.querySelectorAll('button').forEach((b,i)=>b.setAttribute('aria-current',String(i===current)));
 };
 const rebuild=()=>{
  root.querySelectorAll('.grw-more').forEach(b=>{const t=b.parentElement.querySelector('.grw-content');b.hidden=t.dataset.full===t.dataset.short&&t.scrollHeight<=t.clientHeight+1});
  pause();track.querySelectorAll('[data-clone]').forEach(el=>el.remove());index=0;step=originals[0].getBoundingClientRect().width+parseFloat(getComputedStyle(track).gap);shown=Math.max(1,Math.round((viewport.clientWidth+parseFloat(getComputedStyle(track).gap))/step));loop=c.loop&&n>shown;
  if(loop){const clone=card=>{const el=card.cloneNode(true);el.dataset.clone='true';el.setAttribute('aria-hidden','true');el.inert=true;return el};originals.forEach(card=>track.append(clone(card)));const prefix=document.createDocumentFragment();originals.forEach(card=>prefix.append(clone(card)));track.prepend(prefix)}
  
  if(dots){dots.replaceChildren();for(let i=0;i<(n>shown?(loop?n:n-shown+1):0);i++){const b=document.createElement('button');b.type='button';b.setAttribute('aria-label',`${window.GRW_STRINGS?.review||'Review'} ${i+1}`);b.onclick=()=>{interacted=!!c.pause_interaction;index=i;position();start()};dots.append(b)}}position(false);start();
 };
 const move=(delta,user=true)=>{if(n<=shown||animating)return;if(user){event(delta>0?'next':'previous');interacted=!!c.pause_interaction}
 index=loop?index+delta:Math.max(0,Math.min(n-shown,index+delta));position();animating=true;setTimeout(()=>{if(loop){index=((index%n)+n)%n;position(false)}animating=false},c.duration+20);
 if(user){root.querySelector('.grw-status').textContent=`${window.GRW_STRINGS?.review||'Review'} ${((index%n)+n)%n+1} ${window.GRW_STRINGS?.of||'of'} ${n}`;}start();};
 if(prev)prev.onclick=()=>move(-1);if(next)next.onclick=()=>move(1);
 root.addEventListener('mouseenter',()=>{if(c.pause_hover){hover=true;pause()}});root.addEventListener('mouseleave',()=>{hover=false;start()});
 root.addEventListener('focusin',()=>{focused=true;pause()});root.addEventListener('focusout',()=>{setTimeout(()=>{focused=root.contains(document.activeElement);start()},0)});
 root.addEventListener('keydown',e=>{if(e.target.matches('input,textarea'))return;if(e.key==='ArrowRight'||e.key==='ArrowLeft'){e.preventDefault();move(e.key==='ArrowRight'?1:-1)}});
 let down;viewport.addEventListener('pointerdown',e=>{if(e.target.closest('button,a,details'))return;down={x:e.clientX,y:e.clientY};pause()});viewport.addEventListener('pointerup',e=>{if(down){const x=e.clientX-down.x,y=e.clientY-down.y;if(Math.abs(x)>45&&Math.abs(x)>Math.abs(y))move(x<0?1:-1);down=null;start()}});viewport.addEventListener('pointercancel',()=>{down=null;start()});
 document.addEventListener('visibilitychange',start);new ResizeObserver(()=>{clearTimeout(resizeTimer);resizeTimer=setTimeout(rebuild,80)}).observe(viewport);rebuild();
}
window.GRW={init:(scope=document)=>scope.querySelectorAll('[data-grw]').forEach(initialize)};
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',()=>window.GRW.init());else window.GRW.init();
})();
