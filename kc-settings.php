<?php

/*
Plugin name: KC Settings
Plugin URI: http://kucrut.org/2010/10/kc-settings/
Description: Easily create plugin/theme settings page, custom fields metaboxes, term meta and user meta settings.
Version: 1.3.6
Author: Dzikri Aziz
Author URI: http://kucrut.org/
License: GPL v2

*/

class kcSettings {
	var $prefix;
	var $version;
	var $paths;

	function __construct() {
		$this->prefix = 'kc-settings';
		$this->version = '1.3.6';
		$this->paths();
		$this->actions_n_filters();
	}


	function paths() {
		$paths = array();
		$inc_prefix = "/{$this->prefix}-inc";
		$fname = basename( __FILE__ );

		if ( file_exists(WPMU_PLUGIN_DIR . "/{$fname}") )
			$file = WPMU_PLUGIN_DIR . "/{$fname}";
		else
			$file = WP_PLUGIN_DIR . "/{$this->prefix}/{$fname}";

		$paths['file']		= $file;
		$paths['inc']			= dirname( $file ) . $inc_prefix;
		$url							= plugins_url( '', $file );
		$paths['url']			= $url;
		$paths['scripts']	= "{$url}{$inc_prefix}/scripts";
		$paths['styles']	= "{$url}{$inc_prefix}/styles";

		$this->paths = $paths;
	}


	function actions_n_filters() {
		add_action( 'init', array(&$this, 'init'), 11 );
		add_action( 'admin_print_footer_scripts', array(&$this, 'scripts') );
		add_action( 'admin_print_styles', array(&$this, 'styles') );

		add_action( 'admin_footer', array(&$this, 'dev') );
	}


	function init() {
		# i18n
		$locale = get_locale();
		$mo = "{$this->paths['inc']}/languages/kc-settings-{$locale}.mo";
		if ( is_readable($mo) )
			load_textdomain( 'kc-settings', "{$this->paths['inc']}/languages/kc-settings-{$locale}.mo" );

		require_once( "{$this->paths['inc']}/metadata.php" );

		# 1. Plugin / Theme Settings
		$this->plugin_settings_init();
		# 2. Custom Fields / Post Meta
		$this->postmeta_init();
		# 3. Terms Meta
		$this->termmeta_init();
		# 4. User Meta
		$this->usermeta_init();

		# Script & style
		$this->scripts_n_styles();
	}

	function plugin_settings_init() {
		$plugin_groups = apply_filters( 'kc_plugin_settings', array() );
		if ( !is_array($plugin_groups) || empty( $plugin_groups ) )
			return;

		require_once( "{$this->paths['inc']}/theme.php" );
		# Loop through the array and pass each item to kcThemeSettings
		foreach ( $plugin_groups as $group ) {
			if ( !is_array($group) || empty($group) )
				return;

			$do = new kcThemeSettings;
			$do->init( $group );

			require_once( "{$this->paths['inc']}/helper.php" );
			require_once( "{$this->paths['inc']}/form.php" );
		}
	}


	function postmeta_init() {
		$cfields = kc_meta( 'post' );
		if ( !is_array($cfields) || empty( $cfields ) )
			return;

		require_once( "{$this->paths['inc']}/post.php" );
		require_once( "{$this->paths['inc']}/helper.php" );
		require_once( "{$this->paths['inc']}/form.php" );
		$do = new kcPostSettings;
		$do->init( $cfields );
	}


	function termmeta_init() {
		$term_options = kc_meta( 'term' );
		if ( !is_array($term_options) || empty($term_options) )
			return;

		require_once( "{$this->paths['inc']}/term.php" );
		require_once( "{$this->paths['inc']}/helper.php" );
		require_once( "{$this->paths['inc']}/form.php" );

		# Create & set termmeta table
		add_action( 'init', 'kc_termmeta_table', 12 );

		# Add every term fields to its taxonomy add & edit screen
		foreach ( $term_options as $tax => $sections ) {
			add_action( "{$tax}_add_form_fields", 'kc_term_meta_field' );
			add_action( "{$tax}_edit_form_fields", 'kc_term_meta_field', 20, 2 );
		}
		# Also add the saving routine
		add_action( 'edit_term', 'kc_save_termmeta', 10, 3);
		add_action( 'create_term', 'kc_save_termmeta', 10, 3);
	}


	function usermeta_init() {
		$user_options = kc_meta( 'user' );
		if ( !is_array($user_options) || empty($user_options) )
			return;

		require_once( "{$this->paths['inc']}/user.php" );
		require_once( "{$this->paths['inc']}/helper.php" );
		require_once( "{$this->paths['inc']}/form.php" );

		# Display additional fields in user profile page
		add_action( 'show_user_profile', 'kc_user_meta_field' );
		add_action( 'edit_user_profile', 'kc_user_meta_field' );

		# Save the additional data
		add_action( 'personal_options_update', 'kc_user_meta_save' );
		add_action( 'edit_user_profile_update', 'kc_user_meta_save' );
	}


	function scripts_n_styles() {
		if ( !is_admin() )
			return;

		wp_register_script( $this->prefix, "{$this->paths['scripts']}/{$this->prefix}.js", array('jquery'), $this->version, true );
		wp_register_script( 'modernizr', "{$this->paths['scripts']}/modernizr-1.7.min.js", false, '1.7', true );
		wp_register_script( 'jquery-ui-datepicker', "{$this->paths['scripts']}/jquery.ui.datepicker.min.js", array('jquery-ui-core'), '1.8.11', true );

		wp_register_style( 'jquery-ui-smoothness', "{$this->paths['styles']}/jquery-ui-smoothness/jquery-ui-1.8.11.smoothness.css", false, '1.8.11' );
		wp_register_style( $this->prefix, "{$this->paths['styles']}/{$this->prefix}.css", false, $this->version );
	}


	function scripts() {
		global $kc_settings_scripts;
		if ( !isset($kc_settings_scripts) || empty($kc_settings_scripts) )
			return;

		wp_print_scripts( $this->prefix );

		# Datepicker
		if ( isset($kc_settings_scripts['date']) && $kc_settings_scripts['date'] ) {
			wp_print_scripts( array('modernizr', 'jquery-ui-datepicker') );
		}
	}


	function styles() {
		wp_enqueue_style( $this->prefix );
		wp_enqueue_style( 'jquery-ui-smoothness' );
	}


	function dev() {
		echo '<pre>';


		echo '</pre>';
	}
}

$kcSettings = new kcSettings;

?>
