<?php
/**
 * Installs WordPress for the purpose of the unit-tests
 *
 * @todo Reuse the init/load code in init.php
 */

error_reporting( E_ALL & ~E_DEPRECATED & ~E_STRICT );

if(!class_exists('\Codeception\Module\WPLoader')){
    return;
}

Codeception\Module\WPLoader::defineGlobals();

// this replaces the original argument passed in with the `system` function
$multisite = defined('WP_TESTS_MULTISITE') && WP_TESTS_MULTISITE;

$table_prefix = WP_TESTS_TABLE_PREFIX ;

define( 'WP_INSTALLING', true );
require_once dirname( __FILE__ ) . '/functions.php';

$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['HTTP_HOST'] = WP_TESTS_DOMAIN;
$_SERVER['SERVER_NAME'] = WP_TESTS_DOMAIN;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$PHP_SELF = $GLOBALS['PHP_SELF'] = $_SERVER['PHP_SELF'] = '/index.php';

require_once ABSPATH . '/wp-settings.php';

require_once ABSPATH . '/wp-admin/includes/upgrade.php';
require_once ABSPATH . '/wp-includes/wp-db.php';

// Override the PHPMailer
global $phpmailer;
require_once( dirname( __FILE__ ) . '/mock-mailer.php' );
$phpmailer = new MockPHPMailer();

$wpdb->query( 'SET storage_engine = INNODB' );
$wpdb->select( DB_NAME, $wpdb->dbh );

echo "Installing..." . PHP_EOL;

foreach ( $wpdb->tables() as $table => $prefixed_table ) {
	$wpdb->query( "DROP TABLE IF EXISTS $prefixed_table" );
}

foreach ( $wpdb->tables( 'ms_global' ) as $table => $prefixed_table ) {
	$wpdb->query( "DROP TABLE IF EXISTS $prefixed_table" );

	// We need to create references to ms global tables.
	if ( $multisite )
		$wpdb->$table = $prefixed_table;
}

// Prefill a permalink structure so that WP doesn't try to determine one itself.
add_action( 'populate_options', '_set_default_permalink_structure_for_tests' );

wp_install( WP_TESTS_TITLE, 'admin', WP_TESTS_EMAIL, true, null, 'password' );

// Delete dummy permalink structure, as prefilled above.
if ( ! is_multisite() ) {
	delete_option( 'permalink_structure' );
}
remove_action( 'populate_options', '_set_default_permalink_structure_for_tests' );

if ( $multisite ) {
	echo "Installing network..." . PHP_EOL;

	define( 'WP_INSTALLING_NETWORK', true );

	$title = WP_TESTS_TITLE . ' Network';
	$subdomain_install = false;

	install_network();
	populate_network( 1, WP_TESTS_DOMAIN, WP_TESTS_EMAIL, $title, '/', $subdomain_install );
	$wp_rewrite->set_permalink_structure( '' );
}
