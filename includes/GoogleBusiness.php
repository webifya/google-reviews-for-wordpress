<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
/** Owner-facing Business Profile management integration. Google content never enters public review storage. */
final class GoogleBusiness {
    private const OPTION='grw_google_private';
    public static function redirect_uri(): string { return admin_url('admin-post.php?action=grw_google_callback'); }
    public static function status(): array { $s=self::read(); return ['configured'=>!empty($s['client_id'])&&!empty($s['client_secret']),'connected'=>!empty($s['refresh_token']),'redirect_uri'=>self::redirect_uri(),'mode'=>'owner_dashboard_only','last_validation'=>$s['last_validation']??null,'requires_attention'=>!empty($s['requires_attention'])]; }
    private static function key(): string { return hash('sha256',wp_salt('auth'),true); }
    public static function read(): array {
        $encoded=get_option(self::OPTION,''); if (!$encoded) { return []; }
        $raw=base64_decode($encoded,true); if ($raw===false || strlen($raw)<28) { return []; }
        $plain=openssl_decrypt(substr($raw,28),'aes-256-gcm',self::key(),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16));
        return $plain? (json_decode($plain,true) ?: []) : [];
    }
    private static function write(array $data): void { $iv=random_bytes(12); $tag=''; $cipher=openssl_encrypt(wp_json_encode($data),'aes-256-gcm',self::key(),OPENSSL_RAW_DATA,$iv,$tag); if ($cipher===false) { throw new \RuntimeException('Credential encryption unavailable'); } update_option(self::OPTION,base64_encode($iv.$tag.$cipher),false); }
    public static function configure(array $input) {
        $s=self::read(); $id=is_string($input['client_id']??null)?trim($input['client_id']):'';
        $secret=is_string($input['client_secret']??null)?trim($input['client_secret']):'';
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
        if (!Security::can()) { wp_die(esc_html__('Administrator access is required.','google-reviews-for-wordpress'),403); }
        $state=sanitize_text_field(wp_unslash($_GET['state']??'')); $key='grw_oauth_'.hash('sha256',$state); $user=get_transient($key); delete_transient($key);
        $code=sanitize_text_field(wp_unslash($_GET['code']??''));
        $success=false;
        if (preg_match('/^[a-f0-9]{64}$/',$state) && (int)$user===get_current_user_id() && $code && empty($_GET['error'])) {
            $s=self::read(); $tokens=self::token_request(['grant_type'=>'authorization_code','code'=>$code,'redirect_uri'=>self::redirect_uri(),'client_id'=>$s['client_id']??'','client_secret'=>$s['client_secret']??'']);
            if (!is_wp_error($tokens) && !empty($tokens['refresh_token'])) { self::write(array_merge($s,$tokens,['expires_at'=>time()+(int)($tokens['expires_in']??3600)])); $success=true; }
        }
        wp_safe_redirect(admin_url('admin.php?page=grw-integrations&tab=owner&google='.($success?'connected':'action_required'))); exit;
    }
    private static function token_request(array $body) {
        $r=wp_safe_remote_post('https://oauth2.googleapis.com/token',['body'=>$body,'timeout'=>15,'redirection'=>0,'limit_response_size'=>65536]);
        if (is_wp_error($r)) { return new \WP_Error('google_network','Google connection failed. Retry after checking network access.'); }
        $data=json_decode(wp_remote_retrieve_body($r),true);
        if (wp_remote_retrieve_response_code($r)!==200 || !is_array($data) || empty($data['access_token'])) { return new \WP_Error('google_credentials','Google authorization expired or the OAuth project is not configured. Reconnect.'); }
        return $data;
    }
    private static function access() {
        $s=self::read(); if (empty($s['refresh_token'])) { return new \WP_Error('google_reconnect','Connect an eligible Google Business Profile account first'); }
        if (!empty($s['access_token']) && ($s['expires_at']??0)>time()+60) { return $s['access_token']; }
        $token=self::token_request(['grant_type'=>'refresh_token','refresh_token'=>$s['refresh_token'],'client_id'=>$s['client_id'],'client_secret'=>$s['client_secret']]);
        if (is_wp_error($token)) { $s['requires_attention']=true; self::write($s); return $token; }
        self::write(array_merge($s,$token,['expires_at'=>time()+(int)($token['expires_in']??3600)])); return $token['access_token'];
    }
    public static function api(string $url) {
        $p=wp_parse_url($url);
        if (!$p || ($p['scheme']??'')!=='https' || !in_array($p['host']??'',['mybusiness.googleapis.com','mybusinessaccountmanagement.googleapis.com','mybusinessbusinessinformation.googleapis.com'],true) || isset($p['port']) || isset($p['user'])) { return Security::error('Google endpoint denied'); }
        $token=self::access(); if (is_wp_error($token)) { return $token; }
        $r=wp_safe_remote_get($url,['headers'=>['Authorization'=>'Bearer '.$token],'timeout'=>15,'redirection'=>0,'limit_response_size'=>1024*1024]);
        if (is_wp_error($r)) { return new \WP_Error('google_network','Google API is unavailable. Existing authorized imports are preserved.'); }
        $status=wp_remote_retrieve_response_code($r); $data=json_decode(wp_remote_retrieve_body($r),true);
        if ($status!==200 || !is_array($data)) { if ($status===401) { $credentials=self::read(); $credentials['requires_attention']=true; self::write($credentials); } return new \WP_Error($status===401?'google_reconnect':($status===429?'google_quota':'google_access'),'Google access failed. Check project approval, verified-business access, API enablement and quota.',['status'=>400]); }
        $credentials=self::read(); $credentials['last_validation']=current_time('mysql'); $credentials['requires_attention']=false; self::write($credentials);
        return $data;
    }
    public static function accounts(?string $cursor=null) { return self::api('https://mybusinessaccountmanagement.googleapis.com/v1/accounts?pageSize=20'.($cursor?'&pageToken='.rawurlencode($cursor):'')); }
    public static function locations(string $account,?string $cursor=null) {
        if (!preg_match('/^accounts\/\d+$/',$account)) { return Security::error('Invalid Google account'); }
        return self::api('https://mybusinessbusinessinformation.googleapis.com/v1/'.$account.'/locations?readMask=name,title,storefrontAddress,metadata&pageSize=50'.($cursor?'&pageToken='.rawurlencode($cursor):''));
    }
    public static function reviews(int $id,?string $cursor=null,bool $force=false) {
        $l=Locations::get($id); $d=$l?json_decode($l['data'],true):[]; $parent=$d['google_parent']??'';
        if (($d['provider']??'')!=='google_business' || !preg_match('/^accounts\/\d+\/locations\/\d+$/',$parent)) { return Security::error('Choose and confirm an owned Google location first'); }
        if (!self::status()['connected']) { return new \WP_Error('google_reconnect','Connect an eligible Google Business Profile account first'); }
        $key='grw_gbp_'.hash('sha256',get_option('grw_google_cache_generation','initial').':'.$id.':'.$parent.':'.($cursor??''));
        if (!$force && ($cached=get_transient($key))!==false) { return $cached; }
        $r=self::api('https://mybusiness.googleapis.com/v4/'.$parent.'/reviews?pageSize=50'.($cursor?'&pageToken='.rawurlencode($cursor):''));
        if (!is_wp_error($r)) { set_transient($key,$r,900); }
        return $r;
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
    public function label(): string { return __('Google Business Profile — owner dashboard only','google-reviews-for-wordpress'); }
    public function supports_sync(): bool { return true; }
    public function fetch(array $location,?string $cursor) {
        $data=GoogleBusiness::reviews((int)$location['id'],null,true);
        return is_wp_error($data)?$data:['reviews'=>[],'cursor'=>null];
    }
}
