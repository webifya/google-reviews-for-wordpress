#!/usr/bin/env bash
set -euo pipefail
TASK_ROOT=/tmp/grw-browser
mkdir -p "$TASK_ROOT"
curl -fsSL https://wordpress.org/latest.zip -o "$TASK_ROOT/wordpress.zip"
curl -fsSL https://downloads.wordpress.org/plugin/sqlite-database-integration.latest-stable.zip -o "$TASK_ROOT/sqlite.zip"
unzip -q "$TASK_ROOT/wordpress.zip" -d "$TASK_ROOT"
unzip -q "$TASK_ROOT/sqlite.zip" -d "$TASK_ROOT/wordpress/wp-content/plugins"
cp "$TASK_ROOT/wordpress/wp-content/plugins/sqlite-database-integration/db.copy" "$TASK_ROOT/wordpress/wp-content/db.php"
cat > "$TASK_ROOT/wordpress/wp-config.php" <<'PHP'
<?php
define('DB_NAME','grw_test');define('DB_USER','root');define('DB_PASSWORD','');define('DB_HOST','localhost');define('DB_CHARSET','utf8mb4');define('DB_COLLATE','');
define('AUTH_KEY','isolated-test-auth-key');define('SECURE_AUTH_KEY','isolated-test-secure-key');define('LOGGED_IN_KEY','isolated-test-login-key');define('NONCE_KEY','isolated-test-nonce-key');
define('AUTH_SALT','isolated-test-auth-salt');define('SECURE_AUTH_SALT','isolated-test-secure-salt');define('LOGGED_IN_SALT','isolated-test-login-salt');define('NONCE_SALT','isolated-test-nonce-salt');
define('DISABLE_WP_CRON',true);$table_prefix='wp_';if(!defined('ABSPATH'))define('ABSPATH',__DIR__.'/');require_once ABSPATH.'wp-settings.php';
PHP
cat > "$TASK_ROOT/setup.php" <<'PHP'
<?php
define('WP_INSTALLING',true);$_SERVER['HTTP_HOST']='127.0.0.1:8097';require __DIR__.'/wordpress/wp-load.php';require_once ABSPATH.'wp-admin/includes/upgrade.php';
if(!is_blog_installed())wp_install('Sample site','grwtest','test@example.invalid',false,'','local-test-password-change');
update_option('home','http://127.0.0.1:8097');update_option('siteurl','http://127.0.0.1:8097');
PHP
php "$TASK_ROOT/setup.php"
python3 scripts/package.py "$TASK_ROOT/plugin.zip"
unzip -q "$TASK_ROOT/plugin.zip" -d "$TASK_ROOT/wordpress/wp-content/plugins"
GRW_WP_ROOT="$TASK_ROOT/wordpress" php tests/integration.php
GRW_WP_ROOT="$TASK_ROOT/wordpress" php tests/seed.php
cp tests/preview.php "$TASK_ROOT/wordpress/grw-preview.php"

mkdir -p "$TASK_ROOT/wordpress/wp-content/mu-plugins"
cp tests/phase4-browser-fixture.php "$TASK_ROOT/wordpress/wp-content/mu-plugins/grw-phase4-fixture.php"
GRW_WP_ROOT="$TASK_ROOT/wordpress" php tests/phase4-browser-seed.php
cp tests/phase4-preview.php "$TASK_ROOT/wordpress/grw-phase4-preview.php"
