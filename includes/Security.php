<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
final class Security {
    public static function can(): bool { return current_user_can('manage_options'); }
    public static function url($value): string {
        $url = esc_url_raw((string)$value, ['https']);
        $p = wp_parse_url($url);
        if (!$p || empty($p['host']) || isset($p['user']) || isset($p['pass']) || isset($p['port'])) { return ''; }
        $host = strtolower($p['host']);
        if (filter_var($host, FILTER_VALIDATE_IP) || !str_contains($host, '.') || preg_match('/(^|\.)(localhost|local|internal|test|invalid)$/', $host)) { return ''; }
        return $url;
    }
    public static function maps($value): string {
        $url = self::url($value); $p = wp_parse_url($url);
        return $p && !empty($p['host']) && in_array(strtolower($p['host']), ['www.google.com','google.com','maps.google.com','maps.app.goo.gl','goo.gl'], true) ? $url : '';
    }
    public static function embed($value): string {
        $url = self::url($value); $p = wp_parse_url($url);
        return $p && !empty($p['host']) && $p['host'] === 'www.google.com' && ($p['path'] ?? '') === '/maps/embed' && str_starts_with($p['query'] ?? '', 'pb=') ? $url : '';
    }
    public static function ids($input): array { return array_values(array_unique(array_filter(array_map('absint', is_array($input) ? $input : explode(',', (string)$input))))); }
    public static function error(string $message, int $status = 400): \WP_Error { return new \WP_Error('grw_invalid', $message, ['status'=>$status]); }
    public static function csv($value): string { $v = (string)$value; return preg_match('/^[=+\-@\t\r]/u', $v) ? "'" . $v : $v; }
}
