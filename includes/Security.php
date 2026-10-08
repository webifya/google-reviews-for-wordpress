<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
final class Security {
    public static function can(): bool { return current_user_can('manage_options'); }
    public static function url($value): string {
        if (!is_scalar($value)) { return ''; }
        $url = esc_url_raw((string)$value, ['https']);
        $p = wp_parse_url($url);
        if (!$p || empty($p['host']) || isset($p['user']) || isset($p['pass']) || isset($p['port'])) { return ''; }
        $host = strtolower($p['host']);
        if (filter_var($host, FILTER_VALIDATE_IP) || !str_contains($host, '.') || preg_match('/(^|\.)(localhost|local|internal|test|invalid)$/', $host)) { return ''; }
        return $url;
    }
    public static function maps($value): string {
        $url = self::url($value); $p = wp_parse_url($url);
        if (!$p || !in_array(strtolower($p['host']??''), ['www.google.com','google.com','maps.google.com','maps.app.goo.gl','goo.gl'], true)) { return ''; }
        if (($p['host']==='goo.gl' && !str_starts_with($p['path']??'', '/maps/')) || (in_array($p['host'],['www.google.com','google.com'],true) && !preg_match('~^/maps(?:/|$)~',$p['path']??''))) { return ''; }
        return $url;
    }
    /** Resolve official share redirects only; never read review HTML or infer ownership. */
    public static function resolve_maps(string $value) {
        $url=self::maps($value); if (!$url || strlen($url)>2048) { return self::error('Use an official HTTPS Google Maps sharing link'); }
        for ($i=0;$i<3;$i++) {
            $host=wp_parse_url($url,PHP_URL_HOST);
            if (!in_array($host,['maps.app.goo.gl','goo.gl'],true)) { return ['url'=>$url,'identity_confirmed'=>false]; }
            $response=wp_safe_remote_head($url,['timeout'=>8,'redirection'=>0,'reject_unsafe_urls'=>true,'limit_response_size'=>1024]);
            if (is_wp_error($response)) { return self::error('Cannot resolve this short link. Open it in your browser and copy the full Maps URL.'); }
            $code=wp_remote_retrieve_response_code($response);$next=wp_remote_retrieve_header($response,'location');
            if (!in_array($code,[301,302,303,307,308],true) || !is_string($next) || !self::maps($next)) { return self::error('This link does not redirect directly to a supported Maps URL. Copy the full listing URL manually.'); }
            $url=self::maps($next);
        }
        return self::error('Too many share-link redirects. Copy the full listing URL manually.');
    }
    /** Accept only a single official sharing iframe, discard all supplied markup. */
    public static function embed($value): string {
        if (!is_string($value) || strlen($value)>16384) { return ''; }
        $value=trim($value);
        if (str_starts_with($value,'<')) {
            if (!preg_match('~^<iframe\s[^<>]*>\s*</iframe\s*>$~is',$value) || preg_match('/(?:\son[a-z]+\s*=|\ssrcdoc\s*=)/i',$value)) { return ''; }
            $tags=new \WP_HTML_Tag_Processor($value);
            if (!$tags->next_tag('IFRAME')) { return ''; }
            $value=$tags->get_attribute('src');
            if (!is_string($value)) { return ''; }
        }
        $url=self::url(html_entity_decode($value,ENT_QUOTES|ENT_HTML5,'UTF-8')); $p=wp_parse_url($url);
        if (!$p || ($p['host']??'')!=='www.google.com' || ($p['path']??'')!=='/maps/embed' || isset($p['fragment'])) { return ''; }
        parse_str($p['query']??'',$query);
        if (!is_string($query['pb']??null) || !str_starts_with($query['pb'],'!') || strlen($query['pb'])<5 || array_diff(array_keys($query),['pb','hl'])) { return ''; }
        return $url;
    }
    public static function ids($input): array { return array_values(array_unique(array_filter(array_map(fn($id)=>is_scalar($id)&&preg_match('/^\d+$/',trim((string)$id))?absint($id):0, is_array($input) ? $input : (is_scalar($input)?explode(',', (string)$input):[]))))); }
    public static function error(string $message, int $status = 400): \WP_Error { return new \WP_Error('grw_invalid', $message, ['status'=>$status]); }
    public static function csv($value): string { $v = (string)$value; return preg_match('/^[=+\-@\t\r]/u', $v) ? "'" . $v : $v; }
}
