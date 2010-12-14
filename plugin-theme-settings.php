<?php

class kcThemeSettings {

	private $locations = array(
		'options-general.php',	// Settings
		'tools.php',						// Tools
		'users.php',						// Users
		'plugins.php',					// Plugins
		'themes.php',						// Appearance
		'link-manager.php',			// Links
		'upload.php',						// Media
		'edit.php',							// Posts
		'index.php'							// Dashboard
	);

	# Add settings menus and register the options
	function init( $group ) {
		if ( !is_array($group['options']) || empty($group['options']) )
			return;

		if ( !isset($group['menu_title']) || empty($group['menu_title']) ) {
			$title = __( 'My Settings', 'kc-settings' );
			# Set menu title if not found
			$group['menu_title'] = $title;

			# Set page title if not found
			if ( !isset($group['page_title']) || empty($group['page_title']) )
				$group['page_title'] = $title;
		}

		$this->group = $group;

		# Register the menus to WP
		add_action( 'admin_menu', array($this, 'create_menu'));
		# Register the options
		add_action( 'admin_init', array($this, 'register_options') );
	}


	# Create the menu
	function create_menu() {
		extract( $this->group, EXTR_OVERWRITE );

		# Set the location
		if ( !isset($menu_location) )
			$menu_location = 'options-general.php';
		elseif ( $menu_location == 'parent' )
			$this->parent = true;

		$this->screen = ( !in_array($menu_location, $this->locations) ) ? 'options-general' : null;

		# Top level menu title
		$parent_title = ( isset($parent_title) && !empty($parent_title) ) ? $parent_title : $menu_title;


		if ( isset($this->parent) && $this->parent === true ) {
			add_menu_page( $page_title, $parent_title, 'manage_options', "kc-settings-{$prefix}" );
			add_submenu_page( "kc-settings-{$prefix}", $page_title, $menu_title, 'manage_options', "kc-settings-{$prefix}", array($this, 'settings_page') );
		}
		else {
			add_submenu_page( $menu_location, $page_title, $menu_title, 'manage_options', "kc-settings-{$prefix}", array($this, 'settings_page') );
		}
	}


	# Register settings sections and fields
	function register_options() {
		extract( $this->group, EXTR_OVERWRITE );

		if ( is_array($options) && !empty($options) ) {

			# register our options, unique for each theme/plugin
			register_setting( "{$prefix}_settings", "{$prefix}_settings", array($this, 'validate') );

			foreach ( $options as $section ) {
				# Add sections
				add_settings_section( $section['id'], $section['title'], array($this, 'section_desc'), "{$prefix}_settings" );
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

					add_settings_field( $field['id'], $field['title'], 'kc_settings_field', "{$prefix}_settings", $section['id'], $args );
				}
			}

		}
	}


	# Create settings page content/wrapper
	function settings_page() {
		extract( $this->group, EXTR_OVERWRITE ); ?>

	<div class="wrap">
		<?php screen_icon( $this->screen ); ?>
		<h2><?php echo $page_title ?></h2>
		<?php do_action( "{$this->group['prefix']}_kc_settings_page_before", $this->group ) ?>
		<form action="options.php" method="post">
			<?php
				# The hidden fields
				settings_fields( "{$prefix}_settings" );

				# Print the setting sections of this group/page
				kc_do_settings_sections( $prefix, $this->group );
			?>
			<p class="submit"><input class="button-primary" name="submit" type="submit" value="<?php esc_attr_e( 'Save Changes', 'kc-settings' ); ?>" /></p>
		</form>
		<?php do_action( "{$this->group['prefix']}_kc_settings_page_after", $this->group ) ?>
	</div>
	<?php }


	# Settings section description
	function section_desc( $section ) {
		$options = $this->group['options'];

		if ( isset($options[$section['id']]['desc']) && !empty($options[$section['id']]['desc']) )
			echo "{$options[$section['id']]['desc']}\n";
	}


	# Setting field validation callback
	function validate( $user_val ) {
		$options = $this->group['options'];

		# apply validation/sanitation filter(s) on the new values
		$nu_val = array();
		foreach ( $user_val as $sk => $sv ) {
			# section filter
			$nu_val[$sk] = apply_filters( "kc_psv_{$sk}", $sv );

			foreach ( $sv as $fk => $fv ) {
				$type = $options[$sk]['fields'][$fk]['type'];

				# rebuild and cleanup array for multiinput type options
				if ( $type == 'multiinput' ) {
					$fv = kc_array_remove_empty( $fv );
					$fv = kc_array_rebuild_index( $fv );
				}
				elseif ( in_array($type, array('input', 'textarea')) ) {
					$fv = trim( $fv );
				}

				# type-based filter
				$fv = apply_filters( "kc_psv_type_{$type}", $fv, $sk, $type );

				# field-based filter
				$fv = apply_filters( "kc_psv_{$sk}_{$fk}", $fv, $sk, $type );

				# insert the filtered value to our new array
				$nu_val[$sk][$fk] = $fv;
			}
		}

		return apply_filters( "kc_psv", $nu_val );
	}


}

?>