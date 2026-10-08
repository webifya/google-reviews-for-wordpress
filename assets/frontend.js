(() => {
'use strict';
const instances=new Map(),motion=matchMedia('(prefers-reduced-motion: reduce)');
function initialize(root){
 if(instances.has(root))return;
 const c=JSON.parse(root.dataset.grw),track=root.querySelector('.grw-track'),viewport=root.querySelector('.grw-viewport');
 const cleanups=[],timers=new Set();let dead=false;
 const on=(target,type,fn,options)=>{target.addEventListener(type,fn,options);cleanups.push(()=>target.removeEventListener(type,fn,options));};
 const later=(fn,ms)=>{const id=setTimeout(()=>{timers.delete(id);if(!dead)fn()},ms);timers.add(id);return id};
 const observers=[];const watch=(observer,target)=>{observer.observe(target);observers.push(observer)};
 instances.set(root,()=>{dead=true;cleanups.forEach(fn=>fn());timers.forEach(clearTimeout);observers.forEach(o=>o.disconnect());instances.delete(root)});
 on(root,'error',e=>{if(e.target.tagName==='IMG')e.target.remove()},true);
 let queue=[],flushTimer=null,visible=false,visibilityTimer=null,observed=false,token=null,consent=!c.consent||window.grwAnalyticsConsent===true;
 const permitted=()=>c.track&&consent&&!navigator.globalPrivacyControl&&navigator.doNotTrack!=='1';
 const session=()=>{if(token)return token;try{token=sessionStorage.getItem('grw-session')}catch{}if(!token){token=Array.from(crypto.getRandomValues(new Uint8Array(16)),n=>n.toString(16).padStart(2,'0')).join('');try{sessionStorage.setItem('grw-session',token)}catch{}}return token};
 const flush=()=>{flushTimer=null;if(!permitted()){queue=[];return}if(!queue.length)return;const events=queue.splice(0,30);fetch(c.endpoint,{method:'POST',credentials:'same-origin',keepalive:true,headers:{'Content-Type':'application/json'},body:JSON.stringify({events,session:session(),consent})}).catch(()=>{});if(queue.length)flushTimer=later(flush,250)};
 const event=type=>{if(!permitted())return;if(queue.length<120)queue.push({widget:c.widget,event:type});if(!flushTimer)flushTimer=later(flush,1000)};
 const impression=()=>{if(observed||!permitted()||document.hidden)return;observed=true;session();try{const key='grw-view-'+c.widget;if(sessionStorage.getItem(key))return;sessionStorage.setItem(key,'1')}catch{}event('impression');event('unique')};
 if(c.track){
  const measure=()=>{const r=root.getBoundingClientRect(),reachable=Math.min(r.height,innerHeight);visible=r.width>0&&Math.min(r.bottom,innerHeight)-Math.max(r.top,0)>=reachable*.3;clearTimeout(visibilityTimer);if(visible&&!document.hidden)visibilityTimer=later(impression,1000)};
  watch(new IntersectionObserver(measure,{threshold:[0,.01,.1,.3,.5,1]}),root);on(window,'scroll',measure,{passive:true});on(window,'resize',measure);on(document,'visibilitychange',measure);
  on(window,'grw:consent',e=>{consent=e.detail===true;if(!consent)queue=[];measure()});on(window,'pagehide',flush);
 }
 const refreshReadMore=()=>root.querySelectorAll('.grw-more').forEach(b=>{const t=b.parentElement.querySelector('.grw-content');if(b.getAttribute('aria-expanded')!=='true')b.hidden=t.dataset.full===t.dataset.short&&t.scrollHeight<=t.clientHeight+1});
 watch(new ResizeObserver(refreshReadMore),root);refreshReadMore();
 on(root,'click',e=>{
  const more=e.target.closest('.grw-more');if(more){event('readmore');event('interaction');const text=more.parentElement.querySelector('.grw-content');
   if(c.expansion==='modal'){const dialog=document.createElement('dialog'),close=document.createElement('button'),p=document.createElement('p');close.textContent='×';close.setAttribute('aria-label',window.GRW_STRINGS?.close||'Close');p.textContent=text.dataset.full;dialog.append(close,p);root.append(dialog);close.onclick=()=>dialog.close();dialog.addEventListener('close',()=>{dialog.remove();more.focus()},{once:true});dialog.showModal()}
   else{const open=more.getAttribute('aria-expanded')!=='true';more.setAttribute('aria-expanded',String(open));more.textContent=open?more.dataset.less:more.dataset.more;text.textContent=open?text.dataset.full:text.dataset.short;text.classList.toggle('grw-expanded',open);const review=more.closest('[data-review]').dataset.review;root.querySelectorAll('[data-review]').forEach(card=>{if(card.dataset.review!==review)return;const b=card.querySelector('.grw-more'),t=card.querySelector('.grw-content');b.setAttribute('aria-expanded',String(open));b.textContent=open?b.dataset.less:b.dataset.more;t.textContent=open?t.dataset.full:t.dataset.short;t.classList.toggle('grw-expanded',open)})}
  }
  const link=e.target.closest('[data-event]');if(link){event(link.dataset.event);event('interaction')}
 });
 if(!track||root.classList.contains('grw-grid'))return;
 const originals=Array.from(track.children),n=originals.length,prev=root.querySelector('.grw-prev'),next=root.querySelector('.grw-next'),dots=root.querySelector('.grw-dots');
 let index=c.random_start&&n>1?Math.floor(Math.random()*n):0,step=0,shown=1,loop=false,timer=null,hover=false,focused=false,interacted=false,animating=false,resizeTimer;
 const pause=()=>{if(timer){clearTimeout(timer);timers.delete(timer);timer=null}};
 const start=()=>{pause();if(c.autoplay&&n>shown&&!hover&&!focused&&!interacted&&!document.hidden&&!motion.matches&&(loop||index<n-shown))timer=later(()=>{move(1,false);start()},c.interval)};
 const position=(animate=true)=>{
  track.style.transitionDuration=animate?c.duration+'ms':'0ms';track.style.transform=`translateX(-${(index+(loop?n:0))*step}px)`;
  const current=((index%n)+n)%n;if(prev)prev.disabled=n<=shown||(!loop&&index===0);if(next)next.disabled=n<=shown||(!loop&&index>=n-shown);
  Array.from(track.children).forEach((card,i)=>{const begin=index+(loop?n:0),active=i>=begin&&i<begin+shown;card.setAttribute('aria-hidden',String(!active));card.inert=!active});
  dots?.querySelectorAll('button').forEach((b,i)=>b.setAttribute('aria-current',String(i===current)));
 };
 const announce=()=>{const s=root.querySelector('.grw-status');if(s)s.textContent=`${window.GRW_STRINGS?.review||'Review'} ${((index%n)+n)%n+1} ${window.GRW_STRINGS?.of||'of'} ${n}`};
 const rebuild=()=>{
  if(!n)return;pause();track.querySelectorAll('[data-clone]').forEach(el=>el.remove());step=originals[0].getBoundingClientRect().width+parseFloat(getComputedStyle(track).gap);if(!step)return;shown=Math.max(1,Math.floor((viewport.clientWidth+parseFloat(getComputedStyle(track).gap)+1)/step));loop=c.loop&&n>shown;index=loop?((index%n)+n)%n:Math.max(0,Math.min(n-shown,index));
  if(loop){const clone=card=>{const el=card.cloneNode(true);el.dataset.clone='true';el.setAttribute('aria-hidden','true');el.inert=true;return el};originals.forEach(card=>track.append(clone(card)));const prefix=document.createDocumentFragment();originals.forEach(card=>prefix.append(clone(card)));track.prepend(prefix)}
  if(dots){dots.replaceChildren();for(let i=0;i<(n>shown?(loop?n:n-shown+1):0);i++){const b=document.createElement('button');b.type='button';b.setAttribute('aria-label',`${window.GRW_STRINGS?.review||'Review'} ${i+1}`);b.onclick=()=>{interacted=!!c.pause_interaction;index=i;event('navigation');event('interaction');position();announce();start()};dots.append(b)}}position(false);refreshReadMore();start();
 };
 const move=(delta,user=true)=>{if(n<=shown||animating)return;if(user){event(delta>0?'next':'previous');event('interaction');interacted=!!c.pause_interaction}
 index=loop?index+delta:Math.max(0,Math.min(n-shown,index+delta));position();animating=true;later(()=>{if(loop){index=((index%n)+n)%n;position(false)}animating=false},motion.matches?0:c.duration+20);if(user)announce();start();};
 if(prev)on(prev,'click',()=>move(-1));if(next)on(next,'click',()=>move(1));
 on(root,'mouseenter',()=>{if(c.pause_hover){hover=true;pause()}});on(root,'mouseleave',()=>{hover=false;start()});
 on(root,'focusin',()=>{focused=true;pause()});on(root,'focusout',()=>later(()=>{focused=root.contains(document.activeElement);start()},0));
 on(root,'keydown',e=>{if(e.target.matches('input,textarea,select')||e.target.closest('dialog'))return;if(e.key==='ArrowRight'||e.key==='ArrowLeft'){e.preventDefault();move(e.key==='ArrowRight'?1:-1)}});
 let down=null;
 on(viewport,'pointerdown',e=>{if(c.swipe===false||(e.pointerType==='mouse'&&c.mouse_drag===false)||e.target.closest('button,a,details'))return;down={x:e.clientX,y:e.clientY,id:e.pointerId};if(e.pointerId!==undefined)try{viewport.setPointerCapture(e.pointerId)}catch{}pause()});
 on(viewport,'pointerup',e=>{if(down){const x=e.clientX-down.x,y=e.clientY-down.y;if(Math.abs(x)>(c.swipe_sensitivity||45)&&Math.abs(x)>Math.abs(y))move(x<0?1:-1);down=null;start()}});on(viewport,'pointercancel',()=>{down=null;start()});on(viewport,'dragstart',e=>e.preventDefault());
 on(document,'visibilitychange',start);on(motion,'change',start);watch(new ResizeObserver(()=>{clearTimeout(resizeTimer);resizeTimer=later(rebuild,80)}),viewport);rebuild();
}
const dispose=(scope)=>Array.from(instances.entries()).forEach(([root,fn])=>{if(root===scope||scope.contains(root))fn()});
async function liveWidget(node){if(node.dataset.loading)return;node.dataset.loading='1';const data=JSON.parse(node.dataset.grwLive);try{const response=await fetch(data.endpoint,{method:'POST',cache:'no-store',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});if(!response.ok)throw new Error('Live reviews unavailable');const result=await response.json();if(!node.isConnected)return;node.innerHTML=result.html;window.GRW.init(node)}catch{if(node.isConnected)node.textContent=data.empty||''}}
window.GRW={init:(scope=document)=>{scope.querySelectorAll('[data-grw]').forEach(initialize);scope.querySelectorAll('[data-grw-live]').forEach(liveWidget)},dispose,count:()=>instances.size};
new MutationObserver(()=>{for(const [root,fn] of instances)if(!root.isConnected)fn()}).observe(document.documentElement,{childList:true,subtree:true});
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',()=>window.GRW.init());else window.GRW.init();
})();
