<?php
/**
 * Plugin Name: Paid Memberships Pro - Command Palette
 * Description: Adds Paid Memberships Pro commands to the WordPress command palette.
 * Version: 0.1.0
 * Author: Paid Memberships Pro
 * Text Domain: pmpro-command-palette
 * Domain Path: /languages
 * Requires at least: 7.0
 * Requires PHP: 7.4
 * Requires Plugins: paid-memberships-pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PMPRO_COMMAND_PALETTE_VERSION', '0.1.0' );
define( 'PMPRO_COMMAND_PALETTE_DIR', plugin_dir_path( __FILE__ ) );
define( 'PMPRO_COMMAND_PALETTE_URL', plugin_dir_url( __FILE__ ) );
define( 'PMPRO_COMMAND_PALETTE_BASENAME', plugin_basename( __FILE__ ) );

require_once PMPRO_COMMAND_PALETTE_DIR . 'includes/bootstrap.php';
