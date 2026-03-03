# QA Guide

## Baseline

- Plugin active: `wb-community-calendar-pro`
- Social platform active: either BuddyPress or BuddyBoss

## Quick checks

```bash
wp plugin list --status=active --field=name
wp post-type list --field=name | rg '^wb_community_event$'
find wp-content/plugins/wb-community-calendar-pro -name '*.php' -type f -print0 | xargs -0 -n1 php -l
```

## Settings matrix

Run the matrix script (if available):

```bash
wp eval-file /tmp/wbccp-settings-matrix.php
```

Expected:

- `failed = 0`
- `restored_exact = true`

## RSVP checks

```bash
wp eval '$event_id=2618; WBCCP_RSVP::set_status($event_id,24,"attending"); print_r(WBCCP_RSVP::get_counts($event_id));'
```

## Platform compatibility

### BuddyPress mode

```bash
wp plugin deactivate buddyboss-platform
wp plugin activate buddypress
wp eval-file /tmp/wbccp-settings-matrix.php
```

### BuddyBoss mode

```bash
wp plugin activate buddyboss-platform
# deactivate buddypress if your stack cannot run both together
wp eval-file /tmp/wbccp-settings-matrix.php
```

## Frontend smoke

- Create/edit event in admin
- Open single event page
- Submit RSVP and verify counts update
- Verify Add to Calendar opens Google Calendar
