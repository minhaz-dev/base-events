<?php

namespace BaseEvents;

use RRule\RSet;

/**
 * Class EventsManager
 *
 * @package BaseEvents
 */
class EventsManager
{
	/**
	 * The recurrence rules set
	 *
	 * @var RSet
	 */
	private $rset;

	/**
	 * The Repeating Events Table
	 *
	 * @var string
	 */
	private $tableName;

	/**
	 * The global $wpdb
	 *
	 * @var \QM_DB|\wpdb
	 */
	private $wpdb;

	/**
	 * The event duration
	 *
	 * @var int
	 */
	private $duration;

	/**
	 * The wordpress timezone
	 *
	 * @var string
	 */
	private $timezone;

	/**
	 * the current time in the adjusted timezone
	 *
	 * @var \DateTime
	 */
	private $nowDT;


	/**
	 * BaseEventsManager constructor.
	 */
	public function __construct()
	{
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->tableName = $this->wpdb->prefix . 'repeating_events';
		$this->duration = 0;
		$this->timezone = wp_timezone_string();
		$this->nowDT = new \DateTime("now", new \DateTimeZone($this->timezone));
		$this->nowDT = $this->nowDT->setTimestamp(time());

		$this->rset = new RSet();

		// Register Events custom post type
		add_action('init', array($this, 'registerEventsCustomPostType'));;

		// Add save post hook
		add_action('save_post', array($this, 'saveRepeatingEventData'), 20, 3);

		// Add move to trash post hook
		add_action('wp_trash_post', array($this, 'delete'), 20);

		// Add custom cron interval
		add_filter('cron_schedules', array($this, 'add_weekly_cron_interval'));

		// Schedule wp cron event
		add_action('base_events_cron_hook', array($this, 'runInfiniteEventsCron'));
		if (!wp_next_scheduled('base_events_cron_hook')) {
			wp_schedule_event(time(), 'weekly', 'base_events_cron_hook');
		}

		// Register acf fields
		add_action('acf/init', array($this, 'registerAcfFields'));
	}


	/**
	 * Register events post type
	 */
	public function registerEventsCustomPostType()
	{
		// Get settings data
		$this->options = get_option('base_events_option_name');
		$slug = !empty($this->options) && !empty($this->options['events_slug']) ? $this->options['events_slug'] : 'events';

		$labels = array(
			'name'               => __('Events'),
			'menu_name'          => __('Events'),
			'singular_name'      => __('Event'),
			'add_new_item'       => __('Add New Event'),
			'edit_item'          => __('Edit Event'),
			'new_item'           => __('New Event'),
			'view_item'          => __('View Event'),
			'search_items'       => __('Search Events'),
			'not_found'          => __('No Events found'),
			'not_found_in_trash' => __('No Events found in Trash'),
		);
		$args = array(
			'labels'              => $labels,
			'supports'            => array('title', 'thumbnail', 'revisions'),
			'rewrite'             => array('slug' => "{$slug}", 'with_front' => false),
			'capability_type'     => 'post',
			'menu_position'       => 20, // after Pages
			'menu_icon'           => 'dashicons-id', // http://calebserna.com/dashicons-cheatsheet/
			'hierarchical'        => false,
			'public'              => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'query_var'           => true,
			'can_export'          => true,
		);
		register_post_type('events', $args);
	}


	/**
	 * Register ACF fields
	 */
	public function registerAcfFields()
	{
		acf_add_local_field_group(array(
			'key'                   => 'group_base_events',
			'title'                 => 'Plugin: Base Events',
			'fields'                => array(
				array(
					'key'               => 'field_5c9e2b06d6671',
					'label'             => 'Event Schedule Rules',
					'name'              => 'event_schedule',
					'type'              => 'repeater',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'collapsed'         => '',
					'min'               => 0,
					'max'               => 0,
					'layout'            => 'block',
					'button_label'      => 'Add Date',
					'sub_fields'        => array(
						array(
							'key'               => 'field_5c9e2b06e5020',
							'label'             => 'Add or Exclude Date?',
							'name'              => 'add_or_exclude_date',
							'type'              => 'true_false',
							'instructions'      => 'For exclude, the Event Start must match the above rules.',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'message'           => '',
							'default_value'     => 1,
							'ui'                => 1,
							'ui_on_text'        => 'Add',
							'ui_off_text'       => 'Exclude',
						),
						array(
							'key'               => 'field_5c9e2b06e50b1',
							'label'             => 'Event Start',
							'name'              => 'start_date',
							'type'              => 'date_time_picker',
							'instructions'      => 'Use time 12:00am (default) for all day events.',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '50',
								'class' => '',
								'id'    => '',
							),
							'display_format'    => 'F j, Y g:i a',
							'return_format'     => 'Y-m-d H:i:s',
							'first_day'         => 1,
						),
						array(
							'key'               => 'field_c0899db8046a8',
							'label'             => 'Event End',
							'name'              => 'end_date',
							'type'              => 'date_time_picker',
							'instructions'      => 'If this is an all day event, leave blank.',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '50',
								'class' => '',
								'id'    => '',
							),
							'display_format'    => 'F j, Y g:i a',
							'return_format'     => 'Y-m-d H:i:s',
							'first_day'         => 1,
						),
						array(
							'key'               => 'field_5c9e2b06e5136',
							'label'             => 'Repeating Date?',
							'name'              => 'repeating_date',
							'type'              => 'true_false',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'message'           => '',
							'default_value'     => 0,
							'ui'                => 1,
							'ui_on_text'        => '',
							'ui_off_text'       => '',
						),
						array(
							'key'               => 'field_5c9e2b06e5239',
							'label'             => 'Repeat Interval',
							'name'              => 'repeat_interval',
							'type'              => 'number',
							'instructions'      => 'ex; 2 would be \'Repeat every 2 ...\'',
							'required'          => 0,
							'conditional_logic' => array(
								array(
									array(
										'field'    => 'field_5c9e2b06e5136',
										'operator' => '==',
										'value'    => '1',
									),
									array(
										'field'    => 'field_5c9e2b06e52bb',
										'operator' => '!=',
										'value'    => 'specific-days',
									),
								),
							),
							'wrapper'           => array(
								'width' => '50',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => 1,
							'placeholder'       => '',
							'prepend'           => '',
							'append'            => '',
							'min'               => '',
							'max'               => '',
							'step'              => '',
						),
						array(
							'key'               => 'field_5c9e2b06e533f',
							'label'             => 'Specific Days',
							'name'              => 'specific_days',
							'type'              => 'checkbox',
							'instructions'      => ' ',
							'required'          => 0,
							'conditional_logic' => array(
								array(
									array(
										'field'    => 'field_5c9e2b06e52bb',
										'operator' => '==',
										'value'    => 'specific-days',
									),
									array(
										'field'    => 'field_5c9e2b06e5136',
										'operator' => '==',
										'value'    => '1',
									),
								),
							),
							'wrapper'           => array(
								'width' => '50',
								'class' => '',
								'id'    => '',
							),
							'choices'           => array(
								'SU' => 'Sunday',
								'MO' => 'Monday',
								'TU' => 'Tuesday',
								'WE' => 'Wednesday',
								'TH' => 'Thursday',
								'FR' => 'Friday',
								'SA' => 'Saturday',
							),
							'allow_custom'      => 0,
							'default_value'     => array(),
							'layout'            => 'horizontal',
							'toggle'            => 0,
							'return_format'     => 'value',
							'save_custom'       => 0,
						),
						array(
							'key'               => 'field_5c9e2b06e52bb',
							'label'             => 'Repeat Frequency',
							'name'              => 'repeat_frequency',
							'type'              => 'select',
							'instructions'      => ' ',
							'required'          => 0,
							'conditional_logic' => array(
								array(
									array(
										'field'    => 'field_5c9e2b06e5136',
										'operator' => '==',
										'value'    => '1',
									),
								),
							),
							'wrapper'           => array(
								'width' => '50',
								'class' => '',
								'id'    => '',
							),
							'choices'           => array(
								'DAILY'         => 'Day',
								'WEEKLY'        => 'Week',
								'MONTHLY'       => 'Month',
								'specific-days' => 'Specific Days',
							),
							'default_value'     => array(),
							'allow_null'        => 0,
							'multiple'          => 0,
							'ui'                => 0,
							'return_format'     => 'value',
							'ajax'              => 0,
							'placeholder'       => '',
						),
						array(
							'key'               => 'field_5c9e2b06e51b8',
							'label'             => 'Final Event Date',
							'name'              => 'repeat_end_date',
							'type'              => 'date_time_picker',
							'instructions'      => 'The final event in the series will occur on this date.‎',
							'required'          => 0,
							'conditional_logic' => array(
								array(
									array(
										'field'    => 'field_5c9e2b06e5136',
										'operator' => '==',
										'value'    => '1',
									),
								),
							),
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'display_format'    => 'F j, Y g:i a',
							'return_format'     => 'Y-m-d H:i:s',
							'first_day'         => 1,
						),
					),
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'events',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => true,
			'description'           => '',
		));
		// Add ACF validation
		add_filter('acf/validate_value/key=field_5c9e2b06d6671', array($this, 'validate_duration'), 10, 4);
	}


	/**
	 * Add weekly cron interval
	 *
	 * @param $schedules
	 *
	 * @return mixed
	 */
	public function add_weekly_cron_interval($schedules)
	{
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display'  => esc_html__('Weekly'),
		);

		return $schedules;
	}


	/**
	 * Get all event occurrences
	 *
	 * @param int $id
	 *
	 * @return array|object|null
	 */
	public function getAllEventOccurrences($id)
	{
		return $this->extractEvents($this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM $this->tableName WHERE events LIKE '%s' ORDER BY event_date;", '%event_id":"' . $id . '"%')), [$id]);
	}


	/**
	 * Get events by date
	 *
	 * @param string   $startDate
	 * @param string   $endDate
	 * @param int|null $limit
	 *
	 * @return array|object|null
	 */
	public function getEventsByDate($startDate, $endDate = '', $limit = null)
	{
		$query = "SELECT *
		          FROM {$this->tableName}";

		if (!empty($endDate)) {
			$query .= " WHERE event_date >= '{$startDate}'
			             AND event_date <= '{$endDate}'";
		} else {
			$query .= " WHERE event_date LIKE '{$startDate}%'";
		}

		$query .= " ORDER BY event_date ASC";

		if (!empty($limit)) {
			$query .= " LIMIT {$limit}";
		}

		return $this->extractEvents($this->wpdb->get_results($query));
	}


	/**
	 * Takes an array of event post id's and optional dates and returns events up to limit,
	 * sorted by date
	 *
	 * @param array    $ids
	 * @param string   $startDate
	 * @param string   $endDate
	 * @param int|null $limit
	 *
	 * @return array|null
	 */
	public function getEventsByIdsAndDate($ids, $startDate = '', $endDate = '', $limit = null)
	{
		$id_string = implode("|", $ids);
		$startDate = (!empty($startDate)) ? $startDate : $this->nowDT->format('Y-m-d H:i:s');

		$query = "SELECT *
		          FROM {$this->tableName}";

		$query .= " WHERE events REGEXP '({$id_string})'";

		if (!empty($endDate)) {
			$query .= " AND event_date >= '{$startDate}'
			             AND event_date <= '{$endDate}'";
		} else {
			$query .= " AND event_date LIKE '{$startDate}%'";
		}

		$query .= " ORDER BY event_date ASC";

		if (!empty($limit)) {
			$query .= " LIMIT {$limit}";
		}


		return $this->extractEvents($this->wpdb->get_results($query), $ids);
	}


	/**
	 * Get next event occurrence
	 *
	 * @param int      $id
	 * @param string   $afterDate
	 * @param int|null $limit
	 *
	 * @return array|object|null
	 */
	public function getNextEventOccurrences($id, $afterDate = '', $limit = null)
	{
		// if empty, set startDate to now
		if ($afterDate == '') {
			$afterDate = $this->nowDT->format('Y-m-d H:i:s');
		}

		// Alt date for all day events
		$altAfterDate = date('Y-m-d 00:00:00', strtotime($afterDate));

		$query = "SELECT events, MIN(event_date) AS next_event_date
			       FROM {$this->wpdb->prefix}repeating_events
			       WHERE ((event_date >= %s) OR (event_date = %s))";
		$query_params = [$afterDate, $altAfterDate];

		if (!empty($id)) {
			$query .= " AND JSON_SEARCH(events, 'one', %s, null, '$[*].event_id') IS NOT NULL";
			$query_params[] = $id;
		}

		$query .= " GROUP BY events ORDER BY next_event_date";

		if (!empty($limit)) {
			$query .= " LIMIT {$limit}";
		}

		$query_params[] = $limit;

		return $this->extractEvents($this->wpdb->get_results($this->wpdb->prepare($query, $query_params)), [$id]);
	}


	/**
	 * Get previous event occurrences
	 *
	 * @param int      $id
	 * @param string   $beforeDate
	 * @param int|null $limit
	 *
	 * @return array|object|null
	 */
	public function getPreviousEventOccurrences($id, $beforeDate = '', $limit = null)
	{
		$query = "SELECT *
		          FROM {$this->tableName}
		          WHERE post_id = '{$id}'";

		if (!empty($beforeDate)) {
			$query .= " AND event_date < '{$beforeDate}'";
		} else {
			$now = $this->nowDT->format('Y-m-d H:i:s');
			$query .= " AND event_date < '{$now}'";
		}

		$query .= " ORDER BY event_date desc";

		if (!empty($limit)) {
			$query .= " LIMIT {$limit}";
		}

		return $this->extractEvents($this->wpdb->get_results($query), [$id]);
	}


	/**
	 * Get unique events by date
	 *
	 * @param string   $startDate
	 * @param string   $endDate
	 * @param int|null $limit
	 *
	 * @return array|object|null
	 */
	public function getUniqueEventsByDate($startDate = '', $endDate = '', $limit = 4)
	{
		// if empty, set startDate to now
		if ($startDate == '') {
			$startDate = $this->nowDT->format('Y-m-d H:i:s');
		}

		$query = "SELECT events, MIN(event_date) AS next_event_date
			       FROM {$this->tableName} 
			       WHERE event_date >= '{$startDate}'";
		if (!empty($endDate)) {
			$query .= " AND event_date <= '{$endDate}'";
		}

		$query .= " GROUP BY SUBSTRING(events, 1, LOCATE(',', events)) ORDER BY next_event_date LIMIT {$limit}";

		return $this->extractEvents($this->wpdb->get_results($query));

	}


	/**
	 * Takes an array of event post id's and returns the next occurrence of each event up to limit,
	 * sorted by date
	 *
	 * @param array    $ids
	 * @param int|null $limit
	 *
	 * @return array|object|null
	 */
	public function getUniqueEventsByIds($ids, $limit = null)
	{
		$id_string = implode("|", $ids);
		$now = $this->nowDT->format('Y-m-d H:i:s');

		$query = "SELECT events, MIN(event_date) AS next_event_date
			       FROM {$this->tableName}
			       WHERE events REGEXP '({$id_string})' AND event_date >= '{$now}'
			       GROUP BY events
			       ORDER BY next_event_date LIMIT {$limit}";

		return $this->extractEvents($this->wpdb->get_results($query), $ids);
	}


	/**
	 * Add a date
	 *
	 * @param $date
	 */
	private function addDate($date)
	{
		$this->rset->addDate($date);
	}


	/**
	 * Add an exclusion date
	 *
	 * @param $date
	 */
	private function addExDate($date)
	{
		$this->rset->addExDate($date);
	}


	/**
	 * Add a rule
	 *
	 * @param $ruleSet
	 */
	private function addRRule($ruleSet)
	{
		$rulesArray = array();
		$rulesArray['INTERVAL'] = $ruleSet['repeat_interval'];
		$rulesArray['DTSTART'] = $ruleSet['start_date'];

		if ($ruleSet['repeat_frequency'] == 'specific-days') {
			$rulesArray['FREQ'] = 'WEEKLY';
			$rulesArray['BYDAY'] = $this->getSpecificDaysString($ruleSet['specific_days']);
		} else {
			$rulesArray['FREQ'] = $ruleSet['repeat_frequency'];
		}

		// If no end date, set as 1yr from now
		if (!empty($ruleSet['repeat_end_date'])) {
			$rulesArray['UNTIL'] = $ruleSet['repeat_end_date'];
		} else {
			$rulesArray['UNTIL'] = date('Y-m-d H:i:s', strtotime('+1 year'));
		}

		$this->rset->addRRule($rulesArray);
	}


	/**
	 * Add and exclusion rule
	 *
	 * @param $ruleSet
	 */
	private function addExRule($ruleSet)
	{
		$rulesArray = array();

		// Set values depending on if event is set to repeat on specific days 
		if ($ruleSet['repeat_frequency'] == 'specific-days') {
			$rulesArray['FREQ'] = 'WEEKLY';
			$rulesArray['BYDAY'] = $this->getSpecificDaysString($ruleSet['specific_days']);
		} else {
			$rulesArray['FREQ'] = $ruleSet['repeat_frequency'];
		}
		
		$rulesArray['INTERVAL'] = $ruleSet['repeat_interval'];
		$rulesArray['DTSTART'] = $ruleSet['start_date'];

		// If no end date, set as 1yr from now
		if (!empty($ruleSet['repeat_end_date'])) {
			$rulesArray['UNTIL'] = $ruleSet['repeat_end_date'];
		} else {
			$rulesArray['UNTIL'] = date('Y-m-d H:i:s', strtotime('+1 year'));
		}

		$this->rset->addExRule($rulesArray);
	}


	/**
	 * Get specific days string
	 *
	 * @param $daysArray
	 *
	 * @return string
	 */
	private function getSpecificDaysString($daysArray)
	{
		$returnString = $daysArray[0];

		for ($i = 1; $i < count($daysArray); $i++) {
			$returnString .= ', ' . $daysArray[$i];
		}

		return $returnString;
	}


	/**
	 * Run infinite events cron
	 */
	public function runInfiniteEventsCron()
	{
		// get all events
		$events = get_posts([
			'post_type'   => 'events',
			'post_status' => 'publish',
			'numberposts' => -1
		]);

		foreach ($events as $event) {
			$event_schedule = get_field('event_schedule', $event->ID);
			$infiniteFlag = false;

			if (!empty($event_schedule)) {
				foreach ($event_schedule as $item) {
					// if a repeating event with no end date
					if ($item['repeating_date'] == true && empty($item['repeat_end_date'])) {
						$infiniteFlag = true;
					}
				}

				if ($infiniteFlag == true) {
					// refresh rset variable
					$this->rset = new RSet();

					// remove previous records from the table for this post (in case of update)
					$this->delete($event->ID);

					// Build rrule set
					foreach ($event_schedule as $item) {
						if ($item['add_or_exclude_date'] == true) {  // add dates
							if ($item['repeating_date'] == true) {
								$this->addRRule($item);
							} else {
								$this->addDate($item['start_date']);
							}
						} else {                                     // exclude dates
							if ($item['repeating_date'] == true) {
								$this->addExRule($item);
							} else {
								$this->addExDate($item['start_date']);
							}
						}
					}

					// add data to table
					$this->save($event->ID);
				}
			}
		}
	}


	/**
	 * Save repeating event data
	 *
	 * @param $post_id
	 * @param $post
	 * @param $update
	 */
	public function saveRepeatingEventData($post_id, $post, $update)
	{
		// Cancel if not an events post or if previewing changes.
		// Note: pass $post_id explicitly — get_post_type() with no argument
		// reads the global $post, which is not set during programmatic saves
		// (WP-CLI, REST, importers), so schedules were never expanded there.
		if (get_post_type($post_id) != 'events' || $post->post_type == 'revision') {
			return;
		}

		$event_schedules = (array)get_field('event_schedule', $post_id);

		// remove previous records from the table for this post (in case of update)
		// Pass $post_id — delete()/save() fall back to get_the_ID(), which is
		// empty outside the loop and silently wrote rows with a blank event_id.
		$this->delete($post_id);

		// Return if no event schedules or if post is not set to published
		if (empty($event_schedules) || get_post_status($post_id) !== 'publish') {
			return;
		}

		// Build rrule set.
		// Reset first: $this->rset lives on the instance, so without this a
		// second event saved in the same request inherits the first one's
		// dates. Never visible in wp-admin (one save per request), but it
		// corrupts bulk or programmatic saves.
		$this->rset = new RSet();
		$this->duration = 0;

		foreach ($event_schedules as $schedule) {
			$event_schedule = (array)$schedule;
			if ($event_schedule['add_or_exclude_date'] == true) {  // add dates
				if ($event_schedule['repeating_date'] == true) {
					$this->addRRule($event_schedule);
				} else {
					$this->addDate($event_schedule['start_date']);
				}
			} else {                                               // exclude dates
				if ($event_schedule['repeating_date'] == true) {
					$this->addExRule($event_schedule);
				} else {
					$this->addExDate($event_schedule['start_date']);
				}
			}

			$this->duration = strtotime($event_schedule['end_date']) - strtotime($event_schedule['start_date']);
		}

		// add data to table
		$this->save($post_id);
	}


	public function validate_duration($valid, $value, $field, $input)
	{
		if (!$valid) {
			return $valid;
		}

		// load data
		$repeater_row = $_POST['acf']['field_5c9e2b06d6671'];

		$durations = [];
		foreach ($repeater_row as $row) {
			$durations[] = strtotime($row['field_c0899db8046a8']) - strtotime($row['field_5c9e2b06e50b1']);
		}

		if (count($durations)) {
			if (count(array_unique($durations)) !== 1) {
				$valid = __('All repeating event rules must have the same duration. Please check the event end date.');
			}
		}

		return $valid;
	}


	/**
	 * Save event data
	 *
	 * @param string $post_id
	 */
	private function save($post_id = '')
	{
		if ($post_id == '') {
			$post_id = get_the_ID();
		}

		foreach ($this->rset as $item) {
			$end_date = new \DateTime();
			$end_date->setTimestamp($item->getTimestamp() + $this->duration);
			$event = ['event_id' => (string)$post_id, 'end_date' => $end_date->format('Y-m-d H:i:s')];

			// event_id is not a column — 2.0.0 moved it inside the `events`
			// JSON. Match on the date alone, then dedupe within the payload.
			$existing_events = $this->wpdb->get_results($this->wpdb->prepare("SELECT events FROM $this->tableName WHERE event_date = %s;", $item->format('Y-m-d H:i:s')));

			if (count($existing_events)) {
				foreach ($existing_events as $existing_event) {
					$events = json_decode($existing_event->events, true);
					if (!is_array($events)) {
						$events = [];
					}

					// Don't add the same event to a date twice.
					$already = false;
					foreach ($events as $existing) {
						if (isset($existing['event_id']) && (string) $existing['event_id'] === (string) $post_id) {
							$already = true;
							break;
						}
					}
					if ($already) {
						continue;
					}

					$events[] = $event;
					$this->wpdb->query($this->wpdb->prepare("UPDATE $this->tableName SET events = %s WHERE event_date = %s;", json_encode($events), $item->format('Y-m-d H:i:s')));
				}
			} else {
				$this->wpdb->query($this->wpdb->prepare("INSERT INTO $this->tableName (event_date, events) VALUES (%s, %s);", $item->format('Y-m-d H:i:s'), json_encode([$event])));
			}
		}
	}


	/**
	 * Delete an event
	 *
	 * @param string $post_id
	 */
	public function delete($post_id = '')
	{
		if ($post_id == '') {
			$post_id = get_the_ID();
		}

		$existing_events = $this->wpdb->get_results($this->wpdb->remove_placeholder_escape($this->wpdb->prepare("SELECT id, event_date, events FROM $this->tableName WHERE events LIKE '%s';", '%event_id":"' . $post_id . '"%')));
		if (count($existing_events)) {
			foreach ($existing_events as $existing_event) {
				$events = json_decode($existing_event->events, true);
				$updated_events = [];
				foreach ($events as $event) {
					if ($event['event_id'] != $post_id) {
						$updated_events[] = $event;
					}
				}

				if (count($updated_events)) {
					$this->wpdb->query($this->wpdb->prepare("UPDATE $this->tableName SET events = %s WHERE event_date = %s;", json_encode($updated_events), $existing_event->event_date));
				} else {
					$this->wpdb->delete($this->tableName, array('id' => $existing_event->id));
				}

			}
		}
	}


	/**
	 * Extract events
	 *
	 * @param            $events
	 * @param array|null $ids
	 *
	 * @return array
	 */
	private function extractEvents($events, $ids = null)
	{
		$unique_events = [];
		foreach ($events as $event) {
			$event_date = !empty($event->next_event_date) ? $event->next_event_date : $event->event_date;

			$event_data = json_decode($event->events);
			if (!empty($ids)) {
				$event_data = array_filter($event_data, function ($value) use ($ids) {
					return in_array($value->event_id, $ids);
				});
			}
			foreach ($event_data as $event_item) {
				$unique_events[] = (object)['post_id' => $event_item->event_id, 'event_end_date' => $event_item->end_date, 'event_date' => $event_date];
			}
		}

		return $unique_events;
	}
}
