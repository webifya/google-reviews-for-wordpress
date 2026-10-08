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
    private static ?array $scopeLocations=null;
    public static function clear_scope(): void { self::$scopeLocations=null; }
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
            if (($policy['permanent_storage']??false)===true && ($policy['public_display']??false)===true && Security::url($policy['license_reference']??'')) { return array_merge($base,array_intersect_key($policy,$base),['individual_reviews'=>true,'daily'=>$p->supports_sync(),'public_display'=>true,'storage'=>'licensed_permanent']); }
        }
        return array_merge($base,['storage'=>'legacy','requirements'=>'Retained legacy source; no Google review access is implied']);
    }
    public static function eligible(array $location): bool {
        $d=json_decode($location['data'],true)?:[]; $p=self::all()[$d['provider']??'']??null;
        return !empty($d['active']) && $p && $p->supports_sync() && ($d['provider']??'')!=='google_business' && (!($p instanceof PermanentReviewProvider) || self::capabilities($d['provider'])['storage']==='licensed_permanent');
    }
    /** Source identity excludes appearance/scheduling; provider-specific identity fields remain included. */
    public static function binding(array $location): string {
        $d=json_decode($location['data'],true)?:[];
        foreach (['active','frequency','website','logo','listing_confirmed','maps_url','reviews_url','embed_url','connection_mode','listing_status','map_status','review_source_status'] as $k) { unset($d[$k]); }
        if (!empty($d['place_id']) || !empty($d['cid']) || !empty($d['google_parent'])) { unset($d['business_name'],$d['address'],$d['country']); }
        ksort($d); return hash('sha256',wp_json_encode($d));
    }
    /** Fail closed when a license or business identity changes. Retained rows are never deleted or relabeled. */
    public static function scope(array $ids=[]): array {
        $parts=[]; $args=[]; $grants=get_option('grw_license_grants',[]);
        $locations=self::$scopeLocations??=Locations::all();
        foreach ($locations as $l) {
            if ($ids && !in_array((int)$l['id'],$ids,true)) { continue; }
            $d=json_decode($l['data'],true)?:[]; $provider=$d['provider']??''; $cap=self::capabilities($provider); $grant=$grants[$l['id']][$provider]??[]; $binding=self::binding($l);
            if (empty($l['active']) || $cap['storage']!=='licensed_permanent' || ($grant['reference']??'')!==$cap['license_reference'] || ($grant['binding']??'')!==$binding) { continue; }
            $parts[]='(r.location_id=%d AND r.provider_id=%s AND r.source_binding=%s AND r.license_reference=%s)'; array_push($args,(int)$l['id'],$provider,$binding,$cap['license_reference']);
        }
        return ['where'=>$parts?'('.implode(' OR ',$parts).')':'0=1','args'=>$args];
    }
    private static function row_count(array $location): int {
        global $wpdb; $scope=self::scope([(int)$location['id']]);
        $sql="SELECT COUNT(*) FROM {$wpdb->prefix}grw_reviews r WHERE r.source_type='licensed' AND ".$scope['where'];
        return (int)$wpdb->get_var($scope['args']?$wpdb->prepare($sql,...$scope['args']):$sql);
    }
    public static function error_message(string $code): string {
        return match ($code) {
            'provider_auth'=>'Provider credentials are missing or expired. Reconnect the source.',
            'provider_rate_limit'=>'The provider request limit was reached. Synchronization will retry later.',
            'provider_quota'=>'The provider quota is exhausted. Check the selected plan and usage.',
            'provider_network'=>'The provider could not be reached. Synchronization will retry later.',
            'provider_identity'=>'The returned reviews do not match this business. Check the connection.',
            'provider_license'=>'Storage or display permission is unavailable. Check the provider agreement.',
            'invalid_provider_response'=>'The provider returned unusable review data. Check the connection.',
            default=>'Review retrieval failed. Check the source connection and retry.',
        };
    }
    public static function connection(array $location,?int $accessible=null): array {
        $d=json_decode($location['data'],true)?:[]; $id=$d['provider']??'public'; $cap=self::capabilities($id);
        $validation=get_option('grw_places_validation_'.$location['id'],[]);
        $count=$cap['storage']==='licensed_permanent'?($accessible??self::row_count($location)):0; $scheduled=$cap['daily']&&self::eligible($location)&&!empty($d['frequency'])&&!empty($location['next_sync']);
        $error=get_option('grw_sync_error_'.$location['id'],[]); if ($error) { $error['message']=self::error_message($error['code']??''); }
        $verified=$id==='google_places'?Places::status()['configured']&&!empty($validation['has_reviews']):($cap['daily']&&!empty($location['last_success'])&&$location['status']==='last_sync_successful'&&$count>0);
        return ['capabilities'=>$cap,'connected'=>$verified,'accessible_count'=>$id==='google_places'?($validation['retrieved_count']??null):$count,'storage_label'=>$cap['storage']==='request_only'?'Live display only':($cap['storage']==='licensed_permanent'?'Licensed stored collection':'Retained content'),'daily_active'=>$verified&&$scheduled&&($d['frequency']??0)===86400,'scheduled_active'=>$verified&&$scheduled,'frequency'=>$d['frequency']??259200,'eligible_daily'=>$cap['daily']&&self::eligible($location),'status'=>$id==='google_places'?($verified?'Connected — live selection':(Places::status()['configured']?'Test review retrieval':'API key required')):($cap['daily']?($verified?($scheduled&&($d['frequency']??0)===86400?'Connected — Daily Sync Active':($scheduled?'Connected — Every '.((int)$d['frequency']/86400).' days':'Connected — Manual Sync Only')):'Review connection requires validation'):($id==='google_business'?'Owner dashboard only':'Review Retrieval Unavailable')),'last_validation'=>$id==='google_places'?($validation['validated_at']??null):$location['last_success'],'last_error'=>$error,'last_counts'=>get_option('grw_sync_counts_'.$location['id'],[])];
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
