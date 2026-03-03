# Contributing

## Workflow

1. Create a branch from `main`.
2. Make focused changes (one concern per commit).
3. Run lint/tests listed in `docs/QA.md`.
4. Open PR with clear test notes.

## Coding standards

- Follow WordPress coding standards.
- Escape output, sanitize input, validate capabilities/nonces.
- Add translator comments for placeholder strings.
- Keep comments brief and technical.

## Commit style

Use clear imperative commit messages, for example:

- `Fix RSVP count sync on single event`
- `Add settings sanitize fallback for brand color`

## Pull request checklist

- [ ] No PHP syntax errors
- [ ] Settings save/load tested
- [ ] BuddyPress mode tested
- [ ] BuddyBoss mode tested
- [ ] RSVP flow tested
- [ ] No unrelated file changes
