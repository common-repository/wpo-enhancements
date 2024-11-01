<?php

class wpo_enhancements_backend
{
	const WPO_ENHANCEMENTS_FIELD_TYPE_CHECKBOX = 'checkbox';
	const WPO_ENHANCEMENTS_FIELD_TYPE_TEXTAREA = 'textarea';
	const WPO_ENHANCEMENTS_FIELD_TYPE_TEXT = 'text';

	/**
	 * wpo_enhancements_backend constructor.
	 */
	public function __construct()
	{
		add_action('admin_init', array(&$this, 'settings_init') );
		add_action('admin_menu', array(&$this, 'options_page') );
	}


	/**
	 * custom option and settings
	 */
	function settings_init()
	{
		// Register a new setting for "wpo_enhancements" page.
		register_setting('wpo_enhancements', 'wpo_enhancements_options');

		// Register a new section in the "wpo_enhancements" page.
		add_settings_section(
			'wpo_enhancements_section_general',
			__('General', 'wpo_enhancements'),
			array( &$this, 'section_general_callback' ),
			'wpo_enhancements'
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_CHECKBOX,
			'enabled',
			'Enabled',
			'Enable or disable current optimizations'
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_CHECKBOX,
			'remove_all_scripts',
			'Remove all scripts',
			'Remove all scripts added with wp_register_script'
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_CHECKBOX,
			'clean_noscripts',
			'Clean noscript',
			'Remove all noscript tags from body'
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_CHECKBOX,
			'add_load_external',
			'Add Load External',
			''
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_CHECKBOX,
			'load_js_with_timeout',
			'Load JS with timeout',
			'Needs "Add Load External"'
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_CHECKBOX,
			'sort_external_scripts_after_internal_ones',
			'Sort external scripts',
			'Needs "Add Load External"'
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_CHECKBOX,
			'move_rel_stylesheets',
			'Move StyleSheets',
			'Needs "Add Load External"'
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_CHECKBOX,
			'load_rel_stylesheets_with_timeout',
			'Move StyleSheets Timeout',
			'Load moved stylesheet with a timeout aside from user interaction'
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_TEXT,
			'load_rel_stylesheets_with_timeout_config',
			'Move StyleSheets Timeout Configuration',
			'Timeout wait time in milliseconds'
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_CHECKBOX,
			'delay_font_face_loading',
			'Delay FontFace loading',
			''
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_CHECKBOX,
			'replace_remove_cpcss_script',
			'Replace remove_cpcss_script',
			'Put Remove CPCSS script on user interaction events'
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_CHECKBOX,
			'replace_critical_css',
			'Replace Critical CSS',
			'Replaces "rocket-critical-css" style content with contents loaded from "css/frontpage.css"'
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_CHECKBOX,
			'preload',
			'Preload',
			''
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_TEXTAREA,
			'preload_config',
			'Preload Configuration',
			''
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_CHECKBOX,
			'html_replacements',
			'HTML text replacements',
			''
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_TEXTAREA,
			'html_replacements_config',
			'HTML text replacements Configuration',
			''
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_CHECKBOX,
			'custom_inline_css',
			'Custom Inline CSS',
			'Adds inline CSS to the HTML.'
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_TEXTAREA,
			'custom_inline_css_content',
			'Custom Inline CSS Content',
			''
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_CHECKBOX,
			'footer_scripts',
			'Footer Scripts',
			''
		);

		$this->add_settings_field(
			self::WPO_ENHANCEMENTS_FIELD_TYPE_TEXTAREA,
			'footer_scripts_config',
			'Footer Scripts Config',
			'Name of scripts to be inlined. Have to be stored inside plugin\'s js folder'
		);

		if (is_plugin_active('recaptcha-in-wp-comments-form/recaptcha-in-wp-comments.php')) {
			$this->add_settings_field(
				self::WPO_ENHANCEMENTS_FIELD_TYPE_CHECKBOX,
				'load_recaptcha_with_loadext',
				'Recaptcha with loadExt',
				'Load Recaptcha script tag with loadExt function'
			);
		}

	}

	function add_settings_field(
		$type,
		$field,
		$fieldName,
		$fieldDescription,
		$section = 'wpo_enhancements_section_general'
	) {
		$field = sanitize_key($field);

		add_settings_field(
			'wpo_enhancements_field_' . $field,
			// Use $args' label_for to populate the id inside the callback.
			__($fieldName, 'wpo_enhancements'),
			array(&$this, 'field_' . $type ),
			'wpo_enhancements',
			$section,
			array(
				'label_for' => 'wpo_enhancements_field_' . $field,
				'class' => 'wpo_enhancements_row',
				'custom_description' => $fieldDescription,
			)
		);
	}

	function section_general_callback($args)
	{
		?>
        <p id="<?php
		echo esc_attr($args['id']); ?>"><?php
			esc_html_e('General configuration options enabler', 'wpo_enhancements'); ?></p>
		<?php
	}

	function field_checkbox($args)
	{
		// Get the value of the setting we've registered with register_setting()
		$options = get_option('wpo_enhancements_options');
		?>
        <input type="checkbox" name="wpo_enhancements_options[<?php
		echo esc_attr($args['label_for']); ?>]" <?php
		echo isset($options[$args['label_for']]) ? (checked($options[$args['label_for']], 'on', false)) : (''); ?> />

        <p class="description">
			<?php
			esc_html_e(
				$args['custom_description'],
				'wpo_enhancements'
			); ?>
        </p>
		<?php
	}

	function field_text($args)
	{
		// Get the value of the setting we've registered with register_setting()
		$options = get_option('wpo_enhancements_options');
		?>
        <input type="text" name="wpo_enhancements_options[<?php
		echo esc_attr($args['label_for']); ?>]" value="<?php
		echo esc_attr($options[$args['label_for']]); ?>" />

        <p class="description">
			<?php
			esc_html_e(
				$args['custom_description'],
				'wpo_enhancements'
			); ?>
        </p>
		<?php
	}

	function field_textarea($args)
	{
		// Get the value of the setting we've registered with register_setting()
		$options = get_option('wpo_enhancements_options');
		?>
        <textarea name="wpo_enhancements_options[<?php
		echo esc_attr($args['label_for']); ?>]"
                  class="large-text" cols="50" rows="5"
        ><?php
			echo $options[$args['label_for']]
			?></textarea>

        <p class="description">
			<?php
			esc_html_e(
				$args['custom_description'],
				'wpo_enhancements'
			); ?>
        </p>
		<?php
	}

	/**
	 * Add the top level menu page.
	 */
	function options_page()
	{
		add_options_page(
			'WPO Enhancements',
			'WPO Enhancements',
			'manage_options',
			'wpo_enhancements',
			array(&$this,'options_page_html')
		);
	}

	function options_page_html()
	{
		// check user capabilities
		if (!current_user_can('manage_options')) {
			return;
		}

		// add error/update messages
		if (!is_plugin_active('wp-rocket/wp-rocket.php')) {
			add_settings_error(
				'wpo_enhancements_messages',
				'wpo_enhancements_message',
				__(
					'WP Rocket plugin is not active or is not installed so this plugin will not do anything on your site.',
					'wpo_enhancements'
				),
				'error'
			);
		}

		// check if the user have submitted the settings
		// WordPress will add the "settings-updated" $_GET parameter to the url
		if (isset($_GET['settings-updated'])) {
			// add settings saved message with the class of "updated"
			$this->clean_wp_rocket_cache();
			add_settings_error(
				'wpo_enhancements_messages',
				'wpo_enhancements_message',
				__('WP Rocket cache cleared.', 'wpo_enhancements'),
				'updated'
			);

		}

		// show error/update messages
		settings_errors('wpo_enhancements_messages');
		?>
        <div class="wrap">
            <h1><?php
				echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
				<?php
				// output security fields for the registered setting "wpo_enhancements"
				settings_fields('wpo_enhancements');
				// output setting sections and their fields
				// (sections are registered for "wpo_enhancements", each field is registered to a specific section)
				do_settings_sections('wpo_enhancements');
				// output save settings button
				submit_button('Save Settings');
				?>
            </form>
        </div>
		<?php
	}

	function clean_wp_rocket_cache() {

		if ( ! function_exists( 'rocket_clean_domain' ) ) {
			return false;
		}

		// Purge entire WP Rocket cache.
		rocket_clean_domain();
	}

}
