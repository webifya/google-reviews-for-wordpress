#!/usr/bin/env node
'use strict';
const fs=require('node:fs/promises');const {collect,mapsURL}=require('./collector.cjs');
const args=process.argv.slice(2),arg=name=>args.includes(name)?args[args.indexOf(name)+1]:undefined;
async function main(){
 if(args.includes('--collect')){
  const url=arg('--collect');mapsURL(url);const out=arg('--output');if(!out)throw Error('--output is required');
  const result=await collect({url,business_name:arg('--business')||'',screenshot:arg('--screenshot')});
  await fs.writeFile(out,JSON.stringify({...result,checked_at:new Date().toISOString()},null,2),{mode:0o600});
  console.log(JSON.stringify({retrieved:result.rows.length,advertised_total:result.advertised_total,reason:result.reason}));return;
 }
 const site=new URL(process.env.GRW_SITE_URL||'');
 if(site.username||site.password||site.search||site.hash||!(site.protocol==='https:'||(site.protocol==='http:'&&['127.0.0.1','localhost','[::1]'].includes(site.hostname)&&process.env.GRW_ALLOW_LOCAL_HTTP==='1')))throw Error('GRW_SITE_URL must use HTTPS; local HTTP requires explicit test opt-in.');
 const username=process.env.GRW_WP_USER,password=process.env.GRW_WP_APP_PASSWORD;if(!username||!password)throw Error('Set GRW_WP_USER and GRW_WP_APP_PASSWORD in the worker environment.');
 // Query routing also works on sites without pretty permalinks or rewrite rules.
 const endpoint=site.toString().replace(/\/$/,'')+'/?rest_route=/grw/v1/scraper';
 const api=async body=>{
  const response=await fetch(endpoint,{method:'POST',redirect:'error',signal:AbortSignal.timeout(20000),headers:{Authorization:'Basic '+Buffer.from(username+':'+password).toString('base64'),'Content-Type':'application/json'},body:JSON.stringify(body)});
  if(!response.ok)throw Error('WordPress worker request failed (HTTP '+response.status+').');return response.json();
 };
 do{
  const {job}=await api({action:'claim'});
  if(job){
   let result;try{result=await collect(job);}catch{result={rows:[],advertised_total:null,reason:'network_error'};}
   const saved=await api({action:'result',id:job.id,lease:job.lease,place_id:job.place_id,...result});
   console.log(JSON.stringify({location:job.id,retrieved:result.rows.length,reason:result.reason,added:saved.added||0,updated:saved.updated||0}));
  }
  if(args.includes('--once'))break;
  await new Promise(resolve=>setTimeout(resolve,job?2000:300000));
 }while(true);
}
main().catch(()=>{console.error('Collector stopped. Check the site URL, WordPress worker credentials, browser installation, and connection. No credentials are logged.');process.exitCode=1;});
