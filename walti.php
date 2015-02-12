<?php
/**
 * @package Walti
 */
/**
 * Plugin Name: Walti
 * Plugin URI: https://walti.io/
 * Description: Walti makes server-side security scans more accesible. This plugin enables you to execute scans and show their results on WordPress Administration Screen.
 * Version: 0.9.3
 * Author: Walti, Inc.
 * Author URI: https://walti.io/
 * License: GPLv2 or later
 */

// 直接実行された場合は異常終了
defined( 'ABSPATH' ) or die( 'Not Allowed' );

define( 'WALTI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WALTI_URL', 'https://console.walti.io' );
define( 'API_URL', 'https://api.walti.io' );
define( 'WALTI_KEY_FILE', 'walti_key.php');

if ( is_admin() ) {
	require_once( WALTI_PLUGIN_DIR . 'class-walti-util.php' );
	require_once( WALTI_PLUGIN_DIR . 'class-walti-admin.php' );
	require_once( WALTI_PLUGIN_DIR . 'class-walti-api.php' );
	require_once( WALTI_PLUGIN_DIR . 'class-walti-api-result.php' );
	require_once( WALTI_PLUGIN_DIR . 'class-walti-target.php' );
	require_once( WALTI_PLUGIN_DIR . 'class-walti-credentials.php' );
	require_once( WALTI_PLUGIN_DIR . 'class-walti-organization.php' );
	require_once( WALTI_PLUGIN_DIR . 'class-walti-plugin.php' );
	require_once( WALTI_PLUGIN_DIR . 'class-walti-scan.php' );
	require_once( WALTI_PLUGIN_DIR . 'class-walti-flash.php' );

	require_once( WALTI_PLUGIN_DIR . 'exception/class-walti-auth-exception.php' );

	add_action( 'init', array( 'Walti_Admin', 'init' ) );
}
