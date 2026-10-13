<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
/** Manager-authorized Business Profile integration. Google content never enters the permanent review table. */
final class GoogleBusiness {
    private const OPTION='grw_google_private';
    public static function redirect_uri(): string { return admin_url('admin-post.php?action=grw_google_callback'); }
    public static function status(): array { $s=self::read(); return ['configured'=>!empty($s['client_id'])&&!empty($s['client_secret']),'connected'=>!empty($s['refresh_token']),'redirect_uri'=>self::redirect_uri(),'mode'=>'manager_live','public_budget'=>get_option('grw_google_public_limit',500),'last_validation'=>$s['last_validation']??null,'requires_attention'=>!empty($s['requires_attention'])]; }
    private static function key(): string { return hash('sha256',wp_salt('auth'),true); }
    public static function read(): array {
        $encoded=get_option(self::OPTION,''); return self::decrypt($encoded);
    }
    private static function decrypt($encoded): array {
        if (!is_string($encoded) || !$encoded) { return []; }
        $raw=base64_decode($encoded,true); if ($raw===false || strlen($raw)<28) { return []; }
        $plain=openssl_decrypt(substr($raw,28),'aes-256-gcm',self::key(),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16));
        return $plain? (json_decode($plain,true) ?: []) : [];
    }
    private static function encrypt(array $data): string { $iv=random_bytes(12); $tag=''; $cipher=openssl_encrypt(wp_json_encode($data),'aes-256-gcm',self::key(),OPENSSL_RAW_DATA,$iv,$tag); if ($cipher===false) { throw new \RuntimeException('Credential encryption unavailable'); } return base64_encode($iv.$tag.$cipher); }
    private static function write(array $data): void { update_option(self::OPTION,self::encrypt($data),false); }
    public static function configure(array $input) {
        $s=self::read(); $id=is_string($input['client_id']??null)?trim($input['client_id']):'';
        $secret=is_string($input['client_secret']??null)?trim($input['client_secret']):'';
        if (!$id) { $id=$s['client_id']??''; }
        update_option('grw_google_public_limit',max(1,min(10000,absint($input['public_budget']??get_option('grw_google_public_limit',500)))),false);
        if (!$id || strlen($id)>512 || !preg_match('/^[a-zA-Z0-9.-]+\.apps\.googleusercontent\.com$/',$id)) { return Security::error('Enter the OAuth web client ID from your approved Google Cloud project'); }
        if ($secret==='' && ($s['client_id']??'')===$id) { return self::status(); }
        if (!$secret || strlen($secret)>512) { return Security::error('Enter your OAuth client secret'); }
        self::write(['client_id'=>$id,'client_secret'=>$secret]); self::cleanup(true); return self::status();
    }
    public static function authorize() {
        $s=self::read(); if (empty($s['client_id']) || empty($s['client_secret'])) { return Security::error('Configure OAuth credentials first'); }
        if (wp_parse_url(self::redirect_uri(),PHP_URL_SCHEME)!=='https') { return Security::error('Google OAuth requires a publicly reachable HTTPS WordPress site'); }
        $state=bin2hex(random_bytes(32)); set_transient('grw_oauth_'.hash('sha256',$state),get_current_user_id(),600);
        return ['url'=>'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query(['client_id'=>$s['client_id'],'redirect_uri'=>self::redirect_uri(),'response_type'=>'code','scope'=>'https://www.googleapis.com/auth/business.manage','access_type'=>'offline','prompt'=>'consent','state'=>$state])];
    }
    public static function callback(): void {
        $state=sanitize_text_field(wp_unslash($_GET['state']??''));
        $code=empty($_GET['error'])?sanitize_text_field(wp_unslash($_GET['code']??'')):'';
        $result=self::complete_authorization($state,$code);
        wp_safe_redirect(admin_url('admin.php?page=grw-integrations&google='.(is_wp_error($result)?'action_required':'connected'))); exit;
    }
    /** One-time state belongs to the logged-in WordPress administrator who started OAuth. */
    public static function complete_authorization(string $state,string $code) {
        if (!Security::can()) { return Security::error('Administrator access is required.',403); }
        $key='grw_oauth_'.hash('sha256',$state); $user=get_transient($key); delete_transient($key);
        if (!preg_match('/^[a-f0-9]{64}$/',$state) || !$user || (int)$user!==get_current_user_id() || !$code || strlen($code)>4096) { return Security::error('Google sign-in expired or was cancelled. Start Connect Google again.'); }
        $s=self::read(); $tokens=self::token_request(['grant_type'=>'authorization_code','code'=>$code,'redirect_uri'=>self::redirect_uri(),'client_id'=>$s['client_id']??'','client_secret'=>$s['client_secret']??'']);
        if (is_wp_error($tokens)) { return $tokens; }
        if (empty($tokens['refresh_token']) || !is_string($tokens['refresh_token'])) { return Security::error('Google did not return offline access. Reconnect and approve Business Profile access.'); }
        self::write(array_merge($s,$tokens,['expires_at'=>time()+(int)($tokens['expires_in']??3600),'requires_attention'=>false])); self::cleanup(true); return self::status();
    }
    private static function token_request(array $body) {
        $r=wp_safe_remote_post('https://oauth2.googleapis.com/token',['body'=>$body,'timeout'=>15,'redirection'=>0,'limit_response_size'=>65536]);
        if (is_wp_error($r)) { return new \WP_Error('google_network','Google connection failed. Retry after checking network access.'); }
        $data=json_decode(wp_remote_retrieve_body($r),true);
        if (wp_remote_retrieve_response_code($r)!==200 || !is_array($data) || empty($data['access_token']) || !is_string($data['access_token']) || (isset($data['expires_in']) && !is_numeric($data['expires_in']))) { return new \WP_Error('google_credentials','Google authorization expired or the OAuth project is not configured. Reconnect.'); }
        return $data;
    }
    private static function access() {
        $s=self::read(); if (empty($s['refresh_token'])) { return new \WP_Error('google_reconnect','Connect an eligible Google Business Profile account first'); }
        if (!empty($s['access_token']) && ($s['expires_at']??0)>time()+60) { return $s['access_token']; }
        $token=self::token_request(['grant_type'=>'refresh_token','refresh_token'=>$s['refresh_token'],'client_id'=>$s['client_id'],'client_secret'=>$s['client_secret']]);
        if (is_wp_error($token)) { $s['requires_attention']=true; self::write($s); self::cleanup(true); return $token; }
        self::write(array_merge($s,$token,['expires_at'=>time()+(int)($token['expires_in']??3600)])); return $token['access_token'];
    }
    public static function api(string $url) {
        $p=wp_parse_url($url);
        if (!$p || ($p['scheme']??'')!=='https' || !in_array($p['host']??'',['mybusiness.googleapis.com','mybusinessaccountmanagement.googleapis.com','mybusinessbusinessinformation.googleapis.com'],true) || isset($p['port']) || isset($p['user'])) { return Security::error('Google endpoint denied'); }
        $token=self::access(); if (is_wp_error($token)) { return $token; }
        $r=wp_safe_remote_get($url,['headers'=>['Authorization'=>'Bearer '.$token],'timeout'=>15,'redirection'=>0,'limit_response_size'=>1024*1024]);
        if (is_wp_error($r)) { return new \WP_Error('google_network','Google API is unavailable. Existing authorized imports are preserved.'); }
        $status=wp_remote_retrieve_response_code($r); $data=json_decode(wp_remote_retrieve_body($r),true);
        if ($status!==200 || !is_array($data) || isset($data['error'])) { if ($status===401) { $credentials=self::read(); $credentials['requires_attention']=true; self::write($credentials); self::cleanup(true); } return new \WP_Error($status===401?'google_reconnect':($status===429?'google_quota':'google_access'),'Google access failed. Check project approval, verified-business access, API enablement and quota.',['status'=>400]); }
        $credentials=self::read(); $credentials['last_validation']=current_time('mysql'); $credentials['requires_attention']=false; self::write($credentials);
        return $data;
    }
    public static function accounts(?string $cursor=null) { return self::api('https://mybusinessaccountmanagement.googleapis.com/v1/accounts?pageSize=20'.($cursor?'&pageToken='.rawurlencode($cursor):'')); }
    public static function locations(string $account,?string $cursor=null) {
        if (!preg_match('/^accounts\/\d+$/',$account)) { return Security::error('Invalid Google account'); }
        return self::api('https://mybusinessbusinessinformation.googleapis.com/v1/'.$account.'/locations?readMask=name,title,storefrontAddress,metadata&pageSize=50'.($cursor?'&pageToken='.rawurlencode($cursor):''));
    }
    public static function reviews(int $id,?string $cursor=null,bool $force=false,bool $public=false) {
        $l=Locations::get($id); $d=$l?json_decode($l['data'],true):[]; $parent=$d['google_parent']??'';
        if ($cursor!==null && (!is_string($cursor) || strlen($cursor)>2048)) { return Security::error('Invalid Google page cursor.'); }
        if (($d['provider']??'')!=='google_business' || !preg_match('/^accounts\/\d+\/locations\/\d+$/',$parent)) { return Security::error('Choose and confirm a managed Google location first'); }
        if (!self::status()['connected'] || self::status()['requires_attention']) { return new \WP_Error('google_reconnect','Connect an eligible Google Business Profile account first'); }
        $key='grw_gbp_'.hash('sha256',get_option('grw_google_cache_generation','initial').':'.$id.':'.$parent.':'.($cursor??''));
        if (!$force && ($cached=get_transient($key))!==false) { $cached=self::decrypt($cached); if (($cached['expires']??0)>time() && is_array($cached['data']??null)) { return $cached['data']; } }
        if ($public && !self::reserve()) { return new \WP_Error('google_budget','Google review request budget reached. Retry tomorrow or increase the budget in Settings.',['status'=>429]); }
        $r=self::api('https://mybusiness.googleapis.com/v4/'.$parent.'/reviews?pageSize=50&orderBy=updateTime%20desc'.($cursor?'&pageToken='.rawurlencode($cursor):''));
        if (!is_wp_error($r)) {
            if (!is_array($r['reviews']??[]) || count($r['reviews']??[])>50 || (isset($r['nextPageToken']) && (!is_string($r['nextPageToken']) || strlen($r['nextPageToken'])>2048))) { return Security::error('Unexpected Google review response.'); }
            set_transient($key,self::encrypt(['expires'=>time()+900,'data'=>$r]),900);
        }
        return $r;
    }
    /** Re-fetch the selected managed location; do not trust labels or parents from the browser. */
    public static function bind(array $in) {
        $account=$in['account']??''; $name=$in['location']??'';
        if (!is_string($account) || !preg_match('~^accounts/\d+$~',$account) || !is_string($name) || !preg_match('~^locations/\d+$~',$name)) { return Security::error('Select a managed Google business.'); }
        $page=self::locations($account,$in['cursor']??null); if (is_wp_error($page)) { return $page; }
        $chosen=null; foreach ($page['locations']??[] as $l) { if (($l['name']??'')===$name) { $chosen=$l; break; } }
        if (!$chosen || empty($chosen['title'])) { return Security::error('Business no longer appears in this account page. Reload the business list.'); }
        $parent=$account.'/'.$name;
        // This read proves verified-location review access before a connection is saved.
        $probe=self::api('https://mybusiness.googleapis.com/v4/'.$parent.'/reviews?pageSize=50'); if (is_wp_error($probe)) { return $probe; }
        if (!is_array($probe['reviews']??[])) { return Security::error('Unexpected Google review response.'); }
        $id=absint($in['id']??0); $place=$chosen['metadata']['placeId']??'';
        if (!$id) { $matches=[]; foreach (Locations::all() as $l) { $d=json_decode($l['data'],true)?:[]; if (($d['google_parent']??'')===$parent || ($place && ($d['place_id']??'')===$place)) { $matches[]=(int)$l['id']; } } if (count($matches)>1) { return Security::error('Several saved listings match. Choose which existing listing to update.'); } $id=$matches[0]??0; }
        $a=$chosen['storefrontAddress']??[]; $address=implode(', ',array_filter(array_merge($a['addressLines']??[],[$a['locality']??'',$a['administrativeArea']??'',$a['postalCode']??''])));
        $saved=Locations::save(['id'=>$id,'name'=>$chosen['title'],'business_name'=>$chosen['title'],'address'=>$address,'country'=>$a['regionCode']??'','place_id'=>$place,'google_parent'=>$parent,'provider'=>'google_business','identity_confirmed'=>true,'listing_confirmed'=>true,'active'=>true,'frequency'=>0,'maps_url'=>Security::maps($chosen['metadata']['mapsUri']??'')?:($place?'https://www.google.com/maps/search/?api=1&query=Google&query_place_id='.rawurlencode($place):''),'google_public'=>filter_var($in['google_public']??false,FILTER_VALIDATE_BOOLEAN)]);
        self::cleanup(true); return is_wp_error($saved)?$saved:['id'=>$saved,'total'=>$probe['totalReviewCount']??null];
    }
    /** Atomic site-wide public network budget. Cache hits do not spend requests. */
    private static function reserve(): bool {
        global $wpdb; $key='grw_google_public_usage'; $today=gmdate('Y-m-d'); add_option($key,$today.':0','','no');
        for ($i=0;$i<5;$i++) { $old=$wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name=%s",$key)); [$day,$n]=array_pad(explode(':',(string)$old,2),2,0); $n=$day===$today?(int)$n:0; if ($n>=get_option('grw_google_public_limit',500)) { return false; } $ok=$wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value=%s WHERE option_name=%s AND option_value=%s",$today.':'.($n+1),$key,$old)); wp_cache_delete($key,'options'); if ($ok===1) { return true; } } return false;
    }
    /** Ephemeral rows for the existing renderer. Original text and rating-only reviews are retained. */
    public static function rows(int $id,array $page) {
        $location=Locations::get($id); $d=$location?json_decode($location['data'],true):[]; $rows=[]; $seen=[]; $ratings=['ONE'=>1,'TWO'=>2,'THREE'=>3,'FOUR'=>4,'FIVE'=>5];
        foreach ($page['reviews']??[] as $r) {
            if (!is_array($r) || !is_string($r['reviewId']??null) || !$r['reviewId'] || strlen($r['reviewId'])>190 || !is_string($r['starRating']??null) || !isset($ratings[$r['starRating']]) || !is_string($r['createTime']??null) || !strtotime($r['createTime']) || !is_string($r['comment']??'') || !is_array($r['reviewer']??[])) { return Security::error('Google returned invalid review fields.'); }
            $a=$r['reviewer']??[]; if (!is_string($a['displayName']??'') || !is_string($a['profilePhotoUrl']??'') || !is_string($r['reviewReply']['comment']??'')) { return Security::error('Google returned invalid author or reply fields.'); }
            if (isset($seen[$r['reviewId']])) { continue; } $seen[$r['reviewId']]=true;
            $rows[]=['id'=>(int)hexdec(substr(hash('sha256',$id.':'.$r['reviewId']),0,8)),'external_id'=>$r['reviewId'],'location_id'=>$id,'reviewer'=>!empty($a['displayName'])?$a['displayName']:'Anonymous Google reviewer','avatar'=>Security::url($a['profilePhotoUrl']??''),'author_url'=>'','rating'=>$ratings[$r['starRating']],'content'=>$r['comment']??'','review_date'=>gmdate('Y-m-d H:i:s',strtotime($r['createTime'])),'permalink'=>'','source_url'=>Locations::listing_url($d?:[]),'source_type'=>'google_business','source_name'=>'Google','response'=>$r['reviewReply']['comment']??'','visit_date'=>'','attributions'=>[]];
        } return $rows;
    }
    public static function public_locations(array $c): array {
        if ($c['source']!=='connected') { return []; }
        $ids=$c['locations']?:array_column(Locations::all(),'id'); return array_values(array_filter($ids,function($id){$l=Locations::get((int)$id);$d=$l?json_decode($l['data'],true):[];return !empty($l['active']) && !empty($d['active']) && ($d['provider']??'')==='google_business' && !empty($d['google_public']);}));
    }
    public static function token(int $id): string {
        $w=Widgets::get($id); $c=$w?Widgets::sanitize(json_decode($w['config'],true)?:[]):[]; $bindings=[]; foreach ($c?self::public_locations($c):[] as $lid) { $l=Locations::get((int)$lid); $bindings[]=$l['data']; }
        return wp_hash('grw-manager:'.$id.':'.($w['config']??'').':'.wp_json_encode($bindings).':'.get_option('grw_google_cache_generation','initial'));
    }
    /** One API page per request; encrypted continuation prevents changing the selected business or limit. */
    public static function public_widget(\WP_REST_Request $request) {
        $id=absint($request->get_param('id')); $token=$request->get_param('token'); $w=Widgets::get($id);
        if (!$w || !is_string($token) || !hash_equals(self::token($id),$token)) { return new \WP_Error('google_widget','Widget access denied.',['status'=>403]); }
        $c=Widgets::sanitize(json_decode($w['config'],true)?:[]); if (is_scalar($request->get_param('limit')) && absint($request->get_param('limit'))) { $c['limit']=min($c['limit'],absint($request->get_param('limit'))); } $ids=self::public_locations($c);
        if (!$c['active'] || !$ids) { return new \WP_REST_Response(['html'=>'','next'=>null],200,['Cache-Control'=>'no-store, private, max-age=0']); }
        $next=$request->get_param('next'); $state=['widget'=>$id,'token'=>$token,'index'=>0,'cursor'=>null,'remaining'=>$c['limit'],'maximum'=>$c['limit'],'expires'=>time()+900,'seen'=>[]];
        if ($next!==null) { if (!is_string($next) || strlen($next)>40000) { return Security::error('Invalid review continuation.'); } $state=self::decrypt($next); if (($state['widget']??0)!==$id || ($state['token']??'')!==$token || ($state['expires']??0)<=time() || !is_int($state['index']??null) || !isset($ids[$state['index']]) || ($state['remaining']??0)<1 || !is_array($state['seen']??null)) { return Security::error('Review continuation expired. Reload the widget.'); } }
        $c['limit']=min($c['limit'],$state['maximum']);
        $lid=(int)$ids[$state['index']]; $page=self::reviews($lid,$state['cursor'],false,true); if (is_wp_error($page)) { return $page; }
        $rows=self::rows($lid,$page); if (is_wp_error($rows)) { return $rows; }
        $rows=array_values(array_filter($rows,fn($r)=>$r['rating']>=$c['minimum'])); $rows=array_slice($rows,0,$state['remaining']); $state['remaining']-=count($rows);
        $cursor=$page['nextPageToken']??null;
        if ($cursor) { $hash=hash('sha256',$cursor); if (in_array($hash,$state['seen'],true)) { return Security::error('Google repeated a review page. Reload later.'); } $state['seen'][]=$hash; }
        if (!$cursor) { $state['index']++; $state['seen']=[]; } $state['cursor']=$cursor;
        $more=$state['remaining']>0 && isset($ids[$state['index']]);
        return new \WP_REST_Response(['html'=>Renderer::render($id,['limit'=>$c['limit']],false,true,$rows),'next'=>$more?self::encrypt($state):null,'total'=>$page['totalReviewCount']??null],200,['Cache-Control'=>'no-store, private, max-age=0','Pragma'=>'no-cache']);
    }
    public static function cleanup(bool $all=false): void {
        if (!$all) { return; } update_option('grw_google_cache_generation',wp_generate_uuid4(),false); global $wpdb;
        $names=$wpdb->get_col($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",$wpdb->esc_like('_transient_grw_gbp_').'%',$wpdb->esc_like('_transient_timeout_grw_gbp_').'%'));
        foreach ($names as $name) { delete_option($name); }
    }
    public static function disconnect(): array {
        $s=self::read();
        if (!empty($s['refresh_token'])) { wp_safe_remote_post('https://oauth2.googleapis.com/revoke',['body'=>['token'=>$s['refresh_token']],'timeout'=>10,'redirection'=>0,'limit_response_size'=>65536]); }
        delete_option(self::OPTION); self::cleanup(true); return self::status();
    }
}
final class GoogleBusinessSource implements SourceAdapter {
    public function label(): string { return __('Google Business Profile — Manager API','google-reviews-for-wordpress'); }
    public function supports_sync(): bool { return false; }
    public function fetch(array $location,?string $cursor) {
        $data=GoogleBusiness::reviews((int)$location['id'],null,true);
        return is_wp_error($data)?$data:['reviews'=>[],'cursor'=>null];
    }
}
