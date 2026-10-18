## What does this change?

## Related issue / user story
Closes #

## Checklist (Definition of Done)
- [ ] `php -l` passes on every changed file
- [ ] POST handlers call `csrf_check()`; pages call `require_role()` / `require_login()`
- [ ] SQL uses prepared statements; output uses `e()`
- [ ] Tested in the browser as every affected role, on desktop and phone
- [ ] No PHP warnings in the log
- [ ] README / docs / CHANGELOG updated if needed
