/* GRW_BASE_URL and GRW_PAGE_ID point at a disposable seeded WordPress installation. */
const {chromium,firefox,webkit}=require('playwright');
const fs=require('fs'),path=require('path');
(async()=>{
 const base=process.env.GRW_BASE_URL||'http://127.0.0.1:8097',pageId=process.env.GRW_PAGE_ID||6,out=process.env.GRW_SCREENSHOTS||'test-results';fs.mkdirSync(out,{recursive:true});
 const engine=process.env.GRW_BROWSER||'chromium';const browser=await ({chromium,firefox,webkit}[engine]).launch({headless:true,...(process.env.GRW_BROWSER_PATH?{executablePath:process.env.GRW_BROWSER_PATH}:{})});
 const errors=[],results=[];const page=await browser.newPage();page.on('pageerror',e=>errors.push(e.message));
 function assert(ok,label){if(!ok)throw new Error(label);results.push('PASS: '+label)}
 for(const width of [375,768,1024,1440]){
  await page.setViewportSize({width,height:1100});await page.goto(process.env.GRW_PREVIEW_URL||base+'/?page_id='+pageId,{waitUntil:'networkidle'});await page.waitForTimeout(400);
  const dimensions=await page.locator('.grw').first().evaluate(root=>{const track=root.querySelector('.grw-track'),v=root.querySelector('.grw-viewport'),card=root.querySelector('.grw-card:not([data-clone])');return {section:root.getBoundingClientRect().width,viewport:v.clientWidth,card:card.getBoundingClientRect().width,gap:parseFloat(getComputedStyle(track).gap),avatar:root.querySelector('.grw-avatar').getBoundingClientRect(),overflow:document.documentElement.scrollWidth>innerWidth}});
  const expected=dimensions.viewport<=580?1:dimensions.viewport<=900?2:3;assert(Math.round((dimensions.viewport+dimensions.gap)/(dimensions.card+dimensions.gap))===expected,width+' responsive card count');assert(!dimensions.overflow,width+' no page overflow');
  await page.locator('.grw').first().screenshot({path:path.join(out,engine+'-'+width+'.png')});
 }
 const roots=page.locator('.grw');assert(await roots.count()===2,'multiple widgets rendered');
 const before=await roots.nth(1).locator('.grw-track').evaluate(e=>e.style.transform);const first=roots.first();
 await first.locator('.grw-next').click();await page.waitForTimeout(600);assert(await roots.nth(1).locator('.grw-track').evaluate(e=>e.style.transform)===before,'independent carousel navigation');
 for(let i=0;i<7;i++){await first.locator('.grw-next').click();await page.waitForTimeout(550)}assert(await first.locator('.grw-card:not([inert])').count()===3,'loop maintains three interactive visible cards');
 await first.locator('.grw-slider').focus();await page.keyboard.press('ArrowLeft');await page.waitForTimeout(600);assert((await first.locator('.grw-status').textContent()).includes('Review'),'keyboard navigation status');
 const more=first.locator('.grw-card:not([inert]) .grw-more:visible').first();if(await more.count()){await more.click();assert(await more.getAttribute('aria-expanded')==='true','inline read more');await more.click();assert(await more.getAttribute('aria-expanded')==='false','read less')}
 const v=first.locator('.grw-viewport'),box=await v.boundingBox(),old=await first.locator('.grw-track').evaluate(e=>e.style.transform);await page.mouse.move(box.x+box.width*.7,box.y+box.height*.5);await page.mouse.down();await page.mouse.move(box.x+box.width*.3,box.y+box.height*.5,{steps:5});await page.mouse.up();await page.waitForTimeout(600);assert(await first.locator('.grw-track').evaluate(e=>e.style.transform)!==old,'pointer swipe/drag');
 
 if(process.env.GRW_PREVIEW_URL){
  const testURL=process.env.GRW_PREVIEW_URL;
  await page.goto(testURL+'?test=autoplay',{waitUntil:'networkidle'});await page.waitForTimeout(400);const automatic=page.locator('.grw').first().locator('.grw-track'),autoBefore=await automatic.evaluate(e=>e.style.transform);await page.waitForTimeout(2200);assert(await automatic.evaluate(e=>e.style.transform)!==autoBefore,'autoplay advances');
  await page.emulateMedia({reducedMotion:'reduce'});await page.goto(testURL+'?test=autoplay',{waitUntil:'networkidle'});await page.waitForTimeout(400);const reduced=page.locator('.grw').first().locator('.grw-track'),reducedBefore=await reduced.evaluate(e=>e.style.transform);await page.waitForTimeout(2200);assert(await reduced.evaluate(e=>e.style.transform)===reducedBefore,'reduced motion prevents autoplay');await page.emulateMedia({reducedMotion:'no-preference'});
  await page.goto(testURL+'?test=single',{waitUntil:'networkidle'});await page.waitForTimeout(400);assert(await page.locator('.grw').first().locator('.grw-next').isDisabled(),'single review disables navigation');assert(await page.locator('.grw').first().locator('[data-clone]').count()===0,'single review has no loop clones');
  await page.goto(testURL+'?test=avatar',{waitUntil:'networkidle'});await page.waitForTimeout(400);const avatarSafe=await page.locator('.grw').first().evaluate(r=>r.querySelector('.grw-card:not([inert]) .grw-avatar').getBoundingClientRect().top>=r.querySelector('.grw-viewport').getBoundingClientRect().top);assert(avatarSafe,'maximum avatar size is not clipped');
  const touchContext=await browser.newContext({viewport:{width:375,height:900},hasTouch:true});const touchPage=await touchContext.newPage();await touchPage.goto(testURL,{waitUntil:'networkidle'});await touchPage.waitForTimeout(400);const tv=touchPage.locator('.grw').first().locator('.grw-viewport'),tt=touchPage.locator('.grw').first().locator('.grw-track'),tb=await tt.evaluate(e=>e.style.transform);await tv.dispatchEvent('pointerdown',{pointerType:'touch',clientX:300,clientY:400});await tv.dispatchEvent('pointerup',{pointerType:'touch',clientX:100,clientY:400});await touchPage.waitForTimeout(600);assert(await tt.evaluate(e=>e.style.transform)!==tb,'touch pointer swipe');await touchContext.close();
 }
 assert(errors.length===0,'no browser JavaScript errors');
 fs.writeFileSync(path.join(out,engine+'-results.json'),JSON.stringify({results,errors},null,2));console.log(results.join('\n'));await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
