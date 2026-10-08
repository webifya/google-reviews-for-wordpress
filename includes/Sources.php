<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
interface SourceAdapter {
    public function label(): string;
    public function supports_sync(): bool;
    /** Return ['reviews'=>array, 'cursor'=>?string], or WP_Error. Must enforce source license, host allowlist and safe HTTP. */
    public function fetch(array $location, ?string $cursor);
}
final class LocalSource implements SourceAdapter {
    public function __construct(private string $name) {}
    public function label(): string { return $this->name; }
    public function supports_sync(): bool { return false; }
    public function fetch(array $location, ?string $cursor) { return Security::error(__('This source cannot retrieve individual Google reviews. Use authorized imports or register a permitted adapter.', 'google-reviews-for-wordpress')); }
}
final class Sources {
    public static function all(): array {
        $providers=apply_filters('grw_source_adapters',['google_business'=>new GoogleBusinessSource(),'import'=>new LocalSource(__('Authorized import', 'google-reviews-for-wordpress')),'manual'=>new LocalSource(__('Manual testimonials', 'google-reviews-for-wordpress')),'local_json'=>new LocalJsonSource(),'embed'=>new LocalSource(__('Official Google Maps embed', 'google-reviews-for-wordpress'))]);
        return array_filter($providers,fn($p)=>$p instanceof SourceAdapter);
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
