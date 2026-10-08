<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
/** Places content exists only for the current request. No transients, review rows or content hashes. */
final class Places {
    private const OPTION='grw_places_private';
    private static array $memory=[];
    private static function read(): array {
        $raw=base64_decode(get_option(self::OPTION,''),true);
        if (!$raw || strlen($raw)<28) { return []; }
        $plain=openssl_decrypt(substr($raw,28),'aes-256-gcm',hash('sha256',wp_salt('auth'),true),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16));
        return $plain?(json_decode($plain,true)?:[]):[];
    }
    public static function configure(array $in) {
        if (($in['action']??'')==='disconnect') { delete_option(self::OPTION); self::$memory=[]; return self::status(); }
        $old=self::read(); $key=trim((string)($in['api_key']??''));
        if ($key==='' && !empty($old['key'])) { $key=$old['key']; }
        if (!preg_match('/^[A-Za-z0-9_-]{20,200}$/',$key)) { return Security::error('Enter a valid server API key from Google Cloud.'); }
        if (empty($in['terms_confirmed'])) { return Security::error('Confirm your Google agreement, public terms and privacy policy before enabling live requests.'); }
        $iv=random_bytes(12); $tag='';
        $cipher=openssl_encrypt(wp_json_encode(['key'=>$key,'daily_budget'=>max(1,min(10000,absint($in['daily_budget']??100)))]),'aes-256-gcm',hash('sha256',wp_salt('auth'),true),OPENSSL_RAW_DATA,$iv,$tag);
        if ($cipher===false) { return Security::error('Credential encryption unavailable.'); }
        update_option(self::OPTION,base64_encode($iv.$tag.$cipher),false); self::$memory=[];
        // Configuration changes invalidate validation, without changing any legacy data.
        foreach (Locations::all() as $l) { delete_option('grw_places_validation_'.$l['id']); }
        return self::status();
    }
    public static function status(): array { $s=self::read(); return ['configured'=>!empty($s['key']),'daily_budget'=>$s['daily_budget']??100,'mode'=>'live_only','maximum'=>5]; }
    public static function id(string $input): string {
        $input=trim($input);
        if (preg_match('/^[A-Za-z0-9_-]{5,200}$/',$input)) { return $input; }
        $url=Security::maps($input); if (!$url) { return ''; }
        parse_str(wp_parse_url($url,PHP_URL_QUERY)?:'',$q);
        $id=$q['query_place_id']??$q['place_id']??'';
        return is_string($id)&&preg_match('/^[A-Za-z0-9_-]{5,200}$/',$id)?$id:'';
    }
    /** Lookup uses explicit IDs, or the name in an official Maps /place/ URL. It never reads listing HTML. */
    public static function lookup(string $input) {
        $id=self::id($input); if ($id) { return self::details($id,false); }
        $url=Security::maps($input);
        if ($url && self::status()['configured'] && in_array(wp_parse_url($url,PHP_URL_HOST),['maps.app.goo.gl','goo.gl'],true)) {
            $resolved=Security::resolve_maps($url); if (is_wp_error($resolved)) { return $resolved; } $url=$resolved['url'];
            $id=self::id($url); if ($id) { return self::details($id,false); }
        }
        $path=$url?wp_parse_url($url,PHP_URL_PATH):'';
        if (!$path || !preg_match('~/maps/place/([^/]+)~',$path,$match)) { return Security::error('This Maps link has no Place ID or supported business name. Enter the Place ID, or confirm business identity manually.'); }
        $query=trim(urldecode($match[1]));
        if (!$query || mb_strlen($query)>200) { return Security::error('Use a business URL or Place ID.'); }
        $s=self::read(); if (empty($s['key'])) { return Security::error('Configure Google Places in Settings → Review connections, or confirm identity manually.'); }
        if (!self::reserve()) { return new \WP_Error('places_budget','Daily request limit reached.',['status'=>429]); }
        $r=wp_safe_remote_post('https://places.googleapis.com/v1/places:searchText',['headers'=>['Content-Type'=>'application/json','X-Goog-Api-Key'=>$s['key'],'X-Goog-FieldMask'=>'places.id,places.displayName,places.formattedAddress,places.googleMapsUri,places.rating,places.userRatingCount,places.attributions,places.consumerAlert'],'body'=>wp_json_encode(['textQuery'=>$query,'pageSize'=>3]),'timeout'=>12,'redirection'=>0,'limit_response_size'=>1024*1024,'reject_unsafe_urls'=>true]);
        if (is_wp_error($r)) { return new \WP_Error('places_network','Business lookup could not reach Google.',['status'=>503]); }
        $code=wp_remote_retrieve_response_code($r); $data=json_decode(wp_remote_retrieve_body($r),true);
        if ($code!==200 || !is_array($data) || !is_array($data['places']??[]) || count($data['places']??[])>3) { return new \WP_Error($code===429?'places_quota':'places_access','Business lookup failed. Check key, billing, enabled API and quotas.',['status'=>$code===429?429:400]); }
        $candidates=[]; foreach ($data['places']??[] as $p) { if (is_array($p) && is_string($p['id']??null) && preg_match('/^[A-Za-z0-9_-]{5,200}$/',$p['id']??'') && is_string($p['displayName']['text']??null) && empty($p['consumerAlert'])) { $candidates[]=$p; } }
        return ['candidates'=>$candidates];
    }
    /** Atomic daily site-wide budget. Stores request numbers only; no IP or Google content. */
    private static function reserve(): bool {
        global $wpdb; $s=self::read(); $key='grw_places_budget'; $today=gmdate('Y-m-d');
        add_option($key,$today.':0','','no');
        for ($attempt=0;$attempt<5;$attempt++) {
            $old=$wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name=%s",$key));
            [$day,$n]=array_pad(explode(':',(string)$old,2),2,0); $n=$day===$today?(int)$n:0;
            if ($n>=($s['daily_budget']??100)) { return false; }
            $updated=$wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value=%s WHERE option_name=%s AND option_value=%s",$today.':'.($n+1),$key,$old));
            wp_cache_delete($key,'options'); if ($updated===1) { return true; }
        }
        return false;
    }
    public static function details(string $id,bool $reviews=true) {
        $s=self::read(); if (empty($s['key'])) { return new \WP_Error('places_setup','Configure Google Places API (New) in Settings → Review connections.',['status'=>400]); }
        if (!preg_match('/^[A-Za-z0-9_-]{5,200}$/',$id)) { return Security::error('A Place ID is required. Maps share links do not always include one.'); }
        $key=$id.':'.(int)$reviews; if (isset(self::$memory[$key])) { return self::$memory[$key]; }
        if (!self::reserve()) { return new \WP_Error('places_budget','Daily request limit reached. Review your site budget and Google Cloud quota.',['status'=>429]); }
        $response=wp_safe_remote_get('https://places.googleapis.com/v1/places/'.rawurlencode($id),['headers'=>['X-Goog-Api-Key'=>$s['key'],'X-Goog-FieldMask'=>'id,displayName,formattedAddress,googleMapsUri,rating,userRatingCount,attributions,consumerAlert'.($reviews?',reviews':'')],'timeout'=>12,'redirection'=>0,'limit_response_size'=>1024*1024,'reject_unsafe_urls'=>true]);
        if (is_wp_error($response)) { return new \WP_Error('places_network','Google Places could not be reached. Try again later.',['status'=>503]); }
        $code=wp_remote_retrieve_response_code($response); $data=json_decode(wp_remote_retrieve_body($response),true);
        if ($code!==200 || !is_array($data)) { return new \WP_Error($code===429?'places_quota':'places_access',$code===429?'Google request quota reached.':'Google access failed. Check API enablement, billing, key restrictions and Place ID.',['status'=>$code===429?429:400]); }
        if (($data['id']??'')!==$id || !is_string($data['displayName']['text']??null) || !empty($data['consumerAlert'])) { return new \WP_Error('places_identity','Google returned an unexpected identity or a consumer alert. Review this listing directly on Google Maps.',['status'=>400]); }
        if (!is_array($data['reviews']??[]) || count($data['reviews']??[])>5 || !is_array($data['attributions']??[])) { return Security::error('Unexpected Google Places response.'); }
        self::$memory[$key]=$data; return $data;
    }
    public static function retrieve(int $id) {
        $l=Locations::get($id); $d=$l?json_decode($l['data'],true):[];
        if (!$l || empty($d['active']) || ($d['provider']??'')!=='google_places') { return Security::error('Choose an active Google Places location.'); }
        $result=self::details($d['place_id']??'');
        if (is_wp_error($result)) { delete_option('grw_places_validation_'.$id); return $result; }
        delete_option('grw_places_validation_'.$id);
        $rows=[];
        foreach ($result['reviews']??[] as $i=>$r) {
            $author=$r['authorAttribution']??[];
            if (!is_array($r) || !is_array($author) || !is_string($author['displayName']??null) || !is_numeric($r['rating']??null) || $r['rating']<1 || $r['rating']>5 || (int)$r['rating']!=(float)$r['rating'] || !is_string($r['publishTime']??null) || !strtotime($r['publishTime'])) { return Security::error('Google returned invalid review fields.'); }
            $text=$r['originalText']['text']??$r['text']['text']??'';
            if (!is_string($text)) { return Security::error('Google returned invalid review text.'); }
            $rows[]=['id'=>$i+1,'location_id'=>$id,'reviewer'=>$author['displayName'],'avatar'=>Security::url($author['photoUri']??''),'author_url'=>Security::url($author['uri']??''),'rating'=>(int)$r['rating'],'content'=>$text,'review_date'=>gmdate('Y-m-d H:i:s',strtotime($r['publishTime'])),'permalink'=>Security::url($r['googleMapsUri']??''),'source_url'=>Security::url($result['googleMapsUri']??''),'source_type'=>'google_places','source_name'=>'Google Maps','response'=>'','visit_date'=>isset($r['visitDate']['year'],$r['visitDate']['month'])?sprintf('%04d-%02d',(int)$r['visitDate']['year'],(int)$r['visitDate']['month']):'', 'attributions'=>$result['attributions']??[]];
        }
        // Validated response with zero reviews is a working API, but NOT a connected review collection.
        update_option('grw_places_validation_'.$id,['validated_at'=>current_time('mysql',true),'has_reviews'=>(bool)$rows],false);
        return ['reviews'=>$rows,'identity'=>$result];
    }
    public static function token(int $id): string { return wp_hash('grw-live-widget:'.$id.':'.get_option(self::OPTION,'')); }
    public static function public_widget(\WP_REST_Request $request) {
        $id=absint($request->get_param('id')); $token=$request->get_param('token');
        if (!is_string($token) || !hash_equals(self::token($id),$token) || !Widgets::get($id)) { return new \WP_Error('places_widget','Widget access denied.',['status'=>403]); }
        $w=Widgets::get($id); $c=Widgets::sanitize(json_decode($w['config'],true)?:[]);
        if (!$c['active']) { return new \WP_REST_Response(['html'=>''],200,['Cache-Control'=>'no-store, private, max-age=0']); }
        if (is_scalar($request->get_param('limit')) && absint($request->get_param('limit'))) { $c['limit']=min($c['limit'],absint($request->get_param('limit'))); }
        $response=new \WP_REST_Response(['html'=>Renderer::render($id,$c,false,true)],200,['Cache-Control'=>'no-store, private, max-age=0','Pragma'=>'no-cache']);
        return $response;
    }
}
final class PlacesSource implements SourceAdapter {
    public function label(): string { return 'Google Places — live selection, up to 5 reviews'; }
    public function supports_sync(): bool { return false; }
    public function fetch(array $location,?string $cursor) { return new \WP_Error('places_live_only','Places reviews load on demand. Stored daily synchronization is not supported by this provider.'); }
}
