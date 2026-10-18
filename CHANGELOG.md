# Changelog
All notable changes to this project are documented here. Format based on [Keep a Changelog](https://keepachangelog.com/), versioning follows [SemVer](https://semver.org/).

## [1.0.0] - 2026-10-18
First release as **Zelmoriq**, a rebuild of the original Note Sharing Portal.

### Added
- Single login for students, teachers, HODs and managers
- HOD verification of teachers with private certificate viewing
- Browse, filter, sort, preview, download and rate notes
- Manager console: departments, courses, subjects, HODs, users, contact inbox
- First-run `setup.php` with optional demo data
- New responsive design system (no Bootstrap)
- SEO: meta tags, Open Graph, JSON-LD, dynamic `robots.txt` and `sitemap.xml`
- Agile documentation and in-site roadmap page

### Fixed
- 42 defects from the original project, including open manager registration, unrestricted file upload, SQL injection and broken hard-coded paths. See `docs/BUG_FIX_LOG.md`.
