# Contributing to Zelmoriq

Thanks for helping! Zelmoriq is a small, framework-free PHP project, so contributing is easy.

## Ways to help
- Report a bug or suggest a feature (use the issue templates)
- Improve the documentation or translations
- Pick an item from the roadmap in the README and open a pull request

## Local setup
1. Install XAMPP (PHP 8.0+ and MySQL/MariaDB).
2. Fork and clone the repo into `htdocs`.
3. `cp config/config.example.php config/config.php`
4. Import `database/zelmoriq.sql` in phpMyAdmin, then run `/setup.php` and delete it.

## Workflow
1. Create a branch: `git checkout -b feature/short-description`
2. Make your change in small, focused commits.
3. Run a syntax check on everything: `find . -name "*.php" -exec php -l {} \;` (Windows PowerShell: `Get-ChildItem -Recurse -Filter *.php | % { php -l $_.FullName }`)
4. Test the affected flow in the browser, ideally as each role it touches.
5. Open a pull request and fill in the template.

## Code guidelines
- **Every POST handler** starts with `csrf_check()`; state changes never use GET.
- **Every page** calls `require_role(...)` (or `require_login()`) before doing anything.
- **SQL:** prepared statements only, via `db_all`, `db_one`, `db_val`, `db_exec`.
- **Output:** wrap every dynamic value in `e()`.
- **Uploads:** go through `upload_save()`; never trust a filename or MIME type from the browser.
- **Links:** build them with `url()` / `asset()`; never hard-code `/Zelmoriq/...`.
- Follow the existing style: 4 spaces, short functions, comments that explain *why*.
- CSS lives in `css/zelmoriq.css`; reuse the existing variables and components before adding new ones.

## Commit messages
Use the imperative: `Add rating filter to browse page`, `Fix HOD redirect after approval`.

## Agile process
Work is tracked as user stories in [docs/AGILE_PLAN.md](docs/AGILE_PLAN.md). A change is "done" when it meets the Definition of Done listed there.
