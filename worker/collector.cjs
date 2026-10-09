'use strict';
const {chromium}=require('playwright');
const CARD='div[data-review-id][aria-label]';

function mapsURL(input){
 const u=new URL(input);
 if(u.protocol!=='https:'||u.hostname!=='www.google.com'||u.username||u.password||u.port||!u.pathname.startsWith('/maps/')||!/^[-\w]{5,200}$/.test(u.searchParams.get('query_place_id')||''))throw Error('Use a full HTTPS Google Maps URL containing query_place_id.');
 return u;
}
async function barrier(page){
 const url=new URL(page.url());
 if(url.hostname==='accounts.google.com')return 'login_required';
 if(!['www.google.com','consent.google.com'].includes(url.hostname))return 'access_denied';
 const text=await page.locator('body').innerText({timeout:5000});
 if(url.pathname.startsWith('/sorry')||/unusual traffic|not a robot|complete the captcha/i.test(text)||await page.locator('iframe[src*="recaptcha"]').count())return 'captcha';
 if(/access denied|temporarily blocked/i.test(text))return 'access_denied';
 if(await page.getByRole('dialog',{name:/Sign-in/}).count())return 'login_required';
 return null;
}
/** Reads only rendered public DOM. No intercepted responses, private endpoints or account cookies. */
async function collectPage(page,{businessName='',maximum=500,deadlineMs=120000}={}){
 maximum=Math.max(1,Math.min(500,Number(maximum)||500));const started=Date.now();
 let blocked=await barrier(page);if(blocked)return {rows:[],advertised_total:null,reason:blocked};
 const reject=page.getByRole('button',{name:'Reject all',exact:true});if(await reject.isVisible().catch(()=>false))await reject.click();
 await page.locator('h1').first().waitFor({state:'visible',timeout:20000}).catch(()=>{});
 const heading=await page.locator('h1').first().innerText().catch(()=> '');
 const norm=s=>s.toLowerCase().replace(/\s+/g,' ').trim();
 if(businessName&&heading&&norm(heading)!==norm(businessName))return {rows:[],advertised_total:null,reason:'identity_mismatch',business_name:heading};
 const reviewTab=page.getByRole('tab',{name:/^Reviews for /});
 await reviewTab.waitFor({state:'visible',timeout:15000}).catch(()=>{});
 if(await reviewTab.count())await reviewTab.click();
 try{await page.locator(CARD).first().waitFor({state:'visible',timeout:15000});}catch{blocked=await barrier(page);return {rows:[],advertised_total:null,reason:blocked||'layout_changed',business_name:heading};}
 const mainName=await page.locator('main[aria-label]').first().getAttribute('aria-label').catch(()=> '');
 if(businessName&&mainName&&norm(mainName)!==norm(businessName))return {rows:[],advertised_total:null,reason:'identity_mismatch',business_name:mainName};
 const main=page.getByRole('main').first();
 const mainText=await main.innerText().catch(()=> '');
 const totals=[...mainText.matchAll(/(?:^|\n)\s*([\d,]+) reviews\s*(?:\n|$)/g)].map(m=>Number(m[1].replace(/,/g,'')));
 const advertised=totals.length?Math.max(...totals):null;
 const rows=new Map();let stalled=0,reason='stalled';
 for(let step=0;step<60&&Date.now()-started<deadlineMs;step++){
  blocked=await barrier(page);if(blocked){reason=blocked;break;}
  const cards=page.locator(CARD);
  for(let i=0;i<await cards.count();i++){
   if(Date.now()-started>=deadlineMs)break;
   const more=cards.nth(i).getByRole('button',{name:'See more',exact:true});
   if(await more.isVisible().catch(()=>false))await more.click({timeout:5000});
  }
  const visible=await cards.evaluateAll(cards=>cards.map(e=>({
   external_id:e.getAttribute('data-review-id'),reviewer:e.getAttribute('aria-label'),
   rating:Number(Array.from(e.querySelectorAll('[role="img"][aria-label]')).map(s=>s.getAttribute('aria-label').match(/^([1-5]) stars?$/)?.[1]).find(Boolean))||null,
   content:e.querySelector('.MyEned .wiI7pd')?.textContent?.trim()||'',
   review_date_label:e.querySelector('.rsqaWe')?.textContent?.trim()||'',
   avatar:e.querySelector('button[aria-label^="Photo of "] img')?.getAttribute('src')||'',
   response:e.querySelector('.CDe7pd .wiI7pd')?.textContent?.trim()||'',
   truncated:!!e.querySelector('.MyEned button[aria-label="See more"]')
  })));
  const before=rows.size;
  for(const r of visible){if(r.external_id&&r.reviewer&&(r.content||r.rating)&&!r.truncated&&/^[\w-]{5,190}$/.test(r.external_id)){delete r.truncated;rows.set(r.external_id,r);if(rows.size>=maximum)break;}}
  if(rows.size>=maximum){reason='limit_reached';break;}
  if(advertised!==null&&rows.size===advertised){reason='all_visible';break;}
  const limited=/limited view of Google Maps/i.test(await main.innerText());
  if(limited){reason='limited_view';break;}
  stalled=rows.size===before?stalled+1:0;if(stalled>=3)break;
  await cards.last().evaluate(e=>{let p=e.parentElement;while(p){const style=getComputedStyle(p);if(p.scrollHeight>p.clientHeight&&/(auto|scroll)/.test(style.overflowY)){p.scrollTop=p.scrollHeight;return;}p=p.parentElement;}});
  await page.waitForTimeout(1500);
 }
 if(['captcha','access_denied','identity_mismatch'].includes(reason))return {rows:[],advertised_total:advertised,reason,business_name:heading||mainName};
 return {rows:[...rows.values()].reverse(),advertised_total:advertised,reason:rows.size?reason:'layout_changed',business_name:heading||mainName};
}
async function collect({url,business_name='',maximum=500,screenshot}={}){
 const listing=mapsURL(url);listing.searchParams.set('hl','en');const browser=await chromium.launch({headless:process.env.GRW_HEADLESS==='1',...(process.env.GRW_BROWSER_PATH?{executablePath:process.env.GRW_BROWSER_PATH}:{})});
 try{
  const context=await browser.newContext({locale:'en-US',viewport:{width:1440,height:1100}});const page=await context.newPage();
  await page.goto(listing.toString(),{waitUntil:'domcontentloaded',timeout:45000});
  const started=Date.now();const result=await collectPage(page,{businessName:business_name,maximum});result.duration_ms=Date.now()-started;
  if(screenshot)await page.screenshot({path:screenshot});return result;
 }finally{await browser.close();}
}
module.exports={collect,collectPage,mapsURL,barrier};
