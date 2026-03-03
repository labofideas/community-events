# WB Community Calendar Pro

Community events plugin for BuddyPress and BuddyBoss Platform with RSVP, recurring events, iCal export, and frontend calendar views.

## Highlights

- Group and sitewide events
- RSVP statuses: Attending, Maybe, Cannot attend
- Recurrence rules (daily, weekly, monthly, yearly)
- Single event pages
- iCal export for groups and events
- Frontend calendar shortcode
- Notification toggles in settings
- Event slug, labels, and brand color options

## Compatibility

| Component | Supported |
| --- | --- |
| WordPress | 6.0+ |
| PHP | 8.0+ |
| BuddyPress | Yes |
| BuddyBoss Platform | Yes |

Note: BuddyPress and BuddyBoss should not be active together in production unless your stack explicitly supports it.

## Installation

1. Upload plugin to `wp-content/plugins/wb-community-calendar-pro`.
2. Activate the plugin.
3. Ensure BuddyPress or BuddyBoss Platform is active.
4. Visit `Settings -> Community Calendar` to configure options.

## Quick Start

- Create an event: `WP Admin -> Community Events -> Add New`
- Add calendar to a page:

```text
[wbccp_calendar]
```

### Shortcode options

- `group_id` (int)
- `limit` (int)
- `view` (`list` or `month`)
- `scope` (`all`, `group`, `sitewide`)
- `allow_submit` (`yes` or `no`)

## REST API

- `GET /wp-json/wbccp/v1/occurrences`

Example query params:

- `group_id`
- `start` (unix timestamp)
- `end` (unix timestamp)

## iCal Export

- Group calendar: `/?wbccp_ical=1&group_id=123`
- Single event: `/?wbccp_ical=1&event_id=456`

## Development

- Main plugin file: `wb-community-calendar-pro.php`
- Core classes: `includes/`
- Event template: `templates/single-wb-community-event.php`
- Frontend assets: `assets/`

## Testing

See [docs/QA.md](docs/QA.md) for repeatable QA commands and test matrix.

## Security

Please read [SECURITY.md](SECURITY.md) before reporting vulnerabilities.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
