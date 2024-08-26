<?php 
/**
 * Plugin Name: Bulk Task Editor
 * Plugin URI: https://code.recuweb.com
 * Version: 1.0.1.21
 * Description: Bulk Edit posts, terms and users
 * Author: Rafasashi
 * Author URI: https://code.recuweb.com
 * Text Domain: rew-bulk-editor
 * Domain Path: /lang/
 * Requires at least: 4.0
 * Requires PHP: 7.4.28
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires WP: 6.4.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once 'includes/class-rew-bulk-editor.php';
require_once 'includes/class-rew-bulk-editor-settings.php';

require_once 'includes/lib/class-rew-bulk-editor-admin-api.php';
require_once 'includes/lib/class-rew-bulk-editor-post-type.php';
require_once 'includes/lib/class-rew-bulk-editor-taxonomy.php';

function rew_bulk_editor() {
	$instance = Rew_Bulk_Editor::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Rew_Bulk_Editor_Settings::instance( $instance );
	}

	return $instance;
}

rew_bulk_editor();
