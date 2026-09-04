# Base Events

WordPress plugin that adds an events management system with recurring-event
scheduling.

Registers the `events` post type. The Base Theme layers on the
`ctax_event_category`, `ctax_event_type` and `ctax_event_venue` taxonomies plus
editor support — see `inc/custom-posts.php` in that theme.

## Notes

### Version 2.0.14

Version 2.0.0 modified the table structure and cannot be restored. Ensure a
database backup is taken prior to installing this version.

### Upgrading an install that predates the rename

This plugin stores its state under `base_events_*` keys. An older install using
the previous `verb_events_*` names must have its options renamed **before** the
plugin loads:

```sql
UPDATE wp_options SET option_name = 'base_events_db_version'  WHERE option_name = 'verb_events_db_version';
UPDATE wp_options SET option_name = 'base_events_option_name' WHERE option_name = 'verb_events_option_name';
```

Skipping this makes `base_events_db_version` read as `0`, which re-runs the
2.0.0 migration. On an already-migrated table that migration `TRUNCATE`s
`{prefix}_repeating_events` and rewrites it with null rows — every recurring
schedule is lost. Fresh installs are unaffected.
