'use strict';
const assert=require('node:assert/strict'),{chromium}=require('playwright'),{collectPage,mapsURL}=require('../worker/collector.cjs');
(async()=>{
 let n=0;const check=(v,label)=>{assert.ok(v,label);console.log('PASS: '+label);n++;};
 for(const u of ['http://www.google.com/maps/?query_place_id=ChIJtest','https://evil.example/maps/?query_place_id=ChIJtest','https://www.google.com:443/maps/?query_place_id=bad','https://www.google.com/maps/?query_place_id=ChIJtest&x=1']){
  if(u.endsWith('&x=1'))check(mapsURL(u).hostname==='www.google.com','official listing accepted');else check(assert.throws(()=>mapsURL(u))===undefined,'unsafe or invalid listing rejected');
 }
 const browser=await chromium.launch({headless:true,...(process.env.GRW_BROWSER_PATH?{executablePath:process.env.GRW_BROWSER_PATH}:{})});
 try{
 const page=await browser.newPage();let html='';await page.route('**/*',r=>r.fulfill({status:200,contentType:'text/html',body:html}));
 const card=(id,name,text,rating=5)=>`<div data-review-id="${id}" aria-label="${name}"><span role="img" aria-label="${rating} stars"></span><span class="rsqaWe">3 months ago</span><div class="MyEned"><span class="wiI7pd">${text}</span></div><div class="CDe7pd"><span class="wiI7pd">SYNTHETIC owner reply</span></div></div>`;
 const open=async(body,total=2)=>{html=`<h1>SYNTHETIC Business</h1><main aria-label="SYNTHETIC Business"><button role="tab" aria-label="Reviews for SYNTHETIC Business">Reviews</button><p>${total} reviews</p>${body}</main>`;await page.goto('https://www.google.com/maps/search/?query_place_id=ChIJtest');};
 await open(card('ChZsynthetic01','SYNTHETIC A','SYNTHETIC content')+card('ChZsynthetic02','SYNTHETIC B',''));
 let result=await collectPage(page,{businessName:'SYNTHETIC Business'});check(result.rows.length===2,'text and rating-only reviews collected');check(result.reason==='all_visible'&&result.advertised_total===2,'complete only when advertised total matches');check(result.rows[0].review_date_label==='3 months ago'&&!result.rows[0].review_date,'relative date preserved without fabricated timestamp');check(result.rows[0].response==='SYNTHETIC owner reply','owner reply remains separate from customer text');
 await open(card('ChZsynthetic03','SYNTHETIC C','Short...').replace('</div><div class="CDe7pd">','<button aria-label="See more" onclick="this.previousElementSibling.textContent=\'SYNTHETIC expanded text\';this.remove()">More</button></div><div class="CDe7pd">')+'<p>You\'re seeing a limited view of Google Maps.</p>',21);
 result=await collectPage(page,{businessName:'SYNTHETIC Business'});check(result.rows[0].content==='SYNTHETIC expanded text','visible More control expands full text');check(result.rows.length===1&&result.reason==='limited_view'&&result.advertised_total===21,'limited public view is explicitly partial');
 await open(card('ChZsynthetic04','SYNTHETIC D','Text'));result=await collectPage(page,{businessName:'Wrong Business'});check(result.reason==='identity_mismatch'&&result.rows.length===0,'wrong business rejected');
 await open('<p>Our systems detected unusual traffic.</p>');result=await collectPage(page);check(result.reason==='captcha'&&result.rows.length===0,'CAPTCHA stops without collecting or solving');
 await open('<div role="dialog" aria-label="Sign-in to get the best of Google Maps">Sign in</div>');result=await collectPage(page);check(result.reason==='login_required'&&!result.rows.length,'login restriction stops collection');
 console.log(n+' synthetic worker assertions passed; genuine Google requests: 0');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
