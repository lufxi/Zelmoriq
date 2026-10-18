# Bug fix log: Note Sharing Portal to Zelmoriq

Every defect found while auditing the original zip, and how Zelmoriq resolves it.

## Routing and paths
| # | Problem in original | Fix |
|---|---|---|
| 1 | Hard-coded `/note_sharing_portal/...` URLs; the folder is `Note_Sharing_Portal`, so links break on Linux and any other folder name | Base URL auto-detected; every link uses `url()` |
| 2 | `header.php` used `../css/style.css` and `../logout.php`, valid only from one folder depth | Absolute asset URLs via `asset()` / `url()` |
| 3 | CSS background pointed at a wrong relative path | New CSS-only background, no image dependency |
| 4 | `login.php` sent HODs to `admin/dashboard.php`, which does not exist | `role_home()` maps each role to a real page |
| 5 | Header mapped HOD to the manager dashboard and used a non-existent `admin` role | Single role model in the nav builder |
| 6 | `echo` output before `header()` redirects ("headers already sent") in login, teacher/hod profile pages | Logic first, output later; layout rendered through functions |
| 7 | Contact form posted to missing `contact_process.php` (404) | `contact.php` stores messages; Manager inbox added |
| 8 | Duplicate / dead pages: `send_reset.php`, three download scripts, `hod/register.php`, `manager/add_admin.php`, separate hod/manager login pages | Removed or merged |

## Accounts and data model
| # | Problem | Fix |
|---|---|---|
| 9 | Three identity tables (`users`, `admins`, `managers`) with the HOD password stored twice | One `users` table with `role`, plus profile tables |
| 10 | HOD logged in on a separate page against a different table | One login page for all roles |
| 11 | `add_admin.php` inserted into `admins` without the NOT NULL password and used column `status` vs `Status` | Page removed; Manager creates HODs in a transaction |
| 12 | `manager` existed in the enum but login never handled it | Handled |
| 13 | **Anyone could register as a Manager** (`manager/register.php`) | Removed; first manager created in `setup.php`, which locks itself |
| 14 | `change_password.php` worked only for `users`, no strength check | One `password.php` for all roles with policy |
| 15 | Shared `edit_profile.php` crashed for HOD/manager (missing profile fields) | One `profile.php` with per-role fields |
| 16 | Password regex rejected valid symbols such as `#` | Any non-alphanumeric symbol allowed |
| 17 | Reset tokens stored in plain text; expiry text said 1 hour but code added 5; PHP and MySQL clocks disagreed | Token hashed (SHA-256), expiry computed by MySQL, session time zone synced |
| 18 | Reset link hard-coded `localhost/Note_Sharing_Portal` | Built from the current host |
| 19 | "Email not found" revealed which emails are registered | Same message either way |
| 20 | `teacher_profiles.department` was VARCHAR holding an id; courses, notes had no foreign keys; semester stored as text "Semester 3" in an INT column | Typed columns and foreign keys; semester is TINYINT 1-8 |

## Security
| # | Problem | Fix |
|---|---|---|
| 21 | **Arbitrary file upload**: no extension/MIME check, original filenames kept (PHP shell risk) | Whitelist + content check (finfo), random names, script execution blocked in `uploads/` |
| 22 | **SQL injection** in course/department delete (`... WHERE id = $id`) | Prepared statements everywhere (PDO) |
| 23 | Delete/approve/reject done through GET links | POST forms with CSRF tokens and confirm dialogs |
| 24 | No CSRF protection | Token on every form, verified server-side |
| 25 | Missing authorisation: any logged-in user could open upload/manage pages; notes and certificates reachable by direct URL | `require_role()` on every page; files served only by `download.php` / `file.php` after checks |
| 26 | Session fixation | `session_regenerate_id` on login and password change |
| 27 | No brute-force protection | 5 failures per email+IP in 10 minutes blocks login |
| 28 | `display_errors` forced on in production code | Controlled by `APP_ENV` |
| 29 | Several values echoed without escaping (stored XSS) | `e()` on all output |

## Logic and UX
| # | Problem | Fix |
|---|---|---|
| 30 | Student dashboard INNER JOINed course; missing course produced a blank page | Graceful empty state and prompt |
| 31 | Note delete checked `file_exists()` on a relative path, so files were never removed | Absolute paths; file removed with the row |
| 32 | `manage_notes.php` had unclosed `<div>` tags; `rate_notes.php` had markup after `</html>` | Rewritten |
| 33 | "Rate" link passed `note_id` that the rate page ignored | Inline star rating on each card; can be changed later; range validated |
| 34 | Teacher profile edit never reset approval after changing department/certificate | Re-verification required |
| 35 | HOD photo stored as `../uploads/...` in the DB; fixed filenames overwrote each other | Filename only, random names |
| 36 | Session keys inconsistent (`user_id` vs `admin_id`) | Single `uid` session key |
| 37 | Subjects table had no admin UI, so new departments could not receive uploads | Subject management for HOD and Manager |
| 38 | Deleting a department/course in use caused a fatal FK error | Friendly message |
| 39 | Placeholder images from `via.placeholder.com` (service gone); all team photos the same file | Initial-letter avatars |
| 40 | Student table relied on six CDN scripts (DataTables) | Built-in filter and CSV export, no CDN |
| 41 | Project shipped with ~11 MB of screenshots/test uploads and a personal login-slip PDF in `certificates/` | Not included; clean `uploads/` |
| 42 | SQL dump had no `CREATE DATABASE` and contained real-looking test accounts | Fresh schema; no accounts; setup wizard |

## New in Zelmoriq
Pagination, sorting (newest / top rated / most downloaded), download counter, contact inbox, department note chart, student CSV export, one-HOD-per-department rule, responsive layout, accessibility basics (labels, focus rings, skip link, reduced-motion).
