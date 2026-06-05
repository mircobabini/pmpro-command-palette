<?php
/**
 * PMPro command palette bootstrap.
 *
 * @package PMProCommandPalette
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'pmprocommandpalette_load_textdomain' );
add_action( 'admin_notices', 'pmprocommandpalette_admin_notices' );
add_action( 'admin_enqueue_scripts', 'pmprocommandpalette_enqueue_assets' );

function pmprocommandpalette_load_textdomain() {
	load_plugin_textdomain( 'pmpro-command-palette', false, dirname( PMPRO_COMMAND_PALETTE_BASENAME ) . '/languages' );
}

function pmprocommandpalette_is_pmpro_installed() {
	return file_exists( WP_PLUGIN_DIR . '/paid-memberships-pro' );
}

function pmprocommandpalette_is_pmpro_active() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	return is_plugin_active( 'paid-memberships-pro/paid-memberships-pro.php' );
}

function pmprocommandpalette_is_supported_environment() {
	return function_exists( 'wp_enqueue_command_palette_assets' )
		&& version_compare( get_bloginfo( 'version' ), '7.0', '>=' );
}

function pmprocommandpalette_can_boot() {
	return defined( 'PMPRO_VERSION' ) && pmprocommandpalette_is_supported_environment();
}

function pmprocommandpalette_admin_notices() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	if ( ! pmprocommandpalette_is_pmpro_installed() ) {
		$url = admin_url( 'plugin-install.php?s=paid+memberships+pro&tab=search&type=term' );
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			wp_kses_post(
				sprintf(
					/* translators: %s: PMPro install URL. */
					__( 'Paid Memberships Pro - Command Palette requires Paid Memberships Pro. <a href="%s">Install Paid Memberships Pro</a> to continue.', 'pmpro-command-palette' ),
					esc_url( $url )
				)
			)
		);
		return;
	}

	if ( ! pmprocommandpalette_is_pmpro_active() ) {
		$url = wp_nonce_url(
			admin_url( 'plugins.php?action=activate&plugin=paid-memberships-pro/paid-memberships-pro.php' ),
			'activate-plugin_paid-memberships-pro/paid-memberships-pro.php'
		);
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			wp_kses_post(
				sprintf(
					/* translators: %s: PMPro activation URL. */
					__( 'Paid Memberships Pro - Command Palette requires Paid Memberships Pro to be active. <a href="%s">Activate Paid Memberships Pro</a> to continue.', 'pmpro-command-palette' ),
					esc_url( $url )
				)
			)
		);
		return;
	}

	if ( ! pmprocommandpalette_is_supported_environment() ) {
		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'Paid Memberships Pro - Command Palette requires WordPress 7.0 or newer with command palette support enabled.', 'pmpro-command-palette' )
		);
	}
}

function pmprocommandpalette_enqueue_assets() {
	if ( ! pmprocommandpalette_can_boot() ) {
		return;
	}

	if ( ! is_admin() ) {
		return;
	}

	wp_enqueue_command_palette_assets();

	$asset_file = PMPRO_COMMAND_PALETTE_DIR . 'build/index.asset.php';
	$asset      = file_exists( $asset_file )
		? require $asset_file
		: array(
			'dependencies' => array( 'wp-api-fetch', 'wp-commands', 'wp-data', 'wp-element', 'wp-html-entities', 'wp-i18n', 'wp-plugins', 'wp-url' ),
			'version'      => PMPRO_COMMAND_PALETTE_VERSION,
		);

	wp_enqueue_script(
		'pmpro-command-palette',
		PMPRO_COMMAND_PALETTE_URL . 'build/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	wp_add_inline_script(
		'pmpro-command-palette',
		'window.pmproCommandPalette = ' . wp_json_encode( pmprocommandpalette_get_client_settings() ) . ';',
		'before'
	);
}

function pmprocommandpalette_get_client_settings() {
	$commands = array();

	if ( current_user_can( pmpro_get_edit_member_capability() ) || current_user_can( 'manage_options' ) ) {
		$commands[] = array(
			'name'  => 'pmpro/add-new-member',
			'label' => __( 'PMPro: Add New Member', 'pmpro-command-palette' ),
			'url'   => admin_url( 'admin.php?page=pmpro-member' ),
		);
	}

	if ( current_user_can( 'pmpro_membershiplevels' ) || current_user_can( 'manage_options' ) ) {
		$commands[] = array(
			'name'  => 'pmpro/create-membership-level',
			'label' => __( 'PMPro: Create Membership Level', 'pmpro-command-palette' ),
			'url'   => admin_url( 'admin.php?page=pmpro-membershiplevels&edit=-1' ),
		);
	}

	if ( current_user_can( 'pmpro_discountcodes' ) || current_user_can( 'manage_options' ) ) {
		$commands[] = array(
			'name'  => 'pmpro/create-discount-code',
			'label' => __( 'PMPro: Create Discount Code', 'pmpro-command-palette' ),
			'url'   => admin_url( 'admin.php?page=pmpro-discountcodes&edit=-1' ),
		);
	}

	if ( current_user_can( 'pmpro_wizard' ) || current_user_can( 'manage_options' ) ) {
		$commands[] = array(
			'name'  => 'pmpro/open-setup-wizard',
			'label' => __( 'PMPro: Open Setup Wizard', 'pmpro-command-palette' ),
			'url'   => admin_url( 'admin.php?page=pmpro-wizard' ),
		);
	}

	if ( current_user_can( 'pmpro_addons' ) || current_user_can( 'manage_options' ) ) {
		$commands[] = array(
			'name'  => 'pmpro/open-add-ons',
			'label' => __( 'PMPro: Open Add Ons', 'pmpro-command-palette' ),
			'url'   => admin_url( 'admin.php?page=pmpro-addons' ),
		);
	}

	return array(
		'commands'         => $commands,
		'searchCommand'    => array(
			'name'  => 'pmpro/search',
			'label' => __( 'PMPro: Search Members, Orders, Levels, and More', 'pmpro-command-palette' ),
		),
		'quickSearchPath'  => '/pmpro/v1/quick_search',
		'nonce'            => wp_create_nonce( 'wp_rest' ),
		'resultTypeLabels' => array(
			'users'          => __( 'Users', 'pmpro-command-palette' ),
			'subscriptions'  => __( 'Subscriptions', 'pmpro-command-palette' ),
			'orders'         => __( 'Orders', 'pmpro-command-palette' ),
			'reports'        => __( 'Reports', 'pmpro-command-palette' ),
			'levels'         => __( 'Levels', 'pmpro-command-palette' ),
			'discounts'      => __( 'Discount Codes', 'pmpro-command-palette' ),
			'settings'       => __( 'Settings', 'pmpro-command-palette' ),
			'documentation'  => __( 'Documentation', 'pmpro-command-palette' ),
		),
	);
}
