<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }

/** Runs the bundled collector using the existing queue and result validation. */
final class LocalCollector {
    private static function executable($path): string {
        if (!is_string($path) || !str_starts_with($path,'/') || str_contains($path,"\0")) { return ''; }
        $real=realpath($path);
        return $real && is_file($real) && is_executable($real)?$real:'';
    }
    private static function detect(array $paths): string {
        foreach ($paths as $path) { if ($found=self::executable($path)) { return $found; } }
        return '';
    }
    public static function status(): array {
        $cfg=get_option('grw_collector',null);
        // Decide compatibility once; a local claim must never switch the default transport.
        if ($cfg===null) {
            add_option('grw_collector',['mode'=>get_option('grw_browser_heartbeat',0)?'remote':'local','headless'=>true],'','no');
            $cfg=get_option('grw_collector',[]);
        }
        $mode=$cfg['mode']??'local';
        $node=self::executable($cfg['node']??'')?:self::detect(['/usr/bin/node','/usr/local/bin/node','/opt/homebrew/bin/node']);
        $browser=self::executable($cfg['browser']??'')?:self::detect(['/usr/bin/chromium','/usr/bin/chromium-browser','/usr/bin/google-chrome','/opt/google/chrome/chrome','/Applications/Google Chrome.app/Contents/MacOS/Google Chrome']);
        $problem=!function_exists('proc_open')?'This host disables browser processes. Ask your host to enable proc_open or use an external collector.':(!$node?'Node.js is missing. Ask your host to install Node.js 22 or newer.':(!$browser?'Chrome/Chromium is missing. Ask your host to install it.':''));
        if (!$problem && !is_file(dirname(GRW_FILE).'/worker/node_modules/playwright/package.json')) { $problem='The bundled browser libraries are missing. Reinstall the complete plugin ZIP.'; }
        return ['mode'=>$mode,'node'=>$node,'browser'=>$browser,'headless'=>$cfg['headless']??true,'ready'=>$problem==='','message'=>$problem?:'Browser paths found. Run Check browser to verify that it starts.','check'=>get_option('grw_collector_check',[])];
    }
    public static function configure(array $in) {
        if (($in['action']??'')==='check') {
            $s=self::status(); if (!$s['ready']) { return Security::error($s['message'],503); }
            $r=self::process(['check'=>true],$s,25);
            $check=['ok'=>!is_wp_error($r)&&!empty($r['ready']),'at'=>current_time('mysql',true)];
            update_option('grw_collector_check',$check,false);
            if (!$check['ok']) { return Security::error('The browser did not start. Verify Node.js 22+, Chrome system dependencies, and the hosting process permissions. Headed mode also needs a display.',503); }
            return ['message'=>'The bundled browser started successfully. This checks the server, not Google review access.'];
        }
        if (!in_array($in['mode']??'', ['local','remote'],true)) { return Security::error('Choose bundled or external collection.'); }
        $cfg=['mode'=>$in['mode'],'headless'=>filter_var($in['headless']??true,FILTER_VALIDATE_BOOLEAN)];
        foreach (['node','browser'] as $key) {
            $path=$in[$key]??'';
            if (!is_string($path) || strlen($path)>1024 || ($path!==''&&!self::executable($path))) { return Security::error('Use an absolute path to an installed executable, or leave it blank for automatic detection.'); }
            $cfg[$key]=$path!==''?self::executable($path):'';
        }
        update_option('grw_collector',$cfg,false); delete_option('grw_collector_check');
        if ($cfg['mode']==='remote') { wp_clear_scheduled_hook('grw_browser_local'); } else { self::schedule(); }
        return self::status();
    }
    public static function schedule(): void {
        if (self::status()['mode']==='local' && !wp_next_scheduled('grw_browser_local')) { wp_schedule_single_event(time()+5,'grw_browser_local'); }
    }
    private static function process(array $job,array $s,int $timeout=210) {
        if (!$s['ready']) { return Security::error($s['message'],503); }
        $env=['PATH'=>'/usr/local/bin:/usr/bin:/bin','GRW_BROWSER_PATH'=>$s['browser'],'GRW_HEADLESS'=>$s['headless']?'1':'0'];
        foreach (['HOME','TMPDIR','DISPLAY','XAUTHORITY','XDG_RUNTIME_DIR'] as $key) { $value=getenv($key); if ($value!==false) { $env[$key]=$value; } }
        // Fixed script and argument array: no shell, URL interpolation, passwords or raw review files.
        $proc=@proc_open([$s['node'],dirname(GRW_FILE).'/worker/local.cjs'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(GRW_FILE),$env,['bypass_shell'=>true]);
        if (!is_resource($proc)) { return Security::error('Browser process could not start',503); }
        $output=''; $bad=false; $end=microtime(true)+$timeout; $exit=-1;
        try {
            $input=wp_json_encode($job); if (fwrite($pipes[0],$input)!==strlen($input)) { $bad=true; } fclose($pipes[0]);
            stream_set_blocking($pipes[1],false); stream_set_blocking($pipes[2],false);
            do {
                $output.=stream_get_contents($pipes[1],65536); stream_get_contents($pipes[2],65536);
                if (strlen($output)>2*1024*1024 || microtime(true)>$end) { $bad=true; break; }
                $state=proc_get_status($proc);
                if (!$state['running']) { $exit=$state['exitcode']; $output.=stream_get_contents($pipes[1]); break; }
                usleep(50000);
            } while (!$bad);
        } finally {
            if ($bad) {
                proc_terminate($proc);
                $stop=microtime(true)+3;
                while (proc_get_status($proc)['running'] && microtime(true)<$stop) { usleep(50000); }
                if (proc_get_status($proc)['running']) { proc_terminate($proc,9); }
            }
            foreach ($pipes as $pipe) { if (is_resource($pipe)) { fclose($pipe); } }
            $closed=proc_close($proc); if ($exit===-1) { $exit=$closed; }
        }
        $result=json_decode($output,true);
        return !$bad && $exit===0 && strlen($output)<=2*1024*1024 && is_array($result)?$result:Security::error('Browser process stopped or returned an invalid result',503);
    }
    public static function run(): void {
        $s=self::status(); if ($s['mode']!=='local' || !$s['ready']) { return; }
        $lock=get_option('grw_collector_lock',0); if ($lock && $lock<time()-240) { delete_option('grw_collector_lock'); }
        if (!add_option('grw_collector_lock',time(),'','no')) { self::schedule(); return; }
        try {
            if (function_exists('set_time_limit')) { @set_time_limit(240); }
            $job=Scraper::handle(['action'=>'claim','transport'=>'local'])['job']??null;
            if ($job) {
                $result=self::process(['url'=>$job['url'],'business_name'=>$job['business_name'],'maximum'=>$job['maximum']],$s);
                if (is_wp_error($result)) { $result=['rows'=>[],'reason'=>'runtime_error']; }
                $saved=Scraper::handle(array_merge($result,['action'=>'result','id'=>$job['id'],'lease'=>$job['lease'],'place_id'=>$job['place_id']]));
                if (is_wp_error($saved)) { Sync::log($job['id'],'Bundled collector returned a result that failed validation. Previous reviews preserved.'); }
            }
        } finally { delete_option('grw_collector_lock'); }
        foreach (Locations::all() as $l) { if ((get_option('grw_browser_job_'.$l['id'],[])['state']??'')==='queued') { self::schedule(); break; } }
    }
}
