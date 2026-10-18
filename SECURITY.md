# Security Policy

## Supported versions
Only the latest release on the `main` branch receives security fixes.

## Reporting a vulnerability
Please **do not** open a public issue for security problems.

1. On GitHub open **Security → Report a vulnerability** (private advisory), or contact the maintainers privately.
2. Include what you found, the page/URL, steps to reproduce and the impact.
3. You will get an acknowledgement within a few days, and credit in the changelog if you wish.

## Already in place
CSRF tokens, prepared statements, bcrypt password hashing, login throttling, validated uploads with random names, private file serving, hashed one-hour reset tokens, `noindex` on private pages.

## Hardening checklist for deployers
- Set `APP_ENV` to `'production'` and use HTTPS
- Use a dedicated, non-root database user
- Delete `setup.php` and the demo accounts
- Keep PHP and MySQL updated; back up the database and `uploads/` regularly
- On Nginx, recreate the `.htaccess` rules (deny `config/`, `includes/`, `database/`; no PHP execution in `uploads/`)
