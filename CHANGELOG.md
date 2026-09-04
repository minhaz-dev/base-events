# Changelog

## [2.0.14] - 2024-07-02

### Changed
- Fix recurring exclusions when the repeat-frequency is set to specific-days

## [2.0.13] - 2023-11-02

### Changed
- Update events manager private function save, changing this line:<br><br>
$existing_events = $this->wpdb->get_results($this->wpdb->prepare("SELECT events FROM $this->tableName WHERE event_date = %s;", $item->format('Y-m-d H:i:s')));<br><br>
to this:<br><br>
$existing_events = $this->wpdb->get_results($this->wpdb->prepare("SELECT events FROM $this->tableName WHERE event_date = %s AND event_id = %s;", $item->format('Y-m-d H:i:s'), $post_id));<br><br>
Which provides a fix posts not showing up due to having similar start times.

## [2.0.12] - 2023-01-05

### Changed
- Update events manager extractEvents function with a not empty check around $event->next_event_date, fixes a PHP warning ( Undefined property: stdClass::$next_event_date ) in PHP version 8


## [2.0.11] - 2022-12-23

### Changed
- Update events manager functions to account for the Wordpress admin defined timezone


# Changelog

## [2.0.10] - 2022-11-16

### Changed
- Fix issue with getUniqueEventsByDate() where GROUP BY was not grouping events by event ID


# Changelog

## [2.0.9] - 2022-07-29

### Changed
- Fix issue with getNextEventOccurrences() where all day events would be skipped on the day of the event
 

# Changelog

## [2.0.8] - 2022-06-15

### Added
- Added new function getEventsByIdsAndDate() that will work well with filtered lists


## [2.0.7] - 2022-06-14

### Changed
- Fixed issue with limit in query for getNextEventOccurrences()


## [2.0.6] - 2021-06-21

### Changed
- Added single quotes around LIKE values in queries


## [2.0.5] - 2021-02-04

### Changed
- Fixed issues related to db table creation.  Previously the table would not be created unless you already had version 1 of the plugin installed


## [2.0.4] - 2020-10-09

### Added
- Added "ORDER BY" clause to end of database query in function 'getAllEventOccurrences' (EventsManager.php)

### Changed
- Explicitly cast "event_schedules" & "event_schedule" as arrays in function 'saveRepeatingEventData' to avoid warnings (EventsManager.php)


## [2.0.3] - 2020-07-02

### Changed

- Take into account optional event ID when retrieving and extracting events with extractEvents()


## [2.0.2] - 2020-03-31

### Changed

- Fix release version


##  [2.0.1] - 2020-03-31

### Changed

- Fix extract events compatibility


## [2.0.0] - 2020-02-19

### Added

- Event end fields
- Event data extraction helper method

### Changed

- verb_repeating_events table structure
- Event save functionality
- Event delete functionality


## [1.2.2] - 2019-08-01

### Added

- Added support for author to the event cpt


## [1.2.1] - 2019-05-29

### Changed

- Flush rewrite rules when slug is changed on settings page


## [1.2.0] - 2019-05-27

### Added

- Helper function to get next occurences of unique events from a specific set of post ids with an optional limit

### Changed

- getEventsByDate() now sorts by date asc


## [1.1.1] - 2019-05-13

### Added

- Check to save_post hook to prevent db table being wiped on preview

### Fixed

- Prevent post preview from clearing date data from database


## [1.1.0] - 2019-05-06

### Added

- Admin page with an option to change the events custom post type slug


## [1.0.1] - 2019-04-26

### Changed

- Set publicly queryable to true


## [1.0.0] - 2019-03-29

### Added

- Initial commit