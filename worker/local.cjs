#!/usr/bin/env node
'use strict';
// WordPress supplies one bounded job on stdin. Never accepts site credentials or arbitrary collector options.
const {chromium}=require('playwright');
const {collect}=require('./collector.cjs');
const watchdog=setTimeout(()=>{process.kill(process.pid,'SIGTERM');},190000);watchdog.unref();
async function main(){
 if(Number(process.versions.node.split('.')[0])<22)throw Error('Node.js 22 required');
 let input='';for await(const chunk of process.stdin){input+=chunk;if(Buffer.byteLength(input)>8192)throw Error('Oversized job');}
 const job=JSON.parse(input);
 if(job.check===true){
  const browser=await chromium.launch({headless:process.env.GRW_HEADLESS==='1',executablePath:process.env.GRW_BROWSER_PATH,timeout:15000});
  await browser.close();process.stdout.write(JSON.stringify({ready:true}));return;
 }
 const result=await collect({url:job.url,business_name:typeof job.business_name==='string'?job.business_name:'',maximum:Math.max(1,Math.min(500,Number(job.maximum)||500))});
 process.stdout.write(JSON.stringify(result));
}
main().catch(()=>{process.stderr.write('Bundled browser collector stopped.');process.exitCode=1;});
