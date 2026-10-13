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
        if ($p && isset($p['host'])) { $p['host']=strtolower($p['host']); }
        if (!$p || !in_array(strtolower($p['host']??''), ['www.google.com','google.com','maps.google.com','maps.app.goo.gl','goo.gl'], true)) { return ''; }
        if (($p['host']==='goo.gl' && !str_starts_with($p['path']??'', '/maps/')) || (in_array($p['host'],['www.google.com','google.com'],true) && !preg_match('~^/maps(?:/|$)~',$p['path']??''))) { return ''; }
        return $url;
    }
    public static function ids($input): array { return array_values(array_unique(array_filter(array_map(fn($id)=>is_scalar($id)&&preg_match('/^\d+$/',trim((string)$id))?absint($id):0, is_array($input) ? $input : (is_scalar($input)?explode(',', (string)$input):[]))))); }
    public static function error(string $message, int $status = 400): \WP_Error { return new \WP_Error('grw_invalid', $message, ['status'=>$status]); }
}
