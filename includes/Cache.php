<?php
namespace Webifya\GRW;
if (!defined('ABSPATH')) { exit; }
final class Cache {
    public static function invalidate(): void { update_option('grw_cache_generation',wp_generate_uuid4(),false); }
    public static function key(array $config,int $page,int $size): string { return 'grw_q_'.hash('sha256',wp_json_encode([$config,$page,$size,get_option('grw_cache_generation','initial')])); }
}
