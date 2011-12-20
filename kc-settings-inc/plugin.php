<?php

class kcSettings_plugin {

	# Add settings menus and register the options
	function __construct( $group ) {
		# Set menu title if not found
		if ( !isset($group['menu_title']) || empty($group['menu_title']) ) {
			$group['menu_title'] = __( 'My Settings', 'kc-settings' );
			trigger_error( kcSettings::$data['messages']['no_menu_title'] );
		}

		# Set page title if not found
		if ( !isset($group['page_title']) || empty($group['page_title']) ) {
			$group['page_title'] = $group['menu_title'];
			trigger_error( kcSettings::$data['messages']['no_page_title'] );
		}

		$this->group = $group;

		# Register the menus to WP
		add_action( 'admin_menu', array(&$this, 'create_menu'));
		# Register the options
		add_action( 'admin_init', array(&$this, 'register_options'), 11 );
		# Plugin setting link
		add_filter( 'plugin_row_meta', array(&$this, 'setting_link'), 10, 3 );
	}


	# Create the menu
	function create_menu() {
		extract( $this->group, EXTR_OVERWRITE );

		$this->page = add_submenu_page( $menu_location, $page_title, $menu_title, 'manage_options', "kc-settings-{$prefix}", array(&$this, 'settings_page') );
		$this->url = admin_url( "{$menu_location}?page=kc-settings-{$prefix}" );
		kcSettings::$data['pages'][] = $this->page;

		# Help
		if ( isset($help) )
			kcSettings::add_help( $this->page, $help );

		if ( $display == 'metabox' )
			add_action( "load-{$this->page}", array(&$this, 'create_meta_box') );

		if ( isset($load_actions) )
			add_action( "load-{$this->page}", array(&$this, 'load_actions'), 99 );
	}


	function load_actions() {
		foreach ( (array) $this->group['load_actions'] as $func )
			call_user_func( $func, $this );
	}


	# Register settings sections and fields
	function register_options() {
		extract( $this->group, EXTR_OVERWRITE );

		if ( is_array($options) && !empty($options) ) {

			# register our options, unique for each theme/plugin
			register_setting( "{$prefix}_settings", "{$prefix}_settings", array(&$this, 'validate') );

			foreach ( $options as $section ) {
				$section_title = ( isset($section['title']) ) ? $section['title'] : "{$prefix}-section-{$section['id']}";
				# Add sections
				add_settings_section( $section['id'], $section_title, '', "{$prefix}_settings" );
				foreach ( $section['fields'] as $field ) {
					# add fields on each sections
					$args = array(
						'mode' 		=> 'plugin',
						'prefix' 	=> $prefix,
						'section'	=> $section['id'],
						'field'		=> $field,
						'echo'		=> true,
						'tabled'	=> true
					);
					if ( !in_array($field['type'], array('checkbox', 'radio', 'multiinput')) )
						$args['label_for'] = "{$section['id']}__{$field['id']}";

					add_settings_field( $field['id'], $field['title'], 'kcs_settings_field', "{$prefix}_settings", $section['id'], $args );
				}
			}

		}
	}


	# Setting link on the plugins listing page
	function setting_link( $plugin_meta, $plugin_file, $plugin_data ) {
		if ( $plugin_data['Name'] == $this->group['menu_title'] )
			$plugin_meta[] = '<a href="'.$this->url.'">'.__('Settings', 'kc-settings').'</a>';

		return $plugin_meta;
	}


	# Create settings page content/wrapper
	function settings_page() {
		extract( $this->group, EXTR_OVERWRITE ); ?>

	<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php echo $page_title ?></h2>
		<?php do_action( "{$prefix}_kc_settings_page_before", $this->group ) ?>
		<form action="options.php" method="post" id="kc-settings-form">
			<?php
				# The hidden fields
				settings_fields( "{$prefix}_settings" );

				switch ( $this->group['display'] ) {
					case 'metabox' :
						$this->display_meta_box();
					break;
					case 'plain' :
						foreach ( $this->group['options'] as $section ) {
							echo "<h3>{$section['title']}</h3>\n";
							$this->settings_section( $section );
						}
						echo "<p class='submit'><input class='button-primary' name='submit' type='submit' value='".esc_attr( 'Save Changes', 'kc-settings' )."' /></p>\n";
					break;
				}
			?>
		</form>
		<?php do_action( "{$prefix}_kc_settings_page_after", $this->group ) ?>
	</div>
	<?php }


	function settings_section( $section ) {
		if ( isset($section['desc']) && !empty($section['desc']) ) {
			echo "<div class='section-desc'>\n";
			echo wpautop( $section['desc'] );
			echo "</div>\n";
		}

		do_action( 'kc_settings_section_before', $this->group['prefix'], $section );

		# Call user callback function for displaying the section ( if set )
		if ( isset($section['cb']) && is_callable($section['cb']) ) {
			call_user_func( $opt_section['cb'], $opt_section );
		}
		# Defaults to WordPress' Settings API
		else {
			echo "<table class='form-table'>\n";
			do_settings_fields( "{$this->group['prefix']}_settings", $section['id'] );
			echo "</table>\n";
		}

		# Wanna do something after the options table?
		do_action( 'kc_settings_section_after', $this->group['prefix'], $section );
	}


	function create_meta_box() {
		wp_enqueue_script( 'post' );
		add_screen_option('layout_columns', array('max' => 4, 'default' => isset($this->group['has_sidebar']) ? 2 : 1) );
		foreach ( $this->group['options'] as $section )
			add_meta_box( "kc-metabox-{$this->page}-{$section['id']}", $section['title'], array(&$this, 'fill_meta_box'), $this->page, $section['metabox']['context'], $section['metabox']['priority'], $section );
	}


	function fill_meta_box( $object, $box ) {
		$this->settings_section( $box['args'] );
		echo "<p><input class='button-primary' name='submit' type='submit' value='".esc_attr( 'Save Changes', 'kc-settings' )."' /></p>";
	}


	function display_meta_box() {
		wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false );
		wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false );

		global $screen_layout_columns;
		$hide2 = $hide3 = $hide4 = '';
		switch ( $screen_layout_columns ) {
			case 4:
				$width = 'width:25%;';
			break;
			case 3:
				$width = 'width:33.333333%;';
				$hide4 = 'display:none;';
			break;
			case 2:
				$width = 'width:50%;';
				$hide3 = $hide4 = 'display:none;';
			break;
			default:
				$width = 'width:100%;';
				$hide2 = $hide3 = $hide4 = 'display:none;';
		}

		echo "<div class='metabox-holder' id='kc-metabox-{$this->page}'>\n";
		echo "\t<div id='postbox-container-1' class='postbox-container' style='$width'>\n";
		do_meta_boxes( $this->page, 'normal', $this->group );
		do_meta_boxes( $this->page, 'advanced', $this->group );

		echo "\t</div>\n\t<div id='postbox-container-2' class='postbox-container' style='{$hide2}$width'>\n";
		do_meta_boxes( $this->page, 'side', $this->group );

		echo "\t</div>\n\t<div id='postbox-container-3' class='postbox-container' style='{$hide3}$width'>\n";
		do_meta_boxes( $this->page, 'column3', $this->group );

		echo "\t</div>\n\t<div id='postbox-container-4' class='postbox-container' style='{$hide4}$width'>\n";
		do_meta_boxes( $this->page, 'column4', $this->group );
		echo "</div>\n";
	}


	# Setting field validation callback
	function validate( $user_val ) {
		$options = $this->group['options'];
		$prefix = $this->group['prefix'];

		# apply validation/sanitation filter(s) on the new values
		# filter by prefix
		$user_val = apply_filters( "kcv_settings_{$prefix}", $user_val );
		if ( empty($user_val) )
			return apply_filters( "kc_psv", $user_val );

		$nu_val = array();
		foreach ( $user_val as $sk => $sv ) {
			# section filter
			$nu_val[$sk] = apply_filters( "kcv_setting_{$prefix}_{$sk}", $sv );

			foreach ( $sv as $fk => $fv ) {
				$type = $options[$sk]['fields'][$fk]['type'];

				# rebuild and cleanup array for multiinput type options
				if ( $type == 'multiinput' ) {
					$fv = kcs_array_remove_empty( $fv );
					$fv = kcs_array_rebuild_index( $fv );
				}
				elseif ( in_array($type, array('input', 'textarea')) ) {
					$fv = trim( $fv );
				}

				# type-based filter
				$fv = apply_filters( "kcv_setting_{$prefix}_{$type}", $fv );

				# field-based filter
				$fv = apply_filters( "kcv_setting_{$prefix}_{$sk}_{$fk}", $fv );

				# insert the filtered value to our new array
				$nu_val[$sk][$fk] = $fv;
			}
		}

		return apply_filters( "kc_psv", $nu_val );
	}
}

?>
