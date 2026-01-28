=== WB Community Calendar Pro ===
Contributors: wb
Tags: buddypress, community, calendar, events, groups, rsvp
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight BuddyPress-native community calendar with group events and RSVP.

== Description ==
WB Community Calendar Pro adds a clean group events system to BuddyPress. Create events inside groups, show a global community calendar, and let members RSVP with simple statuses.

Key features:

* Group events with BuddyPress group tab
* Month and list calendar views
* RSVP statuses (Attending / Maybe / Can’t)
* Event details (start/end, timezone, location, meeting link)
* Shortcode for global calendar display
* Advanced recurring events (weekly/monthly/yearly with nth weekdays)
* iCal export for single events and group calendars
* Public single event pages
* Email notifications for create/update/cancel + RSVP (toggle in settings)
* Frontend event image upload
* Filters (upcoming/past/all), search, pagination
* Optional moderation for member-submitted events
* REST endpoint for occurrences

== Installation ==
1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin in the WordPress admin.
3. Use the shortcode `[wbccp_calendar]` on any page.

== Usage ==
Shortcode:

`[wbccp_calendar]`

Optional attributes:
- `group_id` (int) — show events from a specific group
- `limit` (int) — number of events for list view
- `view` (list|month) — default view

iCal export:
- Group calendar: `/?wbccp_ical=1&group_id=123`
- Single event: `/?wbccp_ical=1&event_id=456`

REST endpoint:
- `GET /wp-json/wbccp/v1/occurrences?group_id=123&start=TIMESTAMP&end=TIMESTAMP`

== Frequently Asked Questions ==

= Does this require BuddyPress? =
Yes. BuddyPress is required for group events.

= Can group members create events? =
Yes, this is enabled by default and can be changed in plugin settings. You can also enable moderation to approve member events.

== Screenshots ==
1. Group events tab with list view and RSVP
2. Month calendar view
3. Shortcode calendar on a page
4. Single event page with RSVP + iCal
5. Recurrence controls and moderation queue

== Changelog ==

= 0.1.0 =
* Initial release.
