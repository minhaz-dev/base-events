<?php

namespace BaseEvents\Admin;

class AdminSettings
{
	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;

	/**
	 * Start up
	 */
	public function __construct()
	{
		$this->options = get_option('base_events_option_name');

		add_action('admin_menu', array($this, 'add_plugin_page'));
		add_action('admin_init', array($this, 'page_init'));
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page()
	{
		// This page will be under "Settings"
		add_options_page(
			'Settings Admin',
			'BCFS Events Settings',
			'manage_options',
			'base-events-admin',
			array($this, 'create_admin_page')
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function events_slug_callback()
	{
		printf(
			'<input type="text" id="events_slug" name="base_events_option_name[events_slug]" value="%s" style="width: 100%%; max-width: 400px;"/>',
			isset($this->options['events_slug']) ? esc_attr($this->options['events_slug']) : ''
		);

		flush_rewrite_rules();
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page()
	{
		?>
		<div class="wrap">
			<h1>BCFS Events Settings</h1>
			<form method="post" action="options.php">
				<?php
				// This prints out all hidden setting fields
				settings_fields('base_events_option_group');
				do_settings_sections('base-events-admin');
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init()
	{
		register_setting(
			'base_events_option_group', // Option group
			'base_events_option_name', // Option name
			array($this, 'sanitize') // Sanitize
		);

		add_settings_section(
			'setting_section_id', // ID
			'BCFS Events Slug', // Title
			'', // Callback
			'base-events-admin' // Page
		);

		add_settings_field(
			'events_slug', // ID
			'BCFS Events Slug', // Title
			array($this, 'events_slug_callback'), // Callback
			'base-events-admin', // Page
			'setting_section_id' // Section
		);
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 *
	 * @return array
	 */
	public function sanitize($input)
	{
		$new_input = array();
		if (isset($input['events_slug']))
			$new_input['events_slug'] = $input['events_slug'];

		return $new_input;
	}
}