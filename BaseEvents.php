<?php
/*
Plugin Name: Base Events
Plugin URI: https://github.com/minhaz-dev
Description: Events management with recurring-event scheduling. Registers the `events` post type.
Version: 2.0.14
Author: Minhaz
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/** Include the Composer autoload file */
$loader = require __DIR__ . '/vendor/autoload.php';

/**
 * Current plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define('BASE_EVENTS_VERSION', '2.0.14');

use BaseEvents\Admin\AdminSettings;
use BaseEvents\EventsManager;


/**
 * Class BaseEvents
 */
class BaseEvents
{
	var $table_name;

	var $charset_collate;

	var $tableExists;


	function __construct()
	{
		/* Do nothing here */
	}


	function initialize()
	{
		register_activation_hook(__FILE__, array($this, 'activate'));
		register_deactivation_hook(__FILE__, array($this, 'deactivate'));
		add_action('plugins_loaded', array($this, 'update'));

		global $wpdb;

		$this->table_name = $wpdb->prefix . 'repeating_events';
		$this->charset_collate = $wpdb->get_charset_collate();
		$this->tableExists = $wpdb->query ('SELECT 1 from '.$this->table_name);


		global $EventsManager;
		$EventsManager = new EventsManager();

		// Load admin data if in admin
		if (is_admin()) {
			$my_settings_page = new AdminSettings();
		}
	}


	public function activate()
	{
		// If table isn't created, create it
		if (!$this->tableExists) {
			$sql = "CREATE TABLE $this->table_name (
                            id BIGINT NOT NULL AUTO_INCREMENT,
                            event_date DATETIME NOT NULL,
                            events TEXT NOT NULL,
                            PRIMARY KEY  (id)
                        ) $this->charset_collate;";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);

			add_option('base_events_db_version', BASE_EVENTS_VERSION);
		}

		flush_rewrite_rules();
	}


	public function deactivate()
	{
		// Unschedule cron task
		$timestamp = wp_next_scheduled( 'base_events_cron_hook' );
		wp_unschedule_event( $timestamp, 'base_events_cron_hook' );
	}


	public function uninstall()
	{
	}


	public function update()
	{
		global $wpdb;
		$current_version = get_option('base_events_db_version', 0);


		// If the current version is less then 2.0.0
		if (version_compare($current_version, '2.0.0') < 0) {
			// Convert existing events to new format.
			$updated_events = [];
			$events = $wpdb->get_results("SELECT * FROM $this->table_name;");

			foreach ($events as $event) {
				if (!array_key_exists($event->event_date, $updated_events)) {
					$updated_events[$event->event_date] = [];
				}
				$updated_events[$event->event_date][] = ['event_id' => $event->post_id, 'end_date' => null];
			}

			// Update database.
			$sql = "CREATE TABLE $this->table_name (
                            id BIGINT NOT NULL AUTO_INCREMENT,
                            event_date DATETIME NOT NULL,
                            events TEXT NOT NULL,
                            PRIMARY KEY  (id)
                        ) $this->charset_collate;";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);

			$wpdb->query("ALTER TABLE $this->table_name DROP COLUMN post_id;");
			$wpdb->query("TRUNCATE $this->table_name;");

			// Insert updated values.
			foreach ($updated_events as $event_date => $events) {
				$wpdb->query($wpdb->prepare("INSERT INTO $this->table_name (event_date, events)  VALUES (%s, %s);", $event_date, json_encode($events)));
			}

			update_option('base_events_db_version', BASE_EVENTS_VERSION);
		}
	}
}

$BaseEvents = new BaseEvents;
$BaseEvents->initialize();
