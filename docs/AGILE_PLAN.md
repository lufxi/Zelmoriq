# Zelmoriq: Agile (Scrum) Plan

## 1. Product vision
*For* college students and teachers *who* lose time hunting for reliable class notes in scattered chat groups, **Zelmoriq** is a notes-sharing portal *that* lets only HOD-verified teachers publish, and lets students filter, preview, rate and download by department, subject and semester. *Unlike* informal groups, every note has a verified author, a subject and a rating.

## 2. Scrum roles
| Role | Responsibility | Suggested owner (adjust to your team) |
|---|---|---|
| Product Owner | Owns the backlog and priorities, accepts stories | Project guide / faculty coordinator |
| Scrum Master | Runs ceremonies, removes blockers | One team member (rotate each sprint) |
| Development Team | Builds and tests | Fathima Nahan E.A (lead developer), Fathimma Asif (database), Saeeda K S (UI/UX) |

## 3. Cadence
Two-week sprints. Sprint planning (day 1), daily 10-minute stand-up, sprint review with live demo (last day), retrospective (keep / stop / try).

## 4. Personas
- **Student (Sneha):** wants the right notes for her course and semester, fast, on her phone.
- **Teacher (Rahul):** wants to share notes once and know they reach the right students.
- **HOD (Dr. Anitha):** must stop unverified people publishing under the department's name.
- **Manager (Admin):** keeps departments, courses and accounts tidy.

## 5. Product backlog
Priority uses MoSCoW. Points are Fibonacci story points.

| ID | As a | I want to | Priority | Pts | Sprint |
|---|---|---|---|---|---|
| ZQ-01 | Visitor | see what Zelmoriq is and sign up | Must | 3 | 1 |
| ZQ-02 | User | log in once whatever my role | Must | 3 | 1 |
| ZQ-03 | User | reset a forgotten password | Should | 3 | 1 |
| ZQ-04 | Teacher | register with my department and certificate | Must | 5 | 2 |
| ZQ-05 | Student | register with my course and semester | Must | 3 | 2 |
| ZQ-06 | Teacher | upload a PDF note with subject, topic, semester | Must | 5 | 2 |
| ZQ-07 | Student | filter notes by department, subject, semester | Must | 5 | 2 |
| ZQ-08 | Student | preview and download a note | Must | 3 | 2 |
| ZQ-09 | Student | rate a note 1 to 5 stars | Should | 3 | 2 |
| ZQ-10 | Teacher | edit or delete my notes | Should | 3 | 2 |
| ZQ-11 | HOD | approve or reject teachers in my department | Must | 5 | 3 |
| ZQ-12 | HOD | view a teacher's certificate privately | Must | 3 | 3 |
| ZQ-13 | HOD | remove inappropriate notes | Should | 2 | 3 |
| ZQ-14 | HOD | see and export my department's students | Could | 3 | 3 |
| ZQ-15 | HOD | manage subjects for my department | Should | 2 | 3 |
| ZQ-16 | Manager | manage departments, courses, subjects | Must | 5 | 4 |
| ZQ-17 | Manager | create and disable HOD accounts | Must | 5 | 4 |
| ZQ-18 | Manager | see all users and disable abusers | Should | 3 | 4 |
| ZQ-19 | Manager | read messages from the contact form | Should | 2 | 4 |
| ZQ-20 | Everyone | use the site comfortably on a phone | Must | 5 | 5 |
| ZQ-21 | Team | run user acceptance testing and fix findings | Must | 8 | 5 |
| ZQ-22 | Visitor | receive e-mailed reset links and notifications | Could | 8 | 6 |
| ZQ-23 | Student | save favourite notes, see recently viewed | Could | 5 | 6 |

## 6. Sprint plan
| Sprint | Goal | Key stories |
|---|---|---|
| 0 | Discovery and audit | Code audit, bug log, vision, backlog, name and look |
| 1 | One secure way in | ZQ-01..03, unified users table, CSRF, safe uploads, design system |
| 2 | Upload a note, find a note | ZQ-04..10 |
| 3 | Only verified teachers publish | ZQ-11..15 |
| 4 | Run the whole portal | ZQ-16..19 |
| 5 | Prove it, then ship | ZQ-20, ZQ-21, backup and deployment notes |
| 6 | Next release | ZQ-22, ZQ-23 |

Planned points per sprint: S1 9, S2 22, S3 15, S4 15, S5 13, S6 13.

## 7. Acceptance criteria (examples)
- **ZQ-06 upload:** only an approved teacher (or a HOD) can upload; file must be a real PDF under 20 MB; note appears for students of that department immediately.
- **ZQ-11 approval:** a HOD can act only on teachers of their own department; an unapproved teacher sees a "waiting" banner and cannot upload.
- **ZQ-09 rating:** a student can rate each note once and change it later; the average updates on the card.

## 8. Definition of Ready
Story has a clear value, acceptance criteria, an estimate, and no unresolved dependency.

## 9. Definition of Done
- Acceptance criteria met and demoed to the Product Owner
- Server-side validation; CSRF on every state change; prepared statements only
- Works on phone and desktop, reachable by keyboard
- No PHP warnings or notices in the log
- README or changelog updated

## 10. Risks
| Risk | Mitigation |
|---|---|
| Fake teachers publish notes | Certificate + HOD approval, private certificate access |
| Malicious file uploads | Extension + MIME whitelist, random names, no script execution in `uploads/`, private note storage |
| Department without a HOD blocks teachers | Manager can approve; dashboard warns about departments without a HOD |
| No mail server in college lab | Local mode shows reset link on screen |

## 11. Sprint 0 retrospective (what the audit taught us)
- **Keep:** one clear data model, tiny reusable helpers.
- **Stop:** hard-coded URLs, duplicate pages per role, GET links that change data.
- **Try:** a regression checklist before every release (see README and BUG_FIX_LOG).

## 12. Release testing checklist (UAT)
1. Register a student and a teacher (with certificate). 2. Log in as manager, create a HOD. 3. Log in as HOD, approve the teacher. 4. Teacher uploads a PDF. 5. Student filters, previews, downloads, rates. 6. HOD removes a note; manager disables a user. 7. Try a direct URL to another role's page (should redirect). 8. Try uploading a `.php` file as a photo (should be rejected). 9. Test on a phone.
