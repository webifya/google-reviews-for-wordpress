<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
interface SourceAdapter {
    public function label(): string;
    public function supports_sync(): bool;
    /** Return ['reviews'=>array, 'cursor'=>?string], or WP_Error. Must enforce source license, host allowlist and safe HTTP. */
    public function fetch(array $location, ?string $cursor);
}
/** Providers must supply a reviewed, permanent publication/storage grant before joining the daily workflow.
 * Time-limited licenses require a provider-specific purge implementation, not this permanent-storage interface.
 */
interface PermanentReviewProvider extends SourceAdapter {
    public function policy(): array;
}
final class LocalSource implements SourceAdapter {
    public function __construct(private string $name) {}
    public function label(): string { return $this->name; }
    public function supports_sync(): bool { return false; }
    public function fetch(array $location, ?string $cursor) { return Security::error(__('This source cannot retrieve individual Google reviews. Use authorized imports or register a permitted adapter.', 'google-reviews-for-wordpress')); }
}
final class Sources {
    public static function all(): array {
        $providers=apply_filters('grw_source_adapters',['public'=>new LocalSource(__('Public Google Maps listing', 'google-reviews-for-wordpress')),'google_places'=>new PlacesSource(),'google_business'=>new GoogleBusinessSource(),'import'=>new LocalSource(__('Authorized import', 'google-reviews-for-wordpress')),'manual'=>new LocalSource(__('Manual testimonials', 'google-reviews-for-wordpress')),'local_json'=>new LocalJsonSource(),'embed'=>new LocalSource(__('Official Google Maps embed', 'google-reviews-for-wordpress'))]);
        return array_filter($providers,fn($p)=>$p instanceof SourceAdapter);
    }
    public static function catalog(): array { $out=[]; foreach (self::all() as $id=>$p) { $out[$id]=['label'=>$p->label(),'sync'=>$p->supports_sync(),'capabilities'=>self::capabilities($id)]; } return $out; }
    public static function capabilities(string $id): array {
        $p=self::all()[$id]??null;
        $base=['ownership'=>false,'api_key'=>false,'individual_reviews'=>false,'maximum'=>0,'history'=>false,'new_reviews'=>false,'daily'=>false,'public_display'=>false,'storage'=>'none','attribution'=>'Source and author','requirements'=>'Connect a supported source','license_reference'=>''];
        if ($id==='google_places') { return array_merge($base,['api_key'=>true,'individual_reviews'=>true,'maximum'=>5,'new_reviews'=>true,'public_display'=>true,'requirements'=>'Places API (New), billing, restricted server key, applicable Google agreement and public terms/privacy','attribution'=>'Google Maps, author/avatar/profile, source link, provider credits, visit date when supplied','storage'=>'request_only','license_reference'=>'https://developers.google.com/maps/documentation/places/web-service/policies']); }
        if ($id==='google_business') { return array_merge($base,['ownership'=>true,'individual_reviews'=>true,'maximum'=>'50 per page; paginated owner access','history'=>true,'requirements'=>'Eligible approved project, verified managed business and OAuth','storage'=>'owner_only_15_minutes','license_reference'=>'https://developers.google.com/my-business/content/policies']); }
        if ($p instanceof PermanentReviewProvider) {
            $policy=$p->policy();
            if (!empty($policy['permanent_storage']) && !empty($policy['public_display']) && Security::url($policy['license_reference']??'')) { return array_merge($base,array_intersect_key($policy,$base),['individual_reviews'=>true,'daily'=>$p->supports_sync(),'public_display'=>true,'storage'=>'licensed_permanent']); }
        }
        return array_merge($base,['storage'=>'legacy','requirements'=>'Retained legacy source; no Google review access is implied']);
    }
    public static function eligible(array $location): bool {
        $d=json_decode($location['data'],true)?:[]; $p=self::all()[$d['provider']??'']??null;
        return !empty($d['active']) && $p && $p->supports_sync() && ($d['provider']??'')!=='google_business';
    }
    private static function has_rows(array $location): bool { global $wpdb; return (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}grw_reviews WHERE location_id=%d AND source_type='licensed' LIMIT 1",$location['id'])); }
    public static function connection(array $location): array {
        $d=json_decode($location['data'],true)?:[]; $id=$d['provider']??'public'; $cap=self::capabilities($id);
        $validation=get_option('grw_places_validation_'.$location['id'],[]);
        $verified=$id==='google_places'?Places::status()['configured']&&!empty($validation['has_reviews']):($cap['daily']&&!empty($location['last_success'])&&$location['status']==='last_sync_successful'&&self::has_rows($location));
        return ['capabilities'=>$cap,'connected'=>$verified,'eligible_daily'=>$cap['daily']&&self::eligible($location),'status'=>$id==='google_places'?($verified?'Connected — live selection':(Places::status()['configured']?'Test review retrieval':'API key required')):($cap['daily']?($verified?'Connected — Daily Sync Active':'Review connection requires validation'):($id==='google_business'?'Owner dashboard only':'Review Retrieval Unavailable')),'last_validation'=>$id==='google_places'?($validation['validated_at']??null):$location['last_success'],'last_counts'=>get_option('grw_sync_counts_'.$location['id'],[])];
    }
    /** Network helper for optional adapters: exact trusted hosts supplied by code, never location input. */
    public static function request(string $url,array $trusted_hosts) {
        $p=wp_parse_url(Security::url($url));
        if (!$p || empty($p['host']) || !in_array(strtolower($p['host']),$trusted_hosts,true)) { return Security::error('Host is not allowlisted'); }
        return wp_safe_remote_get($url,['timeout'=>15,'redirection'=>0,'limit_response_size'=>1024*1024,'reject_unsafe_urls'=>true]);
    }
}

/** An administrator-maintained authorized feed in the site's own media library. No remote fetches. */
final class LocalJsonSource implements SourceAdapter {
    public function label(): string { return __('Authorized local JSON feed', 'google-reviews-for-wordpress'); }
    public function supports_sync(): bool { return true; }
    public function fetch(array $location, ?string $cursor) {
        $data=json_decode($location['data'],true);
        if (empty($data['authorized'])) { return Security::error('Publishing permission required'); }
        $file=get_attached_file(absint($data['attachment_id']??0),true);
        $path=$file?realpath($file):false; $uploads=wp_get_upload_dir(); $base=realpath($uploads['basedir']);
        if (!$path || !$base || !str_starts_with($path,$base.DIRECTORY_SEPARATOR) || strtolower(pathinfo($path,PATHINFO_EXTENSION))!=='json' || !is_file($path) || filesize($path)>10*1024*1024) { return Security::error('Use a JSON media attachment inside the WordPress uploads directory, maximum 10 MB.'); }
        $raw=file_get_contents($path); $rows=json_decode($raw,true);
        if (!is_array($rows) || !array_is_list($rows)) { return Security::error('Feed must be a JSON array'); }
        $hash=hash('sha256',$raw); $offset=0;
        if ($cursor!==null) {
            if (!preg_match('/^([a-f0-9]{64}):(\d+)$/',$cursor,$m)) { return Security::error('Invalid feed cursor'); }
            if ($m[1]===$hash) { $offset=(int)$m[2]; }
        }
        $next=$offset+200;
        return ['reviews'=>array_slice($rows,$offset,200),'cursor'=>$next<count($rows)?$hash.':'.$next:null];
    }
}
